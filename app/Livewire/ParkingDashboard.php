<?php
// app/Livewire/ParkingDashboard.php
namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ParkingDashboard extends Component
{
    public $spaces = [];
    public $allSpaces = [];
    public $floorFilter = 'all';
    public $availableFloors = [];
    public $floorStats = [];
    public $lastUpdate;
    
    // Statistics
    public $totalSpaces = 0;
    public $occupiedSpaces = 0;
    public $availableSpaces = 0;
    public $occupancyRate = 0;

    // Auto-refresh
    public $isAutoRefreshEnabled = true;

    // Modal properties
    public $showModal = false;
    public $selectedFloor = '';
    public $selectedFloorSpaces = [];
    public $selectedFloorStats = [];

    protected $listeners = [
        'refresh-parking-data' => 'loadParkingData'
    ];

    public function mount()
    {
        $this->loadParkingData();
    }

    public function loadParkingData()
    {
        try {
            $this->ensureTableExists();
            
            // Get all parking spaces with proper Carbon parsing
            $this->allSpaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get()
                ->map(function ($space) {
                    $space->created_at = Carbon::parse($space->created_at);
                    $space->updated_at = Carbon::parse($space->updated_at);
                    return $space;
                })
                ->toArray();

            // Update available floors
            $this->updateAvailableFloors();

            // Filter spaces based on selected floor
            $this->filterSpacesByFloor();

            // Update statistics
            $this->updateStatistics();
            
            // Update floor statistics (now includes all floors 1-4)
            $this->updateFloorStats();
            
            $this->lastUpdate = now()->format('H:i:s');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load parking data: ' . $e->getMessage());
            
            // Fallback to empty data
            $this->allSpaces = [];
            $this->spaces = [];
            $this->availableFloors = [];
            $this->floorStats = $this->getDefaultFloorStats();
            $this->resetStatistics();
        }
    }

    public function selectFloor($floorLevel)
    {
        $this->selectedFloor = $floorLevel;
        $this->loadSelectedFloorData();
        $this->showModal = true;
    }

    public function goToFloor($floorLevel)
    {
        // Check if floor has data
        $hasData = collect($this->allSpaces)->where('floor_level', $floorLevel)->count() > 0;
        
        if (!$hasData) {
            session()->flash('message', 'No data available for ' . $floorLevel . ' yet.');
            return;
        }
        
        $this->redirect('/floor/' . urlencode($floorLevel), navigate: true);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedFloor = '';
        $this->selectedFloorSpaces = [];
        $this->selectedFloorStats = [];
    }

    public function getSensorDisplayName($sensorId)
    {
        $mapping = [
            1 => 'A1',
            2 => 'A2', 
            3 => 'A3',
            4 => 'A4',
            5 => 'B1'
        ];
        
        return $mapping[$sensorId] ?? $sensorId;
    }

    public function getRelativeTime($timestamp)
    {
        return Carbon::parse($timestamp)->diffForHumans();
    }

    private function loadSelectedFloorData()
    {
        try {
            // Get spaces for this specific floor
            $this->selectedFloorSpaces = DB::table('parking_spaces')
                ->where('floor_level', $this->selectedFloor)
                ->orderBy('sensor_id')
                ->get()
                ->map(function ($space) {
                    $space->created_at = Carbon::parse($space->created_at);
                    $space->updated_at = Carbon::parse($space->updated_at);
                    return $space;
                })
                ->toArray();

            // Calculate floor stats
            $total = count($this->selectedFloorSpaces);
            $occupied = collect($this->selectedFloorSpaces)->where('is_occupied', true)->count();
            $available = $total - $occupied;

            $this->selectedFloorStats = [
                'total' => $total,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0
            ];

        } catch (\Exception $e) {
            $this->selectedFloorSpaces = [];
            $this->selectedFloorStats = ['total' => 0, 'occupied' => 0, 'available' => 0, 'occupancy_rate' => 0];
        }
    }

    public function updatedFloorFilter()
    {
        $this->filterSpacesByFloor();
        $this->updateStatistics();
    }

    public function toggleAutoRefresh()
    {
        $this->isAutoRefreshEnabled = !$this->isAutoRefreshEnabled;
        
        if ($this->isAutoRefreshEnabled) {
            $this->dispatch('enable-auto-refresh');
            session()->flash('message', 'Auto-refresh enabled');
        } else {
            $this->dispatch('disable-auto-refresh');
            session()->flash('message', 'Auto-refresh disabled');
        }
    }

    public function refreshNow()
    {
        $this->loadParkingData();
        session()->flash('message', 'Dashboard refreshed successfully');
    }

    private function updateAvailableFloors()
    {
        $floors = collect($this->allSpaces)->pluck('floor_level')->unique()->filter()->sort()->values();
        $this->availableFloors = $floors->toArray();
    }

    private function filterSpacesByFloor()
    {
        if ($this->floorFilter === 'all') {
            $this->spaces = $this->allSpaces;
        } else {
            $this->spaces = collect($this->allSpaces)
                ->where('floor_level', $this->floorFilter)
                ->values()
                ->toArray();
        }
    }

    private function updateStatistics()
    {
        $spaces = collect($this->spaces);
        
        $this->totalSpaces = $spaces->count();
        $this->occupiedSpaces = $spaces->where('is_occupied', true)->count();
        $this->availableSpaces = $this->totalSpaces - $this->occupiedSpaces;
        $this->occupancyRate = $this->totalSpaces > 0 
            ? round(($this->occupiedSpaces / $this->totalSpaces) * 100, 1) 
            : 0;
    }

    private function updateFloorStats()
    {
        $allSpaces = collect($this->allSpaces);
        
        // Define all floors that should be displayed
        $allFloors = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
        
        $this->floorStats = [];
        
        foreach ($allFloors as $floorName) {
            $floorSpaces = $allSpaces->where('floor_level', $floorName);
            $total = $floorSpaces->count();
            
            if ($total > 0) {
                // Floor has data
                $occupied = $floorSpaces->where('is_occupied', true)->count();
                $available = $total - $occupied;
                $occupancyRate = round(($occupied / $total) * 100, 1);
                
                $this->floorStats[] = [
                    'floor_level' => $floorName,
                    'total' => $total,
                    'occupied' => $occupied,
                    'available' => $available,
                    'occupancy_rate' => $occupancyRate,
                    'has_data' => true
                ];
            } else {
                // Floor has no data yet
                $this->floorStats[] = [
                    'floor_level' => $floorName,
                    'total' => 0,
                    'occupied' => 0,
                    'available' => 0,
                    'occupancy_rate' => 0,
                    'has_data' => false
                ];
            }
        }
    }

    private function getDefaultFloorStats()
    {
        return [
            [
                'floor_level' => '1st Floor',
                'total' => 0,
                'occupied' => 0,
                'available' => 0,
                'occupancy_rate' => 0,
                'has_data' => false
            ],
            [
                'floor_level' => '2nd Floor',
                'total' => 0,
                'occupied' => 0,
                'available' => 0,
                'occupancy_rate' => 0,
                'has_data' => false
            ],
            [
                'floor_level' => '3rd Floor',
                'total' => 0,
                'occupied' => 0,
                'available' => 0,
                'occupancy_rate' => 0,
                'has_data' => false
            ],
            [
                'floor_level' => '4th Floor',
                'total' => 0,
                'occupied' => 0,
                'available' => 0,
                'occupancy_rate' => 0,
                'has_data' => false
            ]
        ];
    }

    private function resetStatistics()
    {
        $this->totalSpaces = 0;
        $this->occupiedSpaces = 0;
        $this->availableSpaces = 0;
        $this->occupancyRate = 0;
    }

    public function getSpaceIcon($space)
    {
        if ($space['is_occupied']) {
            return 'fas fa-car';
        }
        return 'fas fa-check-circle';
    }

    public function getStatusText($space)
    {
        if ($space['is_occupied']) {
            return '🚗 Vehicle Present';
        }
        return '✅ Space Available';
    }

    public function getFloorIcon($floorLevel)
    {
        if (str_contains(strtolower($floorLevel), 'basement') || str_contains($floorLevel, 'B')) {
            return 'fas fa-layer-group';
        }
        if (str_contains($floorLevel, '1st') || str_contains($floorLevel, 'Ground')) {
            return 'fas fa-home';
        }
        return 'fas fa-building';
    }

    private function ensureTableExists()
    {
        DB::statement("CREATE TABLE IF NOT EXISTS parking_spaces (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sensor_id INT UNIQUE NOT NULL,
            is_occupied BOOLEAN NOT NULL DEFAULT FALSE,
            distance_cm INT,
            floor_level VARCHAR(255) DEFAULT '4th Floor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        
        // Add floor_level column if it doesn't exist (for existing tables)
        $columnExists = DB::select("SHOW COLUMNS FROM parking_spaces LIKE 'floor_level'");
        if (empty($columnExists)) {
            DB::statement("ALTER TABLE parking_spaces ADD COLUMN floor_level VARCHAR(255) DEFAULT '4th Floor' AFTER distance_cm");
        }
    }

    public function render()
    {
        return view('livewire.parking-dashboard')->layout('layouts.app');
    }
}
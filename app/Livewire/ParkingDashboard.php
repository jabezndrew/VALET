<?php
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
            
            $this->allSpaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get()
                ->map(function ($space) {
                    $space->created_at = Carbon::parse($space->created_at);
                    $space->updated_at = Carbon::parse($space->updated_at);
                    return $space;
                })
                ->toArray();

            $this->updateAvailableFloors();
            $this->filterSpacesByFloor();
            $this->updateStatistics();
            $this->updateFloorStats();
            
            $this->lastUpdate = now()->format('H:i:s');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load parking data: ' . $e->getMessage());
            
            $this->allSpaces = [];
            $this->spaces = [];
            $this->availableFloors = [];
            $this->floorStats = [];
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
        $hasData = collect($this->allSpaces)->where('floor_level', $floorLevel)->count() > 0;
        
        if (!$hasData) {
            session()->flash('message', 'No data available for ' . $floorLevel . ' yet.');
            return;
        }
        
        $this->selectFloor($floorLevel);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedFloor = '';
        $this->selectedFloorSpaces = [];
        $this->selectedFloorStats = [];
        $this->dispatch('modal-closed');
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
        $allFloors = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
        
        $this->floorStats = [];
        
        foreach ($allFloors as $floorName) {
            $floorSpaces = $allSpaces->where('floor_level', $floorName);
            $total = $floorSpaces->count();
            
            if ($total > 0) {
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

    private function resetStatistics()
    {
        $this->totalSpaces = 0;
        $this->occupiedSpaces = 0;
        $this->availableSpaces = 0;
        $this->occupancyRate = 0;
    }

    public function getSpaceIcon($space)
    {
        $isOccupied = is_object($space) ? $space->is_occupied : $space['is_occupied'];
        
        if ($isOccupied) {
            return 'fas fa-car text-danger';
        }
        return 'fas fa-check-circle text-success';
    }

    public function getStatusText($space)
    {
        $isOccupied = is_object($space) ? $space->is_occupied : $space['is_occupied'];
        
        if ($isOccupied) {
            return 'Vehicle Present';
        }
        return 'Space Available';
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
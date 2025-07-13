<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ParkingController extends Component
{
    public $spaces = [];
    public $floorFilter = 'all';
    public $availableFloors = [];
    public $floorStats = [];
    public $lastUpdate;
    
    // Statistics
    public $totalSpaces = 0;
    public $occupiedSpaces = 0;
    public $availableSpaces = 0;

    protected $listeners = ['refresh-parking-data' => 'loadParkingData'];

    public function mount()
    {
        $this->loadParkingData();
    }

    public function loadParkingData()
    {
        try {
            $this->ensureTableExists();
            
            // Get all parking spaces
            $allSpaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get()
                ->map(function ($space) {
                    $space->created_at = Carbon::parse($space->created_at);
                    $space->updated_at = Carbon::parse($space->updated_at);
                    return $space;
                });

            // Update available floors
            $this->availableFloors = $allSpaces->pluck('floor_level')->unique()->sort()->values()->toArray();

            // Filter spaces based on selected floor
            if ($this->floorFilter === 'all') {
                $this->spaces = $allSpaces;
            } else {
                $this->spaces = $allSpaces->where('floor_level', $this->floorFilter)->values();
            }

            // Update statistics
            $this->updateStatistics();
            
            // Update floor statistics
            $this->updateFloorStats($allSpaces);
            
            $this->lastUpdate = now()->format('H:i:s');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load parking data: ' . $e->getMessage());
        }
    }

    public function updatedFloorFilter()
    {
        $this->loadParkingData();
    }

    private function updateStatistics()
    {
        $this->totalSpaces = $this->spaces->count();
        $this->occupiedSpaces = $this->spaces->where('is_occupied', true)->count();
        $this->availableSpaces = $this->totalSpaces - $this->occupiedSpaces;
    }

    private function updateFloorStats($allSpaces)
    {
        $this->floorStats = $allSpaces->groupBy('floor_level')->map(function ($floorSpaces, $floorName) {
            $total = $floorSpaces->count();
            $occupied = $floorSpaces->where('is_occupied', true)->count();
            $available = $total - $occupied;
            $occupancyRate = $total > 0 ? round(($occupied / $total) * 100) : 0;

            return [
                'floor_level' => $floorName,
                'total' => $total,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_rate' => $occupancyRate
            ];
        })->values()->toArray();
    }

    public function getDistanceColor($distance)
    {
        if ($distance <= 5) return '#dc3545';
        if ($distance <= 10) return '#ffc107';
        if ($distance <= 20) return '#28a745';
        return '#007bff';
    }

    public function getDistancePercentage($distance)
    {
        return min(($distance / 100) * 100, 100);
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
       // $this->loadParkingData();
        return view('livewire.parking-dashboard');
    }
}
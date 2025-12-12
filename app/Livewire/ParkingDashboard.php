<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use App\Models\Vehicle;
use Carbon\Carbon;

class ParkingDashboard extends Component
{
    public $allSpaces = [];
    public $floorFilter = 'all';
    public $lastUpdate;
    
    // Statistics
    public $totalSpaces = 0;
    public $occupiedSpaces = 0;
    public $availableSpaces = 0;
    public $occupancyRate = 0;
    public $floorStats = [];

    // Auto-refresh
    public $isAutoRefreshEnabled = true;

    // Floor modal properties
    public $showModal = false;
    public $selectedFloor = '';
    public $selectedFloorData = [];
    
    // Verify modal properties
    public $showVerifyModal = false;
    public $verifyRfid = '';
    public $verifyResult = null;

    protected $listeners = [
        'refresh-parking-data' => 'loadParkingData'
    ];

    protected $rules = [
        'verifyRfid' => 'required|string|max:50'
    ];

    public function mount()
    {
        $this->loadParkingData();
        
        // Debug: Log data loading for troubleshooting
        if (config('app.debug')) {
            logger('ParkingDashboard loaded', [
                'total_spaces' => $this->allSpaces->count(),
                'floors_found' => $this->allSpaces->pluck('floor_level')->unique()->toArray(),
                'floor_stats_count' => count($this->floorStats)
            ]);
        }
    }

    public function loadParkingData()
    {
        try {
            $this->allSpaces = ParkingSpace::orderBy('sensor_id')->get();

            $this->updateStatistics();
            $this->updateFloorStats();

            if ($this->showModal && $this->selectedFloor) {
                $this->loadSelectedFloorData();
            }

            $this->lastUpdate = now()->format('H:i:s');

        } catch (\Exception $e) {
            $this->handleDataLoadError($e);
        }
    }

    public function updatedFloorFilter()
    {
        $this->updateStatistics();
    }

    public function selectFloor($floorLevel)
    {
        $this->selectedFloor = $floorLevel;
        $this->loadSelectedFloorData();
        $this->showModal = true;
    }

    public function goToFloor($floorLevel)
    {
        if (!$this->hasFloorData($floorLevel)) {
            $this->dispatch('show-alert', type: 'info', message: "No data available for {$floorLevel} yet.");
            return;
        }
        
        $this->selectFloor($floorLevel);
    }

    public function closeModal()
    {
        $this->reset(['showModal', 'selectedFloor', 'selectedFloorData']);
    }

    // Verify Vehicle Methods
    public function openVerifyModal()
    {
        if (!$this->canVerifyVehicles()) {
            $this->dispatch('show-alert', type: 'error', message: 'Access denied.');
            return;
        }
        
        $this->reset(['verifyRfid', 'verifyResult']);
        $this->showVerifyModal = true;
    }

    public function closeVerifyModal()
    {
        $this->reset(['showVerifyModal', 'verifyRfid', 'verifyResult']);
    }

    public function verifyVehicle()
    {
        if (!$this->canVerifyVehicles()) {
            $this->dispatch('show-alert', type: 'error', message: 'Access denied.');
            return;
        }

        $this->validate();

        $vehicle = $this->findVehicleByRfid($this->verifyRfid);

        if (!$vehicle) {
            $this->verifyResult = [
                'status' => 'NOT_FOUND',
                'message' => 'Vehicle not found in system',
                'color' => 'danger'
            ];
            return;
        }

        $this->verifyResult = $this->getVehicleVerificationResult($vehicle);
    }

    // Auto-refresh methods
    public function toggleAutoRefresh()
    {
        $this->isAutoRefreshEnabled = !$this->isAutoRefreshEnabled;
        
        $action = $this->isAutoRefreshEnabled ? 'enable' : 'disable';
        $this->dispatch("{$action}-auto-refresh");
        
        $status = $this->isAutoRefreshEnabled ? 'enabled' : 'disabled';
        $this->dispatch('show-alert', type: 'success', message: "Auto-refresh {$status}");
    }

    public function refreshNow()
    {
        // Force reload from database
        $this->allSpaces = collect();
        $this->floorStats = [];
        $this->resetStatistics();
        
        $this->loadParkingData();
        $this->dispatch('show-alert', type: 'success', message: 'Dashboard refreshed successfully');
    }

    // Helper methods for display
    public function getFilteredSpaces()
    {
        if ($this->floorFilter === 'all') {
            return $this->allSpaces;
        }
        
        return $this->allSpaces->where('floor_level', $this->floorFilter);
    }

    public function getAvailableFloors()
    {
        return $this->allSpaces->pluck('floor_level')->unique()->filter()->sort()->values();
    }

    public function getSensorDisplayName($space)
    {
        if ($space->space_code) {
            return $space->space_code;
        }

        $mapping = [
            1 => 'B4', 2 => 'B3', 3 => 'B2', 4 => 'B1', 5 => 'C1'
        ];

        return $mapping[$space->sensor_id] ?? "S{$space->sensor_id}";
    }

    public function getSpaceIcon($space)
    {
        $isOccupied = $space->is_occupied == 1 || $space->is_occupied === true;
        return $isOccupied ? 'fas fa-car text-danger' : 'fas fa-check-circle text-success';
    }

    public function getStatusText($space)
    {
        $isOccupied = $space->is_occupied == 1 || $space->is_occupied === true;
        return $isOccupied ? 'Vehicle Present' : 'Space Available';
    }

    public function getRelativeTime($timestamp)
    {
        return Carbon::parse($timestamp)->diffForHumans();
    }

    // Private helper methods
    private function handleDataLoadError($exception)
    {
        $this->dispatch('show-alert', type: 'error', message: 'Failed to load parking data: ' . $exception->getMessage());
        
        $this->allSpaces = collect();
        $this->floorStats = [];
        $this->resetStatistics();
    }

    private function updateStatistics()
    {
        $spaces = $this->getFilteredSpaces();
        
        $this->totalSpaces = $spaces->count();
        $this->occupiedSpaces = $spaces->filter(function ($space) {
            return $space->is_occupied == 1 || $space->is_occupied === true;
        })->count();
        $this->availableSpaces = $this->totalSpaces - $this->occupiedSpaces;
        $this->occupancyRate = $this->totalSpaces > 0 
            ? round(($this->occupiedSpaces / $this->totalSpaces) * 100, 1) 
            : 0;
    }

    private function updateFloorStats()
    {
        $allFloors = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
        
        $this->floorStats = collect($allFloors)->map(function ($floorName) {
            return $this->calculateFloorStats($floorName);
        })->values()->toArray(); // Ensure array keys are sequential
    }

    private function calculateFloorStats($floorName)
    {
        $floorSpaces = $this->allSpaces->where('floor_level', $floorName);
        $total = $floorSpaces->count();
        
        if ($total === 0) {
            return [
                'floor_level' => $floorName,
                'total' => 0,
                'occupied' => 0,
                'available' => 0,
                'occupancy_rate' => 0,
                'has_data' => false
            ];
        }

        // Handle both boolean and integer values for is_occupied
        $occupied = $floorSpaces->filter(function ($space) {
            return $space->is_occupied == 1 || $space->is_occupied === true;
        })->count();
        
        $available = $total - $occupied;
        $occupancyRate = round(($occupied / $total) * 100, 1);

        return [
            'floor_level' => $floorName,
            'total' => $total,
            'occupied' => $occupied,
            'available' => $available,
            'occupancy_rate' => $occupancyRate,
            'has_data' => true
        ];
    }

    private function loadSelectedFloorData()
    {
        $floorSpaces = $this->allSpaces->where('floor_level', $this->selectedFloor);

        $spacesByColumn = $floorSpaces
            ->sortBy([
                ['column_code', 'asc'],
                ['slot_number', 'asc']
            ])
            ->groupBy('column_code')
            ->sortKeys()
            ->map(fn($spaces) => $spaces->values()->toArray())
            ->toArray();

        $this->selectedFloorData = [
            'spaces' => $floorSpaces->values()->toArray(),
            'spaces_by_column' => $spacesByColumn,
            'stats' => $this->calculateFloorStats($this->selectedFloor)
        ];
    }

    private function hasFloorData($floorLevel)
    {
        return $this->allSpaces->where('floor_level', $floorLevel)->isNotEmpty();
    }

    private function canVerifyVehicles()
    {
        return auth()->user()->role !== 'user';
    }

    private function findVehicleByRfid($rfid)
    {
        return Vehicle::with('owner')->where('rfid_tag', $rfid)->first();
    }

    private function getVehicleVerificationResult($vehicle)
    {
        $isValid = $vehicle->isValid();

        // Convert model to array for backward compatibility with view
        $vehicleData = $vehicle->toArray();
        $vehicleData['owner_name'] = $vehicle->owner->name ?? null;
        $vehicleData['owner_role'] = $vehicle->owner->role ?? null;

        if (!$isValid) {
            $message = !$vehicle->is_active
                ? 'Vehicle is deactivated. Contact administrator.'
                : 'Vehicle registration expired on ' . Carbon::parse($vehicle->expires_at)->format('M j, Y') . '. Renewal required.';

            return [
                'status' => 'Inactive',
                'message' => $message,
                'vehicle' => (object) $vehicleData,
                'color' => 'danger'
            ];
        }

        return [
            'status' => 'Active',
            'message' => 'Vehicle is active and authorized for parking.',
            'vehicle' => (object) $vehicleData,
            'color' => 'success'
        ];
    }

    private function resetStatistics()
    {
        $this->totalSpaces = 0;
        $this->occupiedSpaces = 0;
        $this->availableSpaces = 0;
        $this->occupancyRate = 0;
    }

    // Debug method - can be called from browser console or added as a button
    public function debugData()
    {
        $debug = [
            'total_spaces_loaded' => $this->allSpaces->count(),
            'unique_floors' => $this->allSpaces->pluck('floor_level')->unique()->sort()->values()->toArray(),
            'sample_data' => $this->allSpaces->take(3)->toArray(),
            'floor_stats' => $this->floorStats,
            'fourth_floor_count' => $this->allSpaces->where('floor_level', '4th Floor')->count(),
        ];
        
        $this->dispatch('show-alert', type: 'info', message: 'Debug data logged to console');
        
        // This will be visible in the browser's developer console
        $this->dispatch('debug-data', $debug);
        
        return $debug;
    }

    public function render()
    {
        return view('livewire.parking-dashboard')->layout('layouts.app');
    }
}
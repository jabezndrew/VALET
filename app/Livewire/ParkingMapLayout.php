<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use Carbon\Carbon;

class ParkingMapLayout extends Component
{
    public $selectedFloor = '1st Floor';
    public $parkingSpaces = [];
    public $floorStats = [];
    public $availableFloors = [];
    public $lastUpdate;
    public $isAutoRefreshEnabled = true;

    protected $listeners = [
        'refresh-parking-data' => 'loadParkingData'
    ];

    public function mount($floor = null)
    {
        // Check which floors have data first
        $this->checkAvailableFloors();

        // If floor is passed via URL parameter, use it
        if ($floor) {
            $this->selectedFloor = $floor;
        } else {
            // Auto-select the first floor with data
            if (!empty($this->availableFloors)) {
                $this->selectedFloor = $this->availableFloors[0];
            }
        }

        $this->loadParkingData();
    }

    public function loadParkingData()
    {
        try {
            // Get all parking spaces for the selected floor
            $this->parkingSpaces = ParkingSpace::forFloor($this->selectedFloor)
                ->orderBy('sensor_id')
                ->get();

            // Calculate floor statistics
            $this->calculateFloorStats();

            // Check which floors have data
            $this->checkAvailableFloors();

            $this->lastUpdate = now()->format('H:i:s');

        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to load parking data: ' . $e->getMessage());
        }
    }

    public function changeFloor($floor)
    {
        $this->selectedFloor = $floor;
        $this->loadParkingData();
    }

    public function toggleAutoRefresh()
    {
        $this->isAutoRefreshEnabled = !$this->isAutoRefreshEnabled;

        $status = $this->isAutoRefreshEnabled ? 'enabled' : 'disabled';
        $this->dispatch('show-alert', type: 'success', message: "Auto-refresh {$status}");
    }

    public function refreshNow()
    {
        $this->loadParkingData();
        $this->dispatch('show-alert', type: 'success', message: 'Map refreshed successfully');
    }

    private function calculateFloorStats()
    {
        $total = $this->parkingSpaces->count();
        $occupied = $this->parkingSpaces->filter(function ($space) {
            return $space->is_occupied == 1 || $space->is_occupied === true;
        })->count();

        $available = $total - $occupied;
        $occupancyRate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

        $this->floorStats = [
            'total' => $total,
            'occupied' => $occupied,
            'available' => $available,
            'occupancy_rate' => $occupancyRate
        ];
    }

    public function getSensorDisplayName($sensorId)
    {
        $mapping = [
            1 => 'B4', 2 => 'B3', 3 => 'B2', 4 => 'B1', 5 => 'C1'
        ];

        return $mapping[$sensorId] ?? "S{$sensorId}";
    }

    public function getRelativeTime($timestamp)
    {
        return Carbon::parse($timestamp)->diffForHumans();
    }

    private function checkAvailableFloors()
    {
        $allFloors = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
        $this->availableFloors = [];

        foreach ($allFloors as $floor) {
            $count = ParkingSpace::forFloor($floor)->count();
            if ($count > 0) {
                $this->availableFloors[] = $floor;
            }
        }
    }

    public function hasFloorData($floor)
    {
        return in_array($floor, $this->availableFloors);
    }

    public function render()
    {
        return view('livewire.parking-map-layout')->layout('layouts.app');
    }
}

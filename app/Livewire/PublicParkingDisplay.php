<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use App\Models\SensorAssignment;

class PublicParkingDisplay extends Component
{
    public $selectedFloor = '1st Floor';
    public $parkingSpaces = [];
    public $floorStats = [];
    public $lastUpdate;
    public $availableFloors = [];
    public $allFloorStats = [];
    public $showRoute = false;
    public $selectedSpot = null;
    public $selectedSection = null;
    public $selectedSpotX = 0;
    public $selectedSpotY = 0;

    public function mount()
    {
        $this->loadAllFloorStats();
        $this->loadParkingData();
    }

    public function loadAllFloorStats()
    {
        $allFloors = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
        $this->allFloorStats = [];

        foreach ($allFloors as $floor) {
            $spaces = ParkingSpace::where('floor_level', $floor)->with('sensorAssignment')->get();

            if ($spaces->isEmpty()) {
                continue;
            }

            $spacesWithSensors = $spaces->filter(fn($s) => $s->sensorAssignment !== null);
            $total = $spacesWithSensors->count();
            $occupied = $spacesWithSensors->filter(fn($s) => $s->is_occupied)->count();
            $available = $total - $occupied;

            $this->allFloorStats[$floor] = [
                'total' => $total,
                'available' => $available,
                'occupied' => $occupied,
            ];
        }

        // Sort floors by available spots (descending)
        uasort($this->allFloorStats, function($a, $b) {
            return $b['available'] - $a['available'];
        });
    }

    public function updatedSelectedFloor()
    {
        $this->loadParkingData();
    }

    public function loadParkingData()
    {
        // Load parking spaces for selected floor with sensor assignments
        $this->parkingSpaces = ParkingSpace::where('floor_level', $this->selectedFloor)
            ->with('sensorAssignment')
            ->orderBy('slot_name')
            ->get();

        // Refresh all floor stats
        $this->loadAllFloorStats();

        $this->lastUpdate = now()->format('H:i:s');
    }

    public function changeFloor($floor)
    {
        // Clear any active navigation when switching floors
        $this->clearRoute();

        $this->selectedFloor = $floor;
        $this->loadParkingData();
    }

    public function hasFloorData($floor)
    {
        return ParkingSpace::where('floor_level', $floor)->exists();
    }

    public function getSensorDisplayName($sensorId)
    {
        $assignment = SensorAssignment::find($sensorId);
        return $assignment ? $assignment->space_code : 'N/A';
    }

    public function toggleRoute()
    {
        $this->showRoute = !$this->showRoute;
    }

    public function selectParkingSpot($slotName, $columnCode, $x = 0, $y = 0)
    {
        $this->selectedSpot = $slotName;
        $this->selectedSection = $columnCode;
        $this->selectedSpotX = $x;
        $this->selectedSpotY = $y;
        $this->showRoute = true;
    }

    public function clearRoute()
    {
        $this->selectedSpot = null;
        $this->selectedSection = null;
        $this->selectedSpotX = 0;
        $this->selectedSpotY = 0;
        $this->showRoute = false;
    }

    public function render()
    {
        return view('livewire.public-parking-display')
            ->layout('layouts.app');
    }
}

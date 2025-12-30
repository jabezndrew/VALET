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

    public function mount()
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

        // Calculate statistics
        $total = $this->parkingSpaces->count();
        $spacesWithSensors = $this->parkingSpaces->filter(function($space) {
            return $space->sensorAssignment !== null;
        });

        $occupied = $spacesWithSensors->filter(function($space) {
            return $space->is_occupied;
        })->count();

        $available = $spacesWithSensors->count() - $occupied;

        $this->floorStats = [
            'total' => $total,
            'available' => $available,
            'occupied' => $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0,
        ];

        $this->lastUpdate = now()->format('H:i:s');
    }

    public function changeFloor($floor)
    {
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

    public function render()
    {
        return view('livewire.public-parking-display')
            ->layout('layouts.public');
    }
}

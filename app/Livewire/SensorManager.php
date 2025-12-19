<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SensorAssignment;
use App\Models\ParkingSpace;

class SensorManager extends Component
{
    public $sensors = [];
    public $unassignedSensors = [];
    public $availableSpaces = [];
    public $showAssignModal = false;
    public $selectedSensor = null;
    public $selectedSpaceCode = '';
    public $deviceName = '';
    public $filterStatus = 'all'; // all, assigned, unassigned

    // Floor/Column/Slot configuration
    public $floorNumber = '';
    public $columnCode = '';
    public $slotNumber = '';

    protected $listeners = ['refreshSensors' => '$refresh'];

    public function mount()
    {
        $this->loadSensors();
        $this->loadAvailableSpaces();
    }

    public function loadSensors()
    {
        $query = SensorAssignment::with('parkingSpace')
            ->orderBy('mac_address')
            ->orderBy('sensor_index');

        if ($this->filterStatus === 'assigned') {
            $query->where('status', 'active')->whereNotNull('space_code');
        } elseif ($this->filterStatus === 'unassigned') {
            $query->where(function($q) {
                $q->where('status', 'unassigned')->orWhereNull('space_code');
            });
        }

        $this->sensors = $query->get();
        $this->unassignedSensors = SensorAssignment::where('status', 'unassigned')
            ->orWhereNull('space_code')
            ->orderBy('mac_address')
            ->orderBy('sensor_index')
            ->get();
    }

    public function loadAvailableSpaces()
    {
        // Get all parking spaces that are not assigned to any sensor
        $assignedSpaceCodes = SensorAssignment::whereNotNull('space_code')
            ->where('status', 'active')
            ->pluck('space_code')
            ->toArray();

        $this->availableSpaces = ParkingSpace::whereNotIn('space_code', $assignedSpaceCodes)
            ->orderBy('space_code')
            ->get();
    }

    public function openAssignModal($sensorId)
    {
        $this->selectedSensor = SensorAssignment::find($sensorId);
        $this->deviceName = $this->selectedSensor->device_name ?? '';

        // Parse existing space_code if assigned
        if ($this->selectedSensor->space_code) {
            $parsed = ParkingSpace::parseSpaceCode($this->selectedSensor->space_code);
            if ($parsed) {
                $this->floorNumber = $parsed['floor_number'];
                $this->columnCode = $parsed['column_code'];
                $this->slotNumber = $parsed['slot_number'];
            }
        } else {
            // Reset to defaults
            $this->floorNumber = '';
            $this->columnCode = '';
            $this->slotNumber = '';
        }

        $this->showAssignModal = true;
    }

    public function assignSensor()
    {
        $this->validate([
            'floorNumber' => 'required|integer|min:1|max:4',
            'columnCode' => 'required|string|size:1|regex:/^[A-Z]$/',
            'slotNumber' => 'required|integer|min:1|max:5',
            'deviceName' => 'nullable|string|max:100'
        ]);

        try {
            // Build space_code (e.g., "4B4")
            $spaceCode = "{$this->floorNumber}{$this->columnCode}{$this->slotNumber}";

            // Check if parking space exists, create if it doesn't
            $parkingSpace = ParkingSpace::firstOrCreate(
                ['space_code' => $spaceCode],
                [
                    'floor_number' => $this->floorNumber,
                    'column_code' => $this->columnCode,
                    'slot_number' => $this->slotNumber,
                    'floor_level' => "{$this->floorNumber}th Floor",
                    'is_occupied' => false,
                    'distance_cm' => 0
                ]
            );

            // Check if space is already assigned to another sensor
            $existingAssignment = SensorAssignment::where('space_code', $spaceCode)
                ->where('id', '!=', $this->selectedSensor->id)
                ->where('status', 'active')
                ->first();

            if ($existingAssignment) {
                session()->flash('error', "Parking space {$spaceCode} is already assigned to another sensor");
                return;
            }

            $this->selectedSensor->update([
                'space_code' => $spaceCode,
                'device_name' => $this->deviceName,
                'status' => 'active'
            ]);

            session()->flash('success', "Sensor assigned to {$spaceCode} successfully!");
            $this->closeAssignModal();
            $this->loadSensors();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to assign sensor: ' . $e->getMessage());
        }
    }

    public function unassignSensor($sensorId)
    {
        try {
            $sensor = SensorAssignment::find($sensorId);
            $sensor->update([
                'space_code' => null,
                'status' => 'unassigned'
            ]);

            session()->flash('success', 'Sensor unassigned successfully!');
            $this->loadSensors();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to unassign sensor: ' . $e->getMessage());
        }
    }

    public function deleteSensor($sensorId)
    {
        try {
            $sensor = SensorAssignment::find($sensorId);
            $sensor->delete();

            session()->flash('success', 'Sensor deleted successfully!');
            $this->loadSensors();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete sensor: ' . $e->getMessage());
        }
    }

    public function startIdentify($sensorId)
    {
        try {
            $sensor = SensorAssignment::find($sensorId);
            $sensor->startIdentify();

            session()->flash('success', 'Identify mode started! Blue LED should be blinking.');
            $this->loadSensors();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start identify mode: ' . $e->getMessage());
        }
    }

    public function stopIdentify($sensorId)
    {
        try {
            $sensor = SensorAssignment::find($sensorId);
            $sensor->stopIdentify();

            session()->flash('success', 'Identify mode stopped.');
            $this->loadSensors();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to stop identify mode: ' . $e->getMessage());
        }
    }

    public function closeAssignModal()
    {
        $this->showAssignModal = false;
        $this->selectedSensor = null;
        $this->selectedSpaceCode = '';
        $this->deviceName = '';
        $this->floorNumber = '';
        $this->columnCode = '';
        $this->slotNumber = '';
    }

    public function updatedFilterStatus()
    {
        $this->loadSensors();
    }

    public function render()
    {
        return view('livewire.sensor-manager')->layout('layouts.app');
    }
}


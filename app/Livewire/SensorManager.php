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
    public $filterStatus = 'all'; // all, assigned, unassigned

    // Floor/Column/Slot configuration
    public $floorNumber = '';
    public $columnCode = '';
    public $slotNumber = '';

    // Column configuration (matches parking map)
    private $columnSlotConfig = [
        'A' => 1,  // Section A: 1 slot
        'B' => 4,  // Section B: 4 slots
        'C' => 2,  // Section C: 2 slots
        'D' => 7,  // Section D: 7 slots
        'E' => 3,  // Section E: 3 slots
        'F' => 7,  // Section F: 7 slots
        'G' => 5,  // Section G: 5 slots
        'H' => 3,  // Section H: 3 slots
        'I' => 5,  // Section I: 5 slots
        'J' => 5,  // Section J: 5 slots
    ];

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
        // Get max slots for the selected column
        $maxSlots = $this->columnSlotConfig[$this->columnCode] ?? 1;

        $this->validate([
            'floorNumber' => 'required|integer|min:1|max:4',
            'columnCode' => 'required|string|size:1|in:A,B,C,D,E,F,G,H,I,J',
            'slotNumber' => "required|integer|min:1|max:{$maxSlots}"
        ]);

        try {
            // Build space_code (e.g., "4B4")
            $spaceCode = "{$this->floorNumber}{$this->columnCode}{$this->slotNumber}";

            // Map floor numbers to proper floor names
            $floorNames = [
                1 => '1st Floor',
                2 => '2nd Floor',
                3 => '3rd Floor',
                4 => '4th Floor',
                5 => '5th Floor',
                6 => '6th Floor',
                7 => '7th Floor',
                8 => '8th Floor',
                9 => '9th Floor',
                10 => '10th Floor'
            ];
            $floorLevel = $floorNames[$this->floorNumber] ?? "{$this->floorNumber}th Floor";

            // Check if space is already assigned to another sensor
            $existingAssignment = SensorAssignment::where('space_code', $spaceCode)
                ->where('id', '!=', $this->selectedSensor->id)
                ->where('status', 'active')
                ->first();

            if ($existingAssignment) {
                session()->flash('error', "Parking space {$spaceCode} is already assigned to another sensor");
                return;
            }

            // If sensor was previously assigned, delete old temporary parking space
            if ($this->selectedSensor->space_code) {
                $oldSpaceCode = $this->selectedSensor->space_code;
                // Delete old temporary space (starts with 'T' like TEEFF1)
                if (str_starts_with($oldSpaceCode, 'T')) {
                    ParkingSpace::where('space_code', $oldSpaceCode)->delete();
                }
            }

            // Check if parking space exists, create if it doesn't
            $parkingSpace = ParkingSpace::firstOrCreate(
                ['space_code' => $spaceCode],
                [
                    'floor_number' => $this->floorNumber,
                    'column_code' => $this->columnCode,
                    'slot_number' => $this->slotNumber,
                    'floor_level' => $floorLevel,
                    'is_occupied' => false,
                    'distance_cm' => 0
                ]
            );

            $this->selectedSensor->update([
                'space_code' => $spaceCode,
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

            // Delete the parking space if it exists
            if ($sensor->space_code) {
                ParkingSpace::where('space_code', $sensor->space_code)->delete();
            }

            // Reset ALL sensor fields
            $sensor->update([
                'space_code' => null,
                'status' => 'unassigned'
            ]);

            session()->flash('success', 'Sensor unassigned and reset successfully!');
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
        $this->floorNumber = '';
        $this->columnCode = '';
        $this->slotNumber = '';
    }

    public function updatedFilterStatus()
    {
        $this->loadSensors();
    }

    public function updatedColumnCode()
    {
        // Reset slot number when column changes
        $this->slotNumber = '';
    }

    public function getAvailableColumns()
    {
        return array_keys($this->columnSlotConfig);
    }

    public function getMaxSlotsForColumn()
    {
        if (!$this->columnCode || !isset($this->columnSlotConfig[$this->columnCode])) {
            return 0;
        }
        return $this->columnSlotConfig[$this->columnCode];
    }

    public function render()
    {
        return view('livewire.sensor-manager')->layout('layouts.app');
    }
}


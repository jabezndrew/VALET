<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use App\Models\SensorAssignment;
use Carbon\Carbon;

class ParkingMapLayout extends Component
{
    public $selectedFloor = '4th Floor';
    public $parkingSpaces = [];
    public $floorStats = [];
    public $availableFloors = [];
    public $lastUpdate;
    public $isAutoRefreshEnabled = true;

    // Slot management properties
    public $showSlotModal = false;
    public $selectedSlot = null;
    public $slotName = '';
    public $sensorId = '';
    public $floorLevel = '';
    public $isSlotActive = true;
    public $xPosition = 0;
    public $yPosition = 0;
    public $isCreatingNew = false;
    public $availableSensors = [];
    public $takenSlots = [];
    public $isSlotNameTaken = false;

    protected $listeners = [
        'refresh-parking-data' => 'loadParkingData'
    ];

    protected $rules = [
        'slotName' => 'required|string|max:10',
        'sensorId' => 'required|integer|unique:parking_spaces,sensor_id',
        'floorLevel' => 'required|string',
        'isSlotActive' => 'boolean',
        'xPosition' => 'required|numeric',
        'yPosition' => 'required|numeric',
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
            // Get all parking spaces for the selected floor with positions
            $this->parkingSpaces = ParkingSpace::forFloor($this->selectedFloor)
                ->where(function($query) {
                    $query->whereNotNull('space_code')
                          ->orWhereNotNull('slot_name');
                })
                ->whereNotNull('x_position')
                ->whereNotNull('y_position')
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
        // Complete mapping for 4th floor based on React Native config
        // Format: FloorNumber + Section + SpotNumber (e.g., 4A1, 4B1, etc.)
        // Supports both 400-series (401-442) and direct (1-42) sensor IDs
        $mapping = [
            // Section A
            7 => '4A1',
            407 => '4A1',

            // Section B (B4, B3, B2, B1)
            1 => '4B4',
            401 => '4B4',
            2 => '4B3',
            402 => '4B3',
            3 => '4B2',
            403 => '4B2',
            4 => '4B1',
            404 => '4B1',

            // Section C
            5 => '4C1',
            405 => '4C1',
            6 => '4C2',
            406 => '4C2',

            // Section D (D7 to D1)
            14 => '4D7',
            414 => '4D7',
            13 => '4D6',
            413 => '4D6',
            12 => '4D5',
            412 => '4D5',
            11 => '4D4',
            411 => '4D4',
            10 => '4D3',
            410 => '4D3',
            9 => '4D2',
            409 => '4D2',
            8 => '4D1',
            408 => '4D1',

            // Section E
            17 => '4E3',
            417 => '4E3',
            16 => '4E2',
            416 => '4E2',
            15 => '4E1',
            415 => '4E1',

            // Section F (F1 to F7)
            18 => '4F1',
            418 => '4F1',
            19 => '4F2',
            419 => '4F2',
            20 => '4F3',
            420 => '4F3',
            21 => '4F4',
            421 => '4F4',
            22 => '4F5',
            422 => '4F5',
            23 => '4F6',
            423 => '4F6',
            24 => '4F7',
            424 => '4F7',

            // Section G (G1 to G5)
            25 => '4G1',
            425 => '4G1',
            26 => '4G2',
            426 => '4G2',
            27 => '4G3',
            427 => '4G3',
            28 => '4G4',
            428 => '4G4',
            29 => '4G5',
            429 => '4G5',

            // Section H (H1 to H3)
            30 => '4H1',
            430 => '4H1',
            31 => '4H2',
            431 => '4H2',
            32 => '4H3',
            432 => '4H3',

            // Section I (I5 to I1)
            37 => '4I5',
            437 => '4I5',
            36 => '4I4',
            436 => '4I4',
            35 => '4I3',
            435 => '4I3',
            34 => '4I2',
            434 => '4I2',
            33 => '4I1',
            433 => '4I1',

            // Section J (J5 to J1)
            42 => '4J5',
            442 => '4J5',
            41 => '4J4',
            441 => '4J4',
            40 => '4J3',
            440 => '4J3',
            39 => '4J2',
            439 => '4J2',
            38 => '4J1',
            438 => '4J1',
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
            // Check if floor has parking spaces with assigned sensors
            $count = ParkingSpace::forFloor($floor)
                ->where(function($query) {
                    $query->whereNotNull('space_code')
                          ->orWhereNotNull('slot_name');
                })
                ->whereNotNull('x_position')
                ->whereNotNull('y_position')
                ->whereHas('sensorAssignment')
                ->count();
            if ($count > 0) {
                $this->availableFloors[] = $floor;
            }
        }
    }

    public function getAllFloors()
    {
        return ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
    }

    public function floorHasData($floor)
    {
        return in_array($floor, $this->availableFloors);
    }

    public function hasFloorData($floor)
    {
        return in_array($floor, $this->availableFloors);
    }

    // Slot Management Methods
    public function openSlotModal($slotId = null)
    {
        // Check authorization
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized access');
            return;
        }

        if ($slotId) {
            $this->selectedSlot = ParkingSpace::find($slotId);

            if ($this->selectedSlot) {
                $this->slotName = $this->selectedSlot->slot_name ?? $this->selectedSlot->space_code ?? '';
                $this->sensorId = $this->selectedSlot->sensor_id ?? '';
                $this->floorLevel = $this->selectedSlot->floor_level ?? $this->selectedFloor;

                // Check if slot has an assigned sensor
                $hasAssignedSensor = SensorAssignment::where('space_code', $this->selectedSlot->space_code)->exists();
                $this->isSlotActive = $hasAssignedSensor ? (bool)($this->selectedSlot->is_active ?? true) : false;

                $this->xPosition = $this->selectedSlot->x_position ?? 0;
                $this->yPosition = $this->selectedSlot->y_position ?? 0;
                $this->isCreatingNew = false;
                $this->loadAvailableSensors();
                $this->loadTakenSlots();
                $this->showSlotModal = true;
            }
        }
    }

    public function openCreateSlotModal($x, $y)
    {
        // Check authorization
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized access');
            return;
        }

        $this->isCreatingNew = true;
        $this->xPosition = $x;
        $this->yPosition = $y;
        $this->floorLevel = $this->selectedFloor;
        $this->slotName = '';
        $this->sensorId = '';
        // New slots are inactive by default until a sensor is assigned
        $this->isSlotActive = false;
        $this->loadAvailableSensors();
        $this->loadTakenSlots();
        $this->showSlotModal = true;
    }

    public function closeSlotModal()
    {
        $this->showSlotModal = false;
        $this->selectedSlot = null;
        $this->resetSlotForm();
    }

    public function resetSlotForm()
    {
        $this->slotName = '';
        $this->sensorId = '';
        $this->floorLevel = '';
        $this->isSlotActive = true;
        $this->xPosition = 0;
        $this->yPosition = 0;
        $this->isCreatingNew = false;
        $this->isSlotNameTaken = false;
        $this->resetValidation();
    }

    public function saveSlot()
    {
        // Check authorization
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'ssd'])) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized access');
            return;
        }

        // Adjust validation rules for update vs create
        $rules = $this->rules;
        if (!$this->isCreatingNew && $this->selectedSlot) {
            $rules['sensorId'] = 'required|integer|unique:parking_spaces,sensor_id,' . $this->selectedSlot->id;
        }

        $this->validate($rules);

        try {
            if ($this->isCreatingNew) {
                // Create new parking slot
                ParkingSpace::create([
                    'slot_name' => $this->slotName,
                    'sensor_id' => $this->sensorId,
                    'floor_level' => $this->floorLevel,
                    'is_active' => $this->isSlotActive,
                    'x_position' => $this->xPosition,
                    'y_position' => $this->yPosition,
                    'is_occupied' => false,
                ]);

                $this->dispatch('show-alert', type: 'success', message: 'Parking slot created successfully');
            } elseif ($this->selectedSlot) {
                // Update existing parking slot
                $this->selectedSlot->update([
                    'slot_name' => $this->slotName,
                    'sensor_id' => $this->sensorId,
                    'floor_level' => $this->floorLevel,
                    'is_active' => $this->isSlotActive,
                    'x_position' => $this->xPosition,
                    'y_position' => $this->yPosition,
                ]);

                $this->dispatch('show-alert', type: 'success', message: 'Parking slot updated successfully');
            }

            $this->loadParkingData();
            $this->closeSlotModal();
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to save slot: ' . $e->getMessage());
        }
    }

    public function deleteSlot()
    {
        // Check authorization
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'ssd'])) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized access');
            return;
        }

        try {
            if ($this->selectedSlot) {
                $slotName = $this->selectedSlot->slot_name;
                $this->selectedSlot->delete();

                $this->dispatch('show-alert', type: 'success', message: "Parking slot {$slotName} deleted successfully");
                $this->loadParkingData();
                $this->closeSlotModal();
            }
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete slot: ' . $e->getMessage());
        }
    }

    private function loadAvailableSensors()
    {
        // Get all taken sensor IDs
        $takenSensorIds = ParkingSpace::pluck('sensor_id')->toArray();

        // Show all real sensors with API data (401-405)
        $allSensors = range(401, 405);

        // Build array with sensor info including whether it's taken
        $this->availableSensors = [];
        foreach ($allSensors as $sensorId) {
            $isTaken = in_array($sensorId, $takenSensorIds);
            $isCurrentSensor = $this->selectedSlot && $sensorId == $this->selectedSlot->sensor_id;

            $this->availableSensors[] = [
                'id' => $sensorId,
                'is_taken' => $isTaken && !$isCurrentSensor,
                'is_current' => $isCurrentSensor
            ];
        }
    }

    private function loadTakenSlots()
    {
        // Get all existing slot names
        $this->takenSlots = ParkingSpace::pluck('slot_name')->filter()->toArray();
    }

    public function updatedSlotName($value)
    {
        // Check if slot name is already taken when it changes
        $this->isSlotNameTaken = false;

        if (!empty($value)) {
            $query = ParkingSpace::where('slot_name', $value);

            // Exclude current slot when editing
            if ($this->selectedSlot) {
                $query->where('id', '!=', $this->selectedSlot->id);
            }

            $this->isSlotNameTaken = $query->exists();
        }
    }

    public function updatedSensorId($value)
    {
        // Automatically activate slot if a sensor is assigned
        if (!empty($value)) {
            $hasAssignedSensor = SensorAssignment::exists();
            $this->isSlotActive = $hasAssignedSensor;
        } else {
            // No sensor selected, set to inactive
            $this->isSlotActive = false;
        }
    }

    public function render()
    {
        return view('livewire.parking-map-layout')->layout('layouts.app');
    }
}

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

    // Edit mode properties
    public $editMode = false;
    public $showSlotModal = false;
    public $availableSensors = [];
    public $selectedSlot = null;

    // Slot form data
    public $slotForm = [
        'id' => null,
        'slot_name' => '',
        'sensor_id' => null,
        'is_active' => true,
    ];

    protected $listeners = [
        'refresh-parking-data' => 'loadParkingData'
    ];

    protected $rules = [
        'slotForm.slot_name' => 'required|string|max:10',
        'slotForm.sensor_id' => 'nullable|integer',
        'slotForm.is_active' => 'boolean',
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
        $this->loadAvailableSensors();
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

    public function toggleEditMode()
    {
        if (!$this->canEditLayout()) {
            $this->dispatch('show-alert', type: 'error', message: 'You do not have permission to edit the layout.');
            return;
        }

        $this->editMode = !$this->editMode;

        if ($this->editMode) {
            $this->loadAvailableSensors();
            $this->dispatch('show-alert', type: 'info', message: 'Edit mode enabled. Click on slots to manage them.');
        } else {
            $this->closeSlotModal();
            $this->dispatch('show-alert', type: 'success', message: 'Edit mode disabled.');
        }
    }

    public function handleSlotClick($slotId)
    {
        $slot = ParkingSpace::find($slotId);

        if (!$slot) {
            return;
        }

        $this->selectedSlot = $slot;

        if (!$this->editMode) {
            // Show slot details in read-only mode
            $this->showSlotModal = true;
            return;
        }

        // Load slot data into form for editing
        $this->slotForm = [
            'id' => $slot->id,
            'slot_name' => $slot->slot_name ?? $this->getSensorDisplayName($slot->sensor_id),
            'sensor_id' => $slot->sensor_id,
            'is_active' => $slot->is_active ?? true,
        ];

        $this->showSlotModal = true;
    }

    public function saveSlot()
    {
        if (!$this->canEditLayout()) {
            $this->dispatch('show-alert', type: 'error', message: 'You do not have permission to save slots.');
            return;
        }

        $this->validate();

        try {
            $slotData = [
                'slot_name' => $this->slotForm['slot_name'],
                'sensor_id' => $this->slotForm['sensor_id'],
                'is_active' => $this->slotForm['is_active'] ?? true,
            ];

            if ($this->slotForm['id']) {
                // Update existing slot
                $slot = ParkingSpace::findOrFail($this->slotForm['id']);
                $slot->update($slotData);
                $message = "Slot {$this->slotForm['slot_name']} updated successfully!";
            }

            $this->loadParkingData();
            $this->loadAvailableSensors();
            $this->closeSlotModal();
            $this->dispatch('show-alert', type: 'success', message: $message);

        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to save slot: ' . $e->getMessage());
        }
    }

    public function deleteSlot()
    {
        if (!$this->canEditLayout() || !$this->slotForm['id']) {
            $this->dispatch('show-alert', type: 'error', message: 'Cannot delete slot.');
            return;
        }

        try {
            $slot = ParkingSpace::findOrFail($this->slotForm['id']);
            $slotName = $slot->slot_name ?? $this->getSensorDisplayName($slot->sensor_id);
            $slot->delete();

            $this->loadParkingData();
            $this->loadAvailableSensors();
            $this->closeSlotModal();
            $this->dispatch('show-alert', type: 'success', message: "Slot {$slotName} deleted successfully!");

        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete slot: ' . $e->getMessage());
        }
    }

    public function closeSlotModal()
    {
        $this->showSlotModal = false;
        $this->selectedSlot = null;
        $this->resetSlotForm();
    }

    private function resetSlotForm()
    {
        $this->slotForm = [
            'id' => null,
            'slot_name' => '',
            'sensor_id' => null,
            'is_active' => true,
        ];
    }

    private function loadAvailableSensors()
    {
        $assignedSensors = ParkingSpace::pluck('sensor_id')->toArray();
        $allSensors = range(1, 50); // Adjust range as needed
        $this->availableSensors = array_diff($allSensors, $assignedSensors);
    }

    private function canEditLayout()
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'ssd']);
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

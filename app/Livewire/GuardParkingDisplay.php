<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;

class GuardParkingDisplay extends Component
{
    public $isAuthenticated = false;
    public $pinInput = '';
    public $pinError = '';

    public $floors = [];
    public $selectedFloor = null;
    public $parkingSpaces = [];

    // Action modal
    public $showActionModal = false;
    public $selectedSpace = null;
    public $actionType = null;
    public $incidentCategory = '';
    public $incidentNotes = '';

    public function mount()
    {
        // Check if already authenticated via session
        if (session('guard_authenticated')) {
            $this->isAuthenticated = true;
            $this->loadFloors();
        }
    }

    public function verifyPin()
    {
        $correctPin = config('app.guard_pin', '1234');

        if ($this->pinInput === $correctPin) {
            $this->isAuthenticated = true;
            $this->pinError = '';
            session(['guard_authenticated' => true]);
            $this->loadFloors();
        } else {
            $this->pinError = 'Incorrect PIN. Please try again.';
            $this->pinInput = '';
        }
    }

    public function loadFloors()
    {
        $this->floors = ParkingSpace::whereNotNull('floor_number')
            ->whereNotNull('space_code')
            ->select('floor_number')
            ->distinct()
            ->orderBy('floor_number')
            ->pluck('floor_number')
            ->map(function ($floor) {
                $spaces = ParkingSpace::where('floor_number', $floor)
                    ->whereNotNull('space_code')
                    ->get();
                return [
                    'number' => $floor,
                    'total' => $spaces->count(),
                    'available' => $spaces->where('is_occupied', false)->count(),
                    'occupied' => $spaces->where('is_occupied', true)->count(),
                ];
            })
            ->toArray();

        if (!$this->selectedFloor && count($this->floors) > 0) {
            $this->selectedFloor = $this->floors[0]['number'];
            $this->loadParkingSpaces();
        }
    }

    public function selectFloor($floor)
    {
        $this->selectedFloor = $floor;
        $this->loadParkingSpaces();
    }

    public function loadParkingSpaces()
    {
        if (!$this->selectedFloor) {
            $this->parkingSpaces = [];
            return;
        }

        $this->parkingSpaces = ParkingSpace::where('floor_number', $this->selectedFloor)
            ->whereNotNull('space_code')
            ->orderBy('column_code')
            ->orderBy('slot_number')
            ->get()
            ->toArray();
    }

    public function openActionModal($spaceId)
    {
        $this->selectedSpace = ParkingSpace::find($spaceId);
        if ($this->selectedSpace) {
            $this->showActionModal = true;
            $this->actionType = null;
            $this->incidentCategory = '';
            $this->incidentNotes = '';
        }
    }

    public function closeActionModal()
    {
        $this->showActionModal = false;
        $this->selectedSpace = null;
        $this->actionType = null;
        $this->incidentCategory = '';
        $this->incidentNotes = '';
    }

    public function setOverride($status)
    {
        if (!$this->selectedSpace) return;

        $this->selectedSpace->update([
            'is_occupied' => $status === 'occupied'
        ]);

        $this->closeActionModal();
        $this->loadFloors();
        $this->loadParkingSpaces();

        session()->flash('success', "Space {$this->selectedSpace->space_code} marked as {$status}");
    }

    public function reportIncident()
    {
        if (!$this->selectedSpace || !$this->incidentCategory) {
            return;
        }

        // For now, just log the incident - you can create an incidents table later
        logger()->info('Guard incident reported', [
            'space_code' => $this->selectedSpace->space_code,
            'category' => $this->incidentCategory,
            'notes' => $this->incidentNotes,
            'reported_at' => now(),
        ]);

        $this->closeActionModal();
        session()->flash('success', "Incident reported for space {$this->selectedSpace->space_code}");
    }

    public function refreshData()
    {
        $this->loadFloors();
        $this->loadParkingSpaces();
    }

    public function logout()
    {
        session()->forget('guard_authenticated');
        $this->isAuthenticated = false;
        $this->pinInput = '';
    }

    public function render()
    {
        return view('livewire.guard-parking-display')
            ->layout('layouts.guard');
    }
}

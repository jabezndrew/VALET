<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use App\Models\GuardIncident;
use App\Models\SensorAssignment;

class GuardParkingDisplay extends Component
{
    public $selectedFloor = '1st Floor';
    public $parkingSpaces = [];
    public $allFloorStats = [];
    public $lastUpdate;

    // PIN authentication
    public $isAuthenticated = false;
    public $pinInput = '';
    public $pinError = '';

    // Filter
    public $statusFilter = 'all'; // all, available, occupied, issues

    // Action modals
    public $showActionModal = false;
    public $selectedSpace = null;
    public $actionType = ''; // override, report

    // Override form
    public $overrideStatus = 'occupied';

    // Incident form
    public $incidentCategory = 'debris';
    public $incidentNotes = '';

    // Open incidents count
    public $openIncidentsCount = 0;

    protected $listeners = ['refreshData' => '$refresh'];

    public function mount()
    {
        // Check if already authenticated via session
        $this->isAuthenticated = session('guard_authenticated', false);

        $this->loadAllFloorStats();
        $this->loadParkingData();
        $this->loadOpenIncidentsCount();
    }

    public function verifyPin()
    {
        $correctPin = config('app.guard_pin', '1234');

        if ($this->pinInput === $correctPin) {
            $this->isAuthenticated = true;
            session(['guard_authenticated' => true]);
            $this->pinError = '';
            $this->pinInput = '';
        } else {
            $this->pinError = 'Invalid PIN. Please try again.';
            $this->pinInput = '';
        }
    }

    public function logout()
    {
        $this->isAuthenticated = false;
        session()->forget('guard_authenticated');
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

            // Count based on effective status (considering manual overrides)
            $occupied = $spacesWithSensors->filter(fn($s) => $s->getEffectiveStatus() === 'occupied')->count();
            $blocked = $spacesWithSensors->filter(fn($s) => $s->getEffectiveStatus() === 'blocked')->count();
            $available = $total - $occupied - $blocked;

            // Count open incidents for this floor
            $incidents = GuardIncident::where('floor_level', $floor)
                ->where('status', 'open')
                ->count();

            $this->allFloorStats[$floor] = [
                'total' => $total,
                'available' => $available,
                'occupied' => $occupied,
                'blocked' => $blocked,
                'incidents' => $incidents,
            ];
        }
    }

    public function loadParkingData()
    {
        $query = ParkingSpace::where('floor_level', $this->selectedFloor)
            ->with('sensorAssignment')
            ->orderBy('slot_name');

        $this->parkingSpaces = $query->get();
        $this->loadAllFloorStats();
        $this->lastUpdate = now()->format('H:i:s');
    }

    public function loadOpenIncidentsCount()
    {
        $this->openIncidentsCount = GuardIncident::where('status', 'open')->count();
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

    public function updatedStatusFilter()
    {
        $this->loadParkingData();
    }

    // Action Modal Methods
    public function openActionModal($spaceId, $type)
    {
        if (!$this->isAuthenticated) {
            return;
        }

        $this->selectedSpace = ParkingSpace::find($spaceId);
        $this->actionType = $type;
        $this->showActionModal = true;

        // Reset forms
        $this->overrideStatus = $this->selectedSpace->getEffectiveStatus();
        $this->incidentCategory = 'debris';
        $this->incidentNotes = '';
    }

    public function closeActionModal()
    {
        $this->showActionModal = false;
        $this->selectedSpace = null;
        $this->actionType = '';
    }

    public function submitOverride()
    {
        if (!$this->selectedSpace || !$this->isAuthenticated) {
            return;
        }

        $this->selectedSpace->setManualOverride(
            $this->overrideStatus,
            'Guard',
            60 // expires in 60 minutes
        );

        session()->flash('success', "Spot {$this->selectedSpace->space_code} marked as {$this->overrideStatus}. Override expires in 1 hour.");

        $this->closeActionModal();
        $this->loadParkingData();
    }

    public function clearOverride($spaceId)
    {
        if (!$this->isAuthenticated) {
            return;
        }

        $space = ParkingSpace::find($spaceId);
        if ($space) {
            $space->clearManualOverride();
            session()->flash('success', "Manual override cleared for {$space->space_code}.");
            $this->loadParkingData();
        }
    }

    public function submitIncident()
    {
        if (!$this->isAuthenticated) {
            return;
        }

        $this->validate([
            'incidentCategory' => 'required|in:debris,damaged,blocked,light_issue,sensor_issue,other',
            'incidentNotes' => 'nullable|string|max:500',
        ]);

        GuardIncident::create([
            'space_code' => $this->selectedSpace?->space_code,
            'floor_level' => $this->selectedFloor,
            'category' => $this->incidentCategory,
            'notes' => $this->incidentNotes,
            'status' => 'open',
            'reported_by' => 'Guard',
        ]);

        session()->flash('success', 'Incident reported successfully.');

        $this->closeActionModal();
        $this->loadOpenIncidentsCount();
    }

    public function getFilteredSpaces()
    {
        return $this->parkingSpaces->filter(function ($space) {
            if ($this->statusFilter === 'all') {
                return true;
            }

            $effectiveStatus = $space->getEffectiveStatus();

            if ($this->statusFilter === 'available') {
                return $effectiveStatus === 'available';
            }

            if ($this->statusFilter === 'occupied') {
                return $effectiveStatus === 'occupied';
            }

            if ($this->statusFilter === 'issues') {
                return $effectiveStatus === 'blocked' || $space->isManualOverrideActive();
            }

            return true;
        });
    }

    public function render()
    {
        return view('livewire.guard-parking-display', [
            'filteredSpaces' => $this->getFilteredSpaces(),
        ])->layout('layouts.guard');
    }
}

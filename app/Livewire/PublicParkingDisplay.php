<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use App\Models\SensorAssignment;
use App\Models\GuardIncident;
use App\Models\Feedback;
use Illuminate\Support\Facades\Cache;

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

    // Guard action properties (only used by security roles)
    public $pinInput = '';
    public $pinError = '';
    public $showActionModal = false;
    public $selectedSpace = null;
    public $actionType = '';
    public $overrideStatus = 'occupied';
    public $overrideReason = '';
    public $overrideCustomReason = '';
    public $overrideError = '';
    public $incidentCategory = 'debris';
    public $incidentNotes = '';
    public $openIncidentsCount = 0;
    public $openIncidents = [];
    public $showIncidentsModal = false;

    public function mount()
    {
        $floor = request()->query('floor');
        if ($floor) {
            $this->selectedFloor = $floor;
        }

        $this->loadAllFloorStats();
        $this->loadParkingData();

        if (auth()->check() && auth()->user()->role === 'security') {
            $this->loadOpenIncidentsCount();
        }
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
            $occupied = $spacesWithSensors->filter(fn($s) => $s->getEffectiveStatus() === 'occupied')->count();
            $available = $spacesWithSensors->filter(fn($s) => $s->getEffectiveStatus() === 'available')->count();

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
        return ParkingSpace::where('floor_level', $floor)->whereHas('sensorAssignment')->exists();
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

    // Guard action methods (security roles only)

    public function isGuardUser()
    {
        return auth()->check() && auth()->user()->role === 'security';
    }

    public function loadOpenIncidentsCount()
    {
        $this->openIncidentsCount = GuardIncident::where('status', 'open')->count();
    }

    public function openActionModal($spaceId, $type)
    {
        if (!$this->isGuardUser()) {
            return;
        }

        // Clear any active route
        $this->clearRoute();

        $this->selectedSpace = ParkingSpace::find($spaceId);
        $this->actionType = $type;
        $this->showActionModal = true;

        $this->overrideStatus = $this->selectedSpace->getEffectiveStatus();
        $this->overrideReason = '';
        $this->overrideCustomReason = '';
        $this->overrideError = '';
        $this->incidentCategory = 'debris';
        $this->incidentNotes = '';
        $this->pinError = '';
        $this->pinInput = '';
    }

    public function closeActionModal()
    {
        $this->showActionModal = false;
        $this->selectedSpace = null;
        $this->actionType = '';
        $this->overrideReason = '';
        $this->overrideCustomReason = '';
        $this->overrideError = '';
        $this->pinInput = '';
        $this->pinError = '';
    }

    public function submitOverride()
    {
        if (!$this->selectedSpace || !$this->isGuardUser()) {
            return;
        }

        $currentStatus = $this->selectedSpace->getEffectiveStatus();
        if ($this->overrideStatus === $currentStatus) {
            $this->overrideError = "Spot is already marked as {$currentStatus}. Select a different status.";
            return;
        }

        if (empty($this->overrideReason)) {
            $this->overrideError = 'Please select a reason for the override.';
            return;
        }

        if ($this->overrideReason === 'Other' && empty(trim($this->overrideCustomReason))) {
            $this->overrideError = 'Please specify the reason.';
            return;
        }

        $correctPin = config('app.guard_pin', '1234');
        if ($this->pinInput !== $correctPin) {
            $this->pinError = 'Invalid PIN. Please try again.';
            $this->pinInput = '';
            return;
        }

        $finalReason = $this->overrideReason === 'Other' ? $this->overrideCustomReason : $this->overrideReason;

        $this->selectedSpace->setManualOverride(
            $this->overrideStatus,
            auth()->user()->name,
            $finalReason
        );

        // Send notification to admin/SSD via cache
        $notifications = Cache::get('admin_override_notifications', []);
        $notifications[] = [
            'id' => uniqid(),
            'type' => 'guard_override',
            'space_code' => $this->selectedSpace->space_code,
            'status' => $this->overrideStatus,
            'reason' => $finalReason,
            'guard_name' => auth()->user()->name,
            'floor_level' => $this->selectedFloor,
            'created_at' => now()->toISOString(),
            'read' => false,
        ];
        Cache::put('admin_override_notifications', $notifications, now()->addDays(7));

        session()->flash('success', "Spot {$this->selectedSpace->space_code} marked as {$this->overrideStatus}. Override active until cleared.");

        $this->pinInput = '';
        $this->pinError = '';
        $this->closeActionModal();
        $this->loadParkingData();
    }

    public function clearOverride($spaceId)
    {
        if (!$this->isGuardUser()) {
            return;
        }

        $correctPin = config('app.guard_pin', '1234');
        if ($this->pinInput !== $correctPin) {
            $this->pinError = 'Invalid PIN. Please try again.';
            $this->pinInput = '';
            return;
        }

        $space = ParkingSpace::find($spaceId);
        if ($space) {
            $space->clearManualOverride();
            session()->flash('success', "Manual override cleared for {$space->space_code}.");
            $this->pinInput = '';
            $this->pinError = '';
            $this->closeActionModal();
            $this->loadParkingData();
        }
    }

    public function submitIncident()
    {
        if (!$this->isGuardUser()) {
            return;
        }

        $correctPin = config('app.guard_pin', '1234');
        if ($this->pinInput !== $correctPin) {
            $this->pinError = 'Invalid PIN. Please try again.';
            $this->pinInput = '';
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
            'reported_by' => auth()->user()->name ?? 'Guard',
        ]);

        $categoryLabels = [
            'debris' => 'Debris / Obstruction',
            'damaged' => 'Damaged Spot',
            'blocked' => 'Blocked Area',
            'light_issue' => 'Light Issue',
            'sensor_issue' => 'Sensor Issue',
            'other' => 'Other Issue',
        ];

        $categoryLabel = $categoryLabels[$this->incidentCategory] ?? $this->incidentCategory;
        $spaceCode = $this->selectedSpace?->space_code ?? 'N/A';

        Feedback::create([
            'user_id' => auth()->id(),
            'type' => 'guard_report',
            'message' => "[Guard Report] {$categoryLabel} at Spot {$spaceCode} ({$this->selectedFloor})" .
                        ($this->incidentNotes ? "\n\nNotes: {$this->incidentNotes}" : ''),
            'rating' => null,
            'email' => null,
            'issues' => [
                'category' => $this->incidentCategory,
                'space_code' => $spaceCode,
                'floor_level' => $this->selectedFloor,
            ],
            'device_info' => [
                'platform' => 'Web',
                'reported_by' => auth()->user()->name ?? 'Guard',
                'submitted_at' => now()->toISOString(),
            ],
            'status' => 'pending',
        ]);

        session()->flash('success', 'Incident reported successfully.');

        $this->pinInput = '';
        $this->pinError = '';
        $this->closeActionModal();
        $this->loadOpenIncidentsCount();
    }

    public function openIncidentsModal()
    {
        if (!$this->isGuardUser()) {
            return;
        }

        $this->openIncidents = GuardIncident::where('status', 'open')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
        $this->showIncidentsModal = true;
    }

    public function closeIncidentsModal()
    {
        $this->showIncidentsModal = false;
        $this->openIncidents = [];
    }

    public function resolveIncident($incidentId)
    {
        if (!$this->isGuardUser()) {
            return;
        }

        $correctPin = config('app.guard_pin', '1234');
        if ($this->pinInput !== $correctPin) {
            $this->pinError = 'Invalid PIN. Please try again.';
            $this->pinInput = '';
            return;
        }

        $incident = GuardIncident::find($incidentId);
        if ($incident) {
            $incident->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            session()->flash('success', "Issue at {$incident->space_code} has been resolved.");

            $this->pinInput = '';
            $this->pinError = '';

            $this->loadOpenIncidentsCount();
            $this->openIncidents = GuardIncident::where('status', 'open')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();

            if (count($this->openIncidents) === 0) {
                $this->showIncidentsModal = false;
            }
        }
    }

    public function render()
    {
        $this->parkingSpaces = ParkingSpace::where('floor_level', $this->selectedFloor)
            ->with('sensorAssignment')
            ->orderBy('slot_name')
            ->get();

        return view('livewire.public-parking-display')
            ->layout('layouts.app');
    }
}

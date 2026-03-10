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
    public $showActionModal = false;
    public $selectedSpace = null;
    public $malfunctionReason = '';
    public $malfunctionCustomReason = '';
    public $malfunctionError = '';
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

        if (auth()->check() && $this->isGuardUser()) {
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
        return auth()->check() && in_array(auth()->user()->role, ['security', 'admin', 'ssd']);
    }

    public function loadOpenIncidentsCount()
    {
        $this->openIncidentsCount = GuardIncident::where('status', 'open')->count();
    }

    public function openActionModal($spaceId)
    {
        if (!$this->isGuardUser()) {
            return;
        }

        $this->clearRoute();
        $this->selectedSpace = ParkingSpace::with('sensorAssignment')->find($spaceId);
        $this->showActionModal = true;
        $this->malfunctionReason = '';
        $this->malfunctionCustomReason = '';
        $this->malfunctionError = '';
    }

    public function clearMalfunctionFromModal()
    {
        if (!$this->selectedSpace || !$this->isGuardUser()) {
            return;
        }

        $this->selectedSpace->clearMalfunction();

        $notifications = Cache::get('admin_override_notifications', []);
        $notifications[] = [
            'id'          => uniqid(),
            'type'        => 'malfunction_cleared',
            'space_code'  => $this->selectedSpace->space_code,
            'status'      => 'available',
            'reason'      => 'Malfunction report cleared',
            'guard_name'  => auth()->user()->name,
            'floor_level' => $this->selectedFloor,
            'created_at'  => now()->toISOString(),
            'read'        => false,
        ];
        Cache::put('admin_override_notifications', $notifications, now()->addDays(7));

        session()->flash('success', "Malfunction flag cleared for spot {$this->selectedSpace->space_code}.");
        $this->closeActionModal();
        $this->loadParkingData();
    }

    public function closeActionModal()
    {
        $this->showActionModal = false;
        $this->selectedSpace = null;
        $this->malfunctionReason = '';
        $this->malfunctionCustomReason = '';
        $this->malfunctionError = '';
    }

    public function reportMalfunction()
    {
        if (!$this->selectedSpace || !$this->isGuardUser()) {
            return;
        }

        if ($this->selectedSpace->malfunctioned) {
            $this->malfunctionError = 'This spot is already flagged as malfunctioned.';
            return;
        }

        if (empty($this->malfunctionReason)) {
            $this->malfunctionError = 'Please select an issue type.';
            return;
        }

        if ($this->malfunctionReason === 'Other' && empty(trim($this->malfunctionCustomReason))) {
            $this->malfunctionError = 'Please describe the issue.';
            return;
        }

        $finalReason = $this->malfunctionReason === 'Other' ? $this->malfunctionCustomReason : $this->malfunctionReason;

        $this->selectedSpace->reportMalfunction(auth()->user()->name, $finalReason);

        // Notify admin/SSD via cache
        $notifications = Cache::get('admin_override_notifications', []);
        $notifications[] = [
            'id' => uniqid(),
            'type' => 'malfunction_report',
            'space_code' => $this->selectedSpace->space_code,
            'status' => 'malfunctioned',
            'reason' => $finalReason,
            'guard_name' => auth()->user()->name,
            'floor_level' => $this->selectedFloor,
            'created_at' => now()->toISOString(),
            'read' => false,
        ];
        Cache::put('admin_override_notifications', $notifications, now()->addDays(7));

        session()->flash('success', "Spot {$this->selectedSpace->space_code} flagged as malfunctioned. Admin has been notified.");
        $this->closeActionModal();
        $this->loadParkingData();
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

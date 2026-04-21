<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use App\Models\ParkingEntry;
use App\Models\SensorAssignment;
use App\Models\GuardIncident;
use Illuminate\Support\Facades\Cache;

class PublicParkingDisplay extends Component
{
    public $selectedFloor = '1st Floor';
    public $parkingSpaces = [];
    public $floorStats = [];
    public $lastUpdate;
    public $availableFloors = [];
    public $allFloorStats = [];
    public $floorSortBy = 'available_desc';
    public $showRoute = false;
    public $selectedSpot = null;
    public $selectedSection = null;
    public $selectedSpotX = 0;
    public $selectedSpotY = 0;

    // Sensor pairing modal (admin only)
    public $showSensorModal = false;
    public $sensorModalSpaceId = null;
    public $selectedSensorKey = '';
    public $unassignedSensors = [];

    // Guard action properties (only used by security roles)
    public $showActionModal = false;
    public $selectedSpaceId = null;
    public $guardActionView = 'choice'; // 'choice', 'malfunction', or 'incident'
    public $malfunctionReason = '';
    public $malfunctionCustomReason = '';
    public $malfunctionError = '';
    public $openIncidentsCount = 0;
    public $openIncidents = [];
    public $showIncidentsModal = false;
    public $hasActiveEntry = false;

    // Incident report form properties
    public $incidentCategory = '';
    public $incidentNotes = '';
    public $incidentAt = '';
    public $involvedParty = '';
    public $actionTaken = '';
    public $pinInput = '';
    public $pinError = '';

    public function mount()
    {
        $this->loadAllFloorStats();

        $floor = request()->query('floor');
        if ($floor) {
            $this->selectedFloor = $floor;
        } elseif (!empty($this->allFloorStats)) {
            // Auto-select the floor with the most available spots
            $bestFloor = array_key_first($this->allFloorStats); // already sorted by available desc
            $this->selectedFloor = $bestFloor;
        }

        $this->loadParkingData();

        if (auth()->check() && $this->isGuardUser()) {
            $this->loadOpenIncidentsCount();
        }

        if (auth()->check()) {
            $this->hasActiveEntry = ParkingEntry::where('user_id', auth()->id())
                ->where('status', 'entered')
                ->exists();
        }
    }

    public function markAsParked()
    {
        if (!auth()->check()) return;

        $entry = ParkingEntry::where('user_id', auth()->id())
            ->where('status', 'entered')
            ->latest()
            ->first();

        if ($entry) {
            $entry->update(['status' => 'parked']);
            $this->hasActiveEntry = false;
            session()->flash('success', "You're now marked as parked.");
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

            $spacesWithSensors = $spaces->filter(fn($s) => $s->sensorAssignment !== null && !$s->malfunctioned);
            $total = $spacesWithSensors->count();
            $occupied = $spacesWithSensors->filter(fn($s) => $s->getEffectiveStatus() === 'occupied')->count();
            $available = $spacesWithSensors->filter(fn($s) => $s->getEffectiveStatus() === 'available')->count();

            $this->allFloorStats[$floor] = [
                'total' => $total,
                'available' => $available,
                'occupied' => $occupied,
            ];
        }

        $this->applyFloorSort();
    }

    public function applyFloorSort()
    {
        match($this->floorSortBy) {
            'name_asc'      => uksort($this->allFloorStats, fn($a, $b) => strnatcasecmp($a, $b)),
            'name_desc'     => uksort($this->allFloorStats, fn($a, $b) => strnatcasecmp($b, $a)),
            'available_asc' => uasort($this->allFloorStats, fn($a, $b) => $a['available'] - $b['available']),
            'occupied_desc' => uasort($this->allFloorStats, fn($a, $b) => $b['occupied'] - $a['occupied']),
            'occupied_asc'  => uasort($this->allFloorStats, fn($a, $b) => $a['occupied'] - $b['occupied']),
            default         => uasort($this->allFloorStats, fn($a, $b) => $b['available'] - $a['available']),
        };
    }

    public function updatedFloorSortBy()
    {
        $this->applyFloorSort();
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

    public function getSelectedSpaceProperty()
    {
        if (!$this->selectedSpaceId) return null;
        return ParkingSpace::with('sensorAssignment')->find($this->selectedSpaceId);
    }

    public function openActionModal($spaceId)
    {
        if (!$this->isGuardUser()) {
            return;
        }

        $this->clearRoute();
        $this->selectedSpaceId = $spaceId;
        $this->malfunctionReason = '';
        $this->malfunctionCustomReason = '';
        $this->malfunctionError = '';

        $space = $this->selectedSpace;
        if (!$space) return;

        $this->guardActionView = 'choice';

        $this->showActionModal = true;
    }

    public function showRouteFromModal()
    {
        if (!$this->selectedSpace) return;

        $this->selectParkingSpot(
            $this->selectedSpace->slot_name,
            $this->selectedSpace->column_code,
            $this->selectedSpace->x_position ?? 0,
            $this->selectedSpace->y_position ?? 0
        );
        $this->closeActionModal();
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
        $this->selectedSpaceId = null;
        $this->guardActionView = 'choice';
        $this->malfunctionReason = '';
        $this->malfunctionCustomReason = '';
        $this->malfunctionError = '';
        $this->incidentCategory = '';
        $this->incidentNotes = '';
        $this->incidentAt = '';
        $this->involvedParty = '';
        $this->actionTaken = '';
        $this->pinInput = '';
        $this->pinError = '';
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
            'reporter_role' => 'security',
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

        $this->validate([
            'incidentCategory' => 'required|in:debris,damaged,blocked,light_issue,sensor_issue,other',
            'incidentNotes'    => 'nullable|string|max:1000',
            'incidentAt'       => 'nullable|date',
            'involvedParty'    => 'nullable|string|max:255',
            'actionTaken'      => 'nullable|string|max:500',
        ]);

        GuardIncident::create([
            'space_code'     => $this->selectedSpace?->space_code,
            'floor_level'    => $this->selectedFloor,
            'incident_at'    => $this->incidentAt ?: now(),
            'category'       => $this->incidentCategory,
            'notes'          => $this->incidentNotes,
            'involved_party' => $this->involvedParty,
            'action_taken'   => $this->actionTaken,
            'status'         => 'open',
            'reported_by'    => auth()->user()->name,
        ]);

        session()->flash('success', 'Incident logged successfully.');
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
                'status'      => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => auth()->user()->name,
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

    public function openSensorSetup($spaceId)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'ssd'])) {
            return;
        }

        $this->sensorModalSpaceId = $spaceId;
        $this->selectedSensorKey = '';
        $this->unassignedSensors = SensorAssignment::where(function ($q) {
            $q->where('status', 'unassigned')->orWhereNull('space_code');
        })->orderBy('mac_address')->orderBy('sensor_index')->get()->toArray();
        $this->showSensorModal = true;
    }

    public function pingSensor()
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'ssd'])) {
            return;
        }

        if (!$this->selectedSensorKey) return;

        [$mac, $index] = explode('|', $this->selectedSensorKey);

        $sensor = SensorAssignment::where('mac_address', $mac)
            ->where('sensor_index', $index)
            ->first();

        if (!$sensor) return;

        try {
            $sensor->startIdentify();
            session()->flash('ping_success', 'Sensor is blinking! Check the physical sensor.');
        } catch (\Exception $e) {
            session()->flash('ping_error', 'Failed to ping sensor: ' . $e->getMessage());
        }
    }

    public function pairSensor()
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'ssd'])) {
            return;
        }

        $this->validate(['selectedSensorKey' => 'required']);

        $space = ParkingSpace::find($this->sensorModalSpaceId);
        if (!$space) return;

        [$mac, $index] = explode('|', $this->selectedSensorKey);

        $sensor = SensorAssignment::where('mac_address', $mac)
            ->where('sensor_index', $index)
            ->first();

        if (!$sensor) return;

        // Unlink from any previous space
        if ($sensor->space_code) {
            ParkingSpace::where('space_code', $sensor->space_code)->update(['sensor_id' => null]);
        }

        $sensor->stopIdentify();
        $sensor->update(['space_code' => $space->space_code, 'status' => 'active']);
        $space->update([
            'is_occupied'     => false,
            'manual_override' => false,
            'malfunctioned'   => false,
        ]);

        session()->flash('success', "Sensor paired to spot {$space->space_code}.");
        $this->closeSensorModal();
        $this->loadParkingData();
    }

    public function closeSensorModal()
    {
        $this->showSensorModal = false;
        $this->sensorModalSpaceId = null;
        $this->selectedSensorKey = '';
        $this->unassignedSensors = [];
    }

    public function render()
    {
        return view('livewire.public-parking-display')
            ->layout('layouts.app');
    }
}

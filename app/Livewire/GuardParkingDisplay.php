<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingSpace;
use App\Models\GuardIncident;
use App\Models\SensorAssignment;
use App\Models\Feedback;

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

    // PIN change modal
    public $showPinChangeModal = false;
    public $currentPin = '';
    public $newPin = '';
    public $confirmPin = '';
    public $pinChangeError = '';
    public $pinChangeSuccess = '';

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
    public $openIncidents = [];
    public $showIncidentsModal = false;

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

    public function openPinChangeModal()
    {
        if (!$this->isAuthenticated) {
            return;
        }

        $this->showPinChangeModal = true;
        $this->currentPin = '';
        $this->newPin = '';
        $this->confirmPin = '';
        $this->pinChangeError = '';
        $this->pinChangeSuccess = '';
    }

    public function closePinChangeModal()
    {
        $this->showPinChangeModal = false;
        $this->currentPin = '';
        $this->newPin = '';
        $this->confirmPin = '';
        $this->pinChangeError = '';
        $this->pinChangeSuccess = '';
    }

    public function changePin()
    {
        if (!$this->isAuthenticated) {
            return;
        }

        $this->pinChangeError = '';
        $this->pinChangeSuccess = '';

        // Validate current PIN
        $correctPin = config('app.guard_pin', '1234');
        if ($this->currentPin !== $correctPin) {
            $this->pinChangeError = 'Current PIN is incorrect.';
            return;
        }

        // Validate new PIN
        if (strlen($this->newPin) < 4 || strlen($this->newPin) > 8) {
            $this->pinChangeError = 'New PIN must be 4-8 digits.';
            return;
        }

        if (!ctype_digit($this->newPin)) {
            $this->pinChangeError = 'PIN must contain only numbers.';
            return;
        }

        // Validate confirmation
        if ($this->newPin !== $this->confirmPin) {
            $this->pinChangeError = 'New PIN and confirmation do not match.';
            return;
        }

        // Update .env file
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if (strpos($envContent, 'GUARD_PIN=') !== false) {
            // Update existing GUARD_PIN
            $envContent = preg_replace('/GUARD_PIN=.*/', 'GUARD_PIN=' . $this->newPin, $envContent);
        } else {
            // Add GUARD_PIN if it doesn't exist
            $envContent .= "\nGUARD_PIN=" . $this->newPin;
        }

        file_put_contents($envPath, $envContent);

        // Clear config cache so new PIN takes effect
        \Artisan::call('config:clear');

        $this->pinChangeSuccess = 'PIN changed successfully!';
        $this->currentPin = '';
        $this->newPin = '';
        $this->confirmPin = '';

        // Close modal after short delay (handled in JS)
        session()->flash('success', 'Guard PIN has been updated successfully.');
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

    public function openIncidentsModal()
    {
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
        if (!$this->isAuthenticated) {
            return;
        }

        $incident = GuardIncident::find($incidentId);
        if ($incident) {
            $incident->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            session()->flash('success', "Issue at {$incident->space_code} has been resolved.");

            // Reload incidents
            $this->loadOpenIncidentsCount();
            $this->openIncidents = GuardIncident::where('status', 'open')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();

            // Close modal if no more incidents
            if (count($this->openIncidents) === 0) {
                $this->showIncidentsModal = false;
            }
        }
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

        // Create GuardIncident record
        GuardIncident::create([
            'space_code' => $this->selectedSpace?->space_code,
            'floor_level' => $this->selectedFloor,
            'category' => $this->incidentCategory,
            'notes' => $this->incidentNotes,
            'status' => 'open',
            'reported_by' => 'Guard',
        ]);

        // Also create Feedback entry for admin visibility
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
            'user_id' => null, // Guard report, no user login
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
                'platform' => 'Guard PWA',
                'reported_by' => 'Guard',
                'submitted_at' => now()->toISOString(),
            ],
            'status' => 'pending',
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

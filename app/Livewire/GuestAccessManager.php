<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GuestAccess;
use Carbon\Carbon;

class GuestAccessManager extends Component
{
    // Form properties
    public $name = '';
    public $vehicle_plate = '';
    public $phone = '';
    public $purpose = '';
    public $valid_hours = 24;
    public $notes = '';

    // Modal
    public $showModal = false;
    public $editingId = null;

    // Filters
    public $search = '';
    public $statusFilter = 'active';

    protected $rules = [
        'name' => 'required|string|max:100',
        'vehicle_plate' => 'required|string|max:20',
        'phone' => 'nullable|string|max:20',
        'purpose' => 'nullable|string|max:255',
        'valid_hours' => 'required|integer|min:1|max:168',
        'notes' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        // Check if user has access
        if (!$this->canManageGuests()) {
            abort(403, 'Unauthorized');
        }
    }

    public function render()
    {
        return view('livewire.guest-access-manager', [
            'guests' => $this->getGuests(),
            'stats' => $this->getStats()
        ])->layout('layouts.app');
    }

    public function openModal($guestId = null)
    {
        if ($guestId) {
            $guest = GuestAccess::find($guestId);
            if ($guest) {
                $this->editingId = $guestId;
                $this->fill([
                    'name' => $guest->name,
                    'vehicle_plate' => $guest->vehicle_plate,
                    'phone' => $guest->phone,
                    'purpose' => $guest->purpose,
                    'notes' => $guest->notes,
                ]);
                // Calculate remaining hours
                $this->valid_hours = max(1, Carbon::now()->diffInHours($guest->valid_until, false));
            }
        } else {
            $this->resetForm();
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save()
    {
        $this->validate();

        try {
            $validFrom = Carbon::now();
            $validUntil = Carbon::now()->addHours($this->valid_hours);

            if ($this->editingId) {
                $guest = GuestAccess::find($this->editingId);
                $guest->update([
                    'name' => $this->name,
                    'vehicle_plate' => strtoupper($this->vehicle_plate),
                    'phone' => $this->phone,
                    'purpose' => $this->purpose,
                    'valid_until' => $validUntil,
                    'notes' => $this->notes,
                ]);
                $message = 'Guest access updated successfully.';
            } else {
                // Generate unique guest ID
                $guestId = $this->generateGuestId();

                GuestAccess::create([
                    'guest_id' => $guestId,
                    'name' => $this->name,
                    'vehicle_plate' => strtoupper($this->vehicle_plate),
                    'phone' => $this->phone,
                    'purpose' => $this->purpose,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                    'notes' => $this->notes,
                ]);
                $message = "Guest access created. Pass ID: {$guestId}";
            }

            $this->dispatch('show-alert', type: 'success', message: $message);
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to save: ' . $e->getMessage());
        }
    }

    public function cancelAccess($guestId)
    {
        $guest = GuestAccess::find($guestId);
        if ($guest && $guest->status === 'active') {
            $guest->update(['status' => 'cancelled']);
            $this->dispatch('show-alert', type: 'success', message: 'Guest access cancelled.');
        }
    }

    public function extendAccess($guestId, $hours = 24)
    {
        $guest = GuestAccess::find($guestId);
        if ($guest) {
            $newExpiry = Carbon::parse($guest->valid_until)->addHours($hours);
            $guest->update([
                'valid_until' => $newExpiry,
                'status' => 'active'
            ]);
            $this->dispatch('show-alert', type: 'success', message: "Access extended by {$hours} hours.");
        }
    }

    public function delete($guestId)
    {
        if (!auth()->user()->canManageCars()) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        try {
            GuestAccess::destroy($guestId);
            $this->dispatch('show-alert', type: 'success', message: 'Guest record deleted.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete.');
        }
    }

    // Quick create from plate number (called from dashboard verify modal)
    public function quickCreate($plateNumber, $name = 'Guest')
    {
        $guestId = $this->generateGuestId();
        $validFrom = Carbon::now();
        $validUntil = Carbon::now()->addHours(24);

        GuestAccess::create([
            'guest_id' => $guestId,
            'name' => $name,
            'vehicle_plate' => strtoupper($plateNumber),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return $guestId;
    }

    private function generateGuestId()
    {
        $year = date('Y');
        $lastGuest = GuestAccess::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastGuest ? ((int) substr($lastGuest->guest_id, -4)) + 1 : 1;
        return "GUEST-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private function getGuests()
    {
        $query = GuestAccess::with('creator');

        // Auto-update expired guests
        GuestAccess::where('status', 'active')
            ->where('valid_until', '<', Carbon::now())
            ->update(['status' => 'expired']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$this->search}%")
                  ->orWhere('guest_id', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    private function getStats()
    {
        return [
            'total' => GuestAccess::count(),
            'active' => GuestAccess::where('status', 'active')
                ->where('valid_until', '>', Carbon::now())
                ->count(),
            'expired' => GuestAccess::where('status', 'expired')
                ->orWhere(function ($q) {
                    $q->where('status', 'active')
                      ->where('valid_until', '<', Carbon::now());
                })
                ->count(),
            'today' => GuestAccess::whereDate('created_at', Carbon::today())->count(),
        ];
    }

    private function resetForm()
    {
        $this->reset(['editingId', 'name', 'vehicle_plate', 'phone', 'purpose', 'notes']);
        $this->valid_hours = 24;
        $this->resetErrorBag();
    }

    private function canManageGuests()
    {
        return in_array(auth()->user()->role, ['admin', 'ssd', 'security']);
    }

    public function getStatusBadge($guest)
    {
        if ($guest->status === 'cancelled') {
            return ['bg-secondary', 'Cancelled'];
        }

        if ($guest->status === 'used') {
            return ['bg-info', 'Used'];
        }

        if ($guest->status === 'expired' || Carbon::parse($guest->valid_until)->isPast()) {
            return ['bg-danger', 'Expired'];
        }

        return ['bg-success', 'Active'];
    }

    public function getTimeRemaining($validUntil)
    {
        $until = Carbon::parse($validUntil);
        if ($until->isPast()) {
            return 'Expired';
        }
        return $until->diffForHumans(['parts' => 2]);
    }
}

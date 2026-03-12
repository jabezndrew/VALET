<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class OvernightParkingAlert extends Component
{
    public $showModal = false;
    public $overnightVehicles = [];
    public $overnightCount = 0;
    public $hasUnseenAlerts = false;
    public $overrideNotifications = [];
    public $unseenOverrideCount = 0;

    // Configure overnight threshold (hours parked to be considered overnight)
    public const OVERNIGHT_HOURS = 12; // 12 hours threshold

    protected $listeners = ['refreshOvernightAlerts' => 'loadOvernightVehicles'];

    public function mount()
    {
        $this->loadOvernightVehicles();
    }

    public function loadOvernightVehicles()
    {
        // Only load for security, ssd, and admin
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'ssd', 'security'])) {
            $this->overnightCount = 0;
            $this->overnightVehicles = [];
            return;
        }

        // Load override notifications
        $allOverrides = Cache::get('admin_override_notifications', []);

        if (in_array(auth()->user()->role, ['admin', 'ssd'])) {
            // Admin/SSD see only security-reported notifications (not their own)
            $filtered = array_values(array_filter($allOverrides, fn($n) =>
                ($n['reporter_role'] ?? 'security') === 'security' &&
                ($n['guard_name'] ?? '') !== auth()->user()->name
            ));
        } elseif (auth()->user()->role === 'security') {
            // Security sees only admin-reported notifications
            $filtered = array_values(array_filter($allOverrides, fn($n) =>
                ($n['reporter_role'] ?? 'security') === 'admin'
            ));
        } else {
            $filtered = [];
        }

        $seenOverrideIds = Cache::get('seen_overrides_' . auth()->id(), []);
        $this->overrideNotifications = array_reverse($filtered);
        $unseenOverrides = array_filter($filtered, fn($n) => !in_array($n['id'], $seenOverrideIds));
        $this->unseenOverrideCount = count($unseenOverrides);

        // Check if the table exists first to prevent errors
        try {
            if (!Schema::hasTable('parking_entries')) {
                $this->overnightCount = 0;
                $this->overnightVehicles = [];
                return;
            }

            $thresholdTime = Carbon::now()->subHours(self::OVERNIGHT_HOURS);

            // Get vehicles that are still parked and have been parked for more than threshold hours
            $this->overnightVehicles = ParkingEntry::where('status', 'parked')
                ->where('entry_time', '<', $thresholdTime)
                ->with(['user', 'rfidTag'])
                ->orderBy('entry_time', 'asc')
                ->get()
                ->map(function ($entry) {
                    $hoursParked = Carbon::parse($entry->entry_time)->diffInHours(Carbon::now());
                    $entry->hours_parked = $hoursParked;
                    $entry->parked_since = Carbon::parse($entry->entry_time)->format('M j, g:i A');
                    return $entry;
                })
                ->toArray();

            $this->overnightCount = count($this->overnightVehicles);

            // Check if there are unseen alerts
            $currentIds = collect($this->overnightVehicles)->pluck('id')->sort()->values()->toArray();
            $seenIds = Cache::get('overnight_seen_' . auth()->id(), []);
            $newIds = array_diff($currentIds, $seenIds);
            $this->hasUnseenAlerts = count($newIds) > 0 || $this->unseenOverrideCount > 0;
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist or other DB issues
            $this->overnightCount = 0;
            $this->overnightVehicles = [];
            $this->hasUnseenAlerts = $this->unseenOverrideCount > 0;
        }
    }

    public function openModal()
    {
        $this->loadOvernightVehicles();
        $this->showModal = true;

        // Mark all current overnight alerts as seen
        $seenIds = collect($this->overnightVehicles)->pluck('id')->sort()->values()->toArray();
        Cache::put('overnight_seen_' . auth()->id(), $seenIds, now()->addDays(7));

        // Mark all override notifications as seen
        if (!empty($this->overrideNotifications)) {
            $allOverrideIds = array_column($this->overrideNotifications, 'id');
            Cache::put('seen_overrides_' . auth()->id(), $allOverrideIds, now()->addDays(7));
            $this->unseenOverrideCount = 0;
        }

        $this->hasUnseenAlerts = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.overnight-parking-alert');
    }
}

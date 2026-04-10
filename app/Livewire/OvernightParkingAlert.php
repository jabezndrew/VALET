<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class OvernightParkingAlert extends Component
{
    public $showModal        = false;
    public $overnightVehicles = [];
    public $overnightCount   = 0;
    public $hasUnseenAlerts  = false;
    public $notifications    = [];   // unified: malfunction, override, rfid, guest, feedback
    public $unseenCount      = 0;

    public const OVERNIGHT_HOURS = 12;

    protected $listeners = ['refreshOvernightAlerts' => 'loadOvernightVehicles'];

    public function mount()
    {
        $this->loadOvernightVehicles();
    }

    public function loadOvernightVehicles()
    {
        if (!auth()->check()) return;

        $role   = auth()->user()->role;
        $userId = auth()->id();

        // ── User role: only feedback replies ─────────────────────────────────
        if ($role === 'user') {
            $all    = Cache::get("user_notifications_{$userId}", []);
            $seen   = Cache::get("user_notifications_seen_{$userId}", []);
            $unseen = array_filter($all, fn($n) => !in_array($n['id'], $seen));

            $this->notifications  = array_reverse($all);
            $this->unseenCount    = count($unseen);
            $this->hasUnseenAlerts = $this->unseenCount > 0;
            return;
        }

        // ── Staff roles ───────────────────────────────────────────────────────
        if (!in_array($role, ['security', 'ssd', 'admin'])) return;

        $all = Cache::get('admin_override_notifications', []);

        // Filter by what each role should see
        $filtered = array_values(array_filter($all, function ($n) use ($role) {
            $type = $n['type'] ?? '';
            return match ($role) {
                'admin'    => true,  // admin sees everything
                'ssd'      => in_array($type, ['malfunction_report', 'malfunction_cleared', 'rfid_alert', 'guest_request']),
                'security' => in_array($type, ['malfunction_cleared', 'rfid_alert', 'guest_request']),
                default    => false,
            };
        }));

        $seenIds = Cache::get("seen_overrides_{$userId}", []);
        $unseen  = array_filter($filtered, fn($n) => !in_array($n['id'], $seenIds));

        $this->notifications   = array_reverse($filtered);
        $this->unseenCount     = count($unseen);

        // Overnight vehicles
        $this->overnightCount    = 0;
        $this->overnightVehicles = [];

        try {
            if (Schema::hasTable('parking_entries')) {
                $threshold = Carbon::now()->subHours(self::OVERNIGHT_HOURS);

                $this->overnightVehicles = ParkingEntry::where('status', 'parked')
                    ->where('entry_time', '<', $threshold)
                    ->with(['user', 'rfidTag'])
                    ->orderBy('entry_time', 'asc')
                    ->get()
                    ->map(function ($entry) {
                        $entry->hours_parked  = Carbon::parse($entry->entry_time)->diffInHours(now());
                        $entry->parked_since  = Carbon::parse($entry->entry_time)->format('M j, g:i A');
                        return $entry;
                    })->toArray();

                $this->overnightCount = count($this->overnightVehicles);

                $seenOvernightIds = Cache::get("overnight_seen_{$userId}", []);
                $currentIds       = collect($this->overnightVehicles)->pluck('id')->sort()->values()->toArray();
                $newOvernightIds  = array_diff($currentIds, $seenOvernightIds);

                $this->unseenCount += count($newOvernightIds);
            }
        } catch (\Exception $e) {
            // silently fail
        }

        $this->hasUnseenAlerts = $this->unseenCount > 0;
    }

    public function openModal()
    {
        $this->loadOvernightVehicles();
        $this->showModal = true;

        $userId = auth()->id();
        $role   = auth()->user()->role;

        if ($role === 'user') {
            $all = Cache::get("user_notifications_{$userId}", []);
            $allIds = array_column($all, 'id');
            Cache::put("user_notifications_seen_{$userId}", $allIds, now()->addDays(7));
        } else {
            // Mark staff notifications seen
            $allIds = array_column($this->notifications, 'id');
            Cache::put("seen_overrides_{$userId}", $allIds, now()->addDays(7));

            // Mark overnight seen
            $overnightIds = collect($this->overnightVehicles)->pluck('id')->sort()->values()->toArray();
            Cache::put("overnight_seen_{$userId}", $overnightIds, now()->addDays(7));
        }

        $this->unseenCount    = 0;
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

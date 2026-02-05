<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class OvernightParkingAlert extends Component
{
    public $showModal = false;
    public $overnightVehicles = [];
    public $overnightCount = 0;

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
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist or other DB issues
            $this->overnightCount = 0;
            $this->overnightVehicles = [];
        }
    }

    public function openModal()
    {
        $this->loadOvernightVehicles();
        $this->showModal = true;
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

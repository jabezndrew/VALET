<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RfidScanMonitor extends Component
{
    public $isEnabled = false;
    public $lastScan = null;
    public $countdown = 0;
    public $lastScanId = null; // Track last processed scan to avoid duplicates

    public function mount()
    {
        // Load saved preference from session
        $this->isEnabled = session('rfid_monitor_enabled', false);
    }

    public function toggle()
    {
        $this->isEnabled = !$this->isEnabled;
        session(['rfid_monitor_enabled' => $this->isEnabled]);

        if (!$this->isEnabled) {
            $this->reset(['lastScan', 'countdown', 'lastScanId']);
        }
    }

    public function checkForNewScans()
    {
        if (!$this->isEnabled) {
            return;
        }

        $scanData = Cache::get('rfid_scan_latest');

        if ($scanData) {
            // Create unique ID from scan data to detect new scans
            $scanId = md5(json_encode($scanData));

            // Only show if it's a new scan
            if ($scanId !== $this->lastScanId) {
                $this->lastScanId = $scanId;

                $this->lastScan = [
                    'uid' => $scanData['uid'] ?? 'Unknown',
                    'valid' => $scanData['valid'] ?? false,
                    'message' => $scanData['message'] ?? '',
                    'user_name' => $scanData['user_name'] ?? 'N/A',
                    'vehicle_plate' => $scanData['vehicle_plate'] ?? 'N/A',
                    'time' => Carbon::now()->format('h:i:s A'),
                    'duration' => $scanData['duration'] ?? 7
                ];

                $this->countdown = $this->lastScan['duration'];
                $this->dispatch('start-countdown', $this->countdown);
            }
        }
    }

    public function closeModal()
    {
        $this->reset(['lastScan', 'countdown']);
    }

    public function canViewMonitor()
    {
        $allowedRoles = ['admin', 'security', 'ssd'];
        return in_array(auth()->user()->role, $allowedRoles);
    }

    public function render()
    {
        if (!$this->canViewMonitor()) {
            return view('livewire.rfid-scan-monitor', ['canView' => false]);
        }

        return view('livewire.rfid-scan-monitor', ['canView' => true]);
    }
}

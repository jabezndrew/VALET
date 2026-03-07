<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Tools extends Component
{
    public $showClearLogsModal = false;
    public $clearLogsPassword = '';
    public $clearLogsError = '';

    public function openClearLogsModal()
    {
        if (auth()->user()->role !== 'admin') {
            return;
        }
        $this->clearLogsPassword = '';
        $this->clearLogsError = '';
        $this->showClearLogsModal = true;
    }

    public function closeClearLogsModal()
    {
        $this->showClearLogsModal = false;
        $this->clearLogsPassword = '';
        $this->clearLogsError = '';
    }

    public function clearParkingLogs()
    {
        if (auth()->user()->role !== 'admin') {
            return;
        }

        if ($this->clearLogsPassword !== 'secret') {
            $this->clearLogsError = 'Incorrect password.';
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('parking_entries')->truncate();
        DB::table('rfid_scan_logs')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Cache::forget('rfid_scan_latest');

        $this->closeClearLogsModal();
        session()->flash('success', 'All parking logs have been cleared.');
    }

    public function render()
    {
        return view('livewire.tools')->layout('layouts.app');
    }
}

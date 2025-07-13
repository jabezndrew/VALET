<?php

namespace App\Livewire;

use Livewire\Component;

class ParkingDashboard extends Component
{
    public $message = "✅ Livewire is working in Laravel 11+!";
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.parking-dashboard');
    }
}
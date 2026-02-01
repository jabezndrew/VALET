<?php

namespace App\Livewire;

use Livewire\Component;

class Tools extends Component
{
    public function mount()
    {
        // Tools page accessible by all authenticated users
    }
    public function render()
    {
        return view('livewire.tools')->layout('layouts.app');
    }
}

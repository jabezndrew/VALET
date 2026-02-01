<?php

namespace App\Livewire;

use Livewire\Component;

class Tools extends Component
{
    public function mount()
    {
        if (auth()->user()->role !== 'admin'){
            abort(403, 'Unauthorized');
        }
    }
    public function render()
    {
        return view('livewire.tools')->layout('layouts.app');
    }
}

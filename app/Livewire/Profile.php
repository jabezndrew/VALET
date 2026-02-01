<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Component
{
    public $name = '';
    public $email = '';
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $user->update([
            'name' => $this->name,
        ]);

        $this->dispatch('show-alert', type: 'success', message: 'Profile updated successfully!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('show-alert', type: 'success', message: 'Password changed successfully!');
    }

    public function render()
    {
        return view('livewire.profile')->layout('layouts.app');
    }
}

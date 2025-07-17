<?php
// app/Livewire/Auth/Login.php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\SysUser;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public $error = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = SysUser::where('email', $this->email)->first();
        
        if (!$user) {
            $this->error = 'Invalid credentials provided.';
            return;
        }

        if (!$user->is_active) {
            $this->error = 'Your account has been deactivated. Please contact administrator.';
            return;
        }

        if (!Hash::check($this->password, $user->password)) {
            $this->error = 'Invalid credentials provided.';
            return;
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        $message = match($user->role) {
            'admin' => 'Welcome back, Administrator!',
            'ssd' => 'Welcome back, SSD Personnel!',
            'security' => 'Welcome back, Security!',
            'user' => 'Welcome to VALET Parking!',
            default => 'Welcome!'
        };

        session()->flash('success', $message);
        $this->redirect('/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.base');
    }
}
<?php
// app/Livewire/Auth/Register.php
namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\SysUser;

class Register extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = '';
    public $employee_id = '';
    public $department = '';

    public function register()
    {
        $userCount = SysUser::count();
        if ($userCount > 0 && (!auth()->check() || !auth()->user()->isAdmin())) {
            abort(403, 'Only administrators can register new users.');
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:sys_users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,security,ssd,admin',
            'employee_id' => 'nullable|string|max:50|unique:sys_users',
            'department' => 'nullable|string|max:100',
        ]);

        $user = SysUser::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'employee_id' => $this->employee_id,
            'department' => $this->department,
            'is_active' => true,
        ]);

        if ($userCount == 0) {
            Auth::login($user);
            session()->flash('success', 'Welcome! First admin account created.');
            $this->redirect('/dashboard', navigate: true);
        } else {
            session()->flash('success', 'User created successfully.');
            $this->redirect('/admin/users', navigate: true);
        }
    }

    public function render()
    {
        $userCount = SysUser::count();
        if ($userCount > 0 && (!auth()->check() || !auth()->user()->isAdmin())) {
            abort(403, 'Only administrators can register new users.');
        }
        
        return view('livewire.auth.register')->layout('layouts.guest');
    }
}
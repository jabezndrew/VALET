<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\SysUser;

class SysUserController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Check if user exists and is active
        $user = SysUser::where('email', $credentials['email'])->first();
        
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials provided.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact administrator.'],
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials provided.'],
            ]);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Role-based welcome message
        $message = match($user->role) {
            'admin' => 'Welcome back, Administrator!',
            'ssd' => 'Welcome back, SSD Personnel!',
            'security' => 'Welcome back, Security!',
            'user' => 'Welcome to VALET Parking!',
            default => 'Welcome!'
        };

        return redirect()->route('dashboard')->with('success', $message);
    }

    public function showRegister()
{
    // Allow registration if no users exist, or if user is admin
    $userCount = SysUser::count();
    if ($userCount == 0 || (auth()->check() && auth()->user()->isAdmin())) {
        return view('auth.register');
    }
    
    abort(403, 'Only administrators can register new users.');
}

public function register(Request $request)
{
    // Same check as above
    $userCount = SysUser::count();
    if ($userCount > 0 && (!auth()->check() || !auth()->user()->isAdmin())) {
        abort(403, 'Only administrators can register new users.');
    }

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:sys_users',
        'password' => 'required|string|min:8|confirmed',
        'role' => 'required|in:user,security,ssd,admin',
        'employee_id' => 'nullable|string|max:50|unique:sys_users',
        'department' => 'nullable|string|max:100',
    ]);

    $user = SysUser::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
        'employee_id' => $request->employee_id,
        'department' => $request->department,
        'is_active' => true,
    ]);

    // If this is the first user, log them in
    if ($userCount == 0) {
        Auth::login($user);
        return redirect()->route('dashboard')->with('success', 'Welcome! First admin account created.');
    }

    return redirect()->route('admin.users')->with('success', 'User created successfully.');
}

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
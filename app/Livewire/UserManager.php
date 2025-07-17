<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SysUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManager extends Component
{
    //protected $layout = 'layouts.app';  

    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role = 'user';
    public $employee_id = '';
    public $department = '';
    public $is_active = true;
    
    // Edit mode
    public $editingId = null;
    public $showModal = false;
    public $isPasswordRequired = true;
    
    // Filters
    public $search = '';
    public $roleFilter = 'all';
    public $statusFilter = 'all';

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:sys_users,email,' . $this->editingId,
            'role' => 'required|in:user,security,ssd,admin',
            'employee_id' => 'nullable|string|max:255|unique:sys_users,employee_id,' . $this->editingId,
            'department' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];

        if ($this->isPasswordRequired) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        } else {
            $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    public function mount()
    {
        if (!auth()->user()->canManageUsers()) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function render()
    {
        $users = $this->getUsers();
        $stats = $this->getUserStats();
        
        return view('livewire.user-manager', [
            'users' => $users,
            'stats' => $stats
        ]);
    }

    public function openModal($userId = null)
    {
        if ($userId) {
            $user = SysUser::find($userId);
            if ($user) {
                $this->editingId = $userId;
                $this->name = $user->name;
                $this->email = $user->email;
                $this->role = $user->role;
                $this->employee_id = $user->employee_id;
                $this->department = $user->department;
                $this->is_active = $user->is_active;
                $this->isPasswordRequired = false;
                $this->password = '';
                $this->password_confirmation = '';
            }
        } else {
            $this->resetForm();
            $this->isPasswordRequired = true;
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'employee_id' => $this->employee_id,
                'department' => $this->department,
                'is_active' => $this->is_active,
            ];

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            if ($this->editingId) {
                $user = SysUser::find($this->editingId);
                
                // Prevent deactivating the last admin
                if ($user->isAdmin() && !$this->is_active) {
                    $activeAdmins = SysUser::where('role', 'admin')
                        ->where('is_active', true)
                        ->where('id', '!=', $this->editingId)
                        ->count();
                    
                    if ($activeAdmins == 0) {
                        session()->flash('error', 'Cannot deactivate the last active admin.');
                        return;
                    }
                }

                $user->update($data);
                
                // Revoke tokens if deactivating
                if (!$this->is_active) {
                    $user->tokens()->delete();
                }
                
                session()->flash('success', 'User updated successfully.');
            } else {
                SysUser::create($data);
                session()->flash('success', 'User created successfully.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save user: ' . $e->getMessage());
        }
    }

    public function toggleStatus($userId)
    {
        $user = SysUser::find($userId);
        if (!$user) return;

        // Prevent deactivating the last admin
        if ($user->isAdmin() && $user->is_active) {
            $activeAdmins = SysUser::where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $userId)
                ->count();
            
            if ($activeAdmins == 0) {
                session()->flash('error', 'Cannot deactivate the last active admin.');
                return;
            }
        }

        $user->is_active = !$user->is_active;
        $user->save();

        // Revoke tokens if deactivating
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        $status = $user->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "User {$status} successfully.");
    }

    public function delete($userId)
    {
        $user = SysUser::find($userId);
        if (!$user) return;

        // Prevent deleting the last admin
        if ($user->isAdmin()) {
            $adminCount = SysUser::where('role', 'admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                session()->flash('error', 'Cannot delete the last active admin.');
                return;
            }
        }

        try {
            // Revoke all tokens
            $user->tokens()->delete();
            
            // Delete user
            $user->delete();
            
            session()->flash('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete user.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = 'user';
        $this->employee_id = '';
        $this->department = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    private function getUsers()
    {
        $query = SysUser::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('employee_id', 'like', "%{$this->search}%");
            });
        }

        if ($this->roleFilter !== 'all') {
            $query->where('role', $this->roleFilter);
        }

        if ($this->statusFilter !== 'all') {
            $active = $this->statusFilter === 'active';
            $query->where('is_active', $active);
        }

        return $query->latest()->get();
    }

    private function getUserStats()
    {
        return [
            'total' => SysUser::count(),
            'active' => SysUser::where('is_active', true)->count(),
            'inactive' => SysUser::where('is_active', false)->count(),
            'by_role' => [
                'admin' => SysUser::where('role', 'admin')->count(),
                'ssd' => SysUser::where('role', 'ssd')->count(),
                'security' => SysUser::where('role', 'security')->count(),
                'user' => SysUser::where('role', 'user')->count(),
            ],
        ];
    }
}
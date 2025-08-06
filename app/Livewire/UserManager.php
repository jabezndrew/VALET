<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SysUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class UserManager extends Component
{
    // Form properties
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
    
    // Filters
    public $search = '';
    public $roleFilter = 'all';
    public $statusFilter = 'all';

    protected function rules()
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:sys_users,email,' . $this->editingId,
            'role' => 'required|in:user,security,ssd,admin',
            'employee_id' => 'nullable|string|max:255|unique:sys_users,employee_id,' . $this->editingId,
            'department' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];

        $passwordRule = $this->editingId 
            ? ['nullable', 'confirmed', Password::defaults()]
            : ['required', 'confirmed', Password::defaults()];
            
        return array_merge($baseRules, ['password' => $passwordRule]);
    }

    public function mount()
    {
        if (!auth()->user()->canManageUsers()) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function render()
    {
        return view('livewire.user-manager', [
            'users' => $this->getUsers(),
            'stats' => $this->getUserStats(),
            'pendingCount' => $this->getPendingAccountsCount()
        ])->layout('layouts.app');
    }

    public function openModal($userId = null)
    {
        if ($userId) {
            $user = SysUser::find($userId);
            if ($user) {
                $this->editingId = $userId;
                $this->fill([
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'employee_id' => $user->employee_id ?? '',
                    'department' => $user->department ?? '',
                    'is_active' => $user->is_active,
                ]);
                $this->reset(['password', 'password_confirmation']);
            }
        } else {
            $this->resetForm();
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
            // Check admin protection before making changes
            if ($this->editingId && !$this->is_active) {
                $user = SysUser::find($this->editingId);
                if ($user->isAdmin() && !$this->canDeactivateAdmin($this->editingId)) {
                    $this->dispatch('show-alert', type: 'error', message: 'Cannot deactivate the last active admin.');
                    return;
                }
            }

            $data = $this->getUserData();

            if ($this->editingId) {
                $this->updateUser($data);
            } else {
                $this->createUser($data);
            }

            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to save user: ' . $e->getMessage());
        }
    }

    public function toggleStatus($userId)
    {
        $user = SysUser::find($userId);
        if (!$user) return;

        // Check admin protection
        if ($user->isAdmin() && $user->is_active && !$this->canDeactivateAdmin($userId)) {
            $this->dispatch('show-alert', type: 'error', message: 'Cannot deactivate the last active admin.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);

        // Revoke tokens on deactivation
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        $status = $user->is_active ? 'activated' : 'deactivated';
        $this->dispatch('show-alert', type: 'success', message: "User {$status} successfully.");
    }

    public function delete($userId)
    {
        $user = SysUser::find($userId);
        if (!$user) return;

        // Check admin protection
        if ($user->isAdmin() && !$this->canDeleteAdmin()) {
            $this->dispatch('show-alert', type: 'error', message: 'Cannot delete the last active admin.');
            return;
        }

        try {
            $user->tokens()->delete();
            $user->delete();
            
            $this->dispatch('show-alert', type: 'success', message: 'User deleted successfully.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete user.');
        }
    }

    // Helper methods
    private function getUserData()
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'employee_id' => $this->employee_id ?: null,
            'department' => $this->department ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        return $data;
    }

    private function updateUser($data)
    {
        $user = SysUser::find($this->editingId);
        $user->update($data);

        // Revoke tokens if deactivating
        if (!$this->is_active) {
            $user->tokens()->delete();
        }

        $this->dispatch('show-alert', type: 'success', message: 'User updated successfully.');
    }

    private function createUser($data)
    {
        if (auth()->user()->isAdmin()) {
            SysUser::create($data);
            $this->dispatch('show-alert', type: 'success', message: 'User created successfully.');
        } else {
            // SSD users create pending accounts
            DB::table('pending_accounts')->insert(array_merge($data, [
                'created_by' => auth()->id(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $this->dispatch('show-alert', type: 'success', message: 'Account created and sent for admin approval.');
        }
    }

    private function canDeactivateAdmin($userId)
    {
        return SysUser::byRole('admin')->active()
            ->where('id', '!=', $userId)
            ->exists();
    }

    private function canDeleteAdmin()
    {
        return SysUser::byRole('admin')->active()->count() > 1;
    }

    private function resetForm()
    {
        $this->reset([
            'editingId', 'name', 'email', 'password', 'password_confirmation',
            'employee_id', 'department'
        ]);
        $this->role = 'user';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    private function getUsers()
    {
        $query = SysUser::query();

        // Apply filters
        if ($this->search) {
            $query->where(function ($q) {
                $searchTerms = ['name', 'email', 'employee_id'];
                foreach ($searchTerms as $term) {
                    $q->orWhere($term, 'like', "%{$this->search}%");
                }
            });
        }

        if ($this->roleFilter !== 'all') {
            $query->byRole($this->roleFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        return $query->latest()->get();
    }

    private function getUserStats()
    {
        return [
            'total' => SysUser::count(),
            'active' => SysUser::active()->count(),
            'inactive' => SysUser::inactive()->count(),
            'by_role' => [
                'admin' => SysUser::byRole('admin')->count(),
                'ssd' => SysUser::byRole('ssd')->count(),
                'security' => SysUser::byRole('security')->count(),
                'user' => SysUser::byRole('user')->count(),
            ],
        ];
    }

    private function getPendingAccountsCount()
    {
        return auth()->user()->canApprovePendingAccounts() 
            ? DB::table('pending_accounts')->where('status', 'pending')->count() 
            : 0;
    }
}
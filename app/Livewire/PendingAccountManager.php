<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\SysUser;
use Illuminate\Support\Facades\Validator;

class PendingAccountManager extends Component
{
    public $selectedAccount = null;
    public $showModal = false;
    public $adminNotes = '';
    public $statusFilter = 'pending';

    protected $rules = [
        'adminNotes' => 'nullable|string|max:1000',
        'statusFilter' => 'in:all,pending,approved,rejected'
    ];

    public function mount()
    {
        if (!auth()->user()->canApprovePendingAccounts()) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function render()
    {
        return view('livewire.pending-account-manager', [
            'pendingAccounts' => $this->getPendingAccounts(),
            'stats' => $this->getPendingStats()
        ])->layout('layouts.app');
    }

    public function viewAccount($accountId)
    {
        $this->selectedAccount = $this->getPendingAccountDetails($accountId);
        
        if (!$this->selectedAccount) {
            $this->dispatch('show-alert', type: 'error', message: 'Account not found.');
            return;
        }
        
        $this->adminNotes = $this->selectedAccount->admin_notes ?? '';
        $this->showModal = true;
    }

    public function approveAccount()
    {
        $this->validate(['adminNotes' => 'nullable|string|max:1000']);
        
        if (!$this->validateAccountAction()) return;

        try {
            DB::transaction(function () {
                // Create user account
                SysUser::create([
                    'name' => $this->selectedAccount->name,
                    'email' => $this->selectedAccount->email,
                    'password' => $this->selectedAccount->password, // Already hashed
                    'role' => $this->selectedAccount->role,
                    'employee_id' => $this->selectedAccount->employee_id,
                    'department' => $this->selectedAccount->department,
                    'is_active' => $this->selectedAccount->is_active ?? true,
                ]);

                // Update pending account
                $this->updatePendingAccountStatus('approved');
            });

            $this->closeModal();
            $this->dispatch('show-alert', type: 'success', message: 'Account approved and user created successfully.');

        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to approve account: ' . $e->getMessage());
        }
    }

    public function rejectAccount()
    {
        $this->validate(['adminNotes' => 'nullable|string|max:1000']);
        
        if (!$this->validateAccountAction()) return;

        try {
            $this->updatePendingAccountStatus('rejected');
            $this->closeModal();
            $this->dispatch('show-alert', type: 'success', message: 'Account rejected successfully.');

        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to reject account: ' . $e->getMessage());
        }
    }

    public function deleteAccount($accountId)
    {
        if (!$accountId) {
            $this->dispatch('show-alert', type: 'error', message: 'Invalid account ID.');
            return;
        }

        try {
            $deleted = DB::table('pending_accounts')->where('id', $accountId)->delete();
            
            if ($deleted) {
                $this->dispatch('show-alert', type: 'success', message: 'Pending account deleted successfully.');
            } else {
                $this->dispatch('show-alert', type: 'error', message: 'Account not found or already deleted.');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete account.');
        }
    }

    public function closeModal()
    {
        $this->reset(['showModal', 'selectedAccount', 'adminNotes']);
    }

    // Helper methods
    private function validateAccountAction()
    {
        if (!$this->selectedAccount) {
            $this->dispatch('show-alert', type: 'error', message: 'No account selected.');
            return false;
        }

        if ($this->selectedAccount->status !== 'pending') {
            $this->dispatch('show-alert', type: 'error', message: 'Account has already been processed.');
            return false;
        }

        // Check for email conflicts when approving
        if (request()->routeIs('*approve*') || str_contains(debug_backtrace()[1]['function'], 'approve')) {
            $existingUser = SysUser::where('email', $this->selectedAccount->email)->first();
            if ($existingUser) {
                $this->dispatch('show-alert', type: 'error', message: 'A user with this email already exists.');
                return false;
            }
        }

        return true;
    }

    private function updatePendingAccountStatus($status)
    {
        DB::table('pending_accounts')
            ->where('id', $this->selectedAccount->id)
            ->update([
                'status' => $status,
                'admin_notes' => $this->adminNotes ?: null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function getPendingAccountDetails($accountId)
    {
        return DB::table('pending_accounts')
            ->leftJoin('sys_users as creator', 'pending_accounts.created_by', '=', 'creator.id')
            ->leftJoin('sys_users as reviewer', 'pending_accounts.reviewed_by', '=', 'reviewer.id')
            ->select([
                'pending_accounts.*',
                'creator.name as created_by_name',
                'creator.role as created_by_role',
                'reviewer.name as reviewed_by_name'
            ])
            ->where('pending_accounts.id', $accountId)
            ->first();
    }

    private function getPendingAccounts()
    {
        $query = DB::table('pending_accounts')
            ->leftJoin('sys_users as creator', 'pending_accounts.created_by', '=', 'creator.id')
            ->leftJoin('sys_users as reviewer', 'pending_accounts.reviewed_by', '=', 'reviewer.id')
            ->select([
                'pending_accounts.*',
                'creator.name as created_by_name',
                'creator.role as created_by_role',
                'reviewer.name as reviewed_by_name'
            ]);

        if ($this->statusFilter !== 'all') {
            $query->where('pending_accounts.status', $this->statusFilter);
        }

        return $query->orderBy('pending_accounts.created_at', 'desc')->get();
    }

    private function getPendingStats()
    {
        $stats = DB::table('pending_accounts')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
            ")
            ->first();

        return [
            'total' => $stats->total,
            'pending' => $stats->pending,
            'approved' => $stats->approved,
            'rejected' => $stats->rejected,
        ];
    }

    public function getStatusBadgeClass($status)
    {
        return match($status) {
            'pending' => 'badge bg-warning text-dark',
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
            default => 'badge bg-secondary'
        };
    }

    public function getStatusIcon($status)
    {
        return match($status) {
            'pending' => 'fas fa-clock',
            'approved' => 'fas fa-check-circle',
            'rejected' => 'fas fa-times-circle',
            default => 'fas fa-question-circle'
        };
    }

    public function getRoleDisplayName($role)
    {
        return match($role) {
            'admin' => 'Administrator',
            'ssd' => 'SSD Personnel', 
            'security' => 'Security Personnel',
            'user' => 'User',
            default => ucfirst($role)
        };
    }
}
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SysUser;
use App\Models\PendingAccount;
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

    // FIXED: Accept accountId parameter and fetch fresh data
    public function approveAccount($accountId = null)
    {
        // If no ID passed, use the selected account from modal
        $pendingAccount = $accountId
            ? PendingAccount::find($accountId)
            : PendingAccount::find($this->selectedAccount->id);

        $this->validate(['adminNotes' => 'nullable|string|max:1000']);

        if (!$pendingAccount || $pendingAccount->status !== 'pending') {
            $this->dispatch('show-alert', type: 'error', message: 'Account not found or already processed.');
            return;
        }

        // Check for email conflicts
        if (SysUser::where('email', $pendingAccount->email)->exists()) {
            $this->dispatch('show-alert', type: 'error', message: 'A user with this email already exists.');
            return;
        }

        try {
            // Use model's built-in approve method (has transaction built-in!)
            $pendingAccount->approve(auth()->id(), $this->adminNotes);

            $this->closeModal();
            $this->dispatch('show-alert', type: 'success', message: 'Account approved and user created successfully.');

        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to approve account: ' . $e->getMessage());
        }
    }

    // FIXED: Accept accountId parameter and fetch fresh data
    public function rejectAccount($accountId = null)
    {
        // If no ID passed, use the selected account from modal
        $pendingAccount = $accountId
            ? PendingAccount::find($accountId)
            : PendingAccount::find($this->selectedAccount->id);

        $this->validate(['adminNotes' => 'nullable|string|max:1000']);

        if (!$pendingAccount || $pendingAccount->status !== 'pending') {
            $this->dispatch('show-alert', type: 'error', message: 'Account not found or already processed.');
            return;
        }

        try {
            // Use model's built-in reject method
            $pendingAccount->reject(auth()->id(), $this->adminNotes);

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
            $account = PendingAccount::find($accountId);

            if ($account) {
                $account->delete();
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
    private function getPendingAccountDetails($accountId)
    {
        $account = PendingAccount::with(['creator:id,name,role', 'reviewer:id,name'])
            ->find($accountId);

        if (!$account) {
            return null;
        }

        // Convert to object with flattened data for backward compatibility
        $accountArray = $account->toArray();
        $accountArray['created_by_name'] = $account->creator->name ?? null;
        $accountArray['created_by_role'] = $account->creator->role ?? null;
        $accountArray['reviewed_by_name'] = $account->reviewer->name ?? null;

        return (object) $accountArray;
    }

    private function getPendingAccounts()
    {
        $query = PendingAccount::with(['creator:id,name,role', 'reviewer:id,name']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($account) {
            // Add flattened data for backward compatibility with views
            $accountArray = $account->toArray();
            $accountArray['created_by_name'] = $account->creator->name ?? null;
            $accountArray['created_by_role'] = $account->creator->role ?? null;
            $accountArray['reviewed_by_name'] = $account->reviewer->name ?? null;
            return (object) $accountArray;
        });
    }

    private function getPendingStats()
    {
        $stats = PendingAccount::selectRaw("
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
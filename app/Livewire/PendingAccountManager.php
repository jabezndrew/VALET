<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\SysUser;
use Illuminate\Support\Facades\Hash;

class PendingAccountManager extends Component
{
    public $selectedAccount = null;
    public $showModal = false;
    public $adminNotes = '';
    public $statusFilter = 'pending';

    public function mount()
    {
        if (!auth()->user()->canApprovePendingAccounts()) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function render()
    {
        $pendingAccounts = $this->getPendingAccounts();
        $stats = $this->getPendingStats();
        
        return view('livewire.pending-account-manager', [
            'pendingAccounts' => $pendingAccounts,
            'stats' => $stats
        ])->layout('layouts.app');
    }

    public function viewAccount($accountId)
    {
        $this->selectedAccount = DB::table('pending_accounts')
            ->leftJoin('sys_users as creator', 'pending_accounts.created_by', '=', 'creator.id')
            ->leftJoin('sys_users as reviewer', 'pending_accounts.reviewed_by', '=', 'reviewer.id')
            ->select(
                'pending_accounts.*',
                'creator.name as created_by_name',
                'creator.role as created_by_role',
                'reviewer.name as reviewed_by_name'
            )
            ->where('pending_accounts.id', $accountId)
            ->first();
            
        $this->adminNotes = $this->selectedAccount->admin_notes ?? '';
        $this->showModal = true;
    }

    public function approveAccount()
    {
        if (!$this->selectedAccount || $this->selectedAccount->status !== 'pending') {
            $this->dispatch('show-alert', type: 'error', message: 'Invalid account or already processed.');
            return;
        }

        try {
            DB::beginTransaction();

            // Create the user in sys_users table
            SysUser::create([
                'name' => $this->selectedAccount->name,
                'email' => $this->selectedAccount->email,
                'password' => $this->selectedAccount->password, // Already hashed
                'role' => $this->selectedAccount->role,
                'employee_id' => $this->selectedAccount->employee_id,
                'department' => $this->selectedAccount->department,
                'is_active' => $this->selectedAccount->is_active,
            ]);

            // Update pending account status
            DB::table('pending_accounts')
                ->where('id', $this->selectedAccount->id)
                ->update([
                    'status' => 'approved',
                    'admin_notes' => $this->adminNotes,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            $this->closeModal();
            $this->dispatch('show-alert', type: 'success', message: 'Account approved and user created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-alert', type: 'error', message: 'Failed to approve account: ' . $e->getMessage());
        }
    }

    public function rejectAccount()
    {
        if (!$this->selectedAccount || $this->selectedAccount->status !== 'pending') {
            $this->dispatch('show-alert', type: 'error', message: 'Invalid account or already processed.');
            return;
        }

        try {
            DB::table('pending_accounts')
                ->where('id', $this->selectedAccount->id)
                ->update([
                    'status' => 'rejected',
                    'admin_notes' => $this->adminNotes,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->closeModal();
            $this->dispatch('show-alert', type: 'success', message: 'Account rejected successfully.');

        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to reject account: ' . $e->getMessage());
        }
    }

    public function deleteAccount($accountId)
    {
        try {
            DB::table('pending_accounts')->where('id', $accountId)->delete();
            $this->dispatch('show-alert', type: 'success', message: 'Pending account deleted successfully.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete account.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedAccount = null;
        $this->adminNotes = '';
    }

    private function getPendingAccounts()
    {
        $query = DB::table('pending_accounts')
            ->leftJoin('sys_users as creator', 'pending_accounts.created_by', '=', 'creator.id')
            ->leftJoin('sys_users as reviewer', 'pending_accounts.reviewed_by', '=', 'reviewer.id')
            ->select(
                'pending_accounts.*',
                'creator.name as created_by_name',
                'creator.role as created_by_role',
                'reviewer.name as reviewed_by_name'
            );

        if ($this->statusFilter !== 'all') {
            $query->where('pending_accounts.status', $this->statusFilter);
        }

        return $query->orderBy('pending_accounts.created_at', 'desc')->get();
    }

    private function getPendingStats()
    {
        return [
            'total' => DB::table('pending_accounts')->count(),
            'pending' => DB::table('pending_accounts')->where('status', 'pending')->count(),
            'approved' => DB::table('pending_accounts')->where('status', 'approved')->count(),
            'rejected' => DB::table('pending_accounts')->where('status', 'rejected')->count(),
        ];
    }
}
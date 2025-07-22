<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class FeedbackManager extends Component
{
    // Form properties
    public $type = '';
    public $subject = '';
    public $message = '';
    public $parking_location = '';
    
    // Filter properties
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    
    // Submit feedback modal (for non-admin users)
    public $showModal = false;
    
    // Admin response modal
    public $showResponseModal = false;
    public $selectedFeedbackId = null;
    public $adminResponse = '';
    public $newStatus = '';

    protected $rules = [
        'type' => 'required|in:bug,suggestion,complaint,compliment,general',
        'subject' => 'required|string|max:255',
        'message' => 'required|string|max:2000',
        'parking_location' => 'nullable|string|max:100',
    ];

    public function mount()
    {
        $this->ensureFeedbackTableExists();
    }

    public function render()
    {
        $feedbacks = $this->getFeedbacks();
        $stats = $this->getFeedbackStats();
        
        return view('livewire.feedback-manager', [
            'feedbacks' => $feedbacks,
            'stats' => $stats
        ])->layout('layouts.app');
    }

    // Modal methods for submit feedback
    public function openModal()
    {
        if (!auth()->user()->canManageUsers()) {
            $this->resetForm();
            $this->showModal = true;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->type = '';
        $this->subject = '';
        $this->message = '';
        $this->parking_location = '';
        $this->resetErrorBag();
    }

    public function submitFeedback()
    {
        // Prevent admins from submitting feedback
        if (auth()->user()->canManageUsers()) {
            $this->dispatch('show-alert', type: 'error', message: 'Administrators cannot submit feedback.');
            return;
        }

        $this->validate();

        DB::table('feedbacks')->insert([
            'user_id' => auth()->id(),
            'type' => $this->type,
            'subject' => $this->subject,
            'message' => $this->message,
            'parking_location' => $this->parking_location,
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->closeModal();
        $this->dispatch('show-alert', type: 'success', message: 'Thank you for your feedback! We appreciate your input.');
    }

    public function quickUpdateStatus($feedbackId, $status)
    {
        if (!auth()->user()->canManageUsers()) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        DB::table('feedbacks')
            ->where('id', $feedbackId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

        $this->dispatch('show-alert', type: 'success', message: 'Feedback status updated successfully.');
    }

    public function openResponseModal($feedbackId)
    {
        if (!auth()->user()->canManageUsers()) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $this->selectedFeedbackId = $feedbackId;
        $feedback = DB::table('feedbacks')->where('id', $feedbackId)->first();
        
        if ($feedback) {
            $this->adminResponse = $feedback->admin_response ?? '';
            $this->newStatus = $feedback->status;
            $this->showResponseModal = true;
        }
    }

    public function closeResponseModal()
    {
        $this->showResponseModal = false;
        $this->selectedFeedbackId = null;
        $this->adminResponse = '';
        $this->newStatus = '';
    }

    public function saveAdminResponse()
    {
        if (!auth()->user()->canManageUsers() || !$this->selectedFeedbackId) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $this->validate([
            'newStatus' => 'required|in:new,in_progress,resolved,closed',
            'adminResponse' => 'nullable|string|max:1000',
        ]);

        DB::table('feedbacks')
            ->where('id', $this->selectedFeedbackId)
            ->update([
                'status' => $this->newStatus,
                'admin_response' => $this->adminResponse,
                'updated_at' => now(),
            ]);

        $this->closeResponseModal();
        $this->dispatch('show-alert', type: 'success', message: 'Feedback updated successfully.');
    }

    private function getFeedbacks()
    {
        $query = DB::table('feedbacks')
            ->leftJoin('sys_users', 'feedbacks.user_id', '=', 'sys_users.id')
            ->select(
                'feedbacks.*',
                'sys_users.name as user_name',
                'sys_users.role as user_role'
            );

        // Non-admin users can only see their own feedback
        if (!auth()->user()->canManageUsers()) {
            $query->where('feedbacks.user_id', auth()->id());
        }

        // Apply filters (admin only)
        if (auth()->user()->canManageUsers()) {
            if ($this->statusFilter !== 'all') {
                $query->where('feedbacks.status', $this->statusFilter);
            }

            if ($this->typeFilter !== 'all') {
                $query->where('feedbacks.type', $this->typeFilter);
            }
        }

        return $query->orderBy('feedbacks.created_at', 'desc')->get();
    }

    private function getFeedbackStats()
    {
        $baseQuery = DB::table('feedbacks');
        
        // Non-admin users see only their own stats
        if (!auth()->user()->canManageUsers()) {
            $baseQuery->where('user_id', auth()->id());
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'new' => (clone $baseQuery)->where('status', 'new')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            'by_type' => (clone $baseQuery)
                ->select('type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    private function ensureFeedbackTableExists(): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS feedbacks (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type ENUM('bug', 'suggestion', 'complaint', 'compliment', 'general') NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            parking_location VARCHAR(100) NULL,
            status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
            admin_response TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_type (type),
            FOREIGN KEY (user_id) REFERENCES sys_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    }
}
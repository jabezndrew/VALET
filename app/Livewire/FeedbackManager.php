<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class FeedbackManager extends Component
{
    protected $layout = 'layouts.app';
    // Form properties
    public $type = '';
    public $subject = '';
    public $message = '';
    public $parking_location = '';
    
    // Filter properties
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    
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
        ]);
    }

    public function submitFeedback()
    {
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

        // Reset form
        $this->reset(['type', 'subject', 'message', 'parking_location']);
        
        session()->flash('success', 'Thank you for your feedback! We appreciate your input.');
    }

    public function quickUpdateStatus($feedbackId, $status)
    {
        if (!auth()->user()->canManageUsers()) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        DB::table('feedbacks')
            ->where('id', $feedbackId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

        session()->flash('success', 'Feedback status updated successfully.');
    }

    public function openResponseModal($feedbackId)
    {
        if (!auth()->user()->canManageUsers()) {
            session()->flash('error', 'Unauthorized action.');
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
            session()->flash('error', 'Unauthorized action.');
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
        session()->flash('success', 'Feedback updated successfully.');
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

        // Apply filters
        if ($this->statusFilter !== 'all') {
            $query->where('feedbacks.status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('feedbacks.type', $this->typeFilter);
        }

        return $query->orderBy('feedbacks.created_at', 'desc')->get();
    }

    private function getFeedbackStats()
    {
        return [
            'total' => DB::table('feedbacks')->count(),
            'new' => DB::table('feedbacks')->where('status', 'new')->count(),
            'in_progress' => DB::table('feedbacks')->where('status', 'in_progress')->count(),
            'resolved' => DB::table('feedbacks')->where('status', 'resolved')->count(),
            'closed' => DB::table('feedbacks')->where('status', 'closed')->count(),
            'by_type' => DB::table('feedbacks')
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
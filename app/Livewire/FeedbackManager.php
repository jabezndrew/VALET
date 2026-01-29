<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Feedback;

class FeedbackManager extends Component
{
    // Form properties
    public $type = '';
    public $message = '';
    public $rating = null;
    public $email = '';
    public $issues = [];
    
    // Filter properties
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    
    // Modal properties
    public $showModal = false;
    public $showResponseModal = false;
    public $selectedFeedbackId = null;
    public $adminResponse = '';
    public $newStatus = '';

    protected $rules = [
        'type' => 'required|in:general,bug,feature,parking,guard_report',
        'message' => 'required|string|max:2000',
        'rating' => 'nullable|integer|min:1|max:5',
        'email' => 'nullable|email|max:255',
        'issues' => 'nullable|array',
    ];

    protected $messages = [
        'type.required' => 'Please select a feedback type.',
        'message.required' => 'Please enter your feedback message.',
        'message.max' => 'Feedback message cannot exceed 2000 characters.',
        'rating.min' => 'Rating must be at least 1 star.',
        'rating.max' => 'Rating cannot exceed 5 stars.',
    ];

    public function render()
    {
        return view('livewire.feedback-manager', [
            'feedbacks' => $this->getFeedbacks(),
            'stats' => $this->getFeedbackStats()
        ])->layout('layouts.app');
    }

    // Computed properties
    public function getCanSubmitFeedbackProperty()
    {
        return !auth()->user()->isAdmin();
    }

    public function getCanManageFeedbackProperty()
    {
        return auth()->user()->isAdmin();
    }

    public function getIsOwnFeedbackOnlyProperty()
    {
        return !auth()->user()->isAdmin();
    }

    // Form handlers
    public function updatedType($value)
    {
        if ($value !== 'general') {
            $this->rating = null;
        }
    }

    public function openModal()
    {
        if (!$this->canSubmitFeedback) {
            $this->dispatch('show-alert', type: 'error', message: 'Administrators cannot submit feedback.');
            return;
        }
        
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->reset(['showModal']);
        $this->resetForm();
    }

    public function submitFeedback()
    {
        if (!$this->canSubmitFeedback) {
            $this->dispatch('show-alert', type: 'error', message: 'Administrators cannot submit feedback.');
            return;
        }

        $this->validate();

        try {
            Feedback::create([
                'user_id' => auth()->id(),
                'type' => $this->type,
                'message' => $this->message,
                'rating' => $this->type === 'general' ? $this->rating : null,
                'email' => $this->email ?: null,
                'issues' => $this->issues ?? [],
                'device_info' => $this->getDeviceInfo(),
                'status' => 'pending',
            ]);

            $this->closeModal();
            $this->dispatch('show-alert', type: 'success', message: 'Thank you for your feedback! We appreciate your input.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to submit feedback. Please try again.');
        }
    }

    // Admin management methods
    public function quickUpdateStatus($feedbackId, $status)
    {
        if (!$this->canManageFeedback) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        if (!in_array($status, ['pending', 'reviewed', 'resolved'])) {
            $this->dispatch('show-alert', type: 'error', message: 'Invalid status.');
            return;
        }

        try {
            $feedback = Feedback::find($feedbackId);
            if ($feedback) {
                $feedback->update(['status' => $status]);
            }

            $this->dispatch('show-alert', type: 'success', message: 'Feedback status updated successfully.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to update status.');
        }
    }

    public function openResponseModal($feedbackId)
    {
        if (!$this->canManageFeedback) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $feedback = Feedback::find($feedbackId);

        if (!$feedback) {
            $this->dispatch('show-alert', type: 'error', message: 'Feedback not found.');
            return;
        }

        $this->selectedFeedbackId = $feedbackId;
        $this->adminResponse = $feedback->admin_response ?? '';
        $this->newStatus = $feedback->status;
        $this->showResponseModal = true;
    }

    public function closeResponseModal()
    {
        $this->reset(['showResponseModal', 'selectedFeedbackId', 'adminResponse', 'newStatus']);
    }

    public function saveAdminResponse()
    {
        if (!$this->canManageFeedback || !$this->selectedFeedbackId) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $this->validate([
            'newStatus' => 'required|in:pending,reviewed,resolved',
            'adminResponse' => 'nullable|string|max:1000',
        ]);

        try {
            $feedback = Feedback::find($this->selectedFeedbackId);
            if ($feedback) {
                $feedback->update([
                    'status' => $this->newStatus,
                    'admin_response' => $this->adminResponse ?: null,
                    'admin_id' => auth()->id(),
                    'responded_at' => now(),
                ]);
            }

            $this->closeResponseModal();
            $this->dispatch('show-alert', type: 'success', message: 'Feedback updated successfully.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to update feedback.');
        }
    }

    // Helper methods for display
    public function getStatusBadgeClass($status)
    {
        return match($status) {
            'pending' => 'badge bg-warning text-dark',
            'reviewed' => 'badge bg-info',
            'resolved' => 'badge bg-success',
            default => 'badge bg-secondary'
        };
    }

    public function getStatusIcon($status)
    {
        return match($status) {
            'pending' => 'fas fa-clock',
            'reviewed' => 'fas fa-eye',
            'resolved' => 'fas fa-check-circle',
            default => 'fas fa-question-circle'
        };
    }

    public function getTypeBadgeClass($type)
    {
        return match($type) {
            'general' => 'badge bg-secondary',
            'bug' => 'badge bg-danger',
            'feature' => 'badge bg-primary',
            'parking' => 'badge bg-success',
            'guard_report' => 'badge bg-warning text-dark',
            default => 'badge bg-light text-dark'
        };
    }

    public function getTypeDisplayName($type)
    {
        return match($type) {
            'general' => 'General Feedback',
            'bug' => 'Bug Report',
            'feature' => 'Feature Request',
            'parking' => 'Parking Issue',
            'guard_report' => 'Guard Report',
            default => ucfirst($type)
        };
    }

    public function isGuardReport($feedback)
    {
        return $feedback->type === 'guard_report';
    }

    public function getRelativeTime($timestamp)
    {
        return \Carbon\Carbon::parse($timestamp)->diffForHumans();
    }

    public function canQuickUpdate($feedback)
    {
        return $this->canManageFeedback && $feedback->status !== 'resolved';
    }

    // Private helper methods
    private function resetForm()
    {
        $this->reset(['type', 'message', 'rating', 'email', 'issues']);
        $this->resetErrorBag();
    }

    private function getDeviceInfo()
    {
        return [
            'platform' => 'web',
            'version' => 'browser',
            'model' => request()->header('User-Agent'),
            'appVersion' => '1.0.0',
            'ip_address' => request()->ip(),
            'submitted_at' => now()->toISOString(),
        ];
    }

    private function getFeedbacks()
    {
        $query = Feedback::with('user:id,name,role');

        // Permission-based filtering
        if ($this->isOwnFeedbackOnly) {
            $query->where('user_id', auth()->id());
        }

        // Admin filters
        if ($this->canManageFeedback) {
            if ($this->statusFilter !== 'all') {
                $query->where('status', $this->statusFilter);
            }

            if ($this->typeFilter !== 'all') {
                $query->where('type', $this->typeFilter);
            }
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($feedback) {
            // Add flattened user data for backward compatibility with views
            $feedbackArray = $feedback->toArray();
            $feedbackArray['user_name'] = $feedback->user->name ?? null;
            $feedbackArray['user_role'] = $feedback->user->role ?? null;
            return (object) $feedbackArray;
        });
    }

    private function getFeedbackStats()
    {
        $baseQuery = Feedback::query();

        if ($this->isOwnFeedbackOnly) {
            $baseQuery->where('user_id', auth()->id());
        }

        // Single query for all stats
        $stats = $baseQuery->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'reviewed' THEN 1 END) as reviewed,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
        ")->first();

        // Type breakdown in separate efficient query
        $typeStats = (clone $baseQuery)
            ->select('type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total' => $stats->total,
            'pending' => $stats->pending,
            'reviewed' => $stats->reviewed,
            'resolved' => $stats->resolved,
            'by_type' => $typeStats,
        ];
    }
}
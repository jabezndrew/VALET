<div>
    <!-- Alert container for dynamic alerts -->
    <div id="alert-container"></div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">
                            Feedback & Support
                        </h2>
                        <p class="text-muted mb-0">Help us improve VALET Smart Parking system</p>
                    </div>
                    @if(auth()->user()->canManageUsers())
                    <div class="text-end">
                        <span class="badge badge-new me-2">{{ $stats['new'] }} New</span>
                        <span class="badge badge-in-progress me-2">{{ $stats['in_progress'] }} In Progress</span>
                        <span class="badge badge-resolved">{{ $stats['resolved'] }} Resolved</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Submit Feedback Form -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-valet-charcoal text-white">
                        <h5 class="mb-0">
                            Submit Feedback
                        </h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit="submitFeedback">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Feedback Type</label>
                                <select wire:model="type" class="form-select" required>
                                    <option value="">Select type...</option>
                                    <option value="bug">🐛 Bug Report</option>
                                    <option value="suggestion">💡 Suggestion</option>
                                    <option value="complaint">😠 Complaint</option>
                                    <option value="compliment">😊 Compliment</option>
                                    <option value="general">💬 General Feedback</option>
                                </select>
                                @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Subject</label>
                                <input type="text" wire:model="subject" class="form-control" 
                                       placeholder="Brief description..." maxlength="255" required>
                                @error('subject') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Message</label>
                                <textarea wire:model="message" class="form-control" rows="4" 
                                          placeholder="Tell us more details..." maxlength="2000" required></textarea>
                                <small class="text-muted">Maximum 2000 characters</small>
                                @error('message') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Parking Location <small class="text-muted">(Optional)</small></label>
                                <input type="text" wire:model="parking_location" class="form-control" 
                                       placeholder="e.g., 4th Floor, Section A" maxlength="100">
                                @error('parking_location') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <button type="submit" class="btn btn-valet-charcoal w-100" wire:loading.attr="disabled">
                                <span wire:loading.remove>
                                    Submit Feedback
                                </span>
                                <span wire:loading>
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Submitting...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Feedback List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Recent Feedback
                            <span class="badge badge-total ms-2">{{ $stats['total'] }} Total</span>
                        </h5>
                        
                        <!-- Filters -->
                        @if(auth()->user()->canManageUsers())
                        <div class="d-flex gap-2">
                            <select wire:model.live="statusFilter" class="form-select form-select-sm">
                                <option value="all">All Status</option>
                                <option value="new">New</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                            <select wire:model.live="typeFilter" class="form-select form-select-sm">
                                <option value="all">All Types</option>
                                <option value="bug">Bug</option>
                                <option value="suggestion">Suggestion</option>
                                <option value="complaint">Complaint</option>
                                <option value="compliment">Compliment</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @forelse($feedbacks as $feedback)
                            <div class="border-bottom p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="badge me-2 
                                            @switch($feedback->type)
                                                @case('bug') badge-bug @break
                                                @case('suggestion') badge-suggestion @break
                                                @case('complaint') @break
                                                @case('compliment') badge-compliment @break
                                                @default badge-general
                                            @endswitch
                                        " style="
                                            @if($feedback->type === 'complaint') background-color: #fd7e14; color: white; @endif
                                        ">
                                            @switch($feedback->type)
                                                @case('bug') 🐛 Bug @break
                                                @case('suggestion') 💡 Suggestion @break
                                                @case('complaint') 😠 Complaint @break
                                                @case('compliment') 😊 Compliment @break
                                                @default 💬 General
                                            @endswitch
                                        </span>
                                        
                                        <span class="badge 
                                            @switch($feedback->status)
                                                @case('new') badge-new @break
                                                @case('in_progress') badge-in-progress @break
                                                @case('resolved') badge-resolved @break
                                                @case('closed') badge-types @break
                                            @endswitch
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $feedback->status)) }}
                                        </span>
                                    </div>
                                    
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($feedback->created_at)->diffForHumans() }}
                                    </small>
                                </div>

                                <h6 class="fw-bold mb-2">{{ $feedback->subject }}</h6>
                                <p class="mb-2 text-break">{{ $feedback->message }}</p>

                                @if($feedback->parking_location)
                                    <p class="mb-2">
                                        <small class="text-muted">Location: {{ $feedback->parking_location }}</small>
                                    </p>
                                @endif

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        {{ $feedback->user_name }} 
                                        <span class="badge badge-sm ms-1 
                                            @switch($feedback->user_role)
                                                @case('admin') bg-danger @break
                                                @case('ssd') @break
                                                @case('security') bg-warning @break
                                                @default
                                            @endswitch
                                        " style="
                                            @switch($feedback->user_role)
                                                @case('ssd') background-color: #3A3A3C; color: white; @break
                                                @default background-color: #A0A0A0; color: white;
                                            @endswitch
                                        ">
                                            {{ ucfirst($feedback->user_role) }}
                                        </span>
                                    </small>

                                    @if(auth()->user()->canManageUsers())
                                        <div class="btn-group btn-group-sm">
                                            @if($feedback->status !== 'in_progress')
                                            <button wire:click="quickUpdateStatus({{ $feedback->id }}, 'in_progress')" 
                                                    class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-clock"></i>
                                            </button>
                                            @endif
                                            
                                            @if($feedback->status !== 'resolved')
                                            <button wire:click="quickUpdateStatus({{ $feedback->id }}, 'resolved')" 
                                                    class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            @endif
                                            
                                            <button wire:click="openResponseModal({{ $feedback->id }})" 
                                                    class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                @if($feedback->admin_response)
                                    <div class="mt-3 p-2 bg-light rounded">
                                        <small class="fw-bold text-secondary">
                                            Admin Response:
                                        </small>
                                        <p class="mb-0 mt-1">{{ $feedback->admin_response }}</p>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-comment-slash text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                <h5 class="text-muted">No feedback yet</h5>
                                <p class="text-muted">Be the first to submit feedback!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Response Modal -->
    @if($showResponseModal)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Response</h5>
                    <button type="button" class="btn-close" wire:click="closeResponseModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select wire:model="newStatus" class="form-select">
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Response</label>
                        <textarea wire:model="adminResponse" class="form-control" rows="4" 
                                  placeholder="Optional response to user..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeResponseModal">Cancel</button>
                    <button type="button" class="btn btn-valet-charcoal" wire:click="saveAdminResponse">Save</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Alert handling script -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-alert', (event) => {
                const alertContainer = document.getElementById('alert-container');
                const alertId = 'alert-' + Date.now();
                
                const alertHtml = `
                    <div class="container mt-3">
                        <div id="${alertId}" class="alert alert-${event.type} alert-dismissible fade show" role="alert">
                            <i class="fas fa-${event.type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                            ${event.message}
                            <button type="button" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
                        </div>
                    </div>
                `;
                
                alertContainer.innerHTML = alertHtml;
                
                setTimeout(() => {
                    const alert = document.getElementById(alertId);
                    if (alert) alert.remove();
                }, 5000);
            });
        });
    </script>
</div>
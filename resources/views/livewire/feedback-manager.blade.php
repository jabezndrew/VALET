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
                    <div class="d-flex align-items-center gap-3">
                        @if(auth()->user()->isAdmin())
                        <!-- Admin Stats -->
                        <div class="text-end">
                            <span class="badge badge-pending me-2">{{ $stats['pending'] }} Pending</span>
                            <span class="badge badge-reviewed me-2">{{ $stats['reviewed'] }} Reviewed</span>
                            <span class="badge badge-resolved">{{ $stats['resolved'] }} Resolved</span>
                        </div>
                        @else
                        <!-- Submit Feedback Button (Non-Admin Users Only) -->
                        <button wire:click="openModal" class="btn btn-valet-charcoal">
                            Submit Feedback
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback List (Full Width) -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            @if(auth()->user()->isAdmin())
                                All Feedback & Support Requests
                            @else
                                Your Feedback History
                            @endif
                            <span class="badge badge-total ms-2">{{ $stats['total'] }} Total</span>
                        </h5>
                        
                        <!-- Filters (Admin Only) -->
                        @if(auth()->user()->isAdmin())
                        <div class="d-flex gap-2">
                            <select wire:model.live="statusFilter" class="form-select form-select-sm">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="resolved">Resolved</option>
                            </select>
                            <select wire:model.live="typeFilter" class="form-select form-select-sm">
                                <option value="all">All Types</option>
                                <option value="general">General</option>
                                <option value="bug">Bug</option>
                                <option value="feature">Feature</option>
                                <option value="parking">Parking</option>
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
                                                @case('general') badge-general @break
                                                @case('bug') badge-bug @break
                                                @case('feature') badge-suggestion @break
                                                @case('parking') badge-compliment @break
                                            @endswitch
                                        ">
                                            @switch($feedback->type)
                                                @case('general') 💬 General @break
                                                @case('bug') 🐛 Bug @break
                                                @case('feature') 💡 Feature @break
                                                @case('parking') 🅿️ Parking @break
                                            @endswitch
                                        </span>
                                        
                                        <span class="badge 
                                            @switch($feedback->status)
                                                @case('pending') badge-new @break
                                                @case('reviewed') badge-in-progress @break
                                                @case('resolved') badge-resolved @break
                                            @endswitch
                                        ">
                                            {{ ucfirst($feedback->status) }}
                                        </span>
                                    </div>
                                    
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($feedback->created_at)->diffForHumans() }}
                                    </small>
                                </div>

                                <p class="mb-2 text-break">{{ $feedback->message }}</p>

                                {{-- UPDATED: Only show rating for general feedback type --}}
                                @if($feedback->type === 'general' && $feedback->rating)
                                    <div class="mb-2">
                                        <small class="text-muted">Rating: </small>
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $feedback->rating ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                        <span class="text-muted">({{ $feedback->rating }}/5)</span>
                                    </div>
                                @endif

                                @if($feedback->email)
                                    <p class="mb-2">
                                        <small class="text-muted">Contact: {{ $feedback->email }}</small>
                                    </p>
                                @endif

                                @if($feedback->issues)
                                    @php
                                        $issuesArray = json_decode($feedback->issues, true) ?? [];
                                    @endphp
                                    @if(!empty($issuesArray))
                                        <div class="mb-2">
                                            <small class="text-muted">Issues: </small>
                                            @foreach($issuesArray as $issue)
                                                <span class="badge bg-secondary me-1">{{ str_replace('_', ' ', $issue) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                <div class="d-flex justify-content-between align-items-center">
                                    @if(auth()->user()->isAdmin())
                                    <!-- Show user info for admins -->
                                    <small class="text-muted">
                                        {{ $feedback->user_name }} 
                                        <span class="badge badge-sm ms-1 
                                            @switch($feedback->user_role)
                                                @case('admin') bg-danger @break
                                                @case('ssd') bg-valet-charcoal @break
                                                @case('security') bg-warning @break
                                                @default bg-valet-gray
                                            @endswitch
                                        ">
                                            {{ ucfirst($feedback->user_role) }}
                                        </span>
                                    </small>
                                    @else
                                    <!-- Show submission info for users -->
                                    <small class="text-muted">
                                        Submitted by you
                                    </small>
                                    @endif

                                    @if(auth()->user()->isAdmin())
                                        <div class="btn-group btn-group-sm">
                                            @if($feedback->status !== 'reviewed')
                                            <button wire:click="quickUpdateStatus({{ $feedback->id }}, 'reviewed')" 
                                                    class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-eye"></i>
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
                                        @if($feedback->responded_at)
                                            <small class="text-muted">
                                                Responded {{ \Carbon\Carbon::parse($feedback->responded_at)->diffForHumans() }}
                                            </small>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-comment-slash text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                @if(auth()->user()->isAdmin())
                                    <h5 class="text-muted">No feedback received yet</h5>
                                    <p class="text-muted">Users haven't submitted any feedback yet.</p>
                                @else
                                    <h5 class="text-muted">No feedback submitted yet</h5>
                                    <p class="text-muted">Click "Submit Feedback" to share your thoughts!</p>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Feedback Modal (Non-Admin Users Only) -->
    @if($showModal && !auth()->user()->isAdmin())
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Submit Feedback
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <form wire:submit="submitFeedback">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Feedback Type</label>
                            <select wire:model.live="type" class="form-select" required>
                                <option value="">Select type...</option>
                                <option value="general">💬 General Feedback</option>
                                <option value="bug">🐛 Bug Report</option>
                                <option value="feature">💡 Feature Request</option>
                                <option value="parking">🅿️ Parking Issue</option>
                            </select>
                            @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Message</label>
                            <textarea wire:model="message" class="form-control" rows="4" 
                                      placeholder="Tell us more details..." maxlength="2000" required></textarea>
                            <small class="text-muted">Maximum 2000 characters</small>
                            @error('message') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        {{-- FIXED: Rating only for general feedback --}}
                        @if($type === 'general')
                        <div class="mb-3" wire:key="rating-field">
                            <label class="form-label fw-bold">Rating <small class="text-muted">(Optional)</small></label>
                            <select wire:model="rating" class="form-select">
                                <option value="">Select rating...</option>
                                <option value="5">⭐⭐⭐⭐⭐ Excellent (5)</option>
                                <option value="4">⭐⭐⭐⭐ Good (4)</option>
                                <option value="3">⭐⭐⭐ Average (3)</option>
                                <option value="2">⭐⭐ Poor (2)</option>
                                <option value="1">⭐ Very Poor (1)</option>
                            </select>
                            @error('rating') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-bold">Contact Email <small class="text-muted">(Optional)</small></label>
                            <input type="email" wire:model="email" class="form-control" 
                                   placeholder="your.email@example.com" maxlength="255">
                            <small class="text-muted">We'll only use this to follow up if needed</small>
                            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Related Issues <small class="text-muted">(Optional)</small></label>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input wire:model="issues" class="form-check-input" type="checkbox" value="login_issues" id="issue1">
                                        <label class="form-check-label" for="issue1">Login Issues</label>
                                    </div>
                                    <div class="form-check">
                                        <input wire:model="issues" class="form-check-input" type="checkbox" value="parking_detection" id="issue2">
                                        <label class="form-check-label" for="issue2">Parking Detection</label>
                                    </div>
                                    <div class="form-check">
                                        <input wire:model="issues" class="form-check-input" type="checkbox" value="app_crashes" id="issue3">
                                        <label class="form-check-label" for="issue3">App Crashes</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input wire:model="issues" class="form-check-input" type="checkbox" value="slow_loading" id="issue4">
                                        <label class="form-check-label" for="issue4">Slow Loading</label>
                                    </div>
                                    <div class="form-check">
                                        <input wire:model="issues" class="form-check-input" type="checkbox" value="wrong_data" id="issue5">
                                        <label class="form-check-label" for="issue5">Wrong Data</label>
                                    </div>
                                    <div class="form-check">
                                        <input wire:model="issues" class="form-check-input" type="checkbox" value="ui_problems" id="issue6">
                                        <label class="form-check-label" for="issue6">UI Problems</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="submit" class="btn btn-valet-charcoal" wire:loading.attr="disabled" wire:target="submitFeedback">
                            <span wire:loading.remove wire:target="submitFeedback">
                                Submit Feedback
                            </span>
                            <span wire:loading wire:target="submitFeedback">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Submitting...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Admin Response Modal (Admin Only) -->
    @if($showResponseModal && auth()->user()->isAdmin())
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
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
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

    <!-- CSS for new badge styles -->
    <style>
        .badge-pending { background-color: #3A3A3C; color: white; }
        .badge-reviewed { background-color: #fd7e14; color: white; }
        .badge-resolved { background-color: #2F623D; color: white; }
    </style>

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
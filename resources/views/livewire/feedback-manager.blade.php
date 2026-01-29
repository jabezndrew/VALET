<div>
    <div id="alert-container"></div>

    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Feedback & Support</h2>
                <p class="text-muted mb-0">Help us improve VALET Smart Parking system</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if($this->canManageFeedback)
                    <!-- Admin Stats -->
                    <div class="text-end">
                        <span class="{{ $this->getStatusBadgeClass('pending') }} me-2">{{ $stats['pending'] }} Pending</span>
                        <span class="{{ $this->getStatusBadgeClass('reviewed') }} me-2">{{ $stats['reviewed'] }} Reviewed</span>
                        <span class="{{ $this->getStatusBadgeClass('resolved') }}">{{ $stats['resolved'] }} Resolved</span>
                    </div>
                @elseif($this->canSubmitFeedback)
                    <button wire:click="openModal" class="btn btn-valet-charcoal">
                        Submit Feedback
                    </button>
                @endif
            </div>
        </div>

        <!-- Feedback Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-total">
                    <div class="card-body text-center">
                        <h3>{{ $stats['total'] }}</h3>
                        <p class="mb-0">Total Feedback</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-types">
                    <div class="card-body text-center">
                        <h3>{{ $stats['pending'] }}</h3>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-inactive">
                    <div class="card-body text-center">
                        <h3>{{ $stats['reviewed'] }}</h3>
                        <p class="mb-0">Reviewed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-active">
                    <div class="card-body text-center">
                        <h3>{{ $stats['resolved'] }}</h3>
                        <p class="mb-0">Resolved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    @if($this->canManageFeedback)
                        All Feedback & Support Requests
                    @else
                        Your Feedback History
                    @endif
                </h5>

                <!-- Filters (Admin Only) -->
                @if($this->canManageFeedback)
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
                        <option value="bug">Bug Report</option>
                        <option value="feature">Feature Request</option>
                        <option value="parking">Parking Issue</option>
                        <option value="guard_report">Guard Report</option>
                    </select>
                </div>
                @endif
            </div>
            <div class="card-body p-0">
                @forelse($feedbacks as $feedback)
                    <div class="border-bottom p-3 {{ $feedback->type === 'guard_report' ? 'bg-warning bg-opacity-10 border-start border-warning border-4' : '' }}">
                        <!-- Header Row -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                @if($feedback->type === 'guard_report')
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Guard Report
                                    </span>
                                @else
                                    <span class="{{ $this->getTypeBadgeClass($feedback->type) }}">
                                        {{ $this->getTypeDisplayName($feedback->type) }}
                                    </span>
                                @endif
                                <span class="{{ $this->getStatusBadgeClass($feedback->status) }}">
                                    <i class="{{ $this->getStatusIcon($feedback->status) }} me-1"></i>
                                    {{ ucfirst($feedback->status) }}
                                </span>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                {{ $this->getRelativeTime($feedback->created_at) }}
                            </small>
                        </div>

                        <!-- Message -->
                        <div class="mb-2">
                            <p class="mb-0 text-break {{ $feedback->type === 'guard_report' ? 'fw-medium' : '' }}">{{ $feedback->message }}</p>
                        </div>

                        <!-- Guard Report Details -->
                        @if($feedback->type === 'guard_report' && $feedback->issues && is_array($feedback->issues))
                            <div class="mb-2 d-flex flex-wrap gap-2">
                                @if(isset($feedback->issues['category']))
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-tag me-1"></i>{{ str_replace('_', ' ', ucwords($feedback->issues['category'], '_')) }}
                                    </span>
                                @endif
                                @if(isset($feedback->issues['space_code']))
                                    <span class="badge bg-dark">
                                        <i class="fas fa-parking me-1"></i>{{ $feedback->issues['space_code'] }}
                                    </span>
                                @endif
                                @if(isset($feedback->issues['floor_level']))
                                    <span class="badge bg-info text-dark">
                                        <i class="fas fa-building me-1"></i>{{ $feedback->issues['floor_level'] }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <!-- Rating (General feedback only) -->
                        @if($feedback->type === 'general' && $feedback->rating)
                            <div class="mb-2">
                                <small class="text-muted">Rating: </small>
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $feedback->rating ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                                <span class="text-muted ms-1">({{ $feedback->rating }}/5)</span>
                            </div>
                        @endif

                        <!-- Contact Email -->
                        @if($feedback->email)
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-envelope me-1"></i>
                                    Contact: {{ $feedback->email }}
                                </small>
                            </div>
                        @endif

                        <!-- Issues -->
                        @if($feedback->issues && is_array($feedback->issues) && !empty($feedback->issues))
                            <div class="mb-2">
                                <small class="text-muted">Issues: </small>
                                @foreach($feedback->issues as $issue)
                                    <span class="badge bg-secondary me-1">{{ str_replace('_', ' ', ucwords($issue, '_')) }}</span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Footer Row -->
                        <div class="d-flex justify-content-between align-items-center">
                            @if($this->canManageFeedback)
                                <!-- User info for admins -->
                                <small class="text-muted">
                                    @if($feedback->type === 'guard_report')
                                        <span class="fw-medium">Guard Station</span>
                                        <span class="badge ms-1 bg-warning text-dark">Guard</span>
                                    @else
                                        <span>Reported by: {{ $feedback->user_name }}</span>

                                        <span class="badge ms-1 {{ match($feedback->user_role) {
                                            'admin' => 'bg-danger',
                                            'ssd' => 'bg-valet-charcoal text-white',
                                            'security' => 'bg-warning',
                                            default => 'bg-valet-gray'
                                        } }}">
                                            {{ ucfirst($feedback->user_role ?? 'user') }}
                                        </span>
                                    @endif
                                </small>
                            @else
                                <!-- Submission info for users -->
                                <small class="text-muted">
                                    <i class="fas fa-user-check me-1"></i>
                                    Submitted by you
                                </small>
                            @endif

                            <!-- Admin Actions -->
                            @if($this->canManageFeedback && $this->canQuickUpdate($feedback))
                                <div class="btn-group btn-group-sm">
                                    @if($feedback->status !== 'reviewed')
                                    <button wire:click="quickUpdateStatus({{ $feedback->id }}, 'reviewed')"
                                            class="btn btn-outline-warning btn-sm"
                                            title="Mark as reviewed">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @endif

                                    @if($feedback->status !== 'resolved')
                                    <button wire:click="quickUpdateStatus({{ $feedback->id }}, 'resolved')"
                                            class="btn btn-outline-success btn-sm"
                                            title="Mark as resolved">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @endif

                                    <button wire:click="openResponseModal({{ $feedback->id }})"
                                            class="btn btn-outline-secondary btn-sm"
                                            title="Add response">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Admin Response -->
                        @if($feedback->admin_response)
                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="d-flex align-items-center mb-2">
                                    <strong class="text-primary">Admin Response</strong>
                                </div>
                                <p class="mb-2">{{ $feedback->admin_response }}</p>
                                @if($feedback->responded_at)
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Responded {{ $this->getRelativeTime($feedback->responded_at) }}
                                    </small>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                        @if($this->canManageFeedback)
                            <h5 class="text-muted">No feedback received yet</h5>
                            <p class="text-muted">Users haven't submitted any feedback yet.</p>
                        @else
                            <h5 class="text-muted">No feedback submitted yet</h5>
                            <p class="text-muted">Click "Submit Feedback" to share your thoughts!</p>
                            @if($this->canSubmitFeedback)
                                <button wire:click="openModal" class="btn btn-valet-charcoal mt-2">
                                    Submit Your First Feedback
                                </button>
                            @endif
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Submit Feedback Modal -->
    @if($showModal && $this->canSubmitFeedback)
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
                                <option value="general">General Feedback</option>
                                <option value="bug">Bug Report</option>
                                <option value="feature">Feature Request</option>
                                <option value="parking">Parking Issue</option>
                            </select>
                            @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Message</label>
                            <textarea wire:model="message" class="form-control" rows="4"
                                      placeholder="Tell us more details..." maxlength="2000" required></textarea>
                            <small class="text-muted">2000 characters</small>
                            @error('message') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <!-- Rating (General feedback only) -->
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
                                   placeholder="your.email@usjr.edu.ph" maxlength="255">
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
                                <i class="fas fa-spinner fa-spin me-1"></i>Submitting...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Admin Response Modal -->
    @if($showResponseModal && $this->canManageFeedback)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Response</h5>
                    <button type="button" class="btn-close" wire:click="closeResponseModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select wire:model="newStatus" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                        @error('newStatus') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Response <small class="text-muted">(Optional)</small></label>
                        <textarea wire:model="adminResponse" class="form-control" rows="4"
                                  placeholder="Optional response to user..." maxlength="1000"></textarea>
                        <small class="text-muted">{{ strlen($adminResponse) }}/1000 characters</small>
                        @error('adminResponse') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeResponseModal">Cancel</button>
                    <button type="button" class="btn btn-valet-charcoal" wire:click="saveAdminResponse" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveAdminResponse">
                            <i class="fas fa-save me-1"></i>Save Response
                        </span>
                        <span wire:loading wire:target="saveAdminResponse">
                            <i class="fas fa-spinner fa-spin me-1"></i>Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

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

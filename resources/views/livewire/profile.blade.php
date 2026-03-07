<div>
    <div id="alert-container"></div>

    <div class="container py-4">
        <!-- Header -->
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Profile Settings</h2>
            <p class="text-muted mb-0">Manage your account information</p>
        </div>

        <div class="row">
            <!-- Profile Info Card -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit="updateProfile">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input wire:model="name" type="text" class="form-control" placeholder="Your name">
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Email Address</label>
                                <input wire:model="email" type="email" class="form-control" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Role</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->getRoleDisplayName() }}" disabled>
                            </div>

                            @if(auth()->user()->employee_id)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Employee ID</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->employee_id }}" disabled>
                            </div>
                            @endif

                            @if(auth()->user()->department)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->department }}" disabled>
                            </div>
                            @endif

                            <button type="submit" class="btn btn-valet-charcoal" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="updateProfile">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </span>
                                <span wire:loading wire:target="updateProfile">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Saving...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit="updatePassword">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Password</label>
                                <input wire:model="current_password" type="password" class="form-control" placeholder="Enter current password">
                                @error('current_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">New Password</label>
                                <input wire:model="new_password" type="password" class="form-control" placeholder="Enter new password">
                                @error('new_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Confirm New Password</label>
                                <input wire:model="new_password_confirmation" type="password" class="form-control" placeholder="Confirm new password">
                            </div>

                            <button type="submit" class="btn btn-valet-charcoal" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="updatePassword">
                                    <i class="fas fa-key me-2"></i>Update Password
                                </span>
                                <span wire:loading wire:target="updatePassword">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Updating...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p class="text-muted mb-1">Account Created</p>
                        <p class="fw-bold">{{ auth()->user()->created_at->format('F j, Y') }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="text-muted mb-1">Account Status</p>
                        <span class="badge {{ auth()->user()->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ auth()->user()->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="col-md-4">
                        <p class="text-muted mb-1">Last Updated</p>
                        <p class="fw-bold">{{ auth()->user()->updated_at->format('F j, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

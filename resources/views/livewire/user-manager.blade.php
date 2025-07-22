<div>
    <!-- Alert container for dynamic alerts -->
    <div id="alert-container"></div>

    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    User Management
                </h2>
                <p class="text-muted mb-0">Manage system users and their roles</p>
            </div>
            <button wire:click="openModal" class="btn btn-valet-charcoal">
                Add New User
            </button>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-total">
                    <div class="card-body text-center">
                        <h3>{{ $stats['total'] }}</h3>
                        <p class="mb-0">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-active">
                    <div class="card-body text-center">
                        <h3>{{ $stats['active'] }}</h3>
                        <p class="mb-0">Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-inactive">
                    <div class="card-body text-center">
                        <h3>{{ $stats['inactive'] }}</h3>
                        <p class="mb-0">Inactive</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-types">
                    <div class="card-body text-center">
                        <h3>{{ $stats['by_role']['admin'] }}</h3>
                        <p class="mb-0">Administrators</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <input wire:model.live="search" type="text" class="form-control" 
                               placeholder="Search by name, email, or employee ID...">
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="roleFilter" class="form-select">
                            <option value="all">All Roles</option>
                            <option value="admin">Administrator</option>
                            <option value="ssd">SSD Personnel</option>
                            <option value="security">Security Personnel</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="statusFilter" class="form-select">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- User List -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Employee ID</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $user->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @switch($user->role)
                                                @case('admin') bg-danger @break
                                                @case('ssd') @break
                                                @case('security') bg-warning @break
                                                @default
                                            @endswitch
                                        " style="
                                            @switch($user->role)
                                                @case('ssd') background-color: #3A3A3C; color: white; @break
                                                @default background-color: #A0A0A0; color: white;
                                            @endswitch
                                        ">
                                            {{ $user->getRoleDisplayName() }}
                                        </span>
                                    </td>
                                    <td class="font-monospace">{{ $user->employee_id ?: '-' }}</td>
                                    <td>{{ $user->department ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $user->created_at->format('M j, Y') }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="openModal({{ $user->id }})" 
                                                    class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="toggleStatus({{ $user->id }})" 
                                                    class="btn btn-outline-{{ $user->is_active ? 'warning' : 'success' }}">
                                                <i class="fas fa-{{ $user->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                            @if($user->id !== auth()->id())
                                            <button wire:click="delete({{ $user->id }})" 
                                                    wire:confirm="Are you sure you want to delete this user?"
                                                    class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-users text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <h5 class="text-muted">No users found</h5>
                                        <p class="text-muted">Try adjusting your search filters</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $editingId ? 'Edit User' : 'Add New User' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Full Name</label>
                                    <input wire:model="name" type="text" class="form-control" 
                                           placeholder="John Doe" required>
                                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <input wire:model="email" type="email" class="form-control" 
                                           placeholder="john@usjr.edu.ph" required>
                                    @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        Password {{ $editingId ? '(Leave blank to keep current)' : '' }}
                                    </label>
                                    <input wire:model="password" type="password" class="form-control" 
                                           placeholder="Password" {{ $isPasswordRequired ? 'required' : '' }}>
                                    @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Confirm Password</label>
                                    <input wire:model="password_confirmation" type="password" class="form-control" 
                                           placeholder="Confirm Password">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Role</label>
                                    <select wire:model="role" class="form-select" required>
                                        <option value="user">User</option>
                                        <option value="security">Security Personnel</option>
                                        <option value="ssd">SSD Personnel</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                    @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Employee ID</label>
                                    <input wire:model="employee_id" type="text" class="form-control" 
                                           placeholder="EMP001">
                                    @error('employee_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Department</label>
                                    <input wire:model="department" type="text" class="form-control" 
                                           placeholder="IT Department">
                                    @error('department') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <div class="form-check form-switch">
                                        <input wire:model="is_active" class="form-check-input" 
                                               type="checkbox" id="is_active">
                                        <label class="form-check-label" for="is_active">
                                            {{ $is_active ? 'Active' : 'Inactive' }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="submit" class="btn btn-valet-charcoal" wire:loading.attr="disabled">
                            <span wire:loading.remove">
                                {{ $editingId ? 'Update User' : 'Create User' }}
                            </span>
                            <span wire:loading>
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
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
                            <button type="type" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
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
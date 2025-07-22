<div>
    <!-- Alert container for dynamic alerts -->
    <div id="alert-container"></div>

    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    Vehicle Management
                </h2>
                <p class="text-muted mb-0">Manage registered vehicles and RFID tags</p>
            </div>
            <div class="d-flex gap-2">
                @if(auth()->user()->role !== 'user')
                <button wire:click="openVerifyModal" class="btn btn-outline-success">
                    <i class="fas fa-search me-1"></i> Verify Vehicle
                </button>
                @endif
                @if(auth()->user()->canManageCars())
                <button wire:click="openModal" class="btn btn-valet-charcoal">
                    Register Vehicle
                </button>
                @endif
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-total">
                    <div class="card-body text-center">
                        <h3>{{ $stats['total'] }}</h3>
                        <p class="mb-0">Total Vehicles</p>
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
                        <h3>{{ $stats['expired'] }}</h3>
                        <p class="mb-0">Expired</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-types">
                    <div class="card-body text-center">
                        <h3>{{ $stats['expiring_soon'] }}</h3>
                        <p class="mb-0">Expiring Soon</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <input wire:model.live="search" type="text" class="form-control" 
                               placeholder="Search by plate number, make, model, RFID, or owner...">
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="statusFilter" class="form-select">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="expiring_soon">Expiring Soon</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="typeFilter" class="form-select">
                            <option value="all">All Types</option>
                            <option value="car">Car</option>
                            <option value="suv">SUV</option>
                            <option value="truck">Truck</option>
                            <option value="van">Van</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select wire:model.live="ownerRoleFilter" class="form-select">
                            <option value="all">All Users</option>
                            <option value="user">Students/Parents</option>
                            <option value="security">Security</option>
                            <option value="ssd">SSD Personnel</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button wire:click="exportVehicles" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle List -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Plate Number</th>
                                <th>Vehicle Details</th>
                                <th>Owner</th>
                                <th>RFID Tag</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Registered</th>
                                @if(auth()->user()->canManageCars())
                                <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicles as $vehicle)
                                <tr class="{{ $this->getRowClass($vehicle) }}">
                                    <td class="fw-bold">{{ $vehicle->plate_number }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $vehicle->vehicle_make }} {{ $vehicle->vehicle_model }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                <span class="badge" style="background-color: #A0A0A0; color: white;">{{ ucfirst($vehicle->vehicle_type) }}</span>
                                                {{ $vehicle->vehicle_color }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $vehicle->owner_name }}
                                        <br>
                                        <small class="badge 
                                            @switch($vehicle->owner_role)
                                                @case('admin') bg-danger @break
                                                @case('ssd') @break
                                                @case('security') bg-warning @break
                                                @default
                                            @endswitch
                                        " style="
                                            @switch($vehicle->owner_role)
                                                @case('ssd') background-color: #3A3A3C; color: white; @break
                                                @default background-color: #A0A0A0; color: white;
                                            @endswitch
                                        ">
                                            {{ ucfirst($vehicle->owner_role) }}
                                        </small>
                                    </td>
                                    <td class="font-monospace">{{ $vehicle->rfid_tag }}</td>
                                    <td>
                                        @if($vehicle->expires_at)
                                            <div>
                                                {{ \Carbon\Carbon::parse($vehicle->expires_at)->format('M j, Y') }}
                                                <br>
                                                <small class="text-muted">
                                                    {{ $this->getDaysUntilExpiry($vehicle->expires_at) }}
                                                </small>
                                            </div>
                                        @else
                                            <span class="text-muted">No expiry</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $this->getStatusBadgeClass($vehicle) }}">
                                            {{ $this->getVehicleStatus($vehicle) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($vehicle->created_at)->format('M j, Y') }}
                                        </small>
                                    </td>
                                    @if(auth()->user()->canManageCars())
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="openModal({{ $vehicle->id }})" 
                                                    class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if($this->isExpired($vehicle->expires_at))
                                                <button wire:click="renewVehicle({{ $vehicle->id }})" 
                                                        class="btn btn-outline-success"
                                                        title="Renew for next semester">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            @endif
                                            <button wire:click="toggleStatus({{ $vehicle->id }})" 
                                                    class="btn btn-outline-{{ $vehicle->is_active ? 'warning' : 'success' }}">
                                                <i class="fas fa-{{ $vehicle->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                            <button wire:click="delete({{ $vehicle->id }})" 
                                                    wire:confirm="Are you sure you want to delete this vehicle?"
                                                    class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->canManageCars() ? '8' : '7' }}" class="text-center py-5">
                                        <i class="fas fa-car text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <h5 class="text-muted">No vehicles registered</h5>
                                        <p class="text-muted">Start by registering your first vehicle</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Modal -->
    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $editingId ? 'Edit Vehicle' : 'Register New Vehicle' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Plate Number</label>
                                    <input wire:model="plate_number" type="text" class="form-control" 
                                           placeholder="e.g. ABC-1234, XYZ-9876" maxlength="20" required>
                                    @error('plate_number') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">RFID Tag</label>
                                    <input wire:model="rfid_tag" type="text" class="form-control" 
                                           placeholder="e.g. 0123456789ABCDEF" maxlength="50" required>
                                    @error('rfid_tag') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Make</label>
                                    <input wire:model="vehicle_make" type="text" class="form-control" 
                                           placeholder="e.g. Toyota, Honda, Mitsubishi" maxlength="50" required>
                                    @error('vehicle_make') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Model</label>
                                    <input wire:model="vehicle_model" type="text" class="form-control" 
                                           placeholder="e.g. Camry, Civic, Lancer" maxlength="50" required>
                                    @error('vehicle_model') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Type</label>
                                    <select wire:model="vehicle_type" class="form-select" required>
                                        <option value="car">Car</option>
                                        <option value="suv">SUV</option>
                                        <option value="truck">Truck</option>
                                        <option value="van">Van</option>
                                    </select>
                                    @error('vehicle_type') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Color</label>
                                    <input wire:model="vehicle_color" type="text" class="form-control" 
                                           placeholder="e.g. Red, Blue, White" maxlength="30" required>
                                    @error('vehicle_color') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Owner</label>
                                    <select wire:model="owner_id" class="form-select" required>
                                        <option value="">Select Owner...</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }} ({{ $user->getRoleDisplayName() }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('owner_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Expiry Date</label>
                                    <input wire:model="expires_at" type="date" class="form-control" 
                                           min="{{ date('Y-m-d') }}">
                                    <small class="text-muted">Leave blank for no expiry (staff vehicles)</small>
                                    @error('expires_at') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="submit" class="btn btn-valet-charcoal" wire:loading.attr="disabled">
                            <span wire:loading.remove">
                                {{ $editingId ? 'Update Vehicle' : 'Register Vehicle' }}
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

    <!-- Verify Vehicle Modal -->
    @if($showVerifyModal)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-search me-2"></i>
                        Verify Vehicle
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeVerifyModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">RFID Tag</label>
                        <input wire:model="verifyRfid" type="text" class="form-control" 
                               placeholder="Enter RFID tag to verify..." 
                               wire:keydown.enter="verifyVehicle">
                        @error('verifyRfid') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    @if($verifyResult)
                        <div class="alert alert-{{ $verifyResult['color'] }} mt-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ 
                                    $verifyResult['status'] === 'ACTIVE' ? 'check-circle' : 
                                    ($verifyResult['status'] === 'NOT_FOUND' ? 'times-circle' : 'exclamation-triangle') 
                                }} me-2"></i>
                                <div>
                                    <strong>{{ $verifyResult['status'] }}</strong>
                                    <div>{{ $verifyResult['message'] }}</div>
                                </div>
                            </div>
                            
                            @if(isset($verifyResult['vehicle']))
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-6">
                                        <small>
                                            <strong>Vehicle:</strong><br>
                                            {{ $verifyResult['vehicle']->vehicle_make }} {{ $verifyResult['vehicle']->vehicle_model }}<br>
                                            <strong>Plate:</strong> {{ $verifyResult['vehicle']->plate_number }}
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small>
                                            <strong>Owner:</strong><br>
                                            {{ $verifyResult['vehicle']->owner_name }}<br>
                                            <strong>Role:</strong> {{ ucfirst($verifyResult['vehicle']->owner_role) }}
                                        </small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeVerifyModal">Close</button>
                    <button type="button" class="btn btn-success" wire:click="verifyVehicle">
                        <i class="fas fa-search me-1"></i> Verify
                    </button>
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
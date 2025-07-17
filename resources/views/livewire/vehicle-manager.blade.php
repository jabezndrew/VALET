<x-layouts.app>
<div>
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-car text-primary me-2"></i>
                    Vehicle Management
                </h2>
                <p class="text-muted mb-0">Manage registered vehicles and RFID tags</p>
            </div>
            @if(auth()->user()->canManageCars())
            <button wire:click="openModal" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Register Vehicle
            </button>
            @endif
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>{{ $stats['total'] }}</h3>
                        <p class="mb-0">Total Vehicles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>{{ $stats['active'] }}</h3>
                        <p class="mb-0">Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3>{{ $stats['inactive'] }}</h3>
                        <p class="mb-0">Inactive</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3>{{ count($stats['by_type']) }}</h3>
                        <p class="mb-0">Vehicle Types</p>
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
                               placeholder="Search by plate number, make, model, RFID, or owner...">
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="statusFilter" class="form-select">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="typeFilter" class="form-select">
                            <option value="all">All Types</option>
                            <option value="car">Car</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="suv">SUV</option>
                            <option value="truck">Truck</option>
                            <option value="van">Van</option>
                        </select>
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
                                <th>Status</th>
                                <th>Registered</th>
                                @if(auth()->user()->canManageCars())
                                <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicles as $vehicle)
                                <tr>
                                    <td class="fw-bold">{{ $vehicle->plate_number }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $vehicle->vehicle_make }} {{ $vehicle->vehicle_model }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                <span class="badge bg-secondary">{{ ucfirst($vehicle->vehicle_type) }}</span>
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
                                                @case('ssd') bg-primary @break
                                                @case('security') bg-warning @break
                                                @default bg-secondary
                                            @endswitch
                                        ">
                                            {{ ucfirst($vehicle->owner_role) }}
                                        </small>
                                    </td>
                                    <td class="font-monospace">{{ $vehicle->rfid_tag }}</td>
                                    <td>
                                        <span class="badge {{ $vehicle->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $vehicle->is_active ? 'Active' : 'Inactive' }}
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
                                                    class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </button>
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
                                    <td colspan="{{ auth()->user()->canManageCars() ? '7' : '6' }}" class="text-center py-5">
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
                        <i class="fas fa-car me-2"></i>
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
                                           placeholder="ABC-1234" maxlength="20" required>
                                    @error('plate_number') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">RFID Tag</label>
                                    <input wire:model="rfid_tag" type="text" class="form-control" 
                                           placeholder="RFID Tag ID" maxlength="50" required>
                                    @error('rfid_tag') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Make</label>
                                    <input wire:model="vehicle_make" type="text" class="form-control" 
                                           placeholder="Toyota" maxlength="50" required>
                                    @error('vehicle_make') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Model</label>
                                    <input wire:model="vehicle_model" type="text" class="form-control" 
                                           placeholder="Camry" maxlength="50" required>
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
                                        <option value="motorcycle">Motorcycle</option>
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
                                           placeholder="Red" maxlength="30" required>
                                    @error('vehicle_color') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="fas fa-save me-2"></i>
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
</div>
</x-layouts.app>
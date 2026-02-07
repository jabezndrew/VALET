<div>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>RFID Tag Management</h2>
            <button class="btn btn-primary" wire:click="openCreateModal">Register New RFID Tag</button>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" wire:model.live="searchUid" placeholder="Search UID...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="filterStatus">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="suspended">Suspended</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>User</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tags as $tag)
                                <tr>
                                    <td><code>{{ $tag->uid }}</code></td>
                                    <td>{{ $tag->user->name ?? 'N/A' }}</td>
                                    <td>{{ $tag->vehicle->plate_number ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $tag->status === 'active' ? 'success' : ($tag->status === 'expired' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($tag->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $tag->expiry_date ? $tag->expiry_date->format('M d, Y') : 'No expiry' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" wire:click="openEditModal({{ $tag->id }})">Edit</button>
                                        <button class="btn btn-sm btn-danger" wire:click="delete({{ $tag->id }})" onclick="return confirm('Delete this RFID tag?')">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No RFID tags registered yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $tags->links() }}
            </div>
        </div>
    </div>

    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editMode ? 'Edit' : 'Register' }} RFID Tag</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">UID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('uid') is-invalid @enderror" wire:model="uid" placeholder="Scan with USB RFID reader" autofocus>
                            <small class="text-muted">Scan the RFID tag or enter UID manually</small>
                            @error('uid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">User <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_id') is-invalid @enderror" wire:model="user_id">
                                <option value="">Select User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vehicle</label>
                            <select class="form-select @error('vehicle_id') is-invalid @enderror" wire:model="vehicle_id">
                                <option value="">Select Vehicle (Optional)</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }} - {{ $vehicle->user->name ?? 'No owner' }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" wire:model="status">
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="suspended">Suspended</option>
                                <option value="lost">Lost</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" wire:model="expiry_date">
                            @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" wire:model="notes" rows="3"></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="save">{{ $editMode ? 'Update' : 'Register' }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

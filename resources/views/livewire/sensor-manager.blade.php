<div class="container mt-4">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-microchip me-2" style="color: #B22020;"></i>
                Sensor Management
            </h4>
            <p class="text-muted mb-0">Manage ESP32 sensors and parking space assignments</p>
        </div>

        {{-- Filter --}}
        <div>
            <select wire:model.live="filterStatus" class="form-select">
                <option value="all">All Sensors ({{ count($sensors) }})</option>
                <option value="assigned">Assigned Only</option>
                <option value="unassigned">Unassigned Only ({{ count($unassignedSensors) }})</option>
            </select>
        </div>
    </div>

    {{-- Unassigned Sensors Alert --}}
    @if(count($unassignedSensors) > 0)
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
            <div>
                <strong>{{ count($unassignedSensors) }} Unassigned Sensor(s)</strong>
                <p class="mb-0">These sensors are connected but not assigned to any parking space. Assign them below.</p>
            </div>
        </div>
    @endif

    {{-- Sensors Table --}}
    <div class="card shadow-sm">
        <div class="card-body">
            @if(count($sensors) === 0)
                <div class="text-center py-5">
                    <i class="fas fa-microchip text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted">No sensors found. Sensors will appear here once they connect to the system.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Sensor ID</th>
                                <th>Assigned Space</th>
                                <th>Parking Status</th>
                                <th>Firmware</th>
                                <th>Last Seen</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sensors as $sensor)
                                <tr>
                                    {{-- Status --}}
                                    <td>
                                        @if($sensor->status === 'active' && $sensor->space_code)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        @elseif($sensor->status === 'inactive')
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-pause-circle"></i> Inactive
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation-circle"></i> Unassigned
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Sensor ID (MAC:Index) --}}
                                    <td>
                                        <div>
                                            <code class="text-primary">{{ $sensor->mac_address }}</code>
                                            <span class="badge bg-secondary ms-1">Sensor {{ $sensor->sensor_index }}</span>
                                        </div>
                                    </td>

                                    {{-- Assigned Space --}}
                                    <td>
                                        @if($sensor->space_code)
                                            <span class="badge bg-primary" style="font-size: 0.9rem;">
                                                {{ $sensor->space_code }}
                                            </span>
                                            @if($sensor->parkingSpace)
                                                <br>
                                                <small class="text-muted">
                                                    {{ $sensor->parkingSpace->floor_level }}
                                                </small>
                                                @if($sensor->parkingSpace->malfunctioned)
                                                    <br>
                                                    <span class="badge mt-1" style="background: #FFDE42; color: black;">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Malfunctioned
                                                    </span>
                                                    @if($sensor->parkingSpace->malfunction_reason)
                                                        <br>
                                                        <small class="text-muted fst-italic">{{ $sensor->parkingSpace->malfunction_reason }}</small>
                                                    @endif
                                                @endif
                                            @endif
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>

                                    {{-- Parking Status --}}
                                    <td>
                                        @if($sensor->parkingSpace)
                                            @php $effectiveStatus = $sensor->parkingSpace->getEffectiveStatus(); @endphp
                                            @if($effectiveStatus === 'occupied')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-car me-1"></i>Occupied
                                                </span>
                                            @elseif($effectiveStatus === 'available')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>Available
                                                </span>
                                            @elseif($effectiveStatus === 'malfunctioned')
                                                <span class="badge" style="background: #FFDE42; color: black;">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Malfunctioned
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">—</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Firmware Version --}}
                                    <td>
                                        @if($sensor->firmware_version)
                                            <code class="text-success">{{ $sensor->firmware_version }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Last Seen --}}
                                    <td>
                                        @if($sensor->last_seen)
                                            <span class="text-muted">
                                                {{ $sensor->last_seen->diffForHumans() }}
                                            </span>
                                            @if($sensor->last_seen->gt(now()->subMinutes(5)))
                                                <i class="fas fa-circle text-success ms-1" style="font-size: 0.5rem;" title="Online"></i>
                                            @endif
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td>
                                        @if($sensor->identify_mode)
                                            <button wire:click="stopIdentify({{ $sensor->id }})"
                                                    class="btn btn-sm btn-warning"
                                                    title="Stop Identify (Yellow LED Blinking)">
                                                <i class="fas fa-stop-circle"></i>
                                            </button>
                                        @else
                                            <button wire:click="startIdentify({{ $sensor->id }})"
                                                    class="btn btn-sm btn-outline-warning"
                                                    title="Identify (Blink Yellow LED)">
                                                <i class="fas fa-lightbulb"></i>
                                            </button>
                                        @endif

                                        <button wire:click="openAssignModal({{ $sensor->id }})"
                                                class="btn btn-sm btn-primary"
                                                title="Assign/Reassign">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        @if($sensor->space_code && $sensor->parkingSpace)
                                            @if($sensor->parkingSpace->malfunctioned)
                                                <button wire:click="clearMalfunction({{ $sensor->id }})"
                                                        class="btn btn-sm btn-success"
                                                        title="Clear Malfunction Report">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            @endif
                                            @if(!$sensor->parkingSpace->malfunctioned)
                                                <button wire:click="openMalfunctionModal({{ $sensor->id }})"
                                                        class="btn btn-sm"
                                                        style="background: #FFDE42; color: #3d2e00;"
                                                        title="Flag as Malfunctioned">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </button>
                                            @endif

                                            <button wire:click="unassignSensor({{ $sensor->id }})"
                                                    wire:confirm="Are you sure you want to unassign this sensor?"
                                                    class="btn btn-sm btn-outline-warning"
                                                    title="Unassign">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        @endif

                                        <button wire:click="deleteSensor({{ $sensor->id }})"
                                                wire:confirm="Are you sure you want to delete this sensor? This action cannot be undone."
                                                class="btn btn-sm btn-danger"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Malfunction Modal --}}
    @if($showMalfunctionModal && $malfunctionSensor)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" wire:click.self="closeMalfunctionModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-header" style="background: #B22020; color: white; border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Flag as Malfunctioned — {{ $malfunctionSensor->space_code }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeMalfunctionModal"></button>
                    </div>
                    <div class="modal-body">

                        @if($malfunctionSensor->parkingSpace)
                            <p class="text-muted mb-3">
                                Floor: <strong>{{ $malfunctionSensor->parkingSpace->floor_level }}</strong>
                                &nbsp;|&nbsp; Sensor: <code>{{ $malfunctionSensor->mac_address }}</code>
                            </p>
                        @endif

                        @if($malfunctionSensor->parkingSpace?->malfunctioned)
                            @php $flaggedByAdmin = $malfunctionSensor->parkingSpace->malfunction_reported_by === auth()->user()->name; @endphp
                            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 14px; margin-bottom: 16px; font-size: 0.9rem; color: #856404;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                @if($flaggedByAdmin)
                                    <strong>You flagged this spot as malfunctioned.</strong><br>
                                    <span style="color: #666;">{{ \Carbon\Carbon::parse($malfunctionSensor->parkingSpace->malfunctioned_at)->diffForHumans() }}</span><br>
                                @else
                                    <strong>Flagged as malfunctioned by security.</strong><br>
                                    <span style="color: #666;">Reported by {{ $malfunctionSensor->parkingSpace->malfunction_reported_by }} — {{ \Carbon\Carbon::parse($malfunctionSensor->parkingSpace->malfunctioned_at)->diffForHumans() }}</span><br>
                                @endif
                                @if($malfunctionSensor->parkingSpace->malfunction_reason)
                                    <em style="color: #555;">{{ $malfunctionSensor->parkingSpace->malfunction_reason }}</em>
                                @endif
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fw-bold">Issue type <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model.live="malfunctionReason">
                                    <option value="">— Select an issue —</option>
                                    <option value="Sensor not detecting vehicles">Sensor not detecting vehicles</option>
                                    <option value="Sensor hardware malfunction">Sensor hardware malfunction</option>
                                    <option value="Sensor offline / no data">Sensor offline / no data</option>
                                    <option value="Spot under maintenance or repair">Spot under maintenance or repair</option>
                                    <option value="Other">Other</option>
                                </select>
                                @if($malfunctionReason === 'Other')
                                    <input type="text" class="form-control mt-2" wire:model="malfunctionCustomReason"
                                           placeholder="Please describe the issue...">
                                @endif
                                @if($malfunctionError)
                                    <div class="text-danger small mt-1">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $malfunctionError }}
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeMalfunctionModal">Cancel</button>
                        @if(!$malfunctionSensor->parkingSpace?->malfunctioned)
                            <button type="button" class="btn text-white" style="background: #B22020;" wire:click="reportMalfunction">
                                Flag Spot
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Assignment Modal --}}
    @if($showAssignModal && $selectedSensor)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-link me-2"></i>
                            Assign Sensor to Parking Space
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeAssignModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sensor Identifier</label>
                            <code class="d-block bg-light p-2 rounded">
                                {{ $selectedSensor->mac_address }} - Sensor {{ $selectedSensor->sensor_index }}
                            </code>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Parking Space Configuration <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                {{-- Floor --}}
                                <div class="col-4">
                                    <label for="floorNumber" class="form-label text-muted small">Floor</label>
                                    <select class="form-select" id="floorNumber" wire:model.live="floorNumber">
                                        <option value="">-</option>
                                        @foreach($this->getAvailableFloors() as $floor)
                                            <option value="{{ $floor }}">{{ $floor }}</option>
                                        @endforeach
                                    </select>
                                    @error('floorNumber')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Column --}}
                                <div class="col-4">
                                    <label for="columnCode" class="form-label text-muted small">Column</label>
                                    <select class="form-select" id="columnCode" wire:model.live="columnCode">
                                        <option value="">-</option>
                                        @foreach($this->getAvailableColumns() as $column)
                                            <option value="{{ $column }}">{{ $column }}</option>
                                        @endforeach
                                    </select>
                                    @error('columnCode')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Slot --}}
                                <div class="col-4">
                                    <label for="slotNumber" class="form-label text-muted small">Slot</label>
                                    <select class="form-select" id="slotNumber" wire:model="slotNumber" {{ !$columnCode ? 'disabled' : '' }}>
                                        <option value="">-</option>
                                        @if($columnCode)
                                            @foreach($this->getAvailableSlots() as $slot)
                                                <option value="{{ $slot }}">{{ $slot }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('slotNumber')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Preview --}}
                            @if($floorNumber && $columnCode && $slotNumber)
                                <div class="mt-2">
                                    <small class="text-muted">Space Code: </small>
                                    <span class="badge bg-primary">{{ $floorNumber }}{{ $columnCode }}{{ $slotNumber }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeAssignModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="assignSensor">
                            <i class="fas fa-check me-1"></i> Assign Sensor
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

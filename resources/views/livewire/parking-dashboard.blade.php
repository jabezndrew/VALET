<div wire:poll.3s="loadParkingData">
    <div class="container mt-4">
        <!-- Added: Total Available Parking Space Header -->
        <div class="text-center mb-2">
            <h2 class="fw-bold text-muted" style="font-size: 1.5rem; letter-spacing: 1px;">TOTAL AVAILABLE PARKING SPACE</h2>
        </div>
        
        <div class="campus-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold text-center flex-grow-1">USJ-R Quadricentennial Campus</h4>
                @if(auth()->user()->role !== 'user')
                <div>
                    <button wire:click="openVerifyModal" class="btn btn-outline-success">
                        <i class="fas fa-search me-1"></i> Verify Vehicle
                    </button>
                </div>
                @endif
            </div>
            
            <!-- Overall Stats with DYNAMIC circular progress bars -->
            <div class="row text-center mb-4">
                <div class="col-md-4">
                    @php
                        $availablePercent = $totalSpaces > 0 ? ($availableSpaces / $totalSpaces) * 100 : 0;
                    @endphp
                    <div class="stat-circle" style="background: conic-gradient(#28a745 {{ $availablePercent }}%, #e9ecef {{ $availablePercent }}%);">
                        <div class="stat-number">{{ $availableSpaces }}</div>
                    </div>
                    <h6 class="text-muted">Available</h6>
                </div>
                <div class="col-md-4">
                    @php
                        $occupiedPercent = $totalSpaces > 0 ? ($occupiedSpaces / $totalSpaces) * 100 : 0;
                    @endphp
                    <div class="stat-circle" style="background: conic-gradient(#dc3545 {{ $occupiedPercent }}%, #e9ecef {{ $occupiedPercent }}%);">
                        <div class="stat-number">{{ $occupiedSpaces }}</div>
                    </div>
                    <h6 class="text-muted">Occupied</h6>
                </div>
                <div class="col-md-4">
                    <div class="stat-circle" style="background: conic-gradient(#007bff 100%, #e9ecef 100%);">
                        <div class="stat-number">{{ $totalSpaces }}</div>
                    </div>
                    <h6 class="text-muted">Total Spots</h6>
                </div>
            </div>

            <!-- Overall Occupancy -->
            <div class="text-center mb-4">
                <span class="text-muted">Overall Occupancy</span>
                <div class="h5 fw-bold">{{ round($totalSpaces > 0 ? ($occupiedSpaces / $totalSpaces) * 100 : 0) }}% Full</div>
            </div>
        </div>

        <!-- Select Floor Section -->
        <div class="floor-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold">Select Floor</h4>
                <span class="live-badge">LIVE</span>
            </div>

            <div class="row">
                @foreach($floorStats as $floorStat)
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="floor-card {{ $floorStat['has_data'] ? '' : 'no-data' }}" 
                             wire:click="goToFloor('{{ $floorStat['floor_level'] }}')" 
                             style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 fw-bold">{{ $floorStat['floor_level'] }}</h5>
                                @if(!$floorStat['has_data'])
                                    <span class="no-data-badge">NO DATA</span>
                                @elseif($floorStat['available'] == 0)
                                    <span class="full-badge">FULL</span>
                                @elseif($floorStat['available'] <= 5)
                                    <span class="limited-badge">LIMITED</span>
                                @else
                                    <span class="available-badge">AVAILABLE</span>
                                @endif
                            </div>
                            
                            @if($floorStat['has_data'])
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="floor-number available-color">{{ $floorStat['available'] }}</div>
                                        <small class="text-muted">Available</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="floor-number occupied-color">{{ $floorStat['occupied'] }}</div>
                                        <small class="text-muted">Occupied</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="floor-number total-color">{{ $floorStat['total'] }}</div>
                                        <small class="text-muted">Total Spots</small>
                                    </div>
                                </div>
                                <div class="progress mb-2">
                                    @php
                                        $percentage = $floorStat['total'] > 0 ? ($floorStat['occupied'] / $floorStat['total']) * 100 : 0;
                                        $progressClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                                    @endphp
                                    <div class="progress-bar {{ $progressClass }}" style="width: {{ $percentage }}%"></div>
                                </div>
                                <small class="text-muted">{{ round($percentage) }}% Full</small>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-database text-muted mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                    <p class="text-muted mb-0">No data available yet</p>
                                    <small class="text-muted">Sensors not configured</small>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $selectedFloor }} Details
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <div class="row text-center mb-4">
                        <div class="col-3">
                            <div class="stat-number text-secondary">{{ $selectedFloorStats['total'] ?? 0 }}</div>
                            <small class="text-muted">Total Spots</small>
                        </div>
                        <div class="col-3">
                            <div class="stat-number text-success">{{ $selectedFloorStats['available'] ?? 0 }}</div>
                            <small class="text-muted">Available</small>
                        </div>
                        <div class="col-3">
                            <div class="stat-number text-danger">{{ $selectedFloorStats['occupied'] ?? 0 }}</div>
                            <small class="text-muted">Occupied</small>
                        </div>
                        <div class="col-3">
                            <div class="stat-number text-info">{{ $selectedFloorStats['occupancy_rate'] ?? 0 }}%</div>
                            <small class="text-muted">Occupancy</small>
                        </div>
                    </div>

                    <div class="row">
                        @forelse($selectedFloorSpaces as $space)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="parking-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold">Sensor {{ $space->sensor_id }}</h6>
                                        <span class="status-badge-small {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                                            {{ $space->is_occupied ? 'Occupied' : 'Available' }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="{{ $this->getSpaceIcon($space) }} me-2"></i>
                                        <span class="small">{{ $this->getStatusText($space) }}</span>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $this->getRelativeTime($space->updated_at) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-muted">No parking spaces found for this floor.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Close</button>
                </div>
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
</div>
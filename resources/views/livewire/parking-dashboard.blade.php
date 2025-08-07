<div wire:poll.3s="loadParkingData">
    <div id="alert-container"></div>
    
    <div class="container mt-4">
        <!-- Header -->
        <div class="text-center mb-2">
            <h2 class="fw-bold text-muted" style="font-size: 1.5rem; letter-spacing: 1px;">TOTAL AVAILABLE PARKING SPACE</h2>
        </div>
        
        <div class="campus-section">
            <div class="row align-items-center mb-4">
                <!-- Left spacer column -->
                <div class="col-auto" style="width: 160px;">
                    <!-- Empty space to balance the right button -->
                </div>
                
                <!-- Centered title column -->
                <div class="col text-center">
                    <h4 class="mb-0 fw-bold">USJ-R Quadricentennial Campus</h4>
                </div>
                
                <!-- Right button column -->
                <div class="col-auto" style="width: 160px;">
                    @if(auth()->user()->role !== 'user')
                    <div class="text-end">
                        <button wire:click="openVerifyModal" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-search me-1"></i> Verify Vehicle
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Overall Stats with circular progress bars -->
            @php
                $availablePercent = $totalSpaces > 0 ? ($availableSpaces / $totalSpaces) * 100 : 0;
                $occupiedPercent = $totalSpaces > 0 ? ($occupiedSpaces / $totalSpaces) * 100 : 0;
            @endphp
            
            <div class="row text-center mb-4">
                <div class="col-md-4">
                    <div class="stat-circle" style="background: conic-gradient(#28a745 {{ $availablePercent }}%, #e9ecef {{ $availablePercent }}%);">
                        <div class="stat-number">{{ $availableSpaces }}</div>
                    </div>
                    <h6 class="text-muted">Available</h6>
                </div>
                <div class="col-md-4">
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
                <div class="h5 fw-bold">{{ $occupancyRate }}% Full</div>
                @if($lastUpdate)
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>Last updated: {{ $lastUpdate }}
                </small>
                @endif
            </div>
        </div>

        <!-- Filter Section -->
        @if(count($this->getAvailableFloors()) > 1)
        <div class="mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Filter by Floor</label>
                            <select wire:model.live="floorFilter" class="form-select">
                                <option value="all">All Floors</option>
                                @foreach($this->getAvailableFloors() as $floor)
                                    <option value="{{ $floor }}">{{ $floor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 text-center">
                            <button wire:click="refreshNow" class="btn btn-outline-primary me-2">
                                <i class="fas fa-sync-alt me-1"></i> Refresh Now
                            </button>
                            @if(config('app.debug'))
                            <button wire:click="debugData" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-bug me-1"></i> Debug
                            </button>
                            @endif
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="form-check form-switch">
                                <input wire:model.live="isAutoRefreshEnabled" 
                                       wire:click="toggleAutoRefresh"
                                       class="form-check-input" type="checkbox" id="autoRefresh">
                                <label class="form-check-label" for="autoRefresh">
                                    Auto-refresh
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Floor Selection Section -->
        <div class="floor-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold">Select Floor</h4>
                <span class="live-badge">
                    <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>LIVE
                </span>
            </div>

            <div class="row">
                @foreach($floorStats as $floorStat)
                    @php
                        $percentage = $floorStat['total'] > 0 ? ($floorStat['occupied'] / $floorStat['total']) * 100 : 0;
                        $progressClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                        $badgeClass = match(true) {
                            !$floorStat['has_data'] => 'no-data-badge',
                            $floorStat['available'] == 0 => 'full-badge',
                            $floorStat['available'] <= 5 => 'limited-badge',
                            default => 'available-badge'
                        };
                        $badgeText = match(true) {
                            !$floorStat['has_data'] => 'NO DATA',
                            $floorStat['available'] == 0 => 'FULL',
                            $floorStat['available'] <= 5 => 'LIMITED',
                            default => 'AVAILABLE'
                        };
                    @endphp
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="floor-card {{ $floorStat['has_data'] ? '' : 'no-data' }}" 
                             @if($floorStat['has_data']) 
                                wire:click="goToFloor('{{ $floorStat['floor_level'] }}')" 
                                style="cursor: pointer;" 
                                title="Click to view {{ $floorStat['floor_level'] }} details"
                             @endif>
                             
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 fw-bold">{{ $floorStat['floor_level'] }}</h5>
                                <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
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
                                    <div class="progress-bar {{ $progressClass }}" 
                                         style="width: {{ $percentage }}%"
                                         role="progressbar" 
                                         aria-valuenow="{{ round($percentage) }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">{{ round($percentage) }}% Full</small>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-database text-muted mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                    <p class="text-muted mb-1">No data available yet</p>
                                    <small class="text-muted">Sensors not configured</small>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Fallback if no floor stats for some reason --}}
                @if(empty($floorStats))
                    @foreach(['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'] as $floor)
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="floor-card no-data">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 fw-bold">{{ $floor }}</h5>
                                <span class="no-data-badge">NO DATA</span>
                            </div>
                            <div class="text-center py-4">
                                <i class="fas fa-database text-muted mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                <p class="text-muted mb-1">No data available yet</p>
                                <small class="text-muted">Sensors not configured</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Floor Details Modal -->
    @if($showModal && $selectedFloorData)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-building me-2"></i>{{ $selectedFloor }} Details
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <!-- Floor Statistics -->
                    <div class="row text-center mb-4">
                        <div class="col-3">
                            <div class="stat-number text-secondary">{{ $selectedFloorData['stats']['total'] ?? 0 }}</div>
                            <small class="text-muted">Total Spots</small>
                        </div>
                        <div class="col-3">
                            <div class="stat-number text-success">{{ $selectedFloorData['stats']['available'] ?? 0 }}</div>
                            <small class="text-muted">Available</small>
                        </div>
                        <div class="col-3">
                            <div class="stat-number text-danger">{{ $selectedFloorData['stats']['occupied'] ?? 0 }}</div>
                            <small class="text-muted">Occupied</small>
                        </div>
                        <div class="col-3">
                            <div class="stat-number text-info">{{ $selectedFloorData['stats']['occupancy_rate'] ?? 0 }}%</div>
                            <small class="text-muted">Occupancy</small>
                        </div>
                    </div>

                    <!-- Individual Parking Spaces -->
                    <div class="row">
                        @forelse($selectedFloorData['spaces'] ?? [] as $space)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="parking-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold">{{ $this->getSensorDisplayName($space->sensor_id) }}</h6>
                                        <span class="status-badge-small {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                                            {{ $space->is_occupied ? 'Occupied' : 'Available' }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="{{ $this->getSpaceIcon($space) }} me-2"></i>
                                        <span class="small">{{ $this->getStatusText($space) }}</span>
                                    </div>
                                    @if(isset($space->section) && $space->section)
                                    <div class="small text-muted mb-1">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Section {{ $space->section }}
                                    </div>
                                    @endif
                                    <div class="small text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Updated {{ $this->getRelativeTime($space->updated_at) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-muted">No parking spaces found for {{ $selectedFloor }}.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
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
                        <i class="fas fa-search me-2"></i>Verify Vehicle
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeVerifyModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">RFID Tag</label>
                        <input wire:model="verifyRfid" 
                               type="text" 
                               class="form-control" 
                               placeholder="Enter RFID tag to verify..." 
                               wire:keydown.enter="verifyVehicle"
                               maxlength="50">
                        @error('verifyRfid') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    @if($verifyResult)
                        <div class="alert alert-{{ $verifyResult['color'] }} mt-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-{{ 
                                    $verifyResult['status'] === 'Active' ? 'check-circle' : 
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
                    <button type="button" class="btn btn-success" wire:click="verifyVehicle" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="verifyVehicle">
                            <i class="fas fa-search me-1"></i> Verify
                        </span>
                        <span wire:loading wire:target="verifyVehicle">
                            <i class="fas fa-spinner fa-spin me-1"></i> Verifying...
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

            Livewire.on('debug-data', (data) => {
                console.log('VALET Debug Data:', data);
            });
        }); 
    </script>
</div>
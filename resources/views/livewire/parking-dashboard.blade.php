<div wire:poll.3s="loadParkingData">
    <!-- Campus Dashboard -->
    <div class="container mt-4">
        <div class="campus-section">
            <h4 class="mb-4 fw-bold text-center">USJ-R Quadricentennial Campus</h4>
            
            <!-- Overall Stats -->
            <div class="row text-center mb-4">
                <div class="col-md-4">
                    <div class="stat-circle available-circle">
                        <div class="stat-number">{{ $availableSpaces }}</div>
                    </div>
                    <h6 class="text-muted">Available</h6>
                </div>
                <div class="col-md-4">
                    <div class="stat-circle occupied-circle">
                        <div class="stat-number">{{ $occupiedSpaces }}</div>
                    </div>
                    <h6 class="text-muted">Occupied</h6>
                </div>
                <div class="col-md-4">
                    <div class="stat-circle total-circle">
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
                <!-- 1st Floor -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="floor-card" wire:click="selectFloor('1st Floor')" style="cursor: pointer;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold">1st Floor</h5>
                            <span class="available-badge">NO SENSORS</span>
                        </div>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="floor-number available-color">0</div>
                                <small class="text-muted">Available</small>
                            </div>
                            <div class="col-4">
                                <div class="floor-number occupied-color">0</div>
                                <small class="text-muted">Occupied</small>
                            </div>
                            <div class="col-4">
                                <div class="floor-number total-color">0</div>
                                <small class="text-muted">Total Spots</small>
                            </div>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-secondary" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">No sensors installed</small>
                    </div>
                </div>

                <!-- 2nd Floor -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="floor-card" wire:click="selectFloor('2nd Floor')" style="cursor: pointer;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold">2nd Floor</h5>
                            <span class="available-badge">NO SENSORS</span>
                        </div>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="floor-number available-color">0</div>
                                <small class="text-muted">Available</small>
                            </div>
                            <div class="col-4">
                                <div class="floor-number occupied-color">0</div>
                                <small class="text-muted">Occupied</small>
                            </div>
                            <div class="col-4">
                                <div class="floor-number total-color">0</div>
                                <small class="text-muted">Total Spots</small>
                            </div>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-secondary" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">No sensors installed</small>
                    </div>
                </div>

                <!-- 3rd Floor -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="floor-card" wire:click="selectFloor('3rd Floor')" style="cursor: pointer;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold">3rd Floor</h5>
                            <span class="available-badge">NO SENSORS</span>
                        </div>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="floor-number available-color">0</div>
                                <small class="text-muted">Available</small>
                            </div>
                            <div class="col-4">
                                <div class="floor-number occupied-color">0</div>
                                <small class="text-muted">Occupied</small>
                            </div>
                            <div class="col-4">
                                <div class="floor-number total-color">0</div>
                                <small class="text-muted">Total Spots</small>
                            </div>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-secondary" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">No sensors installed</small>
                    </div>
                </div>

                <!-- Dynamic Floors from Database -->
                @foreach($floorStats as $floorStat)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="floor-card" wire:click="selectFloor('{{ $floorStat['floor_level'] }}')" style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 fw-bold">{{ $floorStat['floor_level'] }}</h5>
                                @if($floorStat['available'] == 0)
                                    <span class="full-badge">FULL</span>
                                @elseif($floorStat['available'] <= 5)
                                    <span class="limited-badge">LIMITED</span>
                                @else
                                    <span class="available-badge">AVAILABLE</span>
                                @endif
                            </div>
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
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Floor Detail Modal -->
    @if($showModal)
    <div class="modal-overlay" wire:click="closeModal">
        <div class="modal-content" wire:click.stop>
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="mb-0 fw-bold">{{ $selectedFloor }}</h4>
                <button type="button" class="btn-close" wire:click="closeModal">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Floor Stats -->
                <div class="row mb-4">
                    <div class="col-3 text-center">
                        <div class="modal-stat-number total-color">{{ $selectedFloorStats['total'] ?? 0 }}</div>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="modal-stat-number available-color">{{ $selectedFloorStats['available'] ?? 0 }}</div>
                        <small class="text-muted">Available</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="modal-stat-number occupied-color">{{ $selectedFloorStats['occupied'] ?? 0 }}</div>
                        <small class="text-muted">Occupied</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="modal-stat-number" style="color: #6f42c1;">{{ $selectedFloorStats['occupancy_rate'] ?? 0 }}%</div>
                        <small class="text-muted">Full</small>
                    </div>
                </div>

                <!-- Parking Spaces -->
                @if(count($selectedFloorSpaces) > 0)
                <div class="parking-spaces-grid">
                    <h6 class="mb-3 fw-bold">Parking Spaces</h6>
                    <div class="row">
                        @foreach($selectedFloorSpaces as $space)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="modal-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">
                                        <i class="{{ $this->getSpaceIcon((array)$space) }}"></i>
                                        #{{ $space->sensor_id }}
                                    </h6>
                                    <span class="status-badge-mini {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                                        {{ $space->is_occupied ? 'OCCUPIED' : 'AVAILABLE' }}
                                    </span>
                                </div>
                                <div class="text-center">
                                    <div class="distance-mini">{{ $space->distance_cm ?? 'N/A' }}cm</div>
                                    <small class="text-muted">{{ $space->updated_at->format('H:i:s') }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-tools fa-3x mb-3 text-muted"></i>
                    <h5>No Sensors Installed</h5>
                    <p class="text-muted">{{ $selectedFloor }} sensors are not yet installed.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
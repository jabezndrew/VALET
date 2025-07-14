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
                    <div class="floor-card" wire:click="$set('floorFilter', '1st Floor')" style="cursor: pointer;">
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
                    <div class="floor-card" wire:click="$set('floorFilter', '2nd Floor')" style="cursor: pointer;">
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
                    <div class="floor-card" wire:click="$set('floorFilter', '3rd Floor')" style="cursor: pointer;">
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
                        <div class="floor-card" wire:click="$set('floorFilter', '{{ $floorStat['floor_level'] }}')" style="cursor: pointer;">
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

        <!-- Selected Floor Details -->
        @if($floorFilter !== 'all' && count($spaces) > 0)
        <div class="selected-floor-section mt-5">
            <h4 class="mb-4 fw-bold">{{ $floorFilter }} - Parking Spaces</h4>
            <div class="row">
                @foreach($spaces as $space)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                    <div class="parking-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="{{ $this->getSpaceIcon((array)$space) }}"></i>
                                Sensor #{{ $space->sensor_id }}
                            </h6>
                            <span class="status-badge-small {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                                {{ $space->is_occupied ? 'OCCUPIED' : 'AVAILABLE' }}
                            </span>
                        </div>
                        <div class="text-center">
                            <div class="distance-reading">{{ $space->distance_cm ?? 'N/A' }}cm</div>
                            <small class="text-muted">{{ $space->updated_at->format('H:i:s') }}</small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @elseif($floorFilter !== 'all')
        <div class="selected-floor-section mt-5 text-center">
            <h4 class="mb-3">{{ $floorFilter }}</h4>
            <div class="alert alert-info">
                <i class="fas fa-tools fa-3x mb-3"></i>
                <h5>No Sensors Installed Yet</h5>
                <p>{{ $floorFilter }} sensors are not yet installed. Please check back later.</p>
            </div>
        </div>
        @endif
    </div>
</div>
<!-- Replace entire resources/views/livewire/parking-dashboard.blade.php -->
<div wire:poll.3s="loadParkingData">
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
</div>
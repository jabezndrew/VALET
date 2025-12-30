<div wire:poll.5s="loadParkingData">
    <div class="container-fluid px-4 py-3" style="background: #f5f5f5; min-height: 100vh;">

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="campus-section" style="background: linear-gradient(135deg, #B22020 0%, #8B0000 100%); color: white; padding: 30px;">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ asset('images/valet-logo.jpg') }}" alt="VALET Logo" style="width: 80px; height: 80px; border-radius: 50%; border: 4px solid white;">
                                <div>
                                    <h1 class="mb-0" style="font-size: 2.5rem; font-weight: 700;">VALET Parking System</h1>
                                    <p class="mb-0" style="font-size: 1.2rem; opacity: 0.9;">USJ-R Quadricentennial Campus</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="live-badge">
                                <i class="fas fa-circle text-white me-1" style="font-size: 0.5rem;"></i>LIVE
                            </span>
                            @if($lastUpdate)
                            <div class="mt-2">
                                <i class="fas fa-clock me-1"></i>Updated: {{ $lastUpdate }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floor Selection and Stats -->
        <div class="campus-section mb-4">
            <!-- Floor Selector -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-bold text-muted mb-2" style="font-size: 1.1rem;">
                        <i class="fas fa-layer-group me-2"></i>Select Floor Level
                    </label>
                    <div class="btn-group w-100 floor-selector" role="group">
                        @foreach(['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'] as $floor)
                            @php
                                $hasData = $this->hasFloorData($floor);
                            @endphp
                            <button wire:click="changeFloor('{{ $floor }}')"
                                    class="btn btn-floor-select {{ $selectedFloor === $floor ? 'active' : '' }} {{ !$hasData ? 'disabled' : '' }}"
                                    {{ !$hasData ? 'disabled' : '' }}
                                    style="font-size: 1.2rem; padding: 18px 25px;"
                                    title="{{ !$hasData ? 'No data available for this floor' : 'View ' . $floor }}">
                                <i class="fas fa-building me-2"></i>{{ $floor }}
                                @if(!$hasData)
                                    <i class="fas fa-ban ms-2" style="font-size: 0.8rem; opacity: 0.5;"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Floor Statistics -->
            <div class="row text-center mb-4">
                <div class="col-md-3">
                    <div class="stat-circle" style="background: conic-gradient(#007bff 100%, #e9ecef 100%); width: 140px; height: 140px;">
                        <div class="stat-number" style="width: 105px; height: 105px; font-size: 3rem;">{{ $floorStats['total'] ?? 0 }}</div>
                    </div>
                    <h6 class="text-muted mt-2" style="font-size: 1.1rem; font-weight: 600;">Total Spots</h6>
                </div>
                <div class="col-md-3">
                    <div class="stat-circle" style="background: conic-gradient(#28a745 {{ $floorStats['total'] > 0 ? ($floorStats['available'] / $floorStats['total']) * 100 : 0 }}%, #e9ecef {{ $floorStats['total'] > 0 ? ($floorStats['available'] / $floorStats['total']) * 100 : 0 }}%); width: 140px; height: 140px;">
                        <div class="stat-number" style="width: 105px; height: 105px; font-size: 3rem;">{{ $floorStats['available'] ?? 0 }}</div>
                    </div>
                    <h6 class="text-muted mt-2" style="font-size: 1.1rem; font-weight: 600;">Available</h6>
                </div>
                <div class="col-md-3">
                    <div class="stat-circle" style="background: conic-gradient(#dc3545 {{ $floorStats['total'] > 0 ? ($floorStats['occupied'] / $floorStats['total']) * 100 : 0 }}%, #e9ecef {{ $floorStats['total'] > 0 ? ($floorStats['occupied'] / $floorStats['total']) * 100 : 0 }}%); width: 140px; height: 140px;">
                        <div class="stat-number" style="width: 105px; height: 105px; font-size: 3rem;">{{ $floorStats['occupied'] ?? 0 }}</div>
                    </div>
                    <h6 class="text-muted mt-2" style="font-size: 1.1rem; font-weight: 600;">Occupied</h6>
                </div>
                <div class="col-md-3">
                    <div class="stat-circle" style="background: conic-gradient(#fd7e14 {{ $floorStats['occupancy_rate'] ?? 0 }}%, #e9ecef {{ $floorStats['occupancy_rate'] ?? 0 }}%); width: 140px; height: 140px;">
                        <div class="stat-number" style="width: 105px; height: 105px; font-size: 3rem;">{{ $floorStats['occupancy_rate'] ?? 0 }}%</div>
                    </div>
                    <h6 class="text-muted mt-2" style="font-size: 1.1rem; font-weight: 600;">Occupancy</h6>
                </div>
            </div>
        </div>

        <!-- Parking Map Visualization -->
        <div class="campus-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold" style="color: #3A3A3C; font-size: 1.8rem;">
                    <i class="fas fa-parking me-2" style="color: #B22020;"></i>
                    {{ $selectedFloor }} - Parking Layout
                </h4>
            </div>

            @if($parkingSpaces->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-database text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted">No parking spaces configured for {{ $selectedFloor }}</p>
                </div>
            @else
                <!-- Parking Map Layout -->
                <div class="parking-map-wrapper">
                    <div class="parking-map-container" id="parkingMapContainer">
                    <!-- Facilities -->
                    <div class="facility elevator" style="left: 675px; top: 315px; width: 90px; height: 60px;">
                        <span>Elevator</span>
                    </div>
                    <div class="facility elevator" style="left: 690px; top: 787px; width: 127px; height: 60px;">
                        <span>Elevator</span>
                    </div>
                    <div class="facility elevator rotated-left" style="left: 985px; top: 742px; width: 127px; height: 60px;">
                        <span>Elevator</span>
                    </div>
                    <div class="facility stairs rotated-left" style="left: 45px; top: 337px; width: 120px; height: 45px;">
                        <span>STAIRS</span>
                    </div>
                    <div class="facility entrance" style="right: 135px; top: 390px; width: 135px; height: 60px;">
                        <span>Entrance</span>
                    </div>
                    <div class="facility exit-sign" style="right: 180px; top: 600px; width: 150px; height: 67px;">
                        <span>EXIT</span>
                    </div>

                    <!-- Direction Arrows -->
                    <i class="fas fa-arrow-up arrow" style="right: 210px; top: 255px;"></i>
                    <i class="fas fa-arrow-left arrow" style="right: 240px; top: 180px;"></i>
                    <i class="fas fa-arrow-left arrow" style="right: 345px; top: 180px;"></i>
                    <i class="fas fa-arrow-down arrow" style="right: 405px; top: 240px;"></i>
                    <i class="fas fa-arrow-down arrow" style="right: 405px; top: 390px;"></i>
                    <i class="fas fa-arrow-left arrow" style="right: 525px; top: 427px;"></i>
                    <i class="fas fa-arrow-left arrow" style="right: 705px; top: 427px;"></i>
                    <i class="fas fa-arrow-left arrow" style="right: 900px; top: 427px;"></i>
                    <i class="fas fa-arrow-down arrow" style="right: 975px; top: 585px;"></i>
                    <i class="fas fa-arrow-right arrow" style="right: 870px; top: 705px;"></i>
                    <i class="fas fa-arrow-right arrow" style="right: 645px; top: 705px;"></i>
                    <i class="fas fa-arrow-right arrow" style="right: 420px; top: 705px;"></i>
                    <i class="fas fa-arrow-down arrow" style="right: 330px; top: 765px;"></i>
                    <i class="fas fa-arrow-down arrow" style="right: 330px; top: 930px;"></i>
                    <i class="fas fa-arrow-down arrow" style="right: 330px; top: 1125px;"></i>
                    <i class="fas fa-arrow-right arrow" style="right: 270px; top: 1260px;"></i>
                    <i class="fas fa-arrow-up arrow" style="right: 210px; top: 1125px;"></i>
                    <i class="fas fa-arrow-up arrow" style="right: 210px; top: 930px;"></i>
                    <i class="fas fa-arrow-up arrow" style="right: 210px; top: 735px;"></i>
                    <i class="fas fa-arrow-right arrow" style="right: 135px; top: 615px;"></i>

                    <!-- Parking Spots -->
                    @foreach($parkingSpaces as $space)
                        @php
                            $hasAssignedSensor = $space->sensorAssignment !== null;
                            $slotName = $space->slot_name ?? '';
                            $x = $space->x_position ?? 0;
                            $y = $space->y_position ?? 0;
                            $rotation = $space->rotation ?? 0;
                            $isOccupied = $space->is_occupied;
                            $slotWidth = 60;
                            $slotHeight = 85;
                        @endphp

                        @if($hasAssignedSensor && $isOccupied)
                            <!-- Occupied Spot: Show Car Image -->
                            <div class="parking-spot-box occupied"
                                 style="left: {{ $x }}px; top: {{ $y }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                        border: 4px solid #dc3545; background: rgba(220, 53, 69, 0.1);
                                        display: flex; align-items: center; justify-content: center;
                                        border-radius: 8px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
                                        position: absolute; pointer-events: none;
                                        transform: rotate({{ $rotation }}deg); transform-origin: center center;">
                                <img src="{{ asset('images/car_top.png') }}" alt="Car" style="max-width: 90%; max-height: 90%;">
                            </div>
                        @elseif($hasAssignedSensor && !$isOccupied)
                            <!-- Available Spot with Sensor: Show Label (Green) -->
                            <div class="parking-spot-box available"
                                 style="left: {{ $x }}px; top: {{ $y }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                        border: 4px solid #28a745; background: linear-gradient(135deg, #2ed573 0%, #28a745 100%);
                                        color: white; font-size: 22px; font-weight: 700;
                                        display: flex; align-items: center; justify-content: center;
                                        border-radius: 8px; box-shadow: 0 4px 12px rgba(46, 213, 115, 0.4);
                                        position: absolute; pointer-events: none;
                                        transform: rotate({{ $rotation }}deg); transform-origin: center center;">
                                {{ $slotName }}
                            </div>
                        @else
                            <!-- No Sensor Assigned: Show Label (Gray/Inactive) -->
                            <div class="parking-spot-box inactive"
                                 style="left: {{ $x }}px; top: {{ $y }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                        border: 3px dashed #6c757d; background: rgba(108, 117, 125, 0.2);
                                        color: #6c757d; font-size: 18px; font-weight: 600;
                                        display: flex; align-items: center; justify-content: center;
                                        border-radius: 8px; opacity: 0.6;
                                        position: absolute; pointer-events: none;
                                        transform: rotate({{ $rotation }}deg); transform-origin: center center;">
                                {{ $slotName }}
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parking-map-layout.css') }}">
<style>
    /* Additional styles for public display */
    .live-badge {
        font-size: 1.3rem !important;
        padding: 10px 20px !important;
    }

    .stat-circle {
        margin: 0 auto;
    }

    /* Larger text for public viewing */
    h1, h4, h6 {
        letter-spacing: 0.5px;
    }
</style>
@endpush

<div wire:poll.3s="loadParkingData">
    <div class="container-fluid p-0" style="background: white; min-height: 100vh;">

        <!-- Main Content -->
        <div class="row g-0">
            <div class="col-12 position-relative">

                <!-- Floor Selector - Top Right Cards -->
                <div style="position: absolute; top: 30px; right: 30px; z-index: 1000; width: 320px;">
                    <div style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 25px; box-shadow: 0 8px 30px rgba(0,0,0,0.2);">
                        <h6 style="font-size: 1.3rem; font-weight: 700; color: #3A3A3C; margin-bottom: 20px;">
                            Select Floor
                        </h6>

                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            @foreach($allFloorStats as $floor => $stats)
                                @php
                                    $hasData = $this->hasFloorData($floor);
                                @endphp

                                <div
                                    wire:key="floor-card-{{ $floor }}"
                                    wire:click="{{ $hasData ? 'changeFloor(\'' . $floor . '\')' : '' }}"
                                    style="
                                        background: {{ $selectedFloor === $floor
                                            ? 'linear-gradient(135deg, #B22020 0%, #8B0000 100%)'
                                            : 'white' }};
                                        border: 3px solid {{ $selectedFloor === $floor ? '#B22020' : '#e0e0e0' }};
                                        border-radius: 12px;
                                        padding: 20px;
                                        opacity: {{ $hasData ? '1' : '0.4' }};
                                        transition: all 0.3s ease;
                                        box-shadow: {{ $selectedFloor === $floor
                                            ? '0 6px 18px rgba(178, 32, 32, 0.4)'
                                            : '0 3px 10px rgba(0,0,0,0.15)' }};
                                        cursor: {{ $hasData ? 'pointer' : 'not-allowed' }};
                                        {{ !$hasData ? 'pointer-events: none;' : '' }}
                                    "
                                    title="{{ !$hasData ? 'No data available for this floor' : 'View ' . $floor }}"
                                >
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div style="flex: 1;">
                                            <div style="font-weight:700;font-size:1.3rem;color:{{ $selectedFloor === $floor ? 'white' : '#3A3A3C' }};">
                                                {{ $floor }}
                                            </div>
                                            <small style="color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#999' }};">
                                                Total: {{ $stats['total'] }}
                                            </small>
                                        </div>

                                        <div class="d-flex gap-4">
                                            <div class="text-center">
                                                <div style="font-size:2rem;font-weight:700;color:#28a745;">
                                                    {{ $stats['available'] }}
                                                </div>
                                                <small style="color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#2e2d2dff' }};">Available</small>
                                            </div>
                                            <div class="text-center">
                                                <div style="font-size:2rem;font-weight:700;color:#dc3545;">
                                                    {{ $stats['occupied'] }}
                                                </div>
                                                <small style="color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#2e2d2dff' }};">Occupied</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Parking Map -->
                <div class="map-section">
                    @if($parkingSpaces->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-database text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted">No parking spaces configured for {{ $selectedFloor }}</p>
                        </div>
                    @else
                        <div class="parking-map-wrapper">
                            <div class="parking-map-container">

                                <!-- Facilities -->
                                <div class="facility elevator" style="left: 675px; top: 315px; width: 127px; height: 60px;">
                                    <span>ELEVATOR</span>
                                </div>
                                <!-- Elevator 2 -->
                                 <div class="facility elevator" style="left: 690px; top: 787px; width: 127px; height: 60px;">
                                    <span>ELEVATOR</span> </div>
                                <!-- Elevator 3 -->
                                  <div class="facility elevator rotated-left" style="left: 1005px; top: 742px; width: 127px; height: 60px;">
                                    <span>ELEVATOR</span> </div>
                                <!-- Stairs --> <div class="facility stairs rotated-left" style="left: 45px; top: 337px; width: 127px; height: 60px;">
                                    <span>STAIRS</span> </div>
                                <div class="facility stairs rotated-left" style="left: 45px; top: 337px; width: 127px; height: 60px;">
                                    <span>STAIRS</span>
                                </div>

                                <div class="facility entrance" style="right: 135px; top: 390px; width: 135px; height: 60px;">
                                    <span>ENTRANCE</span>
                                </div>

                                <div class="facility exit-sign">
                                    <span>EXIT</span>
                                </div>

                                <!-- Parking Spots -->
                                @foreach($parkingSpaces as $space)
                                    @php
                                        $hasAssignedSensor = $space->sensorAssignment !== null;
                                        $slotName = $space->slot_name ?? '';
                                        $x = $space->x_position ?? 0;
                                        $y = $space->y_position ?? 0;
                                        $rotation = $space->rotation ?? 0;
                                        $isOccupied = $space->is_occupied;
                                    @endphp

                                    <div class="parking-spot-box {{ $hasAssignedSensor ? ($isOccupied ? 'occupied' : 'available') : 'inactive' }}"
                                         style="
                                            left: {{ $x }}px;
                                            top: {{ $y }}px;
                                            width: 60px;
                                            height: 85px;
                                            font-size: 22px;
                                            transform: rotate({{ $rotation }}deg);
                                            pointer-events: none;
                                         "
                                    >
                                        {{ $slotName }}
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/public-parking-display.css?v=1.3') }}">
@endpush

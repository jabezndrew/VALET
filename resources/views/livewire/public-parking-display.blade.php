<div>
    <div class="container-fluid p-0" style="background: white; min-height: 100vh;">

        <!-- Main Content: Map with Floor Selector -->
        <div class="row g-0">
            <div class="col-12 position-relative">

                <!-- Floor Selector - Top Right Cards -->
                <div style="position: absolute; top: 30px; right: 30px; z-index: 1000; width: 320px;">
                    <div style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 25px; box-shadow: 0 8px 30px rgba(0,0,0,0.2);">
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
                                            <div style="
                                                font-weight: 700;
                                                font-size: 1.3rem;
                                                color: {{ $selectedFloor === $floor ? 'white' : '#3A3A3C' }};
                                                margin-bottom: 5px;
                                            ">
                                                {{ $floor }}
                                            </div>
                                        </div>

                                        <div class="d-flex gap-4">
                                            <div class="text-center">
                                                <div style="
                                                    font-size: 2rem;
                                                    font-weight: 700;
                                                    color: {{ $selectedFloor === $floor ? '#90EE90' : '#28a745' }};
                                                    line-height: 1;
                                                ">
                                                    {{ $stats['available'] }}
                                                </div>
                                                <div style="
                                                    font-size: 0.75rem;
                                                    color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#666' }};
                                                    margin-top: 4px;
                                                    font-weight: 600;
                                                ">
                                                    Available
                                                </div>
                                            </div>

                                            <div class="text-center">
                                                <div style="
                                                    font-size: 2rem;
                                                    font-weight: 700;
                                                    color: {{ $selectedFloor === $floor ? '#FFB6B6' : '#dc3545' }};
                                                    line-height: 1;
                                                ">
                                                    {{ $stats['occupied'] }}
                                                </div>
                                                <div style="
                                                    font-size: 0.75rem;
                                                    color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#666' }};
                                                    margin-top: 4px;
                                                    font-weight: 600;
                                                ">
                                                    Occupied
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                        </div>
                    </div>
                </div>

        <!-- Parking Map Visualization -->
        <div class="map-section" wire:poll.03s="loadParkingData">

            @if($parkingSpaces->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-database text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted">No parking spaces configured for {{ $selectedFloor }}</p>
                </div>
            @else
                <!-- Parking Map Layout -->
                <div class="parking-map-wrapper">
                    <div class="parking-map-container" id="parkingMapContainer">

                        <!-- Section Labels (Dynamic based on React Native config scaled 1.5x) -->
                        @php
                            // Section label positions based on React Native config (scaled 1.5x)
                            $sectionLabels = [
                                'A' => ['x' => 1027, 'y' => 174],    // Original: x: 685, y: 116 (first A1 spot position)
                                'B' => ['x' => 750, 'y' => 15],      // Original: x: 500, y: 32 (first B spot)
                                'C' => ['x' => 675, 'y' => 142],     // Original: x: 450, y: 95 (first C spot)
                                'D' => ['x' => 150, 'y' => 270],     // Original: x: 100, y: 200 (first D spot)
                                'E' => ['x' => 82, 'y' => 472],      // Original: x: 55, y: 315 (first E spot)
                                'F' => ['x' => 180, 'y' => 750],     // Original: x: 120, y: 520 (first F spot)
                                'G' => ['x' => 750, 'y' => 885],     // Original: x: 500, y: 590 (first G spot)
                                'H' => ['x' => 840, 'y' => 1305],    // Original: x: 560, y: 890 (first H spot)
                                'I' => ['x' => 1020, 'y' => 885],    // Original: x: 680, y: 590 (first I spot)
                                'J' => ['x' => 405, 'y' => 525],     // Original: x: 270, y: 370 (first J spot)
                            ];

                            // Get unique sections from parking spaces
                            $sections = [];
                            foreach ($parkingSpaces as $space) {
                                $slotName = $space->slot_name ?? $this->getSensorDisplayName($space->sensor_id);
                                // Extract section letter (second character for format like "4A1")
                                if (strlen($slotName) >= 2) {
                                    $section = substr($slotName, 1, 1);
                                    if (!in_array($section, $sections) && isset($sectionLabels[$section])) {
                                        $sections[] = $section;
                                    }
                                }
                            }
                        @endphp

                        <!-- Elevator 1 -->
                        <div class="facility elevator" style="left: 675px; top: 315px; width: 127px; height: 60px;">
                            <span>ELEVATOR</span>
                        </div>

                        <!-- Elevator 2 -->
                        <div class="facility elevator" style="left: 690px; top: 787px; width: 127px; height: 60px;">
                            <span>ELEVATOR</span>
                        </div>

                        <!-- Elevator 3 -->
                        <div class="facility elevator rotated-left" style="left: 1005px; top: 742px; width: 127px; height: 60px;">
                            <span>ELEVATOR</span>
                        </div>

                        <!-- Stairs -->
                        <div class="facility stairs rotated-left" style="left: 45px; top: 337px; width: 127px; height: 60px;">
                            <span>STAIRS</span>
                        </div>

                        <!-- Entrance -->
                        <div class="facility entrance" style="right: 135px; top: 390px; width: 135px; height: 60px;">
                            <span>ENTRANCE</span>
                        </div>

                        <!-- Exit Sign -->
                        <div class="facility exit-sign" style="">
                            <span>EXIT</span>
                        </div>

                        <!-- Direction Arrows -->





                        <!-- Parking Spot Labels (Dynamic from Database) -->
                        @foreach($parkingSpaces as $space)
                            @php
                                // Check if slot has an assigned sensor via sensor_assignments table
                                $hasAssignedSensor = $space->sensorAssignment !== null;

                                $slotName = $space->slot_name ?? '';
                                $x = $space->x_position ?? 0;
                                $y = $space->y_position ?? 0;
                                $rotation = $space->rotation ?? 0;
                                $isOccupied = $space->is_occupied;
                                $isActive = $space->is_active ?? true;

                                // Slot dimensions adjusted for proper spacing
                                $slotWidth = 60;
                                $slotHeight = 85;
                                $adjustedX = $x;
                                $adjustedY = $y;
                            @endphp

                            <!-- All Spots: Show with dashed border and label inside -->
                            <div class="parking-spot-box {{ $hasAssignedSensor ? ($isOccupied ? 'occupied' : 'available') : 'inactive' }}"
                                 style="left: {{ $adjustedX }}px; top: {{ $adjustedY }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                        border: 3px dashed {{ $hasAssignedSensor ? ($isOccupied ? '#dc3545' : '#28a745') : '#999' }};
                                        background: {{ $hasAssignedSensor ? ($isOccupied ? 'rgba(220, 53, 69, 0.1)' : 'rgba(40, 167, 69, 0.1)') : 'rgba(200, 200, 200, 0.1)' }};
                                        color: {{ $hasAssignedSensor ? ($isOccupied ? '#dc3545' : '#28a745') : '#666' }};
                                        font-size: 14px; font-weight: 600;
                                        display: flex; align-items: center; justify-content: center;
                                        border-radius: 6px;
                                        position: absolute; pointer-events: none;
                                        transform: rotate({{ $rotation }}deg); transform-origin: center center;"
                                 title="{{ $slotName }} - {{ $hasAssignedSensor ? ($isOccupied ? 'Occupied' : 'Available') : 'No Sensor' }}">
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

@push('styles')
<link rel="stylesheet" href="{{ asset('css/public-parking-display.css?v=1.3') }}">
@endpush

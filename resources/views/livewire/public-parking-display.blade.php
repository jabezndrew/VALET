<div wire:poll.3s="loadParkingData">
    <div class="container-fluid p-0" style="background: white; min-height: 100vh;">

        <!-- Main Content -->
        <div class="row g-0">
            <div class="col-12 position-relative">

                <!-- Route Control Buttons - Top Left -->
                <div style="position: absolute; top: 30px; left: 30px; z-index: 1000; display: flex; gap: 10px;">
                    @if($selectedSpot)
                        <!-- Clear Route Button -->
                        <button wire:click="clearRoute" class="route-toggle-btn active">
                            <svg class="icon" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                            </svg>
                            <span>Clear Route to {{ $selectedSpot }}</span>
                        </button>
                    @else
                        <!-- Info Button -->
                        <div class="route-toggle-btn" style="cursor: default; background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);">
                            <svg class="icon" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                            </svg>
                            <span>Click any parking spot to see route</span>
                        </div>
                    @endif
                </div>

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

                                <!-- Traffic Flow Arrows - Individual Directional Indicators -->
                                @php
                                    // Format: [x, y, rotation] - rotation in degrees (0=right, 90=down, 180=left, 270=up)
                                    $flowArrows = [
                                        // Entry area - from ENTRANCE going into parking
                                        ['x' => 1050, 'y' => 430, 'rotation' => 180, 'type' => 'entry'],

                                        // Right side lane - towards Columns A, B, C, D
                                        ['x' => 1000, 'y' => 350, 'rotation' => 270, 'type' => 'normal'],
                                        ['x' => 1000, 'y' => 200, 'rotation' => 180, 'type' => 'normal'],
                                        ['x' => 925, 'y' => 200, 'rotation' => 180, 'type' => 'normal'],
                                        ['x' => 850, 'y' => 200, 'rotation' => 90, 'type' => 'normal'],
                                        ['x' => 850, 'y' => 350, 'rotation' => 90, 'type' => 'normal'],


                                        // Top lane - going left towards E, F
                                        ['x' => 850, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                        ['x' => 700, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                        ['x' => 550, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                        ['x' => 400, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                        ['x' => 250, 'y' => 480, 'rotation' => 90, 'type' => 'normal'],

                                        // Left side - going down towards G, H, I
                                        ['x' => 248, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                        ['x' => 400, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                        ['x' => 550, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                        ['x' => 700, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                        ['x' => 850, 'y' => 700, 'rotation' => 90, 'type' => 'normal'],
                                        ['x' => 850, 'y' => 900, 'rotation' => 90, 'type' => 'normal'],
                                        ['x' => 850, 'y' => 1100, 'rotation' => 90, 'type' => 'normal'],
                                        ['x' => 850, 'y' => 1280, 'rotation' => 0, 'type' => 'normal'],
                                        ['x' => 925, 'y' => 1280, 'rotation' => 0, 'type' => 'normal'],

                                        // Bottom lane - going right towards EXIT
                                        ['x' => 1000, 'y' => 1280, 'rotation' => 270, 'type' => 'normal'],
                                        ['x' => 1000, 'y' => 1100, 'rotation' => 270, 'type' => 'normal'],
                                        ['x' => 1000, 'y' => 900, 'rotation' => 270, 'type' => 'normal'],
                                        ['x' => 1000, 'y' => 700, 'rotation' => 270, 'type' => 'normal'],

                                        // Middle section - Column J, F area
                                        ['x' => 248, 'y' => 595, 'rotation' => 90, 'type' => 'normal'],
                                        

                                        // Exit area
                                        ['x' => 1050, 'y' => 628, 'rotation' => 0, 'type' => 'exit'],
                                    ];
                                @endphp

                                @foreach($flowArrows as $arrow)
                                    <div class="flow-arrow-indicator {{ $arrow['type'] }}"
                                         style="
                                            position: absolute;
                                            left: {{ $arrow['x'] - 20 }}px;
                                            top: {{ $arrow['y'] - 20 }}px;
                                            width: 40px;
                                            height: 40px;
                                            z-index: 95;
                                            pointer-events: none;
                                         ">
                                        <svg width="40" height="40" viewBox="0 0 40 40" style="transform: rotate({{ $arrow['rotation'] }}deg);">
                                            @if($arrow['type'] === 'entry')
                                                <!-- Green entry arrow -->
                                                <circle cx="20" cy="20" r="18" fill="#2F623D"/>
                                                <path d="M12 20 L26 20 M26 20 L21 14 M26 20 L21 26" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                            @elseif($arrow['type'] === 'exit')
                                                <!-- Red exit arrow -->
                                                <circle cx="20" cy="20" r="18" fill="#B22020"/>
                                                <path d="M12 20 L26 20 M26 20 L21 14 M26 20 L21 26" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                            @else
                                                <!-- Gray normal flow arrow -->
                                                <circle cx="20" cy="20" r="18" fill="#505050"/>
                                                <path d="M12 20 L26 20 M26 20 L21 14 M26 20 L21 26" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                            @endif
                                        </svg>
                                    </div>
                                @endforeach

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
                                        $isSelected = $selectedSpot === $slotName;
                                        $columnCode = $space->column_code ?? '';
                                    @endphp

                                    <div class="parking-spot-box {{ $hasAssignedSensor ? ($isOccupied ? 'occupied' : 'available') : 'inactive' }} {{ $isSelected ? 'selected-spot' : '' }}"
                                         wire:click="selectParkingSpot('{{ $slotName }}', '{{ $columnCode }}', {{ $x }}, {{ $y }})"
                                         style="
                                            left: {{ $x }}px;
                                            top: {{ $y }}px;
                                            width: 60px;
                                            height: 85px;
                                            font-size: 22px;
                                            transform: rotate({{ $rotation }}deg);
                                            pointer-events: auto;
                                            cursor: pointer;
                                            transition: all 0.3s ease;
                                            {{ $isSelected ? 'box-shadow: 0 0 20px 5px #FFD700; border: 3px solid #FFD700 !important; z-index: 600;' : '' }}
                                         "
                                         title="Click to show route to {{ $slotName }}"
                                    >
                                        {{ $slotName }}
                                    </div>
                                @endforeach

                                {{-- ============================================
                                    ROUTE - ENTRANCE TO EXACT PARKING SPOT
                                    You can define routes for:
                                    1. Individual spots (e.g., '1D1', '1D2')
                                    2. Sections as fallback (e.g., 'D')
                                ============================================ --}}
                                @php
                                    // ENTRANCE position - edit this if ENTRANCE moves
                                    $entranceX = 1050;
                                    $entranceY = 425;

                                    // INDIVIDUAL SPOT waypoints - define unique routes for specific spots
                                    // Format: 'SpotName' => [[x1,y1], [x2,y2], ...]
                                    $spotWaypoints = [
                                        //Different routes for each B spot
                                         '1B1' => [[999, 425]],
                                         '1B2' => [[990, 425], [990, 190], [935, 190]],
                                         '1B3' => [[990, 425], [990, 190], [870, 190]],
                                         '1B4' => [[990, 425], [990, 190], [800, 190]],

                                        //Different routes for each C spot
                                         '1D1' => [[628, 430]],
                                         '1D2' => [[560, 430]],

                                        //Different routes for each D spot
                                         '1D1' => [[628, 430]],
                                         '1D2' => [[560, 430]],
                                         '1D3' => [[800, 430], [750, 250]],
                                         '1D4' => [[700, 430], [700, 200]],
                                         '1D5' => [[700, 430], [700, 150]],
                                         '1D6' => [[650, 430], [650, 100]],
                                         '1D7' => [[650, 430], [650, 80]],
                                    ];

                                    // SECTION waypoints - fallback if no individual spot route defined
                                    // Format: 'Section' => [[x1,y1], [x2,y2], ...]
                                    $sectionWaypoints = [
                                        'A' => [[965, 425], [965, 200]],
                                        'B' => [[965, 390], [965, 300]],   
                                        'C' => [[900, 250]],
                                        'D' => [[628, 430]],
                                        'E' => [[600, 390], [600, 50]],
                                        'F' => [[550, 390], [550, 300]],
                                        'G' => [[450, 390], [450, 470]],
                                        'H' => [[300, 390], [300, 500], [150, 500]],
                                        'I' => [[300, 390], [300, 650]],
                                        'J' => [[650, 390], [650, 300]],
                                    ];
                                @endphp

                                @if($showRoute && $selectedSpot && $selectedSpotX > 0)
                                <svg class="route-overlay" viewBox="0 0 1200 1400" style="position: absolute; top: 0; left: 0; width: 1200px; height: 1400px; pointer-events: none; z-index: 500;">
                                    <defs>
                                        <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                                            <polygon points="0 0, 10 3, 0 6" fill="#2ed573" />
                                        </marker>
                                    </defs>
                                    <g class="route-path-group">
                                        @php
                                            // Build path: ENTRANCE → Waypoints → Exact Spot
                                            $spotX = $selectedSpotX + 30; // Center of parking spot
                                            $spotY = $selectedSpotY + 42; // Center of parking spot

                                            $pathData = "M {$entranceX} {$entranceY}";

                                            // Check for individual spot route first, then fall back to section
                                            $waypoints = [];
                                            if (isset($spotWaypoints[$selectedSpot])) {
                                                $waypoints = $spotWaypoints[$selectedSpot];
                                            } elseif (isset($sectionWaypoints[$selectedSection])) {
                                                $waypoints = $sectionWaypoints[$selectedSection];
                                            }

                                            // Add waypoints to path
                                            foreach ($waypoints as $waypoint) {
                                                $pathData .= " L {$waypoint[0]} {$waypoint[1]}";
                                            }

                                            // End at exact parking spot
                                            $pathData .= " L {$spotX} {$spotY}";
                                        @endphp
                                        <path d="{{ $pathData }}"
                                              stroke="#2ed573" stroke-width="6" fill="none"
                                              stroke-linecap="round" stroke-linejoin="round"
                                              marker-end="url(#arrowhead)" />
                                    </g>
                                </svg>
                                @endif

                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/public-parking-display.css?v=1.6') }}">
@endpush

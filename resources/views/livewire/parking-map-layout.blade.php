<div wire:poll.3s="loadParkingData">
    <div id="alert-container"></div>

    <div class="container mt-4">

        <!-- Floor Selection and Stats -->
        <div class="campus-section mb-4">
            <div class="row align-items-center mb-4">
                <div class="col-md-6">
                    <h4 class="mb-0 fw-bold">USJ-R Quadricentennial Campus</h4>
                </div>
                <div class="col-md-6 text-end">
                    <span class="live-badge">
                        <i class="fas fa-circle text-white me-1" style="font-size: 0.5rem;"></i>LIVE
                    </span>
                    @if($lastUpdate)
                    <small class="text-muted ms-3">
                        <i class="fas fa-clock me-1"></i>Updated: {{ $lastUpdate }}
                    </small>
                    @endif
                </div>
            </div>

            <!-- Floor Selector -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-bold text-muted mb-2">
                        Select Floor Level
                    </label>
                    <div class="btn-group w-100 floor-selector" role="group">
                        @foreach(['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'] as $floor)
                            @php
                                $hasData = $this->hasFloorData($floor);
                            @endphp
                            <button wire:click="changeFloor('{{ $floor }}')"
                                    class="btn btn-floor-select {{ $selectedFloor === $floor ? 'active' : '' }} {{ !$hasData ? 'disabled' : '' }}"
                                    {{ !$hasData ? 'disabled' : '' }}
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
                    <div class="stat-circle" style="background: conic-gradient(#007bff 100%, #e9ecef 100%);">
                        <div class="stat-number">{{ $floorStats['total'] ?? 0 }}</div>
                    </div>
                    <h6 class="text-muted">Total Spots</h6>
                </div>
                <div class="col-md-3">
                    <div class="stat-circle" style="background: conic-gradient(#28a745 {{ $floorStats['total'] > 0 ? ($floorStats['available'] / $floorStats['total']) * 100 : 0 }}%, #e9ecef {{ $floorStats['total'] > 0 ? ($floorStats['available'] / $floorStats['total']) * 100 : 0 }}%);">
                        <div class="stat-number">{{ $floorStats['available'] ?? 0 }}</div>
                    </div>
                    <h6 class="text-muted">Available</h6>
                </div>
                <div class="col-md-3">
                    <div class="stat-circle" style="background: conic-gradient(#dc3545 {{ $floorStats['total'] > 0 ? ($floorStats['occupied'] / $floorStats['total']) * 100 : 0 }}%, #e9ecef {{ $floorStats['total'] > 0 ? ($floorStats['occupied'] / $floorStats['total']) * 100 : 0 }}%);">
                        <div class="stat-number">{{ $floorStats['occupied'] ?? 0 }}</div>
                    </div>
                    <h6 class="text-muted">Occupied</h6>
                </div>
                <div class="col-md-3">
                    <div class="stat-circle" style="background: conic-gradient(#fd7e14 {{ $floorStats['occupancy_rate'] ?? 0 }}%, #e9ecef {{ $floorStats['occupancy_rate'] ?? 0 }}%);">
                        <div class="stat-number">{{ $floorStats['occupancy_rate'] ?? 0 }}%</div>
                    </div>
                    <h6 class="text-muted">Occupancy</h6>
                </div>
            </div>
        </div>

        <!-- Parking Map Visualization -->
        <div class="campus-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold" style="color: #3A3A3C;">
                    <i class="fas fa-parking me-2" style="color: #B22020;"></i>
                    {{ $selectedFloor }} - Parking Layout
                </h4>
                @if(auth()->user() && auth()->user()->role === 'admin')
                    <a href="{{ route('admin.sensors') }}" wire:navigate class="btn btn-sm" style="background: #B22020; color: white;">
                        <i class="fas fa-microchip me-2"></i>
                        Manage Sensors
                    </a>
                @endif
            </div>

            @if($parkingSpaces->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-database text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted">No parking spaces configured for {{ $selectedFloor }}</p>
                </div>
            @else
                <!-- Parking Map Layout - Updated 2025-12-28 -->
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
                        <div class="facility elevator" style="left: 675px; top: 315px; width: 90px; height: 60px;">
                            <span>Elevator</span>
                        </div>

                        <!-- Elevator 2 -->
                        <div class="facility elevator" style="left: 690px; top: 787px; width: 127px; height: 60px;">
                            <span>Elevator</span>
                        </div>

                        <!-- Elevator 3 -->
                        <div class="facility elevator rotated-left" style="left: 985px; top: 742px; width: 127px; height: 60px;">
                            <span>Elevator</span>
                        </div>

                        <!-- Stairs -->
                        <div class="facility stairs rotated-left" style="left: 45px; top: 337px; width: 120px; height: 45px;">
                            <span>STAIRS</span>
                        </div>

                        <!-- Entrance -->
                        <div class="facility entrance" style="right: 135px; top: 390px; width: 135px; height: 60px;">
                            <span>Entrance</span>
                        </div>

                        <!-- Exit Sign -->
                        <div class="facility exit-sign" style="">
                            <span>EXIT</span>
                        </div>

                        <!-- Direction Arrows -->
                        <!-- Top right section - vertical arrows between A slots -->
                        <i class="fas fa-arrow-left arrow" style="right: 265px; top: 85px;"></i>
                        <i class="fas fa-arrow-down arrow" style="right: 345px; top: 115px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 115px;"></i>
                        <i class="fas fa-arrow-down arrow" style="right: 345px; top: 190px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 190px;"></i>
                        <i class="fas fa-arrow-down arrow" style="right: 345px; top: 265px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 265px;"></i>
                        <i class="fas fa-arrow-down arrow" style="right: 345px; top: 340px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 340px;"></i>
                        <i class="fas fa-arrow-down arrow" style="right: 345px; top: 415px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 415px;"></i>

                        <!-- Section I - left arrows -->
                        <i class="fas fa-arrow-left arrow" style="right: 1050px; top: 415px;"></i>
                        <i class="fas fa-arrow-left arrow" style="right: 1140px; top: 415px;"></i>

                        <!-- Section B/C middle vertical -->
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 490px;"></i>
                        <i class="fas fa-arrow-down arrow" style="right: 345px; top: 490px;"></i>

                        <!-- Middle horizontal corridor (Section J/C/D) -->
                        <i class="fas fa-arrow-left arrow" style="right: 405px; top: 635px;"></i>
                        <i class="fas fa-arrow-left arrow" style="right: 515px; top: 635px;"></i>
                        <i class="fas fa-arrow-left arrow" style="right: 625px; top: 635px;"></i>
                        <i class="fas fa-arrow-left arrow" style="right: 735px; top: 635px;"></i>

                        <!-- Left side Section F/E/D vertical -->
                        <i class="fas fa-arrow-down arrow" style="right: 95px; top: 450px;"></i>
                        <i class="fas fa-arrow-right arrow" style="right: 155px; top: 515px;"></i>

                        <!-- Bottom Section I - left arrows -->
                        <i class="fas fa-arrow-left arrow" style="right: 1050px; top: 880px;"></i>
                        <i class="fas fa-arrow-left arrow" style="right: 1140px; top: 880px;"></i>

                        <!-- Bottom horizontal corridor (near elevators) -->
                        <i class="fas fa-arrow-right arrow" style="right: 850px; top: 1025px;"></i>
                        <i class="fas fa-arrow-right arrow" style="right: 740px; top: 1025px;"></i>
                        <i class="fas fa-arrow-right arrow" style="right: 630px; top: 1025px;"></i>
                        <i class="fas fa-arrow-right arrow" style="right: 520px; top: 1025px;"></i>
                        <i class="fas fa-arrow-right arrow" style="right: 410px; top: 1025px;"></i>
                        <i class="fas fa-arrow-right arrow" style="right: 300px; top: 1025px;"></i>

                        <!-- Center vertical between elevators -->
                        <i class="fas fa-arrow-down arrow" style="right: 265px; top: 1090px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 1090px;"></i>

                        <!-- Right side vertical corridor (return path) -->
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 1425px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 1315px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 1205px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 950px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 840px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 730px;"></i>
                        <i class="fas fa-arrow-up arrow" style="right: 185px; top: 620px;"></i>

                        <!-- Bottom exit area -->
                        <i class="fas fa-arrow-down arrow" style="right: 265px; top: 1500px;"></i>

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

                            @if($hasAssignedSensor && $isOccupied)
                                <!-- Occupied Spot: Show Car Image -->
                                <div class="parking-spot-box occupied"
                                     style="left: {{ $adjustedX }}px; top: {{ $adjustedY }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                            border: 4px solid #dc3545; background: rgba(220, 53, 69, 0.1);
                                            display: flex; align-items: center; justify-content: center;
                                            border-radius: 8px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
                                            position: absolute; pointer-events: none;
                                            transform: rotate({{ $rotation }}deg); transform-origin: center center;"
                                     title="{{ $slotName }} - Occupied">

                                </div>
                            @elseif($hasAssignedSensor && !$isOccupied)
                                <!-- Available Spot with Sensor: Show Label (Green) -->
                                <div class="parking-spot-box available"
                                     style="left: {{ $adjustedX }}px; top: {{ $adjustedY }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                            border: 4px solid #28a745; background: linear-gradient(135deg, #2ed573 0%, #28a745 100%);
                                            color: white; font-size: 22px; font-weight: 700;
                                            display: flex; align-items: center; justify-content: center;
                                            border-radius: 8px; box-shadow: 0 4px 12px rgba(46, 213, 115, 0.4);
                                            position: absolute; pointer-events: none;
                                            transform: rotate({{ $rotation }}deg); transform-origin: center center;"
                                     title="{{ $slotName }} - Available">
                                    {{ $slotName }}
                                </div>
                            @else
                                <!-- No Sensor Assigned: Show Label (Gray/Inactive) -->
                                <div class="parking-spot-box inactive"
                                     style="left: {{ $adjustedX }}px; top: {{ $adjustedY }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                            border: 3px dashed #6c757d; background: rgba(108, 117, 125, 0.2);
                                            color: #6c757d; font-size: 18px; font-weight: 600;
                                            display: flex; align-items: center; justify-content: center;
                                            border-radius: 8px; opacity: 0.6;
                                            position: absolute; pointer-events: none;
                                            transform: rotate({{ $rotation }}deg); transform-origin: center center;"
                                     title="{{ $slotName }} - No Sensor Assigned">
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

@push('scripts')
<script>
    window.userCanEdit = @json(auth()->user() && in_array(auth()->user()->role, ['admin', 'ssd']));
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parking-map-layout.css?v=' . time()) }}">
@endpush

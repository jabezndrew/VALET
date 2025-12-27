<div wire:poll.3s="loadParkingData">
    <div id="alert-container"></div>

    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-right mb-4">

            <div class="d-flex gap-2">
                <button wire:click="refreshNow" class="btn btn-refresh">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-back-dashboard" wire:navigate>
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

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
                                    <img src="{{ asset('images/car_top.png') }}" alt="Car" style="max-width: 90%; max-height: 90%;">
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

    <!-- Slot Management Modal -->
    @if($showSlotModal && auth()->user() && auth()->user()->role === 'admin')
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #B22020 0%, #8a1818 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas {{ $isCreatingNew ? 'fa-plus-circle' : 'fa-cog' }} me-2"></i>
                        {{ $isCreatingNew ? 'Create New Parking Slot' : 'Manage Parking Slot' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeSlotModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Slot Name</label>
                        <input type="text" class="form-control" wire:model.live="slotName" placeholder="e.g., 4A1">
                        @error('slotName') <span class="text-danger small">{{ $message }}</span> @enderror
                        @if($isSlotNameTaken)
                            <div class="alert alert-warning mt-2 py-2 px-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>This slot name is already taken!</strong>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Assigned Sensor</label>
                        @php
                            $assignedSensor = \App\Models\SensorAssignment::where('space_code', $slotName)->first();
                        @endphp
                        @if($assignedSensor)
                            <div class="card border-success">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-success me-2">Active</span>
                                            <code class="text-primary">{{ $assignedSensor->mac_address }}</code>
                                            @if($assignedSensor->device_name)
                                                <br><small class="text-muted">{{ $assignedSensor->device_name }}</small>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">Last Seen:</small>
                                            <small class="fw-bold">{{ $assignedSensor->last_seen ? $assignedSensor->last_seen->diffForHumans() : 'Never' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-info-circle me-1"></i>
                                To reassign or manage sensors, visit <a href="{{ route('admin.sensors') }}" wire:navigate>Sensor Management</a>
                            </small>
                        @else
                            <div class="alert alert-warning py-2 px-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>No sensor assigned</strong>
                                <p class="mb-0 small">Assign a sensor to this parking space via <a href="{{ route('admin.sensors') }}" wire:navigate>Sensor Management</a></p>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Floor Level</label>
                        <select class="form-select" wire:model="floorLevel">
                            <option value="">Select Floor</option>
                            <option value="1st Floor">1st Floor</option>
                            <option value="2nd Floor">2nd Floor</option>
                            <option value="3rd Floor">3rd Floor</option>
                            <option value="4th Floor">4th Floor</option>
                        </select>
                        @error('floorLevel') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">X Position</label>
                            <input type="number" class="form-control" wire:model="xPosition" placeholder="e.g., 100">
                            @error('xPosition') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Y Position</label>
                            <input type="number" class="form-control" wire:model="yPosition" placeholder="e.g., 200">
                            @error('yPosition') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        @php
                            $hasRealSensor = !empty($sensorId) && in_array((int)$sensorId, [401, 402, 403, 404, 405]);
                            $canToggle = $hasRealSensor;
                        @endphp
                        <input class="form-check-input" type="checkbox" id="isActiveSwitch" wire:model="isSlotActive" {{ !$canToggle ? 'disabled' : '' }}>
                        <label class="form-check-label fw-bold" for="isActiveSwitch">
                            <span class="badge {{ $isSlotActive ? 'bg-success' : 'bg-secondary' }} me-2">
                                {{ $isSlotActive ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                            Slot Status
                            <small class="text-muted d-block">
                                @if($canToggle)
                                    {{ $isSlotActive ? 'This slot is active and can receive parking assignments' : 'This slot is inactive and will appear grayed out on the map' }}
                                @else
                                    Only slots with real sensors (401-405) can be activated
                                @endif
                            </small>
                        </label>
                    </div>

                    <div class="alert {{ $isSlotActive ? 'alert-info' : 'alert-warning' }}">
                        <i class="fas {{ $isSlotActive ? 'fa-info-circle' : 'fa-exclamation-triangle' }} me-2"></i>
                        <strong>{{ $isSlotActive ? 'Active Slot' : 'Inactive Slot' }}:</strong>
                        @if($isSlotActive)
                            This slot will appear on the map with a label and can accept parking assignments from sensors.
                        @else
                            @if($hasRealSensor)
                                This slot will be grayed out on the map with a dashed border and strikethrough text. It will not accept any parking assignments until reactivated.
                            @else
                                This slot requires a real sensor (401-405) to be activated. Future sensors (406-442) and custom sensors (1000+) cannot be activated.
                            @endif
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeSlotModal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    @if(!$isCreatingNew)
                    <button type="button" class="btn btn-danger" wire:click="deleteSlot">
                        <i class="fas fa-trash me-1"></i>Delete Slot
                    </button>
                    @endif
                    <button type="button" class="btn btn-success" wire:click="saveSlot">
                        <i class="fas fa-save me-1"></i>{{ $isCreatingNew ? 'Create Slot' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Pass user permissions to JavaScript
    window.userCanEdit = @json(auth()->user() && in_array(auth()->user()->role, ['admin', 'ssd']));
</script>
<script src="{{ asset('js/parking-map-layout.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parking-map-layout.css?v=' . time()) }}">
@endpush

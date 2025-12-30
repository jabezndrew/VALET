<div wire:poll.5s="loadParkingData">
    <div class="container-fluid p-0" style="background: #1a1a1a; min-height: 100vh;">

        <!-- Compact Header Bar -->
        <div class="row g-0">
            <div class="col-12">
                <div style="background: linear-gradient(135deg, #B22020 0%, #8B0000 100%); color: white; padding: 15px 30px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ asset('images/valet-logo.jpg') }}" alt="VALET Logo" style="width: 50px; height: 50px; border-radius: 50%; border: 3px solid white;">
                            <div>
                                <h1 class="mb-0" style="font-size: 1.8rem; font-weight: 700;">VALET Parking</h1>
                                <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">USJ-R Campus</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="live-badge">
                                <i class="fas fa-circle text-white me-1" style="font-size: 0.5rem;"></i>LIVE
                            </span>
                            @if($lastUpdate)
                            <div style="font-size: 0.9rem;">
                                <i class="fas fa-clock me-1"></i>{{ $lastUpdate }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Map with Floor Selector -->
        <div class="row g-0">
            <div class="col-12 position-relative">

                <!-- Floor Selector - Top Right Dropdown -->
                <div style="position: absolute; top: 30px; right: 30px; z-index: 1000;">
                    @php
                        $allFloors = ['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
                        $currentData = null;
                        foreach($allFloors as $floor) {
                            if($floor === $selectedFloor) {
                                $spaces = \App\Models\ParkingSpace::where('floor_level', $floor)->with('sensorAssignment')->get();
                                $total = $spaces->count();
                                $spacesWithSensors = $spaces->filter(fn($s) => $s->sensorAssignment !== null);
                                $occupied = $spacesWithSensors->filter(fn($s) => $s->is_occupied)->count();
                                $available = $spacesWithSensors->count() - $occupied;
                                $currentData = [
                                    'total' => $total,
                                    'available' => $available,
                                    'occupied' => $occupied
                                ];
                                break;
                            }
                        }
                    @endphp

                    <div style="background: rgba(255, 255, 255, 0.95); border-radius: 15px; padding: 20px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); min-width: 320px;">
                        <label style="font-size: 0.9rem; font-weight: 600; color: #666; margin-bottom: 10px; display: block;">
                            <i class="fas fa-building me-2"></i>Select Floor
                        </label>
                        <select wire:model.live="selectedFloor"
                                style="width: 100%; padding: 12px 15px; font-size: 1.1rem; font-weight: 600;
                                       border: 2px solid #B22020; border-radius: 10px; background: white;
                                       color: #3A3A3C; cursor: pointer; outline: none;">
                            @foreach($allFloors as $floor)
                                @php
                                    $hasData = \App\Models\ParkingSpace::where('floor_level', $floor)->exists();
                                @endphp
                                @if($hasData)
                                    <option value="{{ $floor }}">{{ $floor }}</option>
                                @endif
                            @endforeach
                        </select>

                        @if($currentData)
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="text-center" style="flex: 1;">
                                    <div style="font-size: 2.5rem; font-weight: 700; color: #28a745; line-height: 1;">
                                        {{ $currentData['available'] }}
                                    </div>
                                    <div style="font-size: 0.85rem; color: #666; margin-top: 5px;">Available</div>
                                </div>
                                <div class="text-center" style="flex: 1;">
                                    <div style="font-size: 2.5rem; font-weight: 700; color: #dc3545; line-height: 1;">
                                        {{ $currentData['occupied'] }}
                                    </div>
                                    <div style="font-size: 0.85rem; color: #666; margin-top: 5px;">Occupied</div>
                                </div>
                            </div>
                            <div class="text-center" style="font-size: 0.9rem; color: #999;">
                                Total: {{ $currentData['total'] }} spaces
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Parking Map -->
                <div class="campus-section" style="background: #2a2a2a; margin: 0; padding: 20px; min-height: calc(100vh - 90px); overflow-y: auto; overflow-x: auto;">

            @if($parkingSpaces->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-database text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted">No parking spaces configured for {{ $selectedFloor }}</p>
                </div>
            @else
                <!-- Parking Map Layout - Updated 2025-12-28 -->
                <div class="parking-map-wrapper">
                    <div class="parking-map-container" id="parkingMapContainer">

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
                                <!-- Occupied Spot: Solid Red Box -->
                                <div class="parking-spot-box occupied"
                                     style="left: {{ $adjustedX }}px; top: {{ $adjustedY }}px; width: {{ $slotWidth }}px; height: {{ $slotHeight }}px;
                                            border: 4px solid #dc3545; background: linear-gradient(135deg, #ff4757 0%, #dc3545 100%);
                                            color: white; font-size: 22px; font-weight: 700;
                                            display: flex; align-items: center; justify-content: center;
                                            border-radius: 8px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
                                            position: absolute; pointer-events: none;
                                            transform: rotate({{ $rotation }}deg); transform-origin: center center;"
                                     title="{{ $slotName }} - Occupied">
                                    {{ $slotName }}
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

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parking-map-layout.css') }}">
<style>
    /* Additional styles for public display */
    body {
        overflow-y: auto !important;
        overflow-x: hidden;
    }

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

    /* Parking Map - Fixed Container Approach (same as parking-map-layout) */
    .parking-map-wrapper {
        width: 100% !important;
        height: auto !important;
        min-height: 580px !important;
        max-height: 620px !important;
        overflow: hidden !important;
        background: #2a2a2a !important;
        border-radius: 15px !important;
        padding: 15px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative !important;
    }

    .parking-map-container {
        position: relative !important;
        width: 1400px !important;
        height: 1700px !important;
        background: #2a2a2a !important;
        transform: scale(0.40) rotate(90deg) !important;
        transform-origin: center center !important;
        transition: none !important;
    }

    /* Responsive scaling - maintains fixed positions */
    /* Mobile devices (portrait) */
    @media (max-width: 575.98px) {
        .parking-map-wrapper {
            min-height: 300px !important;
            max-height: 350px !important;
        }
        .parking-map-container {
            transform: scale(0.18) rotate(90deg) !important;
        }
    }

    /* Mobile devices (landscape) and small tablets */
    @media (min-width: 576px) and (max-width: 767.98px) {
        .parking-map-wrapper {
            min-height: 350px !important;
            max-height: 400px !important;
        }
        .parking-map-container {
            transform: scale(0.22) rotate(90deg) !important;
        }
    }

    /* Tablets */
    @media (min-width: 768px) and (max-width: 991.98px) {
        .parking-map-wrapper {
            min-height: 450px !important;
            max-height: 500px !important;
        }
        .parking-map-container {
            transform: scale(0.28) rotate(90deg) !important;
        }
    }

    /* Small laptops */
    @media (min-width: 992px) and (max-width: 1199.98px) {
        .parking-map-wrapper {
            min-height: 500px !important;
            max-height: 550px !important;
        }
        .parking-map-container {
            transform: scale(0.32) rotate(90deg) !important;
        }
    }

    /* Standard laptops */
    @media (min-width: 1200px) and (max-width: 1399.98px) {
        .parking-map-wrapper {
            min-height: 550px !important;
            max-height: 600px !important;
        }
        .parking-map-container {
            transform: scale(0.36) rotate(90deg) !important;
        }
    }

    /* Large laptops and small desktops (default) */
    @media (min-width: 1400px) and (max-width: 1599.98px) {
        .parking-map-container {
            transform: scale(0.40) rotate(90deg) !important;
        }
    }

    /* Full HD displays */
    @media (min-width: 1600px) and (max-width: 1919.98px) {
        .parking-map-wrapper {
            min-height: 650px !important;
            max-height: 700px !important;
        }
        .parking-map-container {
            transform: scale(0.45) rotate(90deg) !important;
        }
    }

    /* 2K displays */
    @media (min-width: 1920px) and (max-width: 2559.98px) {
        .parking-map-wrapper {
            min-height: 700px !important;
            max-height: 750px !important;
        }
        .parking-map-container {
            transform: scale(0.48) rotate(90deg) !important;
        }
    }

    /* Wide 2K+ and 4K displays */
    @media (min-width: 2560px) {
        .parking-map-wrapper {
            min-height: 800px !important;
            max-height: 900px !important;
        }
        .parking-map-container {
            transform: scale(0.55) rotate(90deg) !important;
        }
    }

    /* No scrolling - fit everything on screen */
    .campus-section {
        overflow: hidden !important;
    }
</style>
@endpush

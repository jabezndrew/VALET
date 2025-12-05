<div wire:poll.3s="loadParkingData">
    <div id="alert-container"></div>

    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="color: #3A3A3C;">
                    <i class="fas fa-map-marked-alt me-2" style="color: #B22020;"></i>
                    Parking Map Layout
                </h2>
            </div>
            <div class="d-flex gap-2">
                @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'ssd']))
                <button wire:click="toggleEditMode" class="btn {{ $editMode ? 'btn-edit-active' : 'btn-edit' }}">
                    <i class="fas fa-{{ $editMode ? 'times' : 'edit' }} me-1"></i> {{ $editMode ? 'Exit Edit' : 'Edit Mode' }}
                </button>
                @endif
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
                <div class="map-legend">
                    <span class="legend-item legend-available">
                        <i class="fas fa-square me-1"></i> Available
                    </span>
                    <span class="legend-item legend-occupied">
                        <i class="fas fa-square me-1"></i> Occupied
                    </span>
                </div>
            </div>

            @if($parkingSpaces->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-database text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted">No parking spaces configured for {{ $selectedFloor }}</p>
                </div>
            @else
                <!-- Parking Map Layout -->
                <div class="parking-map-wrapper">
                    <div class="parking-map-container {{ $editMode ? 'edit-mode' : '' }}">

                        <!-- Section A - Box Lines -->
                        <div class="divider-line" style="left: 1035px; top: 165px; width: 66px; height: 3px;"></div>
                        <div class="divider-line" style="left: 1035px; top: 255px; width: 66px; height: 3px;"></div>

                        <!-- Section B - Horizontal dividers -->
                        <div class="divider-line" style="left: 747px; top: 45px; width: 270px; height: 3px;"></div>
                        <div class="divider-line" style="left: 747px; top: 135px; width: 270px; height: 3px;"></div>

                        <!-- Section B - Vertical dividers -->
                        <div class="divider-line" style="left: 810px; top: 45px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 877px; top: 45px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 945px; top: 45px; width: 3px; height: 88px;"></div>

                        <!-- Section C - Vertical dividers -->
                        <div class="divider-line" style="left: 655px; top: 150px; width: 3px; height: 135px;"></div>
                        <div class="divider-line" style="left: 745px; top: 147px; width: 3px; height: 135px;"></div>

                        <!-- Section C - Horizontal dividers -->
                        <div class="divider-line" style="left: 660px; top: 217px; width: 87px; height: 3px;"></div>

                        <!-- Section D - Horizontal dividers -->
                        <div class="divider-line" style="left: 150px; top: 297px; width: 502px; height: 3px;"></div>
                        <div class="divider-line" style="left: 150px; top: 385px; width: 502px; height: 3px;"></div>

                        <!-- Section D - Vertical dividers -->
                        <div class="divider-line" style="left: 225px; top: 297px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 307px; top: 297px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 375px; top: 297px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 457px; top: 297px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 525px; top: 297px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 592px; top: 297px; width: 3px; height: 88px;"></div>

                        <!-- Section E - Lines -->
                        <div class="divider-line" style="left: 79px; top: 469px; width: 3px; height: 307px;"></div>
                        <div class="divider-line" style="left: 168px; top: 469px; width: 3px; height: 307px;"></div>
                        <div class="divider-line" style="left: 79px; top: 562px; width: 88px; height: 3px;"></div>
                        <div class="divider-line" style="left: 79px; top: 660px; width: 88px; height: 3px;"></div>

                        <!-- Section F - Horizontal dividers -->
                        <div class="divider-line" style="left: 172px; top: 777px; width: 495px; height: 3px;"></div>
                        <div class="divider-line" style="left: 172px; top: 865px; width: 495px; height: 3px;"></div>

                        <!-- Section F - Vertical dividers -->
                        <div class="divider-line" style="left: 240px; top: 777px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 322px; top: 777px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 390px; top: 777px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 457px; top: 777px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 540px; top: 777px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 607px; top: 777px; width: 3px; height: 88px;"></div>

                        <!-- Section G - Lines -->
                        <div class="divider-line" style="left: 747px; top: 882px; width: 3px; height: 457px;"></div>
                        <div class="divider-line" style="left: 813px; top: 882px; width: 3px; height: 457px;"></div>
                        <div class="divider-line" style="left: 747px; top: 967px; width: 66px; height: 3px;"></div>
                        <div class="divider-line" style="left: 747px; top: 1057px; width: 66px; height: 3px;"></div>
                        <div class="divider-line" style="left: 747px; top: 1147px; width: 66px; height: 3px;"></div>
                        <div class="divider-line" style="left: 747px; top: 1237px; width: 66px; height: 3px;"></div>

                        <!-- Section I - Lines -->
                        <div class="divider-line" style="left: 1017px; top: 882px; width: 3px; height: 457px;"></div>
                        <div class="divider-line" style="left: 1083px; top: 882px; width: 3px; height: 457px;"></div>
                        <div class="divider-line" style="left: 1017px; top: 967px; width: 66px; height: 3px;"></div>
                        <div class="divider-line" style="left: 1017px; top: 1057px; width: 66px; height: 3px;"></div>
                        <div class="divider-line" style="left: 1017px; top: 1147px; width: 66px; height: 3px;"></div>
                        <div class="divider-line" style="left: 1017px; top: 1237px; width: 66px; height: 3px;"></div>

                        <!-- Section H - Horizontal dividers -->
                        <div class="divider-line" style="left: 817px; top: 1335px; width: 202px; height: 3px;"></div>
                        <div class="divider-line" style="left: 825px; top: 1420px; width: 202px; height: 3px;"></div>

                        <!-- Section H - Vertical dividers -->
                        <div class="divider-line" style="left: 900px; top: 1335px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 967px; top: 1335px; width: 3px; height: 88px;"></div>

                        <!-- Section J - Horizontal dividers -->
                        <div class="divider-line" style="left: 405px; top: 552px; width: 375px; height: 3px;"></div>
                        <div class="divider-line" style="left: 405px; top: 640px; width: 375px; height: 3px;"></div>

                        <!-- Section J - Vertical dividers -->
                        <div class="divider-line" style="left: 465px; top: 552px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 555px; top: 552px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 645px; top: 552px; width: 3px; height: 88px;"></div>
                        <div class="divider-line" style="left: 727px; top: 552px; width: 3px; height: 88px;"></div>

                        <!-- Elevator 1 -->
                        <div class="facility elevator" style="left: 675px; top: 315px; width: 90px; height: 60px;">
                            <span>Elevator</span>
                        </div>

                        <!-- Elevator 2 -->
                        <div class="facility elevator" style="left: 690px; top: 787px; width: 127px; height: 60px;">
                            <span>Elevator</span>
                        </div>

                        <!-- Elevator 3 -->
                        <div class="facility elevator rotated" style="left: 985px; top: 742px; width: 127px; height: 60px;">
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

                        <!-- Parking Slots (Dynamic from Database) -->
                        @foreach($parkingSpaces as $space)
                            @php
                                $slotName = $space->slot_name ?? $this->getSensorDisplayName($space->sensor_id);
                                $x = $space->x_position ?? 0;
                                $y = $space->y_position ?? 0;
                                $isOccupied = $space->is_occupied;
                                $isActive = $space->is_active ?? true;
                            @endphp

                            <div class="parking-slot {{ $isOccupied ? 'occupied' : 'available' }} {{ !$isActive ? 'inactive' : '' }}"
                                 style="left: {{ $x }}px; top: {{ $y }}px;"
                                 wire:click="handleSlotClick({{ $space->id }})"
                                 title="{{ $slotName }} - {{ $isOccupied ? 'Occupied' : 'Available' }}">

                                <div class="slot-label">{{ $slotName }}</div>

                                @if($isOccupied)
                                    <i class="fas fa-car slot-icon"></i>
                                @else
                                    <div class="empty-slot"></div>
                                @endif
                            </div>
                        @endforeach

                    </div>
                </div>
            @endif
        </div>

        <!-- Auto-refresh Toggle -->
        <div class="text-center mt-4">
            <div class="form-check form-switch d-inline-block">
                <input wire:model.live="isAutoRefreshEnabled"
                       wire:click="toggleAutoRefresh"
                       class="form-check-input"
                       type="checkbox"
                       id="autoRefreshMap">
                <label class="form-check-label" for="autoRefreshMap" style="color: #6c757d;">
                    Auto-refresh every 3 seconds
                </label>
            </div>
        </div>
    </div>

    <!-- Slot Management Modal (for Admin/SSD) -->
    @if($showSlotModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #B22020 0%, #8B0000 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-parking me-2"></i>
                        Manage Parking Slot: {{ $slotForm['slot_name'] ?? 'New Slot' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeSlotModal"></button>
                </div>
                <div class="modal-body">
                    @if($editMode)
                    <form wire:submit.prevent="saveSlot">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tag me-1"></i> Slot Name
                            </label>
                            <input type="text" class="form-control" wire:model="slotForm.slot_name" required>
                            @error('slotForm.slot_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-microchip me-1"></i> Assign Sensor
                            </label>
                            <select class="form-select" wire:model="slotForm.sensor_id">
                                <option value="">No Sensor (Inactive)</option>
                                @foreach($availableSensors as $sensorId)
                                <option value="{{ $sensorId }}">Sensor {{ $sensorId }}</option>
                                @endforeach
                                @if($slotForm['sensor_id'])
                                <option value="{{ $slotForm['sensor_id'] }}" selected>Sensor {{ $slotForm['sensor_id'] }} (Current)</option>
                                @endif
                            </select>
                            @error('slotForm.sensor_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="slotForm.is_active" id="slotActive">
                            <label class="form-check-label fw-bold" for="slotActive">
                                <i class="fas fa-toggle-on me-1"></i> Activate Slot
                            </label>
                        </div>
                    </form>
                    @else
                    <!-- View Only Mode -->
                    <div class="slot-details">
                        <p><strong>Status:</strong>
                            <span class="badge {{ $selectedSlot?->is_occupied ? 'bg-danger' : 'bg-success' }}">
                                {{ $selectedSlot?->is_occupied ? 'Occupied' : 'Available' }}
                            </span>
                        </p>
                        @if($selectedSlot?->sensor_id)
                        <p><strong>Sensor ID:</strong> {{ $selectedSlot->sensor_id }}</p>
                        @endif
                        @if($selectedSlot?->distance_cm)
                        <p><strong>Distance:</strong> {{ $selectedSlot->distance_cm }} cm</p>
                        @endif
                        <p><strong>Last Updated:</strong> {{ $this->getRelativeTime($selectedSlot?->updated_at) }}</p>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($editMode && $slotForm['id'])
                    <button type="button" class="btn btn-danger me-auto" wire:click="deleteSlot">
                        <i class="fas fa-trash me-1"></i> Delete Slot
                    </button>
                    @endif
                    <button type="button" class="btn btn-secondary" wire:click="closeSlotModal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    @if($editMode)
                    <button type="button" class="btn btn-primary" wire:click="saveSlot">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-alert', (event) => {
            const alertContainer = document.getElementById('alert-container');
            const alertId = 'alert-' + Date.now();

            const alertHtml = `
                <div class="container mt-3">
                    <div id="${alertId}" class="alert alert-${event.type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${event.type === 'success' ? 'check-circle' : (event.type === 'info' ? 'info-circle' : 'exclamation-circle')} me-2"></i>
                        ${event.message}
                        <button type="button" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
                    </div>
                </div>
            `;

            alertContainer.innerHTML = alertHtml;

            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) alert.remove();
            }, 5000);
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Buttons */
    .btn-edit {
        background: linear-gradient(135deg, #B22020 0%, #8B0000 100%);
        color: white !important;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(178, 32, 32, 0.2);
    }

    .btn-edit:hover {
        background: linear-gradient(135deg, #8B0000 0%, #6B0000 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(178, 32, 32, 0.3);
    }

    .btn-edit-active {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        color: white !important;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
    }

    .btn-refresh {
        background: linear-gradient(135deg, #2F623D 0%, #3a7d4d 100%);
        color: white !important;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(47, 98, 61, 0.2);
    }

    .btn-refresh:hover {
        background: linear-gradient(135deg, #255030 0%, #2e6640 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(47, 98, 61, 0.3);
    }

    .btn-back-dashboard {
        background: white;
        color: #3A3A3C !important;
        border: 2px solid #e9ecef;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-back-dashboard:hover {
        background: #f8f9fa;
        border-color: #B22020;
        color: #B22020 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(178, 32, 32, 0.2);
    }

    /* Floor Selector */
    .floor-selector {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        border-radius: 10px;
        overflow: hidden;
    }

    .btn-floor-select {
        background: white;
        color: #6c757d !important;
        border: 1px solid #e9ecef;
        padding: 14px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-floor-select:hover:not(.disabled) {
        background: #f8f9fa;
        color: #3A3A3C !important;
        transform: translateY(-2px);
    }

    .btn-floor-select.active {
        background: linear-gradient(135deg, #B22020 0%, #8B0000 100%);
        color: white !important;
        border-color: #B22020;
        box-shadow: 0 4px 12px rgba(178, 32, 32, 0.3);
        transform: translateY(-2px);
    }

    /* Map Legend */
    .map-legend {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .legend-available {
        background: linear-gradient(135deg, #2F623D 0%, #3a7d4d 100%);
        color: white;
    }

    .legend-occupied {
        background: linear-gradient(135deg, #B22020 0%, #8B0000 100%);
        color: white;
    }

    /* Statistics */
    .live-badge {
        background: #28a745;
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }

    .stat-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #3A3A3C;
        background: white;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .campus-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    /* Parking Map Styles */
    .parking-map-wrapper {
        width: 100%;
        max-height: 900px;
        overflow: hidden;
        background: #2a2a2a;
        border-radius: 15px;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .parking-map-container {
        width: 1200px;
        height: 1500px;
        background: #2a2a2a;
        border-radius: 10px;
        transform: scale(0.55);
        transform-origin: top center;
    }

    .parking-map-container.edit-mode {
        border: 3px dashed #B22020;
    }

    /* Divider Lines */
    .divider-line {
        position: absolute;
        background-color: #fff;
    }

    /* Facilities */
    .facility {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 600;
        text-align: center;
    }

    .facility.elevator {
        background-color: #d5d821;
        color: black;
    }

    .facility.stairs {
        background-color: #d5d821;
        color: black;
    }

    .facility.entrance {
        background-color: #3ed120;
        color: black;
        font-size: 15px;
    }

    .facility.exit-sign {
        background-color: transparent;
        color: white;
        font-size: 24px;
        font-weight: bold;
    }

    .facility.rotated {
        transform: rotate(90deg);
    }

    .facility.rotated-left {
        transform: rotate(-90deg);
    }

    /* Direction Arrows */
    .arrow {
        position: absolute;
        font-size: 28px;
        color: white;
    }

    /* Parking Slots */
    .parking-slot {
        position: absolute;
        width: 60px;
        height: 85px;
        border: 3px solid;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .parking-slot.available {
        background-color: rgba(46, 213, 115, 0.3);
        border-color: #2ed573;
    }

    .parking-slot.available:hover {
        background-color: rgba(46, 213, 115, 0.5);
        transform: scale(1.1);
        box-shadow: 0 0 15px rgba(46, 213, 115, 0.6);
    }

    .parking-slot.occupied {
        background-color: rgba(255, 71, 87, 0.3);
        border-color: #ff4757;
    }

    .parking-slot.occupied:hover {
        background-color: rgba(255, 71, 87, 0.5);
        transform: scale(1.1);
        box-shadow: 0 0 15px rgba(255, 71, 87, 0.6);
    }

    .parking-slot.inactive {
        opacity: 0.4;
        border-color: #6c757d;
        background-color: rgba(108, 117, 125, 0.2);
    }

    .slot-label {
        font-size: 10px;
        font-weight: bold;
        color: white;
        margin-bottom: 2px;
    }

    .slot-icon {
        font-size: 20px;
        color: white;
    }

    .empty-slot {
        width: 20px;
        height: 20px;
        border: 2px dashed rgba(255, 255, 255, 0.5);
        border-radius: 2px;
    }

    /* Modal Enhancements */
    .modal-content {
        border-radius: 15px;
        border: none;
        overflow: hidden;
    }

    .modal-body {
        padding: 25px;
    }

    /* Responsive */
    @media (max-width: 1400px) {
        .parking-map-wrapper {
            max-height: 600px;
        }

        .parking-map-container {
            transform: scale(0.5);
            transform-origin: top center;
        }
    }

    @media (max-width: 1200px) {
        .parking-map-wrapper {
            max-height: 550px;
        }

        .parking-map-container {
            transform: scale(0.45);
            transform-origin: top center;
        }
    }

    @media (max-width: 992px) {
        .parking-map-wrapper {
            max-height: 500px;
        }

        .parking-map-container {
            transform: scale(0.4);
            transform-origin: top center;
        }
    }

    @media (max-width: 768px) {
        .parking-map-wrapper {
            max-height: 450px;
        }

        .parking-map-container {
            transform: scale(0.35);
            transform-origin: top center;
        }

        .map-legend {
            flex-direction: column;
            gap: 8px;
        }
    }

    @media (max-width: 576px) {
        .parking-map-wrapper {
            max-height: 400px;
            padding: 10px;
        }

        .parking-map-container {
            transform: scale(0.28);
            transform-origin: top center;
        }
    }
</style>
@endpush

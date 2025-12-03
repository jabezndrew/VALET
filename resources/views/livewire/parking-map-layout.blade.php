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
                <!-- Parking Grid Layout -->
                <div class="parking-grid">
                    @foreach($parkingSpaces as $space)
                        <div class="parking-slot {{ $space->is_occupied ? 'occupied' : 'available' }}"
                             title="{{ $this->getSensorDisplayName($space->sensor_id) }} - {{ $space->is_occupied ? 'Occupied' : 'Available' }}">
                            <div class="parking-slot-header">
                                <span class="parking-slot-id">{{ $this->getSensorDisplayName($space->sensor_id) }}</span>
                            </div>
                            <div class="parking-slot-body">
                                @if($space->is_occupied)
                                    <i class="fas fa-car parking-slot-icon"></i>
                                @else
                                    <i class="fas fa-parking parking-slot-icon"></i>
                                @endif
                            </div>
                            <div class="parking-slot-footer">
                                <small>{{ $space->is_occupied ? 'Occupied' : 'Available' }}</small>
                                <small class="d-block text-muted" style="font-size: 0.7rem;">
                                    {{ $this->getRelativeTime($space->updated_at) }}
                                </small>
                            </div>
                        </div>
                    @endforeach
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
                        <i class="fas fa-${event.type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
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
    /* Floor styles */
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
        position: relative;
    }

    .btn-floor-select:hover {
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

    .btn-floor-select.active:hover {
        background: linear-gradient(135deg, #8B0000 0%, #6B0000 100%);
    }

    /* Map legend */
    .map-legend {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .legend-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .legend-available {
        background: linear-gradient(135deg, #2F623D 0%, #3a7d4d 100%);
        color: white;
    }

    .legend-occupied {
        background: linear-gradient(135deg, #B22020 0%, #8B0000 100%);
        color: white;
    }
/* grid layout style */
    .parking-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 20px;
        padding: 20px 0;
    }
    /* slot cards */
    .parking-slot {
        border-radius: 15px;
        padding: 18px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        cursor: pointer;
        border: 3px solid;
        background: white;
        position: relative;
        overflow: hidden;
    }

    .parking-slot::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        transition: all 0.3s ease;
    }

    .parking-slot.available {
        border-color: #2F623D;
        background: linear-gradient(135deg, #ffffff 0%, #f0f9f4 100%);
    }

    .parking-slot.available::before {
        background: linear-gradient(90deg, #2F623D 0%, #3a7d4d 100%);
    }

    .parking-slot.available:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 8px 25px rgba(47, 98, 61, 0.25);
        border-color: #255030;
    }

    .parking-slot.occupied {
        border-color: #B22020;
        background: linear-gradient(135deg, #ffffff 0%, #fff0f0 100%);
    }

    .parking-slot.occupied::before {
        background: linear-gradient(90deg, #B22020 0%, #8B0000 100%);
    }

    .parking-slot.occupied:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 8px 25px rgba(178, 32, 32, 0.25);
        border-color: #8B0000;
    }

    .parking-slot-header {
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 12px;
        color: #3A3A3C;
        letter-spacing: 0.5px;
    }

    .parking-slot-body {
        padding: 25px 0;
    }

    .parking-slot-icon {
        font-size: 3rem;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        transition: all 0.3s ease;
    }

    .parking-slot:hover .parking-slot-icon {
        transform: scale(1.1);
    }

    .parking-slot.available .parking-slot-icon {
        color: #2F623D;
    }

    .parking-slot.occupied .parking-slot-icon {
        color: #B22020;
    }

    .parking-slot-footer {
        border-top: 2px solid #e9ecef;
        padding-top: 12px;
        margin-top: 12px;
    }

    .parking-slot-footer small {
        font-weight: 600;
        font-size: 13px;
    }

    .parking-slot.available .parking-slot-footer small:first-child {
        color: #2F623D;
    }

    .parking-slot.occupied .parking-slot-footer small:first-child {
        color: #B22020;
    }

    /* responsive */
    @media (max-width: 992px) {
        .parking-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
        }

        .floor-selector .btn-floor-select {
            padding: 12px 16px;
            font-size: 14px;
        }

        .btn-floor-select i {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .parking-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
        }

        .parking-slot {
            padding: 12px;
        }

        .parking-slot-icon {
            font-size: 2.2rem;
        }

        .parking-slot-header {
            font-size: 1rem;
        }

        .map-legend {
            flex-direction: column;
            gap: 8px;
        }

        .legend-item {
            font-size: 12px;
            padding: 6px 12px;
        }
    }

    @media (max-width: 576px) {
        .btn-refresh, .btn-back-dashboard {
            padding: 8px 14px;
            font-size: 13px;
        }

        .btn-refresh span, .btn-back-dashboard span {
            display: none;
        }
    }
</style>
@endpush

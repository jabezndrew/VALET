<div class="col-lg-6 col-xl-4">
    <div class="parking-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-microchip"></i> Sensor {{ $space->sensor_id }}
            </h5>
            <span class="status-badge {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                <i class="{{ $space->is_occupied ? 'fas fa-car' : 'fas fa-check-circle' }}"></i>
                {{ $space->is_occupied ? 'OCCUPIED' : 'AVAILABLE' }}
            </span>
        </div>
        
        <div class="space-details">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Distance:</span>
                <strong>{{ $space->distance_cm }} cm</strong>
            </div>
            
            <div class="distance-bar">
                <div class="distance-fill" style="width: {{ min(($space->distance_cm / 100) * 100, 100) }}%; background-color: {{ $space->distance_cm <= 10 ? '#dc3545' : '#28a745' }};"></div>
            </div>
            
            <div class="d-flex justify-content-between mt-3">
                <span class="text-muted">Last Updated:</span>
                <small class="text-muted">{{ $space->updated_at->diffForHumans() }}</small>
            </div>
            
            <div class="d-flex justify-content-between mt-2">
                <span class="text-muted">Status:</span>
                <span class="fw-bold {{ $space->is_occupied ? 'text-danger' : 'text-success' }}">
                    {{ $space->is_occupied ? '🚗 Vehicle Present' : '✅ Space Free' }}
                </span>
            </div>
        </div>
    </div>
</div>
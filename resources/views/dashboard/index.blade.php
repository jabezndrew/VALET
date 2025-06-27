@extends('layouts.app')

@section('title', 'VALET Smart Parking Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="header-section">
        <h1><i class="fas fa-car"></i> VALET Smart Parking</h1>
        <p class="lead">Real-time Parking Space Monitoring System</p>
        <p class="text-light">
            <i class="fas fa-clock"></i> Last updated: <span id="lastUpdate">{{ now()->format('H:i:s') }}</span>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statsSection">
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number available" id="availableCount">{{ $stats['available'] }}</div>
                <h6 class="text-muted mb-0">
                    <i class="fas fa-check-circle"></i> Available Spaces
                </h6>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number occupied" id="occupiedCount">{{ $stats['occupied'] }}</div>
                <h6 class="text-muted mb-0">
                    <i class="fas fa-car"></i> Occupied Spaces
                </h6>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number total" id="totalCount">{{ $stats['total'] }}</div>
                <h6 class="text-muted mb-0">
                    <i class="fas fa-th-large"></i> Total Spaces
                </h6>
            </div>
        </div>
    </div>

    <!-- Parking Spaces Grid -->
    <div class="row" id="parkingSpaces">
        @if($spaces->count() > 0)
            @foreach($spaces as $space)
                @include('dashboard.partials.parking-space', ['space' => $space])
            @endforeach
        @else
            <div class="col-12">
                <div class="status-card no-data">
                    <i class="fas fa-info-circle"></i>
                    <h5>No Parking Sensors Detected</h5>
                    <p>Waiting for ESP32 sensors to send data...</p>
                    <small class="text-muted">Make sure your ESP32 is connected and sending data to the API</small>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    let refreshInterval;
    let isRefreshing = true;
    
    // CSRF Token for Laravel
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    function updateTimestamp() {
        const now = new Date();
        $('#lastUpdate').text(now.toLocaleTimeString());
    }
    
    function getDistanceColor(distance) {
        if (distance <= 5) return '#dc3545';
        if (distance <= 10) return '#ffc107';
        if (distance <= 20) return '#28a745';
        return '#007bff';
    }
    
    function getDistancePercentage(distance) {
        return Math.min((distance / 100) * 100, 100);
    }
    
    function formatTimeAgo(timestamp) {
        const now = new Date();
        const updateTime = new Date(timestamp);
        const diffMs = now - updateTime;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins === 1) return '1 minute ago';
        if (diffMins < 60) return `${diffMins} minutes ago`;
        return updateTime.toLocaleTimeString();
    }
    
    async function fetchParkingData() {
        try {
            const response = await fetch('/api/parking');
            const data = await response.json();
            
            updateDashboard(data);
            updateTimestamp();
            
            $('#refreshStatus')
                .removeClass('refresh-error')
                .addClass('refresh-active')
                .html('<i class="fas fa-sync-alt"></i> Auto-refresh ON');
                
        } catch (error) {
            console.error('Error fetching data:', error);
            $('#refreshStatus')
                .removeClass('refresh-active')
                .addClass('refresh-error')
                .html('<i class="fas fa-exclamation-triangle"></i> Connection Error');
        }
    }
    
    function updateDashboard(spaces) {
        // Update statistics
        const total = spaces.length;
        const occupied = spaces.filter(space => space.is_occupied == 1).length;
        const available = total - occupied;
        
        $('#availableCount').text(available);
        $('#occupiedCount').text(occupied);
        $('#totalCount').text(total);
        
        // Update parking spaces
        if (spaces.length === 0) {
            $('#parkingSpaces').html(`
                <div class="col-12">
                    <div class="status-card no-data">
                        <i class="fas fa-info-circle"></i>
                        <h5>No Parking Sensors Detected</h5>
                        <p>Waiting for ESP32 sensors to send data...</p>
                    </div>
                </div>
            `);
            return;
        }
        
        let html = '';
        spaces.forEach(space => {
            const isOccupied = space.is_occupied == 1;
            const statusClass = isOccupied ? 'occupied' : 'available';
            const statusText = isOccupied ? 'OCCUPIED' : 'AVAILABLE';
            const badgeClass = isOccupied ? 'badge-occupied' : 'badge-available';
            const icon = isOccupied ? 'fas fa-car' : 'fas fa-check-circle';
            const distanceColor = getDistanceColor(space.distance_cm);
            const distancePercentage = getDistancePercentage(space.distance_cm);
            
            html += `
                <div class="col-lg-6 col-xl-4">
                    <div class="parking-space-card ${statusClass}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-microchip"></i> Sensor ${space.sensor_id}
                            </h5>
                            <span class="status-badge ${badgeClass}">
                                <i class="${icon}"></i> ${statusText}
                            </span>
                        </div>
                        
                        <div class="space-details">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Distance:</span>
                                <strong style="color: ${distanceColor}">${space.distance_cm} cm</strong>
                            </div>
                            
                            <div class="distance-bar">
                                <div class="distance-fill" style="width: ${distancePercentage}%; background-color: ${distanceColor};"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-3">
                                <span class="text-muted">Last Updated:</span>
                                <small class="text-muted">${formatTimeAgo(space.updated_at)}</small>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted">Status:</span>
                                <span class="fw-bold ${isOccupied ? 'text-danger' : 'text-success'}">
                                    ${isOccupied ? '🚗 Vehicle Present' : '✅ Space Free'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#parkingSpaces').html(html);
    }
    
    function toggleAutoRefresh() {
        if (isRefreshing) {
            clearInterval(refreshInterval);
            isRefreshing = false;
            $('#refreshStatus')
                .removeClass('refresh-active')
                .addClass('refresh-error')
                .html('<i class="fas fa-pause"></i> Auto-refresh OFF');
        } else {
            startAutoRefresh();
            isRefreshing = true;
            $('#refreshStatus')
                .removeClass('refresh-error')
                .addClass('refresh-active')
                .html('<i class="fas fa-sync-alt"></i> Auto-refresh ON');
        }
    }
    
    function startAutoRefresh() {
        refreshInterval = setInterval(fetchParkingData, 3000); // Every 3 seconds
    }
    
    // Event listeners
    $('#refreshStatus').click(toggleAutoRefresh);
    
    // Handle page visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(refreshInterval);
        } else if (isRefreshing) {
            startAutoRefresh();
        }
    });
    
    // Initialize
    startAutoRefresh();
    
    // Manual refresh button (optional)
    $(document).on('keydown', function(e) {
        if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
            e.preventDefault();
            fetchParkingData();
        }
    });
</script>
@endpush
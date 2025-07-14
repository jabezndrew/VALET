<div wire:poll.3s="loadParkingData">
    <!-- Header Section -->
    <div class="valet-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="valet-logo-container">
                    <img src="{{ asset('resources/images/valet-logo.jpg') }}" alt="VALET" class="valet-logo">
                </div>
                <div class="ms-3">
                    <h5 class="text-white mb-0 fw-bold">VALET</h5>
                    <small class="text-white-50">Your Virtual Parking Buddy</small>
                </div>
            </div>
            <div class="d-flex">
                <i class="fas fa-bell text-white me-3" style="font-size: 1.2rem;"></i>
                <i class="fas fa-cog text-white" style="font-size: 1.2rem;"></i>
            </div>
        </div>
    </div>

    <!-- Campus Title -->
    <div class="campus-section">
        <h6 class="mb-4 fw-bold text-center">USJ-R Quadricentennial Campus</h6>
        
        <!-- Overall Stats -->
        <div class="row text-center mb-4">
            <div class="col-4">
                <div class="stat-circle available-circle">
                    <div class="stat-number">{{ $availableSpaces }}</div>
                </div>
                <small class="text-muted">Available</small>
            </div>
            <div class="col-4">
                <div class="stat-circle occupied-circle">
                    <div class="stat-number">{{ $occupiedSpaces }}</div>
                </div>
                <small class="text-muted">Occupied</small>
            </div>
            <div class="col-4">
                <div class="stat-circle total-circle">
                    <div class="stat-number">{{ $totalSpaces }}</div>
                </div>
                <small class="text-muted">Total Spots</small>
            </div>
        </div>

        <!-- Overall Occupancy -->
        <div class="text-center mb-4">
            <small class="text-muted">Overall Occupancy</small>
            <div class="fw-bold">{{ round($totalSpaces > 0 ? ($occupiedSpaces / $totalSpaces) * 100 : 0) }}% Full</div>
        </div>
    </div>

    <!-- Select Floor Section -->
    <div class="floor-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-bold">Select Floor</h6>
            <span class="live-badge">LIVE</span>
        </div>

        <!-- 1st Floor -->
        <div class="floor-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">1st Floor</h6>
                <span class="available-badge">AVAILABLE</span>
            </div>
            <div class="row text-center mb-2">
                <div class="col-4">
                    <div class="floor-number available-color">15</div>
                    <small class="text-muted">Available</small>
                </div>
                <div class="col-4">
                    <div class="floor-number occupied-color">25</div>
                    <small class="text-muted">Occupied</small>
                </div>
                <div class="col-4">
                    <div class="floor-number total-color">40</div>
                    <small class="text-muted">Total Spots</small>
                </div>
            </div>
            <div class="progress mb-1">
                <div class="progress-bar bg-success" style="width: 37.5%"></div>
            </div>
            <small class="text-muted">37% Full</small>
        </div>

        <!-- 2nd Floor -->
        <div class="floor-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">2nd Floor</h6>
                <span class="limited-badge">LIMITED</span>
            </div>
            <div class="row text-center mb-2">
                <div class="col-4">
                    <div class="floor-number available-color">5</div>
                    <small class="text-muted">Available</small>
                </div>
                <div class="col-4">
                    <div class="floor-number occupied-color">35</div>
                    <small class="text-muted">Occupied</small>
                </div>
                <div class="col-4">
                    <div class="floor-number total-color">40</div>
                    <small class="text-muted">Total Spots</small>
                </div>
            </div>
            <div class="progress mb-1">
                <div class="progress-bar bg-warning" style="width: 87.5%"></div>
            </div>
            <small class="text-muted">87% Full</small>
        </div>

        <!-- 3rd Floor -->
        <div class="floor-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">3rd Floor</h6>
                <span class="full-badge">FULL</span>
            </div>
            <div class="row text-center mb-2">
                <div class="col-4">
                    <div class="floor-number available-color">0</div>
                    <small class="text-muted">Available</small>
                </div>
                <div class="col-4">
                    <div class="floor-number occupied-color">40</div>
                    <small class="text-muted">Occupied</small>
                </div>
                <div class="col-4">
                    <div class="floor-number total-color">40</div>
                    <small class="text-muted">Total Spots</small>
                </div>
            </div>
            <div class="progress mb-1">
                <div class="progress-bar bg-danger" style="width: 100%"></div>
            </div>
            <small class="text-muted">100% Full</small>
        </div>
    </div>
</div>
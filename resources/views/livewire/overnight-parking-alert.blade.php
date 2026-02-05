<div class="overnight-alert-wrapper" wire:poll.60s="loadOvernightVehicles">
    @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'ssd', 'security']))
        <!-- Notification Bell -->
        <button class="notification-bell {{ $overnightCount > 0 ? 'has-alerts' : '' }}"
                wire:click="openModal"
                title="Alerts">
            <i class="fas fa-bell"></i>
            @if($overnightCount > 0)
                <span class="notification-badge">{{ $overnightCount > 99 ? '99+' : $overnightCount }}</span>
            @endif
        </button>

        <!-- Modal -->
        @if($showModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" wire:click.self="closeModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-bell me-2"></i>Alerts
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        @if(count($overnightVehicles) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Plate</th>
                                            <th>Owner</th>
                                            <th>Parked Since</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($overnightVehicles as $vehicle)
                                        <tr>
                                            <td><strong>{{ $vehicle['vehicle_plate'] ?? 'N/A' }}</strong></td>
                                            <td>{{ $vehicle['user']['name'] ?? 'Guest' }}</td>
                                            <td>{{ $vehicle['parked_since'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">No alerts at this moment.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" wire:click="closeModal"></div>
        @endif
    @endif

    <style>
    .overnight-alert-wrapper {
        display: inline-flex;
        align-items: center;
    }

    .notification-bell {
        position: relative;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: rgba(255,255,255,0.7);
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 1.2rem;
    }

    .notification-bell:hover {
        color: white;
        background: rgba(255,255,255,0.1);
    }

    .notification-bell.has-alerts {
        color: #ffc107;
        animation: bell-shake 0.5s ease-in-out;
    }

    .notification-bell.has-alerts:hover {
        color: #ffca2c;
    }

    .notification-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #dc3545;
        color: white;
        font-size: 0.65rem;
        font-weight: bold;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
    }

    @keyframes bell-shake {
        0%, 100% { transform: rotate(0); }
        25% { transform: rotate(10deg); }
        50% { transform: rotate(-10deg); }
        75% { transform: rotate(5deg); }
    }
    </style>
</div>

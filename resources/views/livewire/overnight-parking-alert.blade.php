<div class="overnight-alert-wrapper" wire:poll.60s="loadOvernightVehicles">
    @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'ssd', 'security']))
        <!-- Notification Bell -->
        @php
            $totalUnseenBell = $overnightCount + $unseenOverrideCount;
        @endphp
        <button class="notification-bell {{ $hasUnseenAlerts ? 'has-alerts' : '' }}"
                wire:click="openModal"
                title="Alerts">
            <i class="fas fa-bell"></i>
            @if($hasUnseenAlerts)
                <span class="notification-badge">{{ $totalUnseenBell > 9 ? '9+' : ($totalUnseenBell > 0 ? $totalUnseenBell : '!') }}</span>
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
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">

                        {{-- Guard Override Notifications (admin/ssd only) --}}
                        @if(in_array(auth()->user()->role, ['admin', 'ssd']) && count($overrideNotifications) > 0)
                            <h6 class="fw-bold mb-3" style="color: #fd7e14;">
                                <i class="fas fa-exclamation-triangle me-2"></i>Guard Overrides
                            </h6>
                            @foreach($overrideNotifications as $notif)
                                <div style="background: #fff8f0; border: 1px solid #ffd0a0; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <span style="font-weight: 700; font-size: 1rem; color: #333;">
                                                Spot {{ $notif['space_code'] }}
                                            </span>
                                            <span class="badge ms-2 {{ $notif['status'] === 'available' ? 'bg-success' : 'bg-danger' }}">
                                                {{ ucfirst($notif['status']) }}
                                            </span>
                                        </div>
                                        <small style="color: #999; white-space: nowrap; margin-left: 10px;">
                                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div style="font-size: 0.88rem; color: #666; margin-top: 5px;">
                                        <i class="fas fa-user me-1"></i> {{ $notif['guard_name'] }}
                                        &nbsp;·&nbsp;
                                        <i class="fas fa-map-marker-alt me-1"></i> {{ $notif['floor_level'] }}
                                    </div>
                                    @if(!empty($notif['reason']))
                                        <div style="font-size: 0.85rem; color: #555; margin-top: 4px; font-style: italic;">
                                            <i class="fas fa-info-circle me-1"></i>{{ $notif['reason'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            <hr>
                        @endif

                        {{-- Overnight Vehicles --}}
                        @if(count($overnightVehicles) > 0)
                            <h6 class="fw-bold mb-3" style="color: #dc3545;">
                                <i class="fas fa-moon me-2"></i>Long-Parked Vehicles
                            </h6>
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
                        @endif

                        @if(count($overnightVehicles) === 0 && (in_array(auth()->user()->role, ['admin', 'ssd']) ? count($overrideNotifications) === 0 : true))
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

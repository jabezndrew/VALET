<div class="overnight-alert-wrapper" wire:poll.60s="loadOvernightVehicles">
    @if(auth()->check())
        <!-- Notification Bell -->
        <button class="notification-bell {{ $hasUnseenAlerts ? 'has-alerts' : '' }}"
                wire:click="openModal"
                title="Notifications">
            <i class="fas fa-bell"></i>
            @if($hasUnseenAlerts)
                <span class="notification-badge">{{ $unseenCount > 9 ? '9+' : $unseenCount }}</span>
            @endif
        </button>

        <!-- Modal -->
        @if($showModal)
        <div style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1.75rem;" wire:click.self="closeModal">
            {{-- Backdrop --}}
            <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5);" wire:click="closeModal"></div>
            <div style="position: relative; z-index: 10000; width: 100%; max-width: 800px; display: flex; flex-direction: column; max-height: 90vh; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                {{-- Header --}}
                <div class="modal-header bg-danger text-white" style="border-radius: 12px 12px 0 0; flex-shrink: 0; padding: 16px 20px;">
                    <h5 class="modal-title">
                        <i class="fas fa-bell me-2"></i>NOTIFICATIONS
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                {{-- Scrollable Body --}}
                <div style="overflow-y: auto; flex: 1; padding: 20px;">

                    @php $role = auth()->user()->role; @endphp

                    {{-- ── USER ROLE: Feedback Replies ──────────────────────────── --}}
                    @if($role === 'user')
                        @php
                            $feedbackReplies = array_values(array_filter($notifications, fn($n) => ($n['type'] ?? '') === 'feedback_reply'));
                        @endphp
                        @if(count($feedbackReplies) > 0)
                            <h6 class="fw-bold mb-3" style="color: #0d6efd;">
                                <i class="fas fa-comment-dots me-2"></i>Feedback Replies
                            </h6>
                            @foreach($feedbackReplies as $notif)
                                <div style="background: #f0f5ff; border: 1px solid #b0c8ff; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <span style="font-weight: 600; font-size: 0.9rem; color: #333;">Admin responded to your feedback</span>
                                        <small style="color: #999; white-space: nowrap; margin-left: 10px;">
                                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                                        </small>
                                    </div>
                                    @if(!empty($notif['feedback_preview']))
                                        <div style="font-size: 0.82rem; color: #666; margin-top: 4px; font-style: italic;">
                                            "{{ $notif['feedback_preview'] }}"
                                        </div>
                                    @endif
                                    @if(!empty($notif['admin_reply']))
                                        <div style="font-size: 0.88rem; color: #1a3a6e; margin-top: 6px; background: white; padding: 8px 10px; border-radius: 6px; border-left: 3px solid #0d6efd;">
                                            {{ $notif['admin_reply'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No notifications yet.</p>
                            </div>
                        @endif

                    {{-- ── STAFF ROLES ──────────────────────────────────────────── --}}
                    @else
                        @php
                            $malfunctionNotifs  = array_values(array_filter($notifications, fn($n) => ($n['type'] ?? '') === 'malfunction_report'));
                            $clearedNotifs      = array_values(array_filter($notifications, fn($n) => ($n['type'] ?? '') === 'malfunction_cleared'));
                            $rfidNotifs         = array_values(array_filter($notifications, fn($n) => ($n['type'] ?? '') === 'rfid_alert'));
                            $guestNotifs        = array_values(array_filter($notifications, fn($n) => ($n['type'] ?? '') === 'guest_request'));
                            $overrideNotifs     = array_values(array_filter($notifications, fn($n) => !in_array($n['type'] ?? '', ['malfunction_report','malfunction_cleared','rfid_alert','guest_request'])));
                            $hasAny = count($malfunctionNotifs) || count($clearedNotifs) || count($rfidNotifs) || count($guestNotifs) || count($overrideNotifs) || count($overnightVehicles);
                        @endphp

                        {{-- Malfunction Reports --}}
                        @if(count($malfunctionNotifs) > 0)
                            <h6 class="fw-bold mb-3" style="color: #e0a800;">
                                <i class="fas fa-exclamation-triangle me-2"></i>Malfunction Reports
                            </h6>
                            @foreach($malfunctionNotifs as $notif)
                                <div style="background: #fffbea; border: 1px solid #e0a800; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <span style="font-weight: 700; font-size: 1rem; color: #333;">Spot {{ $notif['space_code'] ?? 'N/A' }}</span>
                                            <span class="badge ms-2" style="background: #e0a800; color: #3d2e00;">Malfunctioned</span>
                                        </div>
                                        <small style="color: #999; white-space: nowrap; margin-left: 10px;">
                                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div style="font-size: 0.88rem; color: #666; margin-top: 5px;">
                                        <i class="fas fa-user me-1"></i> {{ $notif['guard_name'] ?? 'Unknown' }}
                                        @if(!empty($notif['floor_level']))
                                            &nbsp;·&nbsp;<i class="fas fa-map-marker-alt me-1"></i> {{ $notif['floor_level'] }}
                                        @endif
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

                        {{-- Malfunction Cleared --}}
                        @if(count($clearedNotifs) > 0)
                            <h6 class="fw-bold mb-3" style="color: #198754;">
                                <i class="fas fa-check-circle me-2"></i>Malfunction Cleared
                            </h6>
                            @foreach($clearedNotifs as $notif)
                                <div style="background: #f0fff4; border: 1px solid #a3d9a5; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <span style="font-weight: 700; font-size: 1rem; color: #333;">Spot {{ $notif['space_code'] ?? 'N/A' }}</span>
                                            <span class="badge ms-2 bg-success">Cleared</span>
                                        </div>
                                        <small style="color: #999; white-space: nowrap; margin-left: 10px;">
                                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div style="font-size: 0.88rem; color: #666; margin-top: 5px;">
                                        <i class="fas fa-user me-1"></i> {{ $notif['guard_name'] ?? 'Unknown' }}
                                        @if(!empty($notif['floor_level']))
                                            &nbsp;·&nbsp;<i class="fas fa-map-marker-alt me-1"></i> {{ $notif['floor_level'] }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <hr>
                        @endif

                        {{-- RFID Alerts --}}
                        @if(count($rfidNotifs) > 0)
                            <h6 class="fw-bold mb-3" style="color: #b22020;">
                                <i class="fas fa-id-card me-2"></i>RFID Alerts
                            </h6>
                            @foreach($rfidNotifs as $notif)
                                <div style="background: #fff5f5; border: 1px solid #f5a0a0; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <span style="font-weight: 700; font-size: 1rem; color: #333;">
                                                {{ strtoupper($notif['alert_type'] ?? 'Unknown') }} — UID: {{ $notif['uid'] ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <small style="color: #999; white-space: nowrap; margin-left: 10px;">
                                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div style="font-size: 0.88rem; color: #666; margin-top: 5px;">
                                        @if(!empty($notif['user_name']))
                                            <i class="fas fa-user me-1"></i> {{ $notif['user_name'] }}
                                            &nbsp;·&nbsp;
                                        @endif
                                        @if(!empty($notif['vehicle_plate']))
                                            <i class="fas fa-car me-1"></i> {{ $notif['vehicle_plate'] }}
                                        @endif
                                    </div>
                                    @if(!empty($notif['message']))
                                        <div style="font-size: 0.85rem; color: #b22020; margin-top: 4px;">
                                            {{ $notif['message'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            <hr>
                        @endif

                        {{-- Guest Requests --}}
                        @if(count($guestNotifs) > 0)
                            <h6 class="fw-bold mb-3" style="color: #fd7e14;">
                                <i class="fas fa-user-clock me-2"></i>Guest Requests
                            </h6>
                            @foreach($guestNotifs as $notif)
                                <div style="background: #fff8f0; border: 1px solid #ffd0a0; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <span style="font-weight: 700; font-size: 1rem; color: #333;">{{ $notif['guest_name'] ?? 'Guest' }}</span>
                                            <span class="badge ms-2" style="background: #fd7e14; color: white;">Guest Pass</span>
                                        </div>
                                        <small style="color: #999; white-space: nowrap; margin-left: 10px;">
                                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div style="font-size: 0.88rem; color: #666; margin-top: 5px;">
                                        <i class="fas fa-car me-1"></i> {{ $notif['vehicle_plate'] ?? 'N/A' }}
                                        @if(!empty($notif['purpose']))
                                            &nbsp;·&nbsp;<i class="fas fa-info-circle me-1"></i> {{ $notif['purpose'] }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <hr>
                        @endif

                        {{-- Guard Overrides (manual status changes) --}}
                        @if(count($overrideNotifs) > 0)
                            <h6 class="fw-bold mb-3" style="color: #fd7e14;">
                                <i class="fas fa-hand-paper me-2"></i>Guard Overrides
                            </h6>
                            @foreach($overrideNotifs as $notif)
                                <div style="background: #fff8f0; border: 1px solid #ffd0a0; border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <span style="font-weight: 700; font-size: 1rem; color: #333;">Spot {{ $notif['space_code'] ?? 'N/A' }}</span>
                                            <span class="badge ms-2 {{ ($notif['status'] ?? '') === 'available' ? 'bg-success' : 'bg-danger' }}">
                                                {{ ucfirst($notif['status'] ?? '') }}
                                            </span>
                                        </div>
                                        <small style="color: #999; white-space: nowrap; margin-left: 10px;">
                                            {{ \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div style="font-size: 0.88rem; color: #666; margin-top: 5px;">
                                        <i class="fas fa-user me-1"></i> {{ $notif['guard_name'] ?? 'Unknown' }}
                                        @if(!empty($notif['floor_level']))
                                            &nbsp;·&nbsp;<i class="fas fa-map-marker-alt me-1"></i> {{ $notif['floor_level'] }}
                                        @endif
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

                        {{-- Long-Parked / Overnight Vehicles --}}
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

                        @if(!$hasAny)
                            <div class="text-center py-4">
                                <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No alerts at this moment.</p>
                            </div>
                        @endif
                    @endif

                </div>
            </div>
        </div>
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

    .modal-title {
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        font-weight: 700;
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

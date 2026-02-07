<div @if(!$showActionModal) wire:poll.3s="loadParkingData" @endif>

    {{-- Header --}}
    <div class="guard-header">
        <div class="guard-logo">
            <img src="/images/valet-logo.jpg" alt="VALET" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2280%22>P</text></svg>'">
            <div>
                <div class="guard-logo-text">VALET</div>
                <div class="guard-logo-sub">Your Virtual Parking Buddy</div>
            </div>
        </div>

        <div class="guard-status">
            {{-- Guard view - no session login needed --}}
        </div>
    </div>

    {{-- Main Content --}}
    <div class="guard-content">
        {{-- Toast Messages --}}
        @if(session()->has('success'))
            <div class="toast-container">
                <div class="toast success">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <div class="container-fluid p-0" style="background: white; min-height: calc(100vh - 70px);">
            <div class="row g-0">
                <div class="col-12 position-relative">

                    {{-- Open Issues Alert - Top Left (Clickable) --}}
                    @if($openIncidentsCount > 0)
                    <div style="position: absolute; top: 30px; left: 30px; z-index: 1000;">
                        <div class="issues-alert" wire:click="openIncidentsModal" style="cursor: pointer;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $openIncidentsCount }} Open Issue(s) reported
                            <i class="fas fa-chevron-right ms-2" style="font-size: 0.85rem;"></i>
                        </div>
                    </div>
                    @endif

                    {{-- Floor Selector - Top Right Cards --}}
                    <div style="position: absolute; top: 30px; right: 30px; z-index: 1000; width: 320px;">
                        <div style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 25px; box-shadow: 0 8px 30px rgba(0,0,0,0.2);">
                            <h6 style="font-size: 1.3rem; font-weight: 700; color: #3A3A3C; margin-bottom: 20px;">
                                Select Floor
                            </h6>

                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                @foreach($allFloorStats as $floor => $stats)
                                    <div
                                        wire:key="floor-card-{{ $floor }}"
                                        wire:click="changeFloor('{{ $floor }}')"
                                        style="
                                            background: {{ $selectedFloor === $floor
                                                ? 'linear-gradient(135deg, #B22020 0%, #8B0000 100%)'
                                                : 'white' }};
                                            border: 3px solid {{ $selectedFloor === $floor ? '#B22020' : '#e0e0e0' }};
                                            border-radius: 12px;
                                            padding: 20px;
                                            transition: all 0.3s ease;
                                            box-shadow: {{ $selectedFloor === $floor
                                                ? '0 6px 18px rgba(178, 32, 32, 0.4)'
                                                : '0 3px 10px rgba(0,0,0,0.15)' }};
                                            cursor: pointer;
                                        "
                                    >
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div style="flex: 1;">
                                                <div style="font-weight:700;font-size:1.3rem;color:{{ $selectedFloor === $floor ? 'white' : '#3A3A3C' }};">
                                                    {{ $floor }}
                                                </div>
                                                <small style="color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#999' }};">
                                                    Total: {{ $stats['total'] ?? ($stats['available'] + $stats['occupied']) }}
                                                </small>
                                                @if($stats['incidents'] > 0)
                                                    <div style="margin-top: 5px;">
                                                        <span style="color: {{ $selectedFloor === $floor ? '#ffc107' : '#fd7e14' }}; font-size: 0.85rem;">
                                                            <i class="fas fa-exclamation-triangle"></i> {{ $stats['incidents'] }} issues
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="d-flex gap-4">
                                                <div class="text-center">
                                                    <div style="font-size:2rem;font-weight:700;color:#28a745;">
                                                        {{ $stats['available'] }}
                                                    </div>
                                                    <small style="color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#2e2d2dff' }};">Available</small>
                                                </div>
                                                <div class="text-center">
                                                    <div style="font-size:2rem;font-weight:700;color:#dc3545;">
                                                        {{ $stats['occupied'] }}
                                                    </div>
                                                    <small style="color: {{ $selectedFloor === $floor ? 'rgba(255,255,255,0.85)' : '#2e2d2dff' }};">Occupied</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Parking Map --}}
                    <div class="map-section">
                        @if($parkingSpaces->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-database text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="text-muted">No parking spaces configured for {{ $selectedFloor }}</p>
                            </div>
                        @else
                            <div class="parking-map-wrapper">
                                <div wire:key="map-{{ $selectedFloor }}" class="parking-map-container">

                                    {{-- Traffic Flow Arrows --}}
                                    @php
                                        $flowArrows = [
                                            ['x' => 1050, 'y' => 430, 'rotation' => 180, 'type' => 'entry'],
                                            ['x' => 1000, 'y' => 350, 'rotation' => 270, 'type' => 'normal'],
                                            ['x' => 1000, 'y' => 200, 'rotation' => 180, 'type' => 'normal'],
                                            ['x' => 925, 'y' => 200, 'rotation' => 180, 'type' => 'normal'],
                                            ['x' => 850, 'y' => 200, 'rotation' => 90, 'type' => 'normal'],
                                            ['x' => 850, 'y' => 350, 'rotation' => 90, 'type' => 'normal'],
                                            ['x' => 850, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                            ['x' => 700, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                            ['x' => 550, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                            ['x' => 400, 'y' => 480, 'rotation' => 180, 'type' => 'normal'],
                                            ['x' => 250, 'y' => 480, 'rotation' => 90, 'type' => 'normal'],
                                            ['x' => 248, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                            ['x' => 400, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                            ['x' => 550, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                            ['x' => 700, 'y' => 700, 'rotation' => 0, 'type' => 'normal'],
                                            ['x' => 850, 'y' => 700, 'rotation' => 90, 'type' => 'normal'],
                                            ['x' => 850, 'y' => 900, 'rotation' => 90, 'type' => 'normal'],
                                            ['x' => 850, 'y' => 1100, 'rotation' => 90, 'type' => 'normal'],
                                            ['x' => 850, 'y' => 1280, 'rotation' => 0, 'type' => 'normal'],
                                            ['x' => 925, 'y' => 1280, 'rotation' => 0, 'type' => 'normal'],
                                            ['x' => 1000, 'y' => 1280, 'rotation' => 270, 'type' => 'normal'],
                                            ['x' => 1000, 'y' => 1100, 'rotation' => 270, 'type' => 'normal'],
                                            ['x' => 1000, 'y' => 900, 'rotation' => 270, 'type' => 'normal'],
                                            ['x' => 1000, 'y' => 700, 'rotation' => 270, 'type' => 'normal'],
                                            ['x' => 248, 'y' => 595, 'rotation' => 90, 'type' => 'normal'],
                                            ['x' => 1050, 'y' => 628, 'rotation' => 0, 'type' => 'exit'],
                                        ];
                                    @endphp

                                    @foreach($flowArrows as $arrow)
                                        <div class="flow-arrow-indicator {{ $arrow['type'] }}"
                                             style="
                                                position: absolute;
                                                left: {{ $arrow['x'] - 20 }}px;
                                                top: {{ $arrow['y'] - 20 }}px;
                                                width: 40px;
                                                height: 40px;
                                                z-index: 95;
                                                pointer-events: none;
                                             ">
                                            <svg width="40" height="40" viewBox="0 0 40 40" style="transform: rotate({{ $arrow['rotation'] }}deg);">
                                                @if($arrow['type'] === 'entry')
                                                    <circle cx="20" cy="20" r="18" fill="#2F623D"/>
                                                    <path d="M12 20 L26 20 M26 20 L21 14 M26 20 L21 26" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                @elseif($arrow['type'] === 'exit')
                                                    <circle cx="20" cy="20" r="18" fill="#B22020"/>
                                                    <path d="M12 20 L26 20 M26 20 L21 14 M26 20 L21 26" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                @else
                                                    <circle cx="20" cy="20" r="18" fill="#505050"/>
                                                    <path d="M12 20 L26 20 M26 20 L21 14 M26 20 L21 26" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                @endif
                                            </svg>
                                        </div>
                                    @endforeach

                                    {{-- Facilities --}}
                                    <div class="facility elevator" style="left: 675px; top: 315px; width: 127px; height: 60px;">
                                        <span>ELEVATOR</span>
                                    </div>
                                    <div class="facility elevator" style="left: 690px; top: 787px; width: 127px; height: 60px;">
                                        <span>ELEVATOR</span>
                                    </div>
                                    <div class="facility elevator rotated-left" style="left: 1005px; top: 742px; width: 127px; height: 60px;">
                                        <span>ELEVATOR</span>
                                    </div>
                                    <div class="facility stairs rotated-left" style="left: 45px; top: 337px; width: 127px; height: 60px;">
                                        <span>STAIRS</span>
                                    </div>
                                    <div class="facility entrance" style="right: 135px; top: 390px; width: 135px; height: 60px;">
                                        <span>ENTRANCE</span>
                                    </div>
                                    <div class="facility exit-sign" style="right: 125px; top: 600px; width: 150px; height: 67px;">
                                        <span>EXIT</span>
                                    </div>

                                    {{-- Parking Spots --}}
                                    @foreach($parkingSpaces as $space)
                                        @php
                                            $hasAssignedSensor = $space->sensorAssignment !== null;
                                            $slotName = $space->slot_name ?? '';
                                            $x = $space->x_position ?? 0;
                                            $y = $space->y_position ?? 0;
                                            $rotation = $space->rotation ?? 0;
                                            $effectiveStatus = $space->getEffectiveStatus();
                                            $isManualOverride = $space->isManualOverrideActive();
                                        @endphp

                                        <div
                                            class="parking-spot-box {{ $hasAssignedSensor ? $effectiveStatus : 'inactive' }} {{ $isManualOverride ? 'manual-override' : '' }}"
                                            style="
                                                left: {{ $x }}px;
                                                top: {{ $y }}px;
                                                width: 60px;
                                                height: 85px;
                                                font-size: 22px;
                                                transform: rotate({{ $rotation }}deg);
                                                pointer-events: auto;
                                                cursor: {{ $hasAssignedSensor ? 'pointer' : 'default' }};
                                            "
                                            @if($hasAssignedSensor)
                                                wire:click="openActionModal({{ $space->id }}, 'override')"
                                            @endif
                                            title="{{ $space->space_code }} - {{ ucfirst($effectiveStatus) }}{{ $isManualOverride ? ' (Manual Override)' : '' }}"
                                        >
                                            {{ $slotName }}
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Action Modal --}}
    @if($showActionModal && $selectedSpace)
    <div class="action-overlay" wire:click.self="closeActionModal">
        <div class="action-modal">
            <div class="action-header">
                <h3>
                    Spot Actions
                </h3>
                <button class="action-close" wire:click="closeActionModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="action-body">
                {{-- Space Info --}}
                <div class="space-info">
                    <span class="space-code">{{ $selectedSpace->space_code }}</span>
                    <span class="space-status {{ $selectedSpace->getEffectiveStatus() }}">
                        {{ ucfirst($selectedSpace->getEffectiveStatus()) }}
                    </span>
                    @if($selectedSpace->isManualOverrideActive())
                        <div style="margin-top: 8px; font-size: 0.85rem; color: #fd7e14;">
                            <i class="fas fa-hand-paper me-1"></i>
                            Manual override active (expires {{ $selectedSpace->manual_override_expires->diffForHumans() }})
                        </div>
                    @endif
                </div>

                {{-- Action Tabs --}}
                <div class="action-tabs">
                    <button
                        class="action-tab {{ $actionType === 'override' ? 'active' : '' }}"
                        wire:click="$set('actionType', 'override')">
                        Override Status
                    </button>
                    <button
                        class="action-tab {{ $actionType === 'report' ? 'active' : '' }}"
                        wire:click="$set('actionType', 'report')">Report Issue
                    </button>
                </div>

                {{-- Override Form --}}
                @if($actionType === 'override')
                    <div class="form-group">
                        <label class="form-label">Set Status:</label>
                        <div class="status-options">
                            <div
                                class="status-option available {{ $overrideStatus === 'available' ? 'selected' : '' }}"
                                wire:click="$set('overrideStatus', 'available')"
                            >
                                <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.5rem;"></i>
                                <div style="margin-top: 5px; font-weight: 600;">Available</div>
                            </div>
                            <div
                                class="status-option occupied {{ $overrideStatus === 'occupied' ? 'selected' : '' }}"
                                wire:click="$set('overrideStatus', 'occupied')"
                            >
                                <i class="fas fa-car" style="color: #dc3545; font-size: 1.5rem;"></i>
                                <div style="margin-top: 5px; font-weight: 600;">Occupied</div>
                            </div>
                            <div
                                class="status-option blocked {{ $overrideStatus === 'blocked' ? 'selected' : '' }}"
                                wire:click="$set('overrideStatus', 'blocked')"
                            >
                                <i class="fas fa-ban" style="color: #fd7e14; font-size: 1.5rem;"></i>
                                <div style="margin-top: 5px; font-weight: 600;">Blocked</div>
                            </div>
                        </div>
                    </div>

                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">
                        <i class="fas fa-info-circle me-1"></i>
                        Override will automatically expire in 1 hour or when sensor detects a change.
                    </p>

                    {{-- PIN Input for Override --}}
                    <div class="form-group" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                        <label class="form-label">
                            Enter PIN to confirm:
                        </label>
                        @if($pinError)
                            <div style="background: #f8d7da; color: #721c24; padding: 8px 12px; border-radius: 8px; margin-bottom: 10px; font-size: 0.9rem;">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                {{ $pinError }}
                            </div>
                        @endif
                        <input
                            type="password"
                            class="form-input"
                            wire:model="pinInput"
                            placeholder="****"
                            maxlength="8"
                            inputmode="numeric"
                            style="text-align: center; font-size: 1.1rem; letter-spacing: 3px;"
                        >
                    </div>
                     @if($selectedSpace->isManualOverrideActive())
                        <button
                            class="action-submit"
                            style="background: #6c757d; margin-bottom: 10px; background: COLORS.primary;"
                            wire:click="clearOverride({{ $selectedSpace->id }})"
                        >
                            <i class="fas fa-undo me-2"></i>
                            Clear Override
                        </button>
                    @endif
                    <button class="action-submit override" wire:click="submitOverride">
                        <i class="fas fa-check me-2"></i>
                        Apply Override
                    </button>
                @endif

                {{-- Report Form --}}
                @if($actionType === 'report')
                    <div class="form-group">
                        <label class="form-label">Issue Category:</label>
                        <select class="form-select" wire:model="incidentCategory">
                            <option value="debris">Debris / Obstruction</option>
                            <option value="damaged">Damaged Spot</option>
                            <option value="blocked">Blocked Area</option>
                            <option value="light_issue">Light Issue</option>
                            <option value="sensor_issue">Sensor Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes (Optional):</label>
                        <textarea
                            class="form-textarea"
                            wire:model="incidentNotes"
                            placeholder="Describe the issue..."
                        ></textarea>
                    </div>

                    {{-- PIN Input for Report --}}
                    <div class="form-group" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                        <label class="form-label">
                            <i class="fas fa-shield-alt me-1" style="color: #B22020;"></i>
                            Enter PIN to confirm:
                        </label>
                        @if($pinError)
                            <div style="background: #f8d7da; color: #721c24; padding: 8px 12px; border-radius: 8px; margin-bottom: 10px; font-size: 0.9rem;">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                {{ $pinError }}
                            </div>
                        @endif
                        <input
                            type="password"
                            class="form-input"
                            wire:model="pinInput"
                            placeholder="****"
                            maxlength="8"
                            inputmode="numeric"
                            style="text-align: center; font-size: 1.1rem; letter-spacing: 3px;"
                        >
                    </div>

                    <button class="action-submit report" wire:click="submitIncident">
                        <i class="fas fa-paper-plane me-2"></i>
                        Submit Report
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Open Incidents Modal --}}
    @if($showIncidentsModal)
    <div class="action-overlay" wire:click.self="closeIncidentsModal">
        <div class="action-modal" style="max-width: 550px;">
            <div class="action-header" style="background: linear-gradient(135deg, #fd7e14 0%, #e06b00 100%); color: white; border-radius: 20px 20px 0 0;">
                <h3>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Open Issues ({{ count($openIncidents) }})
                </h3>
                <button class="action-close" wire:click="closeIncidentsModal" style="color: white;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="action-body" style="max-height: 60vh; overflow-y: auto;">
                @if(count($openIncidents) === 0)
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; opacity: 0.5;"></i>
                        <p class="mt-3 text-muted">No open issues at this time.</p>
                    </div>
                @else
                    <div class="incidents-list">
                        @php
                            $categoryLabels = [
                                'debris' => 'Debris / Obstruction',
                                'damaged' => 'Damaged Spot',
                                'blocked' => 'Blocked Area',
                                'light_issue' => 'Light Issue',
                                'sensor_issue' => 'Sensor Issue',
                                'other' => 'Other Issue',
                            ];
                            $categoryIcons = [
                                'debris' => 'fa-trash',
                                'damaged' => 'fa-car-crash',
                                'blocked' => 'fa-ban',
                                'light_issue' => 'fa-lightbulb',
                                'sensor_issue' => 'fa-wifi',
                                'other' => 'fa-question-circle',
                            ];
                        @endphp

                        @foreach($openIncidents as $incident)
                            <div class="incident-card">
                                <div class="incident-header">
                                    <div class="incident-category">
                                        <i class="fas {{ $categoryIcons[$incident['category']] ?? 'fa-exclamation-circle' }} me-2"></i>
                                        {{ $categoryLabels[$incident['category']] ?? $incident['category'] }}
                                    </div>
                                    <div class="incident-time">
                                        {{ \Carbon\Carbon::parse($incident['created_at'])->diffForHumans() }}
                                    </div>
                                </div>

                                <div class="incident-details">
                                    <div class="incident-location">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <strong>{{ $incident['space_code'] ?? 'N/A' }}</strong>
                                        <span class="text-muted ms-2">{{ $incident['floor_level'] }}</span>
                                    </div>

                                    @if($incident['notes'])
                                        <div class="incident-notes">
                                            <i class="fas fa-sticky-note me-1"></i>
                                            {{ $incident['notes'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="incident-actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <input
                                        type="password"
                                        class="form-input"
                                        wire:model="pinInput"
                                        placeholder="PIN"
                                        maxlength="8"
                                        inputmode="numeric"
                                        style="width: 80px; text-align: center; font-size: 0.9rem; padding: 6px 10px;"
                                    >
                                    <button
                                        class="btn-resolve"
                                        wire:click="resolveIncident({{ $incident['id'] }})"
                                    >
                                        <i class="fas fa-check me-1"></i>
                                        Mark Resolved
                                    </button>
                                </div>
                                @if($pinError)
                                    <div style="color: #dc3545; font-size: 0.8rem; margin-top: 5px;">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $pinError }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Auto-hide toast messages
    document.addEventListener('livewire:init', () => {
        Livewire.hook('message.processed', () => {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            });
        });
    });
</script>
@endpush

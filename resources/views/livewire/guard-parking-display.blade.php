<<<<<<< HEAD
<div class="guard-container">
    @if(!$isAuthenticated)
        <!-- PIN Entry Modal -->
        <div class="pin-overlay">
            <div class="pin-modal">
                <div class="pin-header">
                    <img src="/images/valet-logo.jpg" alt="VALET" class="pin-logo" onerror="this.style.display='none'">
                    <h2>Guard Assist</h2>
                    <p>Enter PIN to continue</p>
                </div>

                @if($pinError)
                    <div class="pin-error">{{ $pinError }}</div>
                @endif

                <form wire:submit.prevent="verifyPin">
                    <input
                        type="password"
                        wire:model="pinInput"
                        class="pin-input"
                        placeholder="Enter PIN"
                        maxlength="6"
                        autofocus
                        inputmode="numeric"
                    >
                    <button type="submit" class="pin-submit">
                        <i class="fas fa-unlock"></i> Unlock
                    </button>
                </form>
            </div>
        </div>
    @else
        <!-- Header -->
        <div class="guard-header">
            <div class="header-left">
                <img src="/images/valet-logo.jpg" alt="VALET" class="header-logo" onerror="this.style.display='none'">
                <div>
                    <h1>Guard Assist</h1>
                    <span class="header-subtitle">Real-time Parking Monitor</span>
                </div>
            </div>
            <div class="header-right">
                <button wire:click="refreshData" class="refresh-btn">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button wire:click="logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="flash-message success">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        <!-- Floor Selector -->
        <div class="floor-selector">
            @foreach($floors as $floor)
                <button
                    wire:click="selectFloor({{ $floor['number'] }})"
                    class="floor-btn {{ $selectedFloor == $floor['number'] ? 'active' : '' }}"
                >
                    <span class="floor-name">Floor {{ $floor['number'] }}</span>
                    <span class="floor-stats">
                        <span class="available">{{ $floor['available'] }}</span>
                        /
                        <span class="total">{{ $floor['total'] }}</span>
                    </span>
=======
<div wire:poll.3s="loadParkingData">
    {{-- PIN Authentication Modal --}}
    @if(!$isAuthenticated)
    <div class="pin-overlay">
        <div class="pin-modal">
            <div class="pin-title">
                <i class="fas fa-shield-alt me-2"></i>
                Guard Access
            </div>
            <p class="pin-subtitle">Enter your PIN to access guard features</p>

            @if($pinError)
                <div class="pin-error">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    {{ $pinError }}
                </div>
            @endif

            <input
                type="password"
                class="pin-input"
                wire:model="pinInput"
                wire:keydown.enter="verifyPin"
                placeholder="****"
                maxlength="6"
                inputmode="numeric"
                autofocus
            >

            <button class="pin-submit" wire:click="verifyPin">
                <i class="fas fa-unlock me-2"></i>
                Unlock
            </button>

            <p class="mt-3 text-muted" style="font-size: 0.85rem;">
                You can still view the map without PIN.<br>
                <a href="{{ route('parking-display') }}" style="color: #B22020;">View Public Map</a>
            </p>
        </div>
    </div>
    @endif

    {{-- Header --}}
    <div class="guard-header">
        <div class="guard-logo">
            <img src="/images/valet-logo.jpg" alt="VALET" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2280%22>P</text></svg>'">
            <div>
                <div class="guard-logo-text">VALET Guard</div>
                <div class="guard-logo-sub">Parking Assist</div>
            </div>
        </div>

        <div class="guard-status">
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span>LIVE</span>
            </div>

            @if($isAuthenticated)
                <button class="auth-btn" wire:click="logout">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout
                </button>
            @else
                <button class="auth-btn" onclick="document.querySelector('.pin-input').focus()">
                    <i class="fas fa-lock me-1"></i>
                    Unlock
                </button>
            @endif
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

        {{-- Floor Selector --}}
        <div class="floor-selector">
            @foreach($allFloorStats as $floor => $stats)
                <button
                    class="floor-btn {{ $selectedFloor === $floor ? 'active' : '' }}"
                    wire:click="changeFloor('{{ $floor }}')"
                >
                    <div class="floor-name">{{ $floor }}</div>
                    <div class="floor-stats">
                        <span class="stat-available">
                            <i class="fas fa-check-circle"></i> {{ $stats['available'] }}
                        </span>
                        <span class="stat-occupied">
                            <i class="fas fa-car"></i> {{ $stats['occupied'] }}
                        </span>
                        @if($stats['incidents'] > 0)
                            <span style="color: #fd7e14;">
                                <i class="fas fa-exclamation-triangle"></i> {{ $stats['incidents'] }}
                            </span>
                        @endif
                    </div>
>>>>>>> develop
                </button>
            @endforeach
        </div>

<<<<<<< HEAD
        <!-- Parking Map -->
        <div class="parking-map-wrapper">
            <div class="parking-map-container">
                @if($selectedFloor)
                    @php
                        $columns = collect($parkingSpaces)->groupBy('column_code');
                    @endphp

                    <div class="parking-grid">
                        @foreach($columns as $columnCode => $slots)
                            <div class="parking-column">
                                <div class="column-label">{{ $columnCode }}</div>
                                <div class="slots-container">
                                    @foreach($slots->sortBy('slot_number') as $space)
                                        <div
                                            wire:click="openActionModal({{ $space['id'] }})"
                                            class="parking-slot {{ $space['is_occupied'] ? 'occupied' : 'available' }}"
                                        >
                                            <span class="slot-number">{{ $space['slot_number'] }}</span>
                                            <i class="fas {{ $space['is_occupied'] ? 'fa-car' : 'fa-parking' }}"></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="no-floor-selected">
                        <i class="fas fa-hand-pointer"></i>
                        <p>Select a floor to view parking spaces</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Modal -->
        @if($showActionModal && $selectedSpace)
            <div class="action-modal-overlay" wire:click.self="closeActionModal">
                <div class="action-modal">
                    <div class="action-modal-header">
                        <h3>Space {{ $selectedSpace->space_code }}</h3>
                        <button wire:click="closeActionModal" class="close-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="action-modal-body">
                        <div class="current-status">
                            <span>Current Status:</span>
                            <span class="status-badge {{ $selectedSpace->is_occupied ? 'occupied' : 'available' }}">
                                {{ $selectedSpace->is_occupied ? 'Occupied' : 'Available' }}
                            </span>
                        </div>

                        <div class="action-section">
                            <h4>Manual Override</h4>
                            <div class="override-buttons">
                                <button
                                    wire:click="setOverride('available')"
                                    class="override-btn available"
                                    {{ !$selectedSpace->is_occupied ? 'disabled' : '' }}
                                >
                                    <i class="fas fa-check-circle"></i> Set Available
                                </button>
                                <button
                                    wire:click="setOverride('occupied')"
                                    class="override-btn occupied"
                                    {{ $selectedSpace->is_occupied ? 'disabled' : '' }}
                                >
                                    <i class="fas fa-car"></i> Set Occupied
                                </button>
                            </div>
                        </div>

                        <div class="action-section">
                            <h4>Report Issue</h4>
                            <select wire:model="incidentCategory" class="incident-select">
                                <option value="">Select issue type...</option>
                                <option value="debris">Debris/Obstruction</option>
                                <option value="damaged">Damaged Space</option>
                                <option value="blocked">Blocked Access</option>
                                <option value="light_issue">Lighting Issue</option>
                                <option value="sensor_issue">Sensor Malfunction</option>
                                <option value="other">Other</option>
                            </select>
                            <textarea
                                wire:model="incidentNotes"
                                class="incident-notes"
                                placeholder="Additional notes (optional)"
                                rows="3"
                            ></textarea>
                            <button
                                wire:click="reportIncident"
                                class="report-btn"
                                {{ !$incidentCategory ? 'disabled' : '' }}
                            >
                                <i class="fas fa-exclamation-triangle"></i> Submit Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Loading Indicator -->
        <div wire:loading class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
    @endif
</div>
=======
        {{-- Open Issues Alert --}}
        @if($openIncidentsCount > 0)
        <div style="padding: 12px 20px; background: #fff3cd; border-bottom: 1px solid #ffc107;">
            <span style="color: #856404; font-weight: 600;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ $openIncidentsCount }} Open Issue(s) reported
            </span>
        </div>
        @endif

        {{-- Map Container --}}
        <div class="map-container">
            <div class="parking-map-wrapper">
                <div class="parking-map-container">
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
                                font-size: 18px;
                                transform: rotate({{ $rotation }}deg);
                            "
                            @if($hasAssignedSensor && $isAuthenticated)
                                wire:click="openActionModal({{ $space->id }}, 'override')"
                            @endif
                            title="{{ $space->space_code }} - {{ ucfirst($effectiveStatus) }}{{ $isManualOverride ? ' (Manual Override)' : '' }}"
                        >
                            {{ $slotName }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Last Update --}}
        <div style="text-align: center; padding: 10px; color: #666; font-size: 0.85rem;">
            Last updated: {{ $lastUpdate }}
            @if(!$isAuthenticated)
                <span style="margin-left: 15px; color: #B22020;">
                    <i class="fas fa-lock me-1"></i> Tap "Unlock" to enable actions
                </span>
            @endif
        </div>
    </div>

    {{-- Action Modal --}}
    @if($showActionModal && $selectedSpace)
    <div class="action-overlay" wire:click.self="closeActionModal">
        <div class="action-modal">
            <div class="action-header">
                <h3>
                    <i class="fas fa-parking me-2"></i>
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
                        wire:click="$set('actionType', 'override')"
                    >
                        <i class="fas fa-hand-paper me-1"></i>
                        Override Status
                    </button>
                    <button
                        class="action-tab {{ $actionType === 'report' ? 'active' : '' }}"
                        wire:click="$set('actionType', 'report')"
                    >
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Report Issue
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

                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 20px;">
                        <i class="fas fa-info-circle me-1"></i>
                        Override will automatically expire in 1 hour or when sensor detects a change.
                    </p>

                    @if($selectedSpace->isManualOverrideActive())
                        <button
                            class="action-submit"
                            style="background: #6c757d; margin-bottom: 10px;"
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

                    <button class="action-submit report" wire:click="submitIncident">
                        <i class="fas fa-paper-plane me-2"></i>
                        Submit Report
                    </button>
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
>>>>>>> develop

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
                </button>
            @endforeach
        </div>

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

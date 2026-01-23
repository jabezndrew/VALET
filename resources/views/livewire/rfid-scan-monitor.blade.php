<div wire:poll.2s="checkForNewScans">
    @if($canView)
        <!-- Toggle Button (fixed position) -->
        <div class="position-fixed" style="bottom: 20px; right: 20px; z-index: 1040;">
            <button
                wire:click="toggle"
                class="btn btn-{{ $isEnabled ? 'success' : 'secondary' }} btn-lg rounded-circle shadow-lg"
                title="{{ $isEnabled ? 'RFID Monitor: ON' : 'RFID Monitor: OFF' }}"
                style="width: 60px; height: 60px;">
                <i class="fas fa-{{ $isEnabled ? 'broadcast-tower' : 'tower-broadcast' }}"></i>
            </button>
        </div>

        <!-- Scan Event Modal -->
        @if($lastScan && $isEnabled)
            <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.7); z-index: 1050;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-{{ $lastScan['valid'] ? 'success' : 'danger' }} text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-{{ $lastScan['valid'] ? 'check-circle' : 'times-circle' }} me-2"></i>
                                RFID Scan Detected
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <div class="display-1 mb-3">
                                    @if($lastScan['valid'])
                                        <i class="fas fa-door-open text-success"></i>
                                    @else
                                        <i class="fas fa-ban text-danger"></i>
                                    @endif
                                </div>
                                <h4 class="text-{{ $lastScan['valid'] ? 'success' : 'danger' }}">
                                    {{ $lastScan['message'] }}
                                </h4>
                            </div>

                            <hr>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="text-muted small">UID</label>
                                    <div class="fw-bold"><code>{{ $lastScan['uid'] }}</code></div>
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small">Time</label>
                                    <div class="fw-bold">{{ $lastScan['time'] }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small">User</label>
                                    <div class="fw-bold">{{ $lastScan['user_name'] }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small">Vehicle</label>
                                    <div class="fw-bold">{{ $lastScan['vehicle_plate'] }}</div>
                                </div>
                            </div>

                            @if($lastScan['valid'])
                                <div class="alert alert-success mt-3 mb-0">
                                    <i class="fas fa-hourglass-half me-2"></i>
                                    Gate will remain open for <strong>{{ $lastScan['duration'] }} seconds</strong>
                                </div>
                            @else
                                <div class="alert alert-danger mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Please contact security or visit the office
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer bg-light">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Auto-closing in <span id="countdown">{{ $countdown }}</span>s
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
            <script>
                document.addEventListener('livewire:initialized', () => {
                    let countdownInterval;
                    let currentCount = {{ $countdown }};

                    Livewire.on('start-countdown', () => {
                        clearInterval(countdownInterval);
                        currentCount = {{ $countdown }};

                        const countdownElement = document.getElementById('countdown');

                        countdownInterval = setInterval(() => {
                            currentCount--;
                            if (countdownElement) {
                                countdownElement.textContent = currentCount;
                            }

                            if (currentCount <= 0) {
                                clearInterval(countdownInterval);
                                @this.closeModal();
                            }
                        }, 1000);
                    });

                    // Cleanup on component destroy
                    Livewire.hook('element.removed', (el) => {
                        clearInterval(countdownInterval);
                    });
                });
            </script>
            @endpush
        @endif
    @endif
</div>

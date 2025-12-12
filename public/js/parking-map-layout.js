document.addEventListener('livewire:init', () => {
    // Alert notification handler
    Livewire.on('show-alert', (event) => {
        const alertContainer = document.getElementById('alert-container');
        const alertId = 'alert-' + Date.now();

        const alertHtml = `
            <div class="container mt-3">
                <div id="${alertId}" class="alert alert-${event.type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${event.type === 'success' ? 'check-circle' : (event.type === 'info' ? 'info-circle' : 'exclamation-circle')} me-2"></i>
                    ${event.message}
                    <button type="button" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
                </div>
            </div>
        `;

        alertContainer.innerHTML = alertHtml;

        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) alert.remove();
        }, 5000);
    });

    // Add click handler for parking map (admin/SSD only)
    const mapContainer = document.getElementById('parkingMapContainer');
    if (mapContainer) {
        mapContainer.addEventListener('click', function(e) {
            // Check if user is admin or SSD
            const canEdit = window.userCanEdit || false;
            if (!canEdit) return;

            // Don't trigger if clicking on existing slots or facilities
            if (e.target.closest('.parking-spot-label, .parking-spot-occupied, .facility, .divider-line, .section-label, .arrow')) {
                return;
            }

            // Get click position relative to the container
            const rect = mapContainer.getBoundingClientRect();
            const x = Math.round(e.clientX - rect.left);
            const y = Math.round(e.clientY - rect.top);

            // Open create slot modal
            Livewire.dispatch('openCreateSlotModal', { x: x, y: y });
        });

        // Add visual feedback on hover for admins/SSD
        const canEdit = window.userCanEdit || false;
        if (canEdit) {
            mapContainer.style.cursor = 'crosshair';
        }
    }
});

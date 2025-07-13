<div class="container">
    <div class="alert alert-success">
        <h3>{{ $message }}</h3>
        <p>Count: {{ $count }}</p>
        <button wire:click="increment" class="btn btn-primary">
            Click Me! (Test Livewire)
        </button>
    </div>
    
    <div class="alert alert-info">
        <h4>🎉 Laravel 11+ Livewire Test</h4>
        <p>If you can see this and the button increments the counter, Livewire is working perfectly!</p>
        <small class="text-muted">Component location: app/Livewire/ParkingDashboard.php</small>
    </div>
</div>
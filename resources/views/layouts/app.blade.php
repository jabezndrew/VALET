<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VALET Smart Parking')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .dashboard-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
        }
        
        .valet-header {
            background-color: #B22020;
            padding: 20px;
            border-radius: 0 0 20px 20px;
        }
        
        .valet-logo-container {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .valet-logo {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        
        .campus-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stat-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
        }
        
        .available-circle {
            background: conic-gradient(#28a745 70%, #e9ecef 70%);
        }
        
        .occupied-circle {
            background: conic-gradient(#dc3545 33%, #e9ecef 33%);
        }
        
        .total-circle {
            background: conic-gradient(#007bff 100%, #e9ecef 100%);
        }
        
        .stat-circle .stat-number {
            background: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .floor-section {
            padding: 0 20px 20px;
        }
        
        .live-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .floor-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .available-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .limited-badge {
            background: #fd7e14;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .full-badge {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .floor-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .available-color { color: #28a745; }
        .occupied-color { color: #dc3545; }
        .total-color { color: #007bff; }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
    </style>
    
    @stack('styles')
    
    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body>
    <div class="dashboard-container">
        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        // Global Livewire configuration
        document.addEventListener('livewire:init', () => {
            // Auto-refresh every 3 seconds
            setInterval(() => {
                Livewire.dispatch('refresh-parking-data');
            }, 3000);
        });
    </script>
    
    @stack('scripts')
</body>
</html>
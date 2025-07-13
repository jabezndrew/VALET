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
            background-color: #B22020;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            padding: 20px;
        }
        
        .header-section {
            text-align: left;
            background-color: white;
            color: #B22020;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 15px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        
        .status-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .parking-space-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 6px solid;
        }
        
        .parking-space-card.available {
            border-left-color: #28a745;
        }
        
        .parking-space-card.occupied {
            border-left-color: #B22020;
            transform: scale(1.02);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .badge-available {
            background-color: #28a745;
            color: white;
        }
        
        .badge-occupied {
            background-color: #B22020;
            color: white;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-number.available { color: #28a745; }
        .stat-number.occupied { color: #B22020; }
        .stat-number.total { color: #B22020; }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: white;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
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
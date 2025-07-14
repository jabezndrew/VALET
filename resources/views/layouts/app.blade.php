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
        }
        
        .valet-header {
            background-color: #B22020;
            padding: 20px 0;
        }
        
        .valet-logo-container {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .valet-logo {
            width: 35px;
            height: 35px;
            object-fit: contain;
        }
        
        .campus-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .stat-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
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
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.8rem;
        }
        
        .floor-section {
            margin-top: 30px;
        }
        
        .live-badge {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .floor-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .floor-card:hover {
            transform: translateY(-5px);
        }
        
        .available-badge {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .limited-badge {
            background: #fd7e14;
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .full-badge {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .floor-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .available-color { color: #28a745; }
        .occupied-color { color: #dc3545; }
        .total-color { color: #007bff; }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 5px;
        }

        .selected-floor-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .parking-space-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid;
            height: 100%;
        }
        
        .parking-space-card.available {
            border-left-color: #28a745;
        }
        
        .parking-space-card.occupied {
            border-left-color: #dc3545;
        }

        .status-badge-small {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .badge-available {
            background: #28a745;
            color: white;
        }

        .badge-occupied {
            background: #dc3545;
            color: white;
        }

        .distance-reading {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
    </style>
    
    @stack('styles')
    
    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body>
    <!-- Header Section - Applied to ALL pages -->
    <div class="valet-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="valet-logo-container">
                        <img src="{{ asset('../../images/valet-logo.jpg') }}" alt="VALET" class="valet-logo">
                    </div>
                    <div class="ms-3">
                        <h3 class="text-white mb-0 fw-bold">VALET</h3>
                        <span class="text-white-50">Your Virtual Parking Buddy</span>
                    </div>
                </div>
                <div class="d-flex">
                    <i class="fas fa-bell text-white me-3" style="font-size: 1.5rem;"></i>
                    <i class="fas fa-cog text-white" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    @yield('content')

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
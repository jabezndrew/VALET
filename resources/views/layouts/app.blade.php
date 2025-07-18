<!-- resources/views/components/layouts/app.blade.php -->
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
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        /* REMOVED static backgrounds - now dynamic */
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
    
    .navbar-nav {
        gap: 15px;
    }
    
    .nav-link {
        color: rgba(255,255,255,0.9) !important;
        font-weight: 500;
        border-radius: 8px;
        padding: 8px 16px !important;
        transition: all 0.3s ease;
        text-decoration: none;
        cursor: pointer;
    }
    
    .nav-link:hover, .nav-link.active {
        background: rgba(255,255,255,0.1);
        color: white !important;
    }
    
    .nav-link.feedback-btn {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .nav-link.feedback-btn:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-1px);
    }
    
    /* Simple User Dropdown */
    .user-dropdown-wrapper {
        position: relative;
    }

    .user-dropdown {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 12px;
        padding: 8px 16px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .user-dropdown:hover {
        background: rgba(255,255,255,0.2);
    }

    .user-dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        min-width: 200px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
        margin-top: 5px;
    }

    .user-dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: block;
        padding: 10px 20px;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        margin: 2px 8px;
        transition: background 0.3s ease;
        cursor: pointer;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
        color: #333;
    }

    .logout-btn {
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        color: #dc3545;
    }

    .logout-btn:hover {
        background: #f8f9fa;
        color: #dc3545;
    }

    .dropdown-divider {
        height: 1px;
        background: #e9ecef;
        margin: 8px 0;
    }
    
    .role-badge {
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: 8px;
    }
    
    .dropdown-role-info {
        padding: 8px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 2px 8px;
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    /* Alert styles */
    .alert {
        border-radius: 12px;
        border: none;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    /* Floor card no-data styles */
    .floor-card.no-data {
        opacity: 0.6;
        border: 2px dashed #dee2e6;
        background: #f8f9fa;
        cursor: not-allowed !important;
    }
    
    .no-data-badge {
        background: #6c757d;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: bold;
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
    }

    /* Loading overlay for smooth navigation */
    .nav-loading {
        opacity: 0.7;
        pointer-events: none;
    }
</style>
    @livewireStyles
</head>
<body>
    <!-- Header Section -->
    <div class="valet-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="valet-logo-container">
                        <img src="/images/valet-logo.jpg" alt="VALET" class="valet-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <i class="fas fa-car" style="display: none; font-size: 1.5rem; color: #B22020;"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="text-white mb-0 fw-bold">VALET</h3>
                        <span class="text-white-50">Your Virtual Parking Buddy</span>
                    </div>
                </div>
                
                <!-- Navigation -->
                @auth
                <div class="d-flex align-items-center">
                    <nav class="navbar-nav d-flex flex-row me-3">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
                           href="{{ route('dashboard') }}" 
                           wire:navigate>
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                        
                        @if(auth()->user()->canViewCars())
                        <a class="nav-link {{ request()->routeIs('cars.*') ? 'active' : '' }}" 
                           href="{{ route('cars.index') }}" 
                           wire:navigate>
                            <i class="fas fa-car me-1"></i> Vehicles
                        </a>
                        @endif
                        
                        @if(auth()->user()->canManageUsers())
                        <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" 
                           href="{{ route('admin.users') }}" 
                           wire:navigate>
                            <i class="fas fa-users me-1"></i> Users
                        </a>
                        @endif
                        
                        <!-- Feedback Button - Available to all authenticated users -->
                        <a class="nav-link feedback-btn {{ request()->routeIs('feedback.*') ? 'active' : '' }}" 
                           href="{{ route('feedback.index') }}" 
                           wire:navigate>
                            <i class="fas fa-comment-dots me-1"></i> Feedback
                        </a>
                    </nav>
                    
                    <!-- User Dropdown - SIMPLIFIED VERSION -->
                    <div class="user-dropdown-wrapper">
                        <button class="user-dropdown" onclick="toggleUserDropdown()">
                            <i class="fas fa-user me-2"></i>
                            {{ auth()->user()->name }}
                            <i class="fas fa-chevron-down ms-2"></i>
                        </button>
                        
                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <div class="dropdown-role-info">
                                <i class="fas fa-id-badge me-2"></i>
                                <strong>{{ auth()->user()->getRoleDisplayName() }}</strong>
                            </div>
                            <div class="dropdown-divider"></div>
                            <button onclick="logout()" class="dropdown-item logout-btn">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>
                @else
                <div class="d-flex">
                    <a href="{{ route('login') }}" class="btn btn-outline-light">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </a>
                </div>
                @endauth
            </div>
        </div>
    </div>

    <!-- Loading indicator for navigation -->
    <div id="navigation-loading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, #B22020, #ff6b6b); z-index: 9999;"></div>

    <!-- Alert Messages -->
    @if(session('success') || session('error') || session('warning') || session('info'))
    <div class="container mt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>
    @endif

    <!-- Page Content -->
    {{ $slot }}
   
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        // Simple dropdown toggle
        function toggleUserDropdown() {
            const menu = document.getElementById('userDropdownMenu');
            menu.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const wrapper = document.querySelector('.user-dropdown-wrapper');
            const menu = document.getElementById('userDropdownMenu');
            
            if (!wrapper.contains(event.target)) {
                menu.classList.remove('show');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // Global Livewire configuration
        document.addEventListener('livewire:init', () => {
            setInterval(() => {
                Livewire.dispatch('refresh-parking-data');
            }, 3000);
        });

        // Livewire navigation events for smooth transitions
        document.addEventListener('livewire:navigate', function() {
            document.getElementById('navigation-loading').style.display = 'block';
            document.body.classList.add('nav-loading');
        });

        document.addEventListener('livewire:navigated', function() {
            document.getElementById('navigation-loading').style.display = 'none';
            document.body.classList.remove('nav-loading');
            
            // Close dropdown on navigation
            const menu = document.getElementById('userDropdownMenu');
            if (menu) {
                menu.classList.remove('show');
            }
        });
        
        // Logout function
        function logout() {
            fetch('/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            }).then(() => {
                window.location.href = '/login';
            });
        }
    </script>
</body>
</html>
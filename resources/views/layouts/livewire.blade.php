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
    
    .dropdown-role-info {
        padding: 8px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 2px 8px;
        font-size: 0.85rem;
        color: #6c757d;
    }
    
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
                        
                        <a class="nav-link feedback-btn {{ request()->routeIs('feedback.*') ? 'active' : '' }}" 
                           href="{{ route('feedback.index') }}" 
                           wire:navigate>
                            <i class="fas fa-comment-dots me-1"></i> Feedback
                        </a>
                    </nav>
                    
                    <!-- User Dropdown -->
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
                @endauth
            </div>
        </div>
    </div>

    <!-- Loading indicator for navigation -->
    <div id="navigation-loading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, #B22020, #ff6b6b); z-index: 9999;"></div>

    <!-- Page Content -->
    {{ $slot }}

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        function toggleUserDropdown() {
            const menu = document.getElementById('userDropdownMenu');
            menu.classList.toggle('show');
        }

        document.addEventListener('click', function(event) {
            const wrapper = document.querySelector('.user-dropdown-wrapper');
            const menu = document.getElementById('userDropdownMenu');
            
            if (!wrapper.contains(event.target)) {
                menu.classList.remove('show');
            }
        });

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

        // Livewire navigation events
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

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
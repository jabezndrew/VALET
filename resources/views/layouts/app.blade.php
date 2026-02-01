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
   /* Anti-flash CSS */
html {
   background-color: #f8f9fa !important;
}

body {
   background-color: #f8f9fa !important;
   font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
   transition: none !important;
   opacity: 1 !important;
}

/* Prevent white flash during navigation */
[wire\:loading], [wire\:loading\.delay], [wire\:loading\.inline-block], [wire\:loading\.flex] {
   background-color: #f8f9fa !important;
}

.livewire-loading, .nav-loading {
   background-color: #f8f9fa !important;
   opacity: 0.8;
   transition: opacity 0.2s ease;
}

main, .container, .valet-header + * {
   background-color: transparent;
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
   background: #2F623D;
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
   background: #B22020;
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

.available-color { color: #2F623D; }
.occupied-color { color: #B22020; }
.total-color { color: #3A3A3C; }

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
   border-left-color: #2F623D;
}

.parking-space-card.occupied {
   border-left-color: #B22020;
}

.status-badge-small {
   padding: 4px 8px;
   border-radius: 8px;
   font-size: 0.7rem;
   font-weight: bold;
}

.badge-available {
   background: #2F623D;
   color: white;
}

.badge-occupied {
   background: #B22020;
   color: white;
}

.parking-space-compact {
   background: white;
   border-radius: 8px;
   box-shadow: 0 2px 8px rgba(0,0,0,0.1);
   cursor: pointer;
   transition: transform 0.2s, box-shadow 0.2s;
   display: flex;
   flex-direction: column;
   position: relative;
}

.parking-space-compact:hover {
   transform: translateY(-2px);
   box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.parking-space-compact .space-code {
   padding: 7px;
   text-align: center;
   font-size: 0.75rem;
   font-weight: bold;
   color: #333;
   border-radius: 8px 8px 0 0;
}

.parking-space-compact .space-indicator {
   height: 4px;
   width: 100%;
   border-radius: 0 0 8px 8px;
}

.parking-space-compact .space-indicator.available {
   background: #2F623D;
}

.parking-space-compact .space-indicator.occupied {
   background: #B22020;
}

/* Hover popup styles */
.space-popup {
   position: absolute;
   bottom: 100%;
   left: 50%;
   transform: translateX(-50%) translateY(-10px);
   background: white;
   border-radius: 12px;
   box-shadow: 0 8px 24px rgba(0,0,0,0.2);
   padding: 16px;
   min-width: 250px;
   z-index: 1000;
   opacity: 0;
   pointer-events: none;
   transition: opacity 0.2s, transform 0.2s;
   margin-bottom: 8px;
}

.parking-space-compact:hover .space-popup {
   opacity: 1;
   pointer-events: auto;
   transform: translateX(-50%) translateY(0);
}

.space-popup::after {
   content: '';
   position: absolute;
   top: 100%;
   left: 50%;
   transform: translateX(-50%);
   border: 8px solid transparent;
   border-top-color: white;
}

.popup-header {
   display: flex;
   justify-content: space-between;
   align-items: center;
   padding-bottom: 12px;
   border-bottom: 1px solid #eee;
   margin-bottom: 12px;
}

.popup-header strong {
   font-size: 1.1rem;
   color: #333;
}

.popup-badge {
   padding: 4px 10px;
   border-radius: 6px;
   font-size: 0.75rem;
   font-weight: 600;
   text-transform: uppercase;
}

.popup-badge.available {
   background: #2F623D;
   color: white;
}

.popup-badge.occupied {
   background: #B22020;
   color: white;
}

.popup-body {
   margin-bottom: 12px;
}

.popup-info {
   display: flex;
   align-items: center;
   gap: 8px;
   padding: 6px 0;
   font-size: 0.9rem;
   color: #555;
}

.popup-info i {
   width: 16px;
   text-align: center;
   color: #888;
}

.popup-map-btn {
   display: block;
   width: 100%;
   padding: 10px;
   background: linear-gradient(135deg, #2F623D 0%, #3d7a4f 100%);
   color: white;
   text-align: center;
   border-radius: 8px;
   text-decoration: none;
   font-weight: 600;
   transition: all 0.2s;
}

.popup-map-btn:hover {
   background: linear-gradient(135deg, #3d7a4f 0%, #2F623D 100%);
   color: white;
   transform: translateY(-1px);
   box-shadow: 0 4px 12px rgba(47, 98, 61, 0.3);
}

.popup-map-btn i {
   margin-right: 6px;
}

/* Column Navigator Styles */
.column-nav-buttons {
   display: flex;
   flex-wrap: wrap;
   gap: 6px;
}

.column-nav-btn {
   min-width: 32px;
   height: 32px;
   border: 2px solid #B22020;
   background: white;
   color: #B22020;
   border-radius: 6px;
   font-weight: bold;
   font-size: 0.85rem;
   cursor: pointer;
   transition: all 0.2s;
   display: flex;
   align-items: center;
   justify-content: center;
}

.column-nav-btn:hover {
   background: #B22020;
   color: white;
   transform: translateY(-1px);
   box-shadow: 0 3px 10px rgba(178, 32, 32, 0.3);
}

.column-nav-btn:active {
   transform: translateY(0);
}

/* Fix modal overflow for popups */
.modal-body {
   overflow: visible !important;
}

.modal-content {
   overflow: visible !important;
}

/* Ensure rows don't clip popups */
.row {
   overflow: visible !important;
}

.navbar-nav {
   gap: 15px;
}

.nav-link {
   color: rgba(255,255,255,0.9) !important;
   font-weight: 600;
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
   background: #A0A0A0;
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

/* NEW VALET COLOR SCHEME - Updated according to provided palette */
.card-total {
   background-color: #3A3A3C !important; /* Charcoal Gray */
   color: white !important;
}

.card-active {
   background-color: #2F623D !important; /* Forest Green */
   color: white !important;
}

.card-inactive {
   background-color: #B22020 !important; /* Keep as is (Red) */
   color: white !important;
}

.card-types {
   background-color: #A0A0A0 !important; /* Warm Gray */
   color: white !important;
}

/* New Button Classes for VALET Color Scheme */
.btn-valet-charcoal {
   background-color: #3A3A3C;
   border-color: #3A3A3C;
   color: white;
}

.btn-valet-charcoal:hover {
   background-color: #2d2d2f;
   border-color: #2d2d2f;
   color: white;
}

.btn-valet-forest {
   background-color: #2F623D;
   border-color: #2F623D;
   color: white;
}

.btn-valet-forest:hover {
   background-color: #255030;
   border-color: #255030;
   color: white;
}

.btn-valet-gray {
   background-color: #A0A0A0;
   border-color: #A0A0A0;
   color: white;
}

.btn-valet-gray:hover {
   background-color: #8a8a8a;
   border-color: #8a8a8a;
   color: white;
}

/* Card Header Colors */
.bg-valet-charcoal {
   background-color: #3A3A3C !important;
}

.bg-valet-forest {
   background-color: #2F623D !important;
}

.bg-valet-gray {
   background-color: #A0A0A0 !important;
}

/* Loading states */
.nav-loading {
   opacity: 0.8;
   pointer-events: none;
}

/* Progress bar animation */
@keyframes progress {
   0% { transform: translateX(-100%); }
   50% { transform: translateX(0%); }
   100% { transform: translateX(100%); }
}

/* Badges with new colors */
.badge-total { background-color: #3A3A3C; color: white; }
.badge-active { background-color: #2F623D; color: white; }
.badge-inactive { background-color: #B22020; color: white; }
.badge-types { background-color: #A0A0A0; color: white; }

/* Progress bars with new colors */
.progress-bar-total { background-color: #3A3A3C; }
.progress-bar-active { background-color: #2F623D; }
.progress-bar-inactive { background-color: #B22020; }
.progress-bar-types { background-color: #A0A0A0; }

/* Feedback type badges */
.badge-bug { background-color: #B22020; }
.badge-suggestion { background-color: #3A3A3C; }
.badge-complaint { background-color: #fd7e14; }
.badge-compliment { background-color: #2F623D; }
.badge-general { background-color: #A0A0A0; }

/* Status badges */
.badge-new { background-color: #3A3A3C; }
.badge-in-progress { background-color: #fd7e14; }
.badge-resolved { background-color: #2F623D; }
.badge-closed { background-color: #A0A0A0; }
</style>
   @livewireStyles
   @stack('styles')
</head>
<body>
   @php
       use Illuminate\Support\Facades\DB;
   @endphp

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
                           Dashboard
                       </a>

                       @if(auth()->user()->canViewCars())
                       <a class="nav-link {{ request()->routeIs('cars.*') ? 'active' : '' }}"
                          href="{{ route('cars.index') }}"
                          wire:navigate>
                           Vehicles
                       </a>
                       @endif

                       @if(auth()->user()->canManageUsers())
                       <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
                          href="{{ route('admin.users') }}"
                          wire:navigate>
                           Users
                           @if(auth()->user()->canApprovePendingAccounts())
                               @php
                                   $pendingCount = DB::table('pending_accounts')->where('status', 'pending')->count();
                               @endphp
                               @if($pendingCount > 0)
                                   <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                               @endif
                           @endif
                       </a>
                       @endif

                        <a class="nav-link {{ request()->routeIs('parking-display') ? 'active' : '' }}"
                          href="{{ route('parking-display') }}"
                          wire:navigate>
                           Parking Map
                        </a>
                       @if(in_array(auth()->user()->role, ['admin', 'ssd', 'security']))
                       <a class="nav-link {{ request()->routeIs('parking-log') ? 'active' : '' }}"
                          href="{{ route('parking-log') }}"
                          wire:navigate>
                           Parking Log
                       </a>
                       @endif
                       @if(auth()->user()->role === 'admin')
                       <a class="nav-link {{ request()->routeIs('admin.sensors') ? 'active' : '' }}"
                          href="{{ route('admin.sensors') }}"
                          wire:navigate>
                           Sensors
                       </a>
                       <a class="nav-link {{ request()->routeIs('admin.rfid') ? 'active' : '' }}"
                          href="{{ route('admin.rfid') }}"
                          wire:navigate>
                           RFID Tags
                       </a>
                       @endif

                       <!-- Feedback Button - Now matches other nav items -->
                       <a class="nav-link {{ request()->routeIs('feedback.*') ? 'active' : '' }}"
                          href="{{ route('feedback.index') }}"
                          wire:navigate>
                           Feedback
                       </a>
                   </nav>

                   <!-- User Dropdown - ONLY this has button styling -->
                   <div class="user-dropdown-wrapper">
                       <button class="user-dropdown" onclick="toggleUserDropdown()">
                           {{ auth()->user()->name }}
                           <i class="fas fa-chevron-down ms-2"></i>
                       </button>

                       <div class="user-dropdown-menu" id="userDropdownMenu">
                           <div class="dropdown-role-info">
                               <strong>{{ auth()->user()->getRoleDisplayName() }}</strong>
                           </div>
                           <div class="dropdown-divider"></div>
                           <button onclick="logout()" class="dropdown-item logout-btn">
                               Logout
                           </button>
                       </div>
                   </div>
               </div>
               @else
               <div class="d-flex">
                   <a href="{{ route('login') }}" class="btn btn-outline-light">
                       Login
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

   <!-- RFID Scan Monitor (for Security/SSD/Admin) -->
   @auth
       @livewire('rfid-scan-monitor')
   @endauth

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
               Livewire.navigate('/login');
           });
       }
   </script>
   @stack('scripts')
</body>
</html>

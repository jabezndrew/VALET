<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#B22020">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="VALET Guard">

    <title>VALET Guard Assist</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Icons for PWA -->
    <link rel="apple-touch-icon" href="/images/valet-logo.jpg">
    <link rel="icon" type="image/png" href="/images/valet-logo.jpg">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        /* Guard Header - Compact */
        .guard-header {
            background: linear-gradient(135deg, #B22020 0%, #8B0000 100%);
            padding: 12px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .guard-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .guard-logo img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            padding: 4px;
        }

        .guard-logo-text {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .guard-logo-sub {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .guard-status {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .auth-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .auth-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Main Content */
        .guard-content {
            padding-top: 70px;
            height: 100vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Floor Selector - Horizontal scroll */
        .floor-selector {
            display: flex;
            gap: 15px;
            padding: 20px 25px;
            overflow-x: auto;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            -webkit-overflow-scrolling: touch;
        }

        .floor-selector::-webkit-scrollbar {
            display: none;
        }

        .floor-btn {
            flex-shrink: 0;
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 16px;
            padding: 18px 30px;
            min-width: 180px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .floor-btn.active {
            background: linear-gradient(135deg, #B22020 0%, #8B0000 100%);
            border-color: #B22020;
            color: white;
            box-shadow: 0 4px 15px rgba(178, 32, 32, 0.3);
        }

        .floor-btn .floor-name {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .floor-btn .floor-stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 1.1rem;
        }

        .floor-btn .stat-available {
            color: #28a745;
            font-weight: 700;
        }

        .floor-btn .stat-occupied {
            color: #dc3545;
            font-weight: 700;
        }

        .floor-btn.active .stat-available,
        .floor-btn.active .stat-occupied {
            color: white;
            opacity: 0.9;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 10px;
            padding: 12px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            overflow-x: auto;
        }

        .filter-btn {
            flex-shrink: 0;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background: #3A3A3C;
            border-color: #3A3A3C;
            color: white;
        }

        .filter-btn.available.active {
            background: #28a745;
            border-color: #28a745;
        }

        .filter-btn.occupied.active {
            background: #dc3545;
            border-color: #dc3545;
        }

        .filter-btn.issues.active {
            background: #fd7e14;
            border-color: #fd7e14;
        }

        /* Map Container */
        .map-container {
            padding: 15px;
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Parking Map Styles - Reused from public display */
        .parking-map-wrapper {
            width: 100%;
            height: calc(100vh - 220px);
            min-height: 400px;
            overflow: hidden;
            background: white;
            border-radius: 15px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .parking-map-container {
            position: relative;
            width: 85rem;
            height: 80rem;
            background: white;
            transform: scale(0.38) rotate(90deg);
            transform-origin: center center;
        }

        /* Parking Spots - Guard specific (clickable) */
        .parking-spot-box {
            position: absolute;
            width: 60px;
            height: 85px;
            border: 3px solid;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 700;
            font-size: 18px;
        }

        .parking-spot-box.available {
            border: 4px solid #28a745;
            background: linear-gradient(135deg, #2ed573 0%, #28a745 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(46, 213, 115, 0.4);
        }

        .parking-spot-box.occupied {
            border: 4px solid #dc3545;
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .parking-spot-box.blocked {
            border: 4px solid #fd7e14;
            background: rgba(253, 126, 20, 0.15);
            color: #fd7e14;
            box-shadow: 0 4px 12px rgba(253, 126, 20, 0.3);
        }

        .parking-spot-box.inactive {
            border: 3px dashed #6c757d;
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            opacity: 0.5;
            cursor: not-allowed;
        }

        .parking-spot-box.manual-override {
            position: relative;
        }

        .parking-spot-box.manual-override::after {
            content: 'M';
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            background: #fd7e14;
            color: white;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .parking-spot-box:active:not(.inactive) {
            transform: scale(0.95);
        }

        /* Facilities */
        .facility {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
        }

        .facility.elevator {
            background-color: #d5d821;
            color: black;
        }

        .facility.stairs {
            background-color: #d5d821;
            color: black;
        }

        .facility.entrance {
            background-color: #3ed120;
            color: black;
        }

        .facility.exit-sign {
            background: #B22020;
            color: white;
        }

        .facility.rotated-left {
            transform: rotate(-90deg);
        }

        /* PIN Modal */
        .pin-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .pin-modal {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .pin-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #B22020;
        }

        .pin-subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .pin-input {
            width: 100%;
            padding: 15px;
            font-size: 2rem;
            text-align: center;
            letter-spacing: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .pin-input:focus {
            outline: none;
            border-color: #B22020;
        }

        .pin-error {
            color: #dc3545;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .pin-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #B22020 0%, #8B0000 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .pin-submit:active {
            transform: scale(0.98);
        }

        /* Action Modal */
        .action-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            z-index: 2000;
        }

        .action-modal {
            background: white;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        .action-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }

        .action-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
        }

        .action-body {
            padding: 20px;
        }

        .space-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .space-code {
            font-size: 1.5rem;
            font-weight: 700;
            color: #B22020;
        }

        .space-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .space-status.available { background: #28a745; color: white; }
        .space-status.occupied { background: #dc3545; color: white; }
        .space-status.blocked { background: #fd7e14; color: white; }

        .action-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .action-tab {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-tab.active {
            background: #3A3A3C;
            border-color: #3A3A3C;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-select, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .status-options {
            display: flex;
            gap: 10px;
        }

        .status-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-option.selected {
            border-width: 3px;
        }

        .status-option.available.selected {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        .status-option.occupied.selected {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .status-option.blocked.selected {
            border-color: #fd7e14;
            background: rgba(253, 126, 20, 0.1);
        }

        .action-submit {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .action-submit.override {
            background: linear-gradient(135deg, #3A3A3C 0%, #2d2d2f 100%);
            color: white;
        }

        .action-submit.report {
            background: linear-gradient(135deg, #fd7e14 0%, #e06b00 100%);
            color: white;
        }

        .action-submit:active {
            transform: scale(0.98);
        }

        /* Toast Messages */
        .toast-container {
            position: fixed;
            bottom: 80px;
            left: 20px;
            right: 20px;
            z-index: 3000;
        }

        .toast {
            background: #333;
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }

        .toast.success { background: #28a745; }
        .toast.error { background: #dc3545; }
        .toast.warning { background: #fd7e14; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        /* Extra large screens (1920px+) */
        @media (min-width: 1920px) {
            .parking-map-container {
                transform: scale(0.50) rotate(90deg);
            }
        }

        /* Large screens (1400px - 1919px) */
        @media (min-width: 1400px) and (max-width: 1919px) {
            .parking-map-container {
                transform: scale(0.45) rotate(90deg);
            }
        }

        /* Desktop (1200px - 1399px) */
        @media (min-width: 1200px) and (max-width: 1399px) {
            .parking-map-container {
                transform: scale(0.40) rotate(90deg);
            }
        }

        /* Tablet landscape / small desktop (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199px) {
            .parking-map-container {
                transform: scale(0.36) rotate(90deg);
            }
        }

        /* Tablet portrait (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .parking-map-container {
                transform: scale(0.32) rotate(90deg);
            }

            .floor-btn {
                padding: 15px 20px;
                min-width: 150px;
            }

            .floor-btn .floor-name {
                font-size: 1.1rem;
            }

            .floor-btn .floor-stats {
                font-size: 1rem;
            }
        }

        /* Mobile landscape (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .parking-map-container {
                transform: scale(0.28) rotate(90deg);
            }

            .parking-map-wrapper {
                height: calc(100vh - 180px);
            }

            .floor-btn {
                padding: 12px 16px;
                min-width: 130px;
            }
        }

        /* Mobile portrait (up to 575px) */
        @media (max-width: 575px) {
            .parking-map-container {
                transform: scale(0.24) rotate(90deg);
            }

            .parking-map-wrapper {
                height: calc(100vh - 160px);
            }

            .guard-header {
                padding: 10px 15px;
            }

            .guard-logo-text {
                font-size: 1rem;
            }

            .floor-btn {
                padding: 10px 14px;
                min-width: 110px;
            }

            .floor-btn .floor-name {
                font-size: 1rem;
            }

            .floor-btn .floor-stats {
                font-size: 0.9rem;
                gap: 10px;
            }
        }

        /* Landscape orientation for tablets */
        @media (orientation: landscape) and (max-height: 700px) {
            .parking-map-wrapper {
                height: calc(100vh - 160px);
            }

            .floor-selector {
                padding: 12px 20px;
            }

            .floor-btn {
                padding: 10px 18px;
            }
        }
    </style>

    @livewireStyles
</head>
<body>
    {{ $slot }}

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @livewireScripts

    <script>
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed:', err));
            });
        }

        // Prevent zoom on double tap
        document.addEventListener('touchend', function(event) {
            const now = Date.now();
            const DOUBLE_TAP_DELAY = 300;
            if (now - (this.lastTouchEnd || 0) <= DOUBLE_TAP_DELAY) {
                event.preventDefault();
            }
            this.lastTouchEnd = now;
        }, { passive: false });
    </script>

    @stack('scripts')
</body>
</html>

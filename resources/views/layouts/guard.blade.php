<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Guard Assist">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/valet-logo.jpg">

    <title>Guard Assist - VALET</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
        }

        .guard-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* PIN Modal Styles */
        .pin-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .pin-modal {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 350px;
            width: 90%;
        }

        .pin-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        .pin-header h2 {
            color: #fff;
            margin-bottom: 5px;
        }

        .pin-header p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 25px;
        }

        .pin-error {
            background: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .pin-input {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            text-align: center;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
            color: #fff;
            margin-bottom: 15px;
            letter-spacing: 10px;
        }

        .pin-input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .pin-submit {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: #fff;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .pin-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.4);
        }

        /* Header Styles */
        .guard-header {
            background: rgba(0,0,0,0.3);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-logo {
            width: 45px;
            height: 45px;
            border-radius: 10px;
        }

        .header-left h1 {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
        }

        .header-right {
            display: flex;
            gap: 10px;
        }

        .refresh-btn, .logout-btn {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .refresh-btn {
            background: rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }

        .logout-btn {
            background: rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        /* Flash Messages */
        .flash-message {
            margin: 10px 20px;
            padding: 12px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flash-message.success {
            background: rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }

        /* Floor Selector */
        .floor-selector {
            display: flex;
            gap: 12px;
            padding: 15px 20px;
            overflow-x: auto;
            background: rgba(0,0,0,0.2);
            flex-wrap: wrap;
            justify-content: center;
        }

        .floor-btn {
            padding: 18px 30px;
            min-width: 180px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .floor-btn:hover {
            background: rgba(255,255,255,0.15);
        }

        .floor-btn.active {
            background: linear-gradient(135deg, #B22020, #8B0000);
            border-color: #B22020;
        }

        .floor-btn .floor-name {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .floor-btn .floor-stats {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .floor-btn .floor-stats .available {
            color: #4CAF50;
            font-weight: 600;
        }

        .floor-btn .floor-stats .total {
            opacity: 0.7;
        }

        /* Parking Map */
        .parking-map-wrapper {
            flex: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            height: calc(100vh - 220px);
        }

        .parking-map-container {
            transform: scale(0.38) rotate(90deg);
            transform-origin: center center;
        }

        .parking-grid {
            display: flex;
            gap: 20px;
            padding: 20px;
        }

        .parking-column {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .column-label {
            text-align: center;
            font-weight: 700;
            font-size: 1.5rem;
            color: rgba(255,255,255,0.8);
            padding: 10px;
        }

        .slots-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .parking-slot {
            width: 120px;
            height: 80px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
        }

        .parking-slot.available {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-color: #388E3C;
        }

        .parking-slot.occupied {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            border-color: #c62828;
        }

        .parking-slot:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        .parking-slot .slot-number {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .parking-slot i {
            font-size: 1.5rem;
            margin-top: 5px;
        }

        .no-floor-selected {
            text-align: center;
            color: rgba(255,255,255,0.5);
            padding: 50px;
        }

        .no-floor-selected i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        /* Action Modal */
        .action-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .action-modal {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .action-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .action-modal-header h3 {
            font-size: 1.3rem;
        }

        .close-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 18px;
        }

        .action-modal-body {
            padding: 20px;
        }

        .current-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-badge.available {
            background: rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }

        .status-badge.occupied {
            background: rgba(244, 67, 54, 0.3);
            color: #f44336;
        }

        .action-section {
            margin-bottom: 25px;
        }

        .action-section h4 {
            color: rgba(255,255,255,0.8);
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .override-buttons {
            display: flex;
            gap: 10px;
        }

        .override-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .override-btn.available {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: #fff;
        }

        .override-btn.occupied {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: #fff;
        }

        .override-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .incident-select, .incident-notes {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: #fff;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .incident-select option {
            background: #1a1a2e;
            color: #fff;
        }

        .incident-notes {
            resize: none;
        }

        .report-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: #fff;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .report-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top-color: #B22020;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .floor-btn {
                padding: 12px 20px;
                min-width: 130px;
            }

            .floor-btn .floor-name {
                font-size: 1rem;
            }

            .floor-btn .floor-stats {
                font-size: 0.9rem;
            }

            .parking-map-container {
                transform: scale(0.24) rotate(90deg);
            }
        }

        @media (min-width: 481px) and (max-width: 768px) {
            .parking-map-container {
                transform: scale(0.32) rotate(90deg);
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .parking-map-container {
                transform: scale(0.40) rotate(90deg);
            }

            .floor-btn {
                padding: 20px 35px;
                min-width: 200px;
            }

            .floor-btn .floor-name {
                font-size: 1.4rem;
            }

            .floor-btn .floor-stats {
                font-size: 1.2rem;
            }
        }

        @media (min-width: 1025px) {
            .parking-map-container {
                transform: scale(0.50) rotate(90deg);
            }

            .floor-btn {
                padding: 22px 40px;
                min-width: 220px;
            }

            .floor-btn .floor-name {
                font-size: 1.5rem;
            }

            .floor-btn .floor-stats {
                font-size: 1.3rem;
            }
        }
    </style>

    @livewireStyles
</head>
<body>
    {{ $slot }}

    @livewireScripts

    <script>
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered:', registration);
                    })
                    .catch(function(error) {
                        console.log('SW registration failed:', error);
                    });
            });
        }

        // Auto-refresh every 5 seconds
        setInterval(function() {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('refreshData');
            }
        }, 5000);

        // Hide flash messages after 3 seconds
        document.addEventListener('livewire:navigated', function() {
            setTimeout(function() {
                const flash = document.querySelector('.flash-message');
                if (flash) {
                    flash.style.opacity = '0';
                    setTimeout(() => flash.remove(), 300);
                }
            }, 3000);
        });
    </script>
</body>
</html>

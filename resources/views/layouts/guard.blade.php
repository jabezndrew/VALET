<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<<<<<<< HEAD
    <meta name="theme-color" content="#1a1a2e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Guard Assist">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/valet-logo.jpg">

    <title>Guard Assist - VALET</title>

=======
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
>>>>>>> develop
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
<<<<<<< HEAD
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
=======
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
>>>>>>> develop
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
<<<<<<< HEAD
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
=======
            z-index: 1000;
        }

        .guard-logo {
>>>>>>> develop
            display: flex;
            align-items: center;
            gap: 12px;
        }

<<<<<<< HEAD
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
=======
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
>>>>>>> develop
        }

        .floor-btn .floor-name {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .floor-btn .floor-stats {
<<<<<<< HEAD
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
=======
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
>>>>>>> develop
            transform: scale(0.38) rotate(90deg);
            transform-origin: center center;
        }

<<<<<<< HEAD
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
=======
        /* Parking Spots - Guard specific (clickable) */
        .parking-spot-box {
            position: absolute;
            width: 60px;
            height: 85px;
            border: 3px solid;
            border-radius: 8px;
>>>>>>> develop
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
<<<<<<< HEAD
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
=======
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
>>>>>>> develop
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
<<<<<<< HEAD
            background: rgba(0,0,0,0.5);
=======
            background: rgba(0,0,0,0.8);
>>>>>>> develop
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

<<<<<<< HEAD
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
=======
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
>>>>>>> develop

            .floor-btn .floor-name {
                font-size: 1rem;
            }

            .floor-btn .floor-stats {
                font-size: 0.9rem;
<<<<<<< HEAD
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
=======
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
>>>>>>> develop
            }
        }
    </style>

    @livewireStyles
</head>
<body>
    {{ $slot }}

<<<<<<< HEAD
=======
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

>>>>>>> develop
    @livewireScripts

    <script>
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
<<<<<<< HEAD
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
=======
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
>>>>>>> develop
</body>
</html>

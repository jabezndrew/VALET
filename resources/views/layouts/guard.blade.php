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
    <!-- Guard CSS -->
    <link rel="stylesheet" href="/css/guard.css">

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

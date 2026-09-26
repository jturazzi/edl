<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="icon" type="image/png" sizes="192x192" href="/icon-192.png?v=2">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <title>{{ config('app.name', 'État des lieux') }}</title>
    <script>
        window.__APP_NAME__ = @json(config('app.name', 'État des lieux'));
        window.__APP_VERSION__ = @json(config('app.version'));
        window.__SENTRY_DSN__ = @json(config('sentry.js_dsn'));
        window.__SENTRY_ENV__ = @json(config('sentry.environment'));
        window.__APP_LOGO__ = @json(config('app.logo', ''));
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div id="app"></div>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .catch((err) => console.error('Service Worker non enregistré :', err));
            });
        }
    </script>
</body>
</html>

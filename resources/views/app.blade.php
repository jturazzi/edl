<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <!-- Icônes : à ajouter dans public/images/ une fois disponibles -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1e40af">
    <title>{{ config('app.name', 'État des lieux') }}</title>
    <script>
        window.__APP_NAME__ = @json(config('app.name', 'État des lieux'));
        window.__APP_LOGO__ = @json(config('app.logo', ''));
        window.__APP_DEPARTEMENT__ = @json(config('app.departement', '42'));
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

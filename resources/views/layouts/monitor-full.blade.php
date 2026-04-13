<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Security O&M Monitor - Wara XDR')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            min-height: 100%;
        }
        body.monitor-full {
            font-family: 'Inter', sans-serif;
            background: #050d1a;
            color: #e2e8f0;
            overflow-x: hidden;
        }
    </style>
    @stack('styles')
</head>
<body class="monitor-full">
    @yield('content')
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Operator QA') - Yazaki Monitoring</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: var(--font-sans); }
        /* Disable callout and user-select on primary interactive touch elements for app feel */
        .touch-manipulation { touch-action: manipulation; }
    </style>
    @stack('styles')
</head>
<body class="bg-canvas text-gray-800 min-h-screen py-4 sm:py-6 px-3 sm:px-4 flex justify-center items-start antialiased touch-manipulation">

    <div class="w-full max-w-md flex flex-col justify-start">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Monitoring Defect') - Yazaki QA</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body { font-family: var(--font-sans); }
        .overflow-x-auto::-webkit-scrollbar { height: 6px; }
        .overflow-x-auto::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 8px; }
        .overflow-x-auto::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 8px; }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
    @stack('styles')
</head>
<body class="bg-canvas text-gray-800 h-screen overflow-hidden flex antialiased">

    <!-- Admin Left Sidebar Partial -->
    @include('partials.sidebar')

    <!-- Main Workspace Area -->
    <main class="flex-1 overflow-y-auto px-6 lg:px-10 py-8 flex flex-col justify-start">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Farmers Hostel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        .hidden-form { display: none; }
    </style>
    @livewireStyles
    @stack('styles')
</head>
<body class="antialiased overflow-x-hidden font-sans text-ink">

    <!-- Ambient campus backdrop -->
    <div class="fixed inset-0 z-0">
        <img src="{{ asset('image/hostel1.jpg') }}" class="w-full h-full object-cover animate-slow-zoom" alt="Farmers Hostel">
        <div class="absolute inset-0 bg-gradient-to-b from-clsu-950/80 via-clsu-950/60 to-clsu-950/90"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,rgba(8,36,20,0.55)_100%)]"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center p-4 md:p-8">
        @yield('content')
    </div>

    @livewireScripts
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.error-alert, #success-alert').forEach(el => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    setTimeout(() => { el.style.display = 'none'; }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>

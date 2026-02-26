<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Hostel | Book Now</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Glassmorphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(17px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Custom Brand Color Utility */
        .bg-brand { background-color: #3B612A; }
        .text-brand { color: #3B612A; }
        .border-brand { border-color: #3B612A; }
        .focus-ring-brand:focus { --tw-ring-color: #3B612A; }

        .hidden-form { display: none; }
    </style>
</head>
<body class="antialiased overflow-x-hidden">

    <div class="fixed inset-0 z-0">
        <img src="{{ asset('image/hostel1.jpg') }}" 
             class="w-full h-full object-cover" alt="Hostel Vibe">
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center p-4 md:p-8">
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
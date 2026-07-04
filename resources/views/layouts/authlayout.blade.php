<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Hostel | Book Now</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
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
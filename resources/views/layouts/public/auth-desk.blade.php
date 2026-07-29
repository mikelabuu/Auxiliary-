<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Farmers Hostel')</title>
    <meta name="theme-color" content="#FAF7EF">
    @vite(['resources/css/app.css'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Same three faces as layouts.public.base — the auth screen is the same
         property as the rest of the site, so it loads the same type system.
         No icon font: the icons here are inline SVG in the world's own hairline
         grammar (see 14-auth.css). --}}
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..600;1,400..500&family=Oswald:wght@300;400;500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>[x-cloak],.hidden-form{display:none}</style>

    {{-- Marks that JS is available, so the heading can stay hidden for the few
         frames between paint and the word-split (see .fha-js in 14-auth.css).
         form-js always reveals it, even if the font promise never settles, so a
         script failure can never leave a heading invisible. --}}
    <script>document.documentElement.classList.add('fha-js');</script>
</head>
{{-- theme-boutique re-points canvas/ink/fonts at the Boutique Farmstead values,
     exactly as the public base layout does. The sign-in screen is not a separate
     visual world; it is the same house seen from the front desk. --}}
<body class="theme-boutique fha-desk-body antialiased">

    {{-- The room: limewashed cream wall, daylight falling from the upper left,
         and the site-wide film grain that keeps the paper from reading digital. --}}
    <div class="fha-desk-room" aria-hidden="true">
        <span class="fha-desk-daylight"></span>
        <span class="fha-desk-grain"></span>
    </div>

    @yield('content')

    @stack('scripts')
</body>
</html>

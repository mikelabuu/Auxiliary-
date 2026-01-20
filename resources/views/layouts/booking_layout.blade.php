<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Farmers Hostel')</title>

    {{-- Booking CSS --}}
    <link rel="stylesheet" href="{{ asset('css/booking.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bookingModal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/galleryReviews.css') }}">
    <link rel="stylesheet" href="{{ asset('css/payment.css') }}">

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap 5 JS (Bundle includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- SweetAlert --}}
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    {{-- LightBox --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox-plus-jquery.min.js"></script>
</head>
<body>

<!-- Nav Bar -->
    <main class="flex-1 flex flex-col">
        <nav>
            <div class="logo">
                <a href="{{ route('booking.form') }}"><img class="fhlogo" src="{{ asset('image/FHLogo2.png') }}" alt="logo" /></a>
            </div>
            <ul>
                <li><a href="{{ route('booking.form') }}">Home</a></li>
                <li><a href="{{ route('booking.form') }}#rooms">Rooms</a></li>
                <li><a href="#Footer">Contact Us</a></li>
                <li><a href="{{ route('settings.profile') }}" class="user-settings">{{ $username }}</a></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf 
                        <button type="submit" class="logoutbtn">Log out</button>
                    </form>
                </li>
            </ul>
        </nav>
        @yield('content')
  </main> 
</body>

@include('footer')
</html>

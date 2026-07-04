<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Farmers Hostel')</title>

    <!-- Tailwind & Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- LightBox for Gallery -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox-plus-jquery.min.js"></script>

    <!-- Flatpickr Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Swiper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    @livewireStyles
    @stack('styles')
</head>
<body class="antialiased font-sans bg-white-canvas text-portrait-ink flex flex-col min-h-screen selection:bg-mint-wash selection:text-portrait-ink">

    <!-- Floating Pill Nav -->
    <header class="fixed top-4 left-0 right-0 z-50 px-4 sm:px-6 lg:px-8 flex justify-center w-full pointer-events-none">
        <div class="pointer-events-auto w-full max-w-6xl bg-white/75 backdrop-blur-xl rounded-[32px] h-[64px] px-[24px] flex items-center justify-between shadow-lg shadow-portrait-ink/5 border border-white/50 transition-all duration-300 hover:bg-white/85">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <img class="h-8 w-auto object-contain transition-transform duration-300 group-hover:scale-105" src="{{ asset('image/FHLogo2.png') }}" alt="Farmers Hostel Logo" />
                    <span class="block text-[17px] font-bold text-portrait-ink tracking-tight font-display group-hover:text-nautical-teal transition-colors">Farmers Hostel</span>
                </a>
            </div>

            <!-- Center Nav Links -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="{{ route('home') }}" class="relative text-[15px] font-semibold text-slate-700 hover:text-nautical-teal transition-colors group py-1">Home<span class="absolute left-0 bottom-0 w-0 h-[2px] bg-nautical-teal transition-all duration-300 group-hover:w-full rounded-full"></span></a>
                <a href="{{ route('home') }}#rooms" class="relative text-[15px] font-semibold text-slate-700 hover:text-nautical-teal transition-colors group py-1">Rooms<span class="absolute left-0 bottom-0 w-0 h-[2px] bg-nautical-teal transition-all duration-300 group-hover:w-full rounded-full"></span></a>
                @auth
                    <a href="{{ route('settings.bookings') }}" class="relative text-[15px] font-semibold text-slate-700 hover:text-nautical-teal transition-colors group py-1">My Bookings<span class="absolute left-0 bottom-0 w-0 h-[2px] bg-nautical-teal transition-all duration-300 group-hover:w-full rounded-full"></span></a>
                @endauth
            </nav>

            <!-- Right Side Actions & User Menu -->
            <div class="hidden md:flex items-center gap-4">
                    @auth
                        <!-- User Dropdown Menu -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="flex items-center gap-2.5 px-3 py-1.5 rounded-full hover:bg-slate-50 transition-all border border-slate-200/60 cursor-pointer select-none shadow-sm hover:shadow">
                                <div class="w-8 h-8 rounded-full bg-nautical-teal text-white flex items-center justify-center font-bold text-sm shadow-inner">
                                    {{ substr(auth()->user()->username ?? 'U', 0, 1) }}
                                </div>
                                <span class="text-[14px] font-bold text-slate-700 ml-1">{{ $username ?? auth()->user()->username ?? 'Account' }}</span>
                                <span class="material-icons text-slate-400 transition-transform duration-200 text-[20px]" :class="open ? 'rotate-180' : ''">expand_more</span>
                            </button>

                            <!-- Dropdown list -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-3 w-56 rounded-2xl bg-white border border-slate-100 shadow-[0_10px_40px_rgba(0,0,0,0.08)] py-2 z-50 overflow-hidden"
                                 style="display: none;"
                            >
                                <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 px-4 py-3 text-[14px] font-semibold text-slate-600 hover:text-nautical-teal hover:bg-slate-50 transition-all">
                                    <span class="material-icons text-slate-400 text-[18px]">person</span>
                                    My Profile
                                </a>
                                <a href="{{ route('settings.bookings') }}" class="flex items-center gap-3 px-4 py-3 text-[14px] font-semibold text-slate-600 hover:text-nautical-teal hover:bg-slate-50 transition-all">
                                    <span class="material-icons text-slate-400 text-[18px]">book</span>
                                    My Bookings
                                </a>
                                <a href="{{ route('settings.transactions') }}" class="flex items-center gap-3 px-4 py-3 text-[14px] font-semibold text-slate-600 hover:text-nautical-teal hover:bg-slate-50 transition-all">
                                    <span class="material-icons text-slate-400 text-[18px]">payments</span>
                                    Transactions
                                </a>
                                <div class="h-px bg-slate-100 my-1 mx-4"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf 
                                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-[14px] font-bold text-cherry-red hover:bg-red-50/50 transition-all cursor-pointer text-left">
                                        <span class="material-icons text-cherry-red/70 text-[18px]">logout</span>
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-[15px] font-semibold text-slate-700 hover:text-nautical-teal transition-colors px-2">Log In</a>
                        <a href="{{ route('login') }}" class="relative rounded-full p-[2px] inline-flex items-center justify-center group overflow-hidden bg-gradient-to-r from-nautical-teal to-cobalt-pop shadow-[0_4px_12px_rgba(8,78,114,0.2)] hover:shadow-[0_6px_16px_rgba(8,78,114,0.3)] transition-all">
                            <span class="relative bg-white rounded-full px-5 py-1.5 text-portrait-ink text-[14px] font-bold group-hover:bg-transparent group-hover:text-white transition-colors duration-200">Sign Up</span>
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobileMenuBtn" class="text-portrait-ink w-8 h-8 rounded-full hover:bg-slate-50 flex items-center justify-center transition-all cursor-pointer">
                        <span class="material-icons text-[24px]">menu</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Navigation -->
    <div id="mobileDrawer" class="fixed inset-0 z-50 flex justify-end bg-slate-900/40 backdrop-blur-xs transition-opacity duration-300 hidden">
        <div class="bg-white w-72 h-full shadow-2xl p-6 flex flex-col justify-between border-l border-slate-100">
            <div>
                <!-- Drawer Header -->
                <div class="flex items-center justify-between pb-6 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <img class="h-10 w-auto" src="{{ asset('image/FHLogo2.png') }}" alt="Farmers Hostel" />
                        <span class="text-sm font-black text-brand tracking-tight">Farmers Hostel</span>
                    </div>
                    <button id="mobileDrawerCloseBtn" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center transition-all cursor-pointer">
                        <span class="material-icons text-[20px]">close</span>
                    </button>
                </div>

                <!-- Drawer Links -->
                <nav class="flex flex-col gap-4 mt-8">
                    <a href="{{ route('home') }}" class="text-slate-700 hover:text-brand font-bold text-base transition-all flex items-center gap-3">
                        <span class="material-icons text-[20px]">home</span> Home
                    </a>
                    <a href="{{ route('home') }}#rooms" id="mobileRoomsLink" class="text-slate-700 hover:text-brand font-bold text-base transition-all flex items-center gap-3">
                        <span class="material-icons text-[20px]">hotel</span> Rooms
                    </a>
                    <a href="#Footer" id="mobileContactLink" class="text-slate-700 hover:text-brand font-bold text-base transition-all flex items-center gap-3">
                        <span class="material-icons text-[20px]">contacts</span> Contact Us
                    </a>
                    @auth
                        <hr class="border-slate-100 my-2" />
                        <a href="{{ route('settings.profile') }}" class="text-slate-700 hover:text-brand font-bold text-base transition-all flex items-center gap-3">
                            <span class="material-icons text-[20px]">person</span> My Profile
                        </a>
                        <a href="{{ route('settings.bookings') }}" class="text-slate-700 hover:text-brand font-bold text-base transition-all flex items-center gap-3">
                            <span class="material-icons text-[20px]">book</span> My Bookings
                        </a>
                        <a href="{{ route('settings.transactions') }}" class="text-slate-700 hover:text-brand font-bold text-base transition-all flex items-center gap-3">
                            <span class="material-icons text-[20px]">payments</span> Transactions
                        </a>
                    @endauth
                </nav>
            </div>

            <!-- Drawer Bottom -->
            <div>
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf 
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition-all cursor-pointer">
                            <span class="material-icons text-[20px]">logout</span> Log Out
                        </button>
                    </form>
                @else
                    <x-booking.button variant="primary" :href="route('login')" class="w-full py-3">Log In / Sign Up</x-booking.button>
                @endauth
            </div>
        </div>
    </div>

    <!-- Main Content wrapper -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Global Footer -->
    <footer class="bg-slate-950 border-t border-slate-900 mt-auto pt-16 pb-10" id="Footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-8 pb-10 border-b border-slate-100">
                <!-- Branding column -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img class="h-12 w-auto brightness-0 invert opacity-90" src="{{ asset('image/FHLogo2.png') }}" alt="Farmers Hostel Logo" />
                        <div>
                            <span class="block text-base font-black text-white tracking-tight">Farmers Hostel</span>
                            <span class="block text-[10px] font-bold text-accent uppercase tracking-widest mt-0.5">CLSU Campus</span>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-slate-400 leading-relaxed max-w-sm">
                        Providing comfortable, secure, and highly convenient accommodation inside the Central Luzon State University (CLSU) campus for visiting researchers, students, and guests.
                    </p>
                </div>

                <!-- Navigation Quick Links -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Quick Links</h3>
                    <ul class="space-y-2.5 text-sm font-medium text-slate-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="{{ route('home') }}#rooms" class="hover:text-white transition-colors">Our Rooms</a></li>
                        @auth
                            <li><a href="{{ route('settings.profile') }}" class="hover:text-white transition-colors">User Center</a></li>
                            <li><a href="{{ route('settings.bookings') }}" class="hover:text-white transition-colors">My Bookings</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Login / Register</a></li>
                        @endauth
                    </ul>
                </div>

                <!-- Contact column -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Contact Information</h3>
                    <ul class="space-y-3 text-sm font-medium text-slate-400">
                        <li class="flex items-start gap-3">
                            <span class="material-icons text-accent text-[18px] mt-0.5">place</span>
                            <span class="leading-relaxed hover:text-slate-300 transition-colors">Farmers Hostel, Central Luzon State University,<br/>Science City of Muñoz, Nueva Ecija, Philippines</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="material-icons text-accent text-[18px]">phone</span>
                            <span class="hover:text-slate-300 transition-colors">+63 945 123 4567</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="material-icons text-accent text-[18px]">email</span>
                            <span class="break-all hover:text-slate-300 transition-colors">farmershostel@clsu.edu.ph</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-8 mt-10 border-t border-slate-800/50">
                <p class="text-xs font-semibold text-slate-500">&copy; {{ date('Y') }} Farmers' Hostel. All rights reserved.</p>
                <div class="flex items-center gap-6 text-xs font-bold text-slate-500">
                    <a href="{{ route('staff.login') }}" class="hover:text-white transition-colors flex items-center gap-1.5">
                        <span class="material-icons text-[14px]">lock</span>
                        Staff Portal
                    </a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')

    <!-- Header Drawer Toggling Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('mobileMenuBtn');
            const drawer = document.getElementById('mobileDrawer');
            const closeBtn = document.getElementById('mobileDrawerCloseBtn');
            const roomsLink = document.getElementById('mobileRoomsLink');
            const contactLink = document.getElementById('mobileContactLink');

            function toggleDrawer(open) {
                if (open) {
                    drawer.classList.remove('hidden');
                } else {
                    drawer.classList.add('hidden');
                }
            }

            menuBtn && menuBtn.addEventListener('click', () => toggleDrawer(true));
            closeBtn && closeBtn.addEventListener('click', () => toggleDrawer(false));
            drawer && drawer.addEventListener('click', (e) => {
                if (e.target === drawer) toggleDrawer(false);
            });

            // Close when anchor links are clicked
            roomsLink && roomsLink.addEventListener('click', () => toggleDrawer(false));
            contactLink && contactLink.addEventListener('click', () => toggleDrawer(false));
        });
    </script>

    <!-- AOS Init -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                once: true,
                offset: 50,
                duration: 600,
                easing: 'ease-out-cubic',
            });
        });
    </script>
</body>
</html>

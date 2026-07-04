<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Hotel Booking Admin')</title>

  <!-- Tailwind CSS via Vite -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  @livewireStyles
</head>
<body class="bg-stone-50 font-sans text-stone-900 antialiased">

  <div id="shell" x-data="{ collapsed: localStorage.getItem('sidebar_collapsed') === 'true', mobileOpen: false }" :data-collapsed="collapsed ? 'true' : 'false'">
    
    <!-- Mobile drawer backdrop -->
    <div id="sidebarBackdrop" 
         class="fixed inset-0 bg-clsu-950/50 backdrop-blur-sm z-20 transition-opacity duration-200 lg:hidden"
         :class="mobileOpen ? 'opacity-100 block' : 'opacity-0 hidden'"
         @click="mobileOpen = false"></div>

    <x-admin.sidebar />
    <x-admin.topbar />

    <!-- Main content wrapper matching farmersnew.html -->
    <main id="mainContent" class="lg:ml-64 pt-16 min-h-screen relative transition-[margin] duration-200">
      <div aria-hidden="true" class="absolute inset-x-0 top-0 h-[440px] overflow-hidden -z-10 pointer-events-none">
        <div class="absolute -top-24 left-[18%] w-[420px] h-[420px] bg-clsu-200/40 rounded-full blur-3xl"></div>
        <div class="absolute -top-28 right-[8%] w-[360px] h-[360px] bg-palay-200/30 rounded-full blur-3xl"></div>
        <!-- Sheaf-of-grain watermark -->
        <svg class="absolute top-2 right-10 w-56 h-56 text-clsu-800 opacity-[0.07] rotate-6 hidden md:block" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round">
          <path d="M100,182 C68,140 58,86 80,16"/>
          <path d="M100,182 C84,132 80,68 92,10"/>
          <path d="M100,182 C100,120 100,58 100,10"/>
          <path d="M100,182 C116,132 120,68 108,10"/>
          <path d="M100,182 C132,140 142,86 120,16"/>
          <ellipse cx="100" cy="148" rx="27" ry="9" stroke-width="3"/>
        </svg>
      </div>

      <div class="relative z-10 p-4 sm:p-6 lg:p-8 space-y-6 lg:space-y-7 max-w-[1680px] mx-auto">
        @yield('content')
      </div>
    </main>

  </div>

  <script>
    // Live clock
    function updateClock() {
      const now = new Date();
      const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
      const date = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
      const el = document.getElementById('liveClock');
      if (el) el.textContent = time + ' · ' + date;
    }
    updateClock();
    setInterval(updateClock, 30000);
  </script>
  @livewireScripts
  @stack('scripts')
</body>
</html>

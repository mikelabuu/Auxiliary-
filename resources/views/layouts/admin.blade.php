<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Farmers Hostel · Admin Console')</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  @vite(['resources/css/admin.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body class="shell-root bg-surface text-ink antialiased" x-data="{ sidebarOpen: false }">

  <div class="grid-overlay"></div>

  {{-- Mobile overlay --}}
  <div class="sidebar-overlay" :class="{ 'open': sidebarOpen }" @click="sidebarOpen = false"></div>

  <x-admin.layout.sidebar />
  <x-admin.layout.topbar />

  {{-- Main content --}}
  <main class="shell-main">
    <div class="shell-content-wrap stagger-enter space-y-6">
      @yield('content')
    </div>
  </main>

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

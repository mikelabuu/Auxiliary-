<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Hotel Booking Front Desk')</title>

  {{-- dashboard CSS --}}
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  @livewireStyles
</head>
<body class="flex h-screen bg-gray-100">

  <!-- Sidebar -->
  <aside class="w-64 bg-clsuGreen text-white flex flex-col shrink-0 shadow-lg">
    <div class="h-20 flex items-center justify-center border-b border-clsuGreenLight bg-clsuGreenDark px-4">
      <img src="{{ asset('image/FHLogo2.png') }}" class="h-12 w-auto object-contain mr-2" alt="logo">
      <div class="flex flex-col">
          <span class="text-xs font-bold text-clsuGold tracking-widest uppercase">Farmers Hostel</span>
          <span class="text-[10px] text-gray-300 font-semibold tracking-wider">FRONT DESK</span>
      </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto py-4 space-y-1">
      <!-- SECTION: MAIN -->
      <div class="px-4 py-1.5">
        <span class="text-[9px] font-bold text-clsuGold tracking-wider uppercase opacity-75">Main</span>
      </div>
      <div class="px-2 space-y-1">
        <x-frontdesk.sidebar-link :href="route('frontdesk.dashboard.index')" icon="dashboard" :active="request()->routeIs('frontdesk.dashboard.index')">
          Dashboard
        </x-frontdesk.sidebar-link>
      </div>

      <!-- SECTION: OPERATIONS -->
      <div class="px-4 py-1.5 pt-3">
        <span class="text-[9px] font-bold text-clsuGold tracking-wider uppercase opacity-75">Operations</span>
      </div>
      <div class="px-2 space-y-1">
        <x-frontdesk.sidebar-link :href="route('frontdesk.walkin.create')" icon="edit_calendar" :active="request()->routeIs('frontdesk.walkin.create')">
          Manual Booking
        </x-frontdesk.sidebar-link>
        <x-frontdesk.sidebar-link :href="route('frontdesk.room.index')" icon="hotel" :active="request()->routeIs('frontdesk.room.index')">
          Rooms
        </x-frontdesk.sidebar-link>
        <x-frontdesk.sidebar-link :href="route('frontdesk.booking')" icon="book" :active="request()->routeIs('frontdesk.booking')">
          Bookings
        </x-frontdesk.sidebar-link>
      </div>
    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-clsuGreenLight bg-clsuGreenDark">
      <form method="POST" action="{{ route('staff.logout') }}">
        @csrf
        <button type="submit" class="w-full flex items-center justify-center px-4 py-2.5 bg-red-700 hover:bg-red-800 text-white font-medium rounded-lg transition duration-150 shadow">
          <span class="material-icons mr-2 text-base">logout</span> Logout
        </button>
      </form>
    </div>
  </aside>

  <!-- Main content -->
  <main class="flex-1 flex flex-col">
    <!-- Top bar -->
    <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
      <!-- Left: Page title -->
      <h1 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>

      <!-- Right: Clock + User Info -->
      <div class="flex items-center space-x-6">
        <!-- Digital clock/date -->
        <div class="flex flex-col text-right text-sm text-gray-700">
          <span class="font-medium" id="localTime">--:--:--</span>
          <span class="text-gray-500 text-xs" id="todayDate">Loading...</span>
        </div>

        <!-- User info -->
        <span class="text-gray-600">{{ Auth::guard('staff')->user()->name }}</span>
        <img src="{{ asset('image/icon/admin2.png') }}" class="w-10 h-10 rounded-full border-2 border-[#3b612a]" alt="Profile">
      </div>
    </header>



    <!-- Dynamic Content -->
    <section class="flex-1 p-6 overflow-y-auto">
      @yield('content')
    </section>
  </main>
  <script src="//unpkg.com/alpinejs" defer></script>
  <script>
  function updateTimeAndDate() {
    const timeEl = document.getElementById('localTime');
    const dateEl = document.getElementById('todayDate');

    const now = new Date();

    // Format time as HH:MM:SS AM/PM
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const timeString = `${hours}:${minutes}:${seconds} ${ampm}`;

    // Format date as Month Day, Year (e.g., Sep 5, 2025)
    const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
    const dateString = now.toLocaleDateString(undefined, options);

    timeEl.textContent = timeString;
    dateEl.textContent = dateString;
  }

  // Update immediately
  updateTimeAndDate();

  // Update every second
  setInterval(updateTimeAndDate, 1000);
</script>
@livewireScripts
@stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Hotel Booking Admin')</title>

  {{-- dashboard CSS --}}
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  @livewireStyles
</head>
<body class="flex h-screen bg-gray-100">

  <!-- Sidebar -->
  <aside class="w-64 bg-[#3b612a] text-white flex flex-col">
    <div class="header">
      <img class="sidebar-logo" src="{{ asset('image/FHLogo2.png') }}" alt="logo" />
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-4 py-6 space-y-2">
      <a href="{{ route('staff.dashboard') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">dashboard</span> Dashboard
      </a>

      <a href="{{ route('staff.rooms') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">hotel</span> Rooms
      </a>

      <!-- Bookings Dropdown -->
      <div x-data="{ open: false }" class="space-y-1">
        <button @click="open = !open" class="w-full flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition focus:outline-none">
          <span class="material-icons mr-3">book_online</span> Bookings
          <span class="material-icons ml-auto" x-show="!open">expand_more</span>
          <span class="material-icons ml-auto" x-show="open">expand_less</span>
        </button>

        <!-- Dropdown items -->
        <div x-show="open" x-transition class="pl-12 space-y-1">
          <a href="{{ route('staff.bookings.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-600 transition">
            <span class="material-icons mr-2 text-sm">list</span> All bookings
          </a>
          <a href="{{ route('staff.completedbookings.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-600 transition">
            <span class="material-icons mr-2 text-sm">list</span> Completed Bookings
          </a>
          <a href="{{ route('staff.bookinglogs.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-600 transition">
            <span class="material-icons mr-2 text-sm">settings</span>Booking Logs
          </a>
        </div>
      </div>

      <a href="{{ route('staff.discounts.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
          <span class="material-icons mr-3">discount</span> Discounts
      </a>

      <a href="{{ route('staff.paymentlogs.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">credit_card</span> Payments
      </a>

      {{-- <a href="{{ route('staff.balance') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">credit_card</span> Balance
      </a> --}}

      <a href="{{ route('staff.userrecords.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">people</span> Users
      </a>

      <a href="{{ route('staff.staffrecords.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">badge</span> Staff
      </a>

      <a href="{{ route('staff.audit.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">settings</span> Audit Logs
      </a>
      {{-- <a href="{{ route('reports.central') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
        <span class="material-icons mr-3">settings</span> Data Central
      </a> --}}
    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-green-800">
      <form method="POST" action="{{ route('staff.logout') }}">
        @csrf
        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 bg-red-600 rounded-lg hover:bg-red-700 transition">
          <span class="material-icons mr-2">logout</span> Logout
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
        @livewire('staff.discount-alert')

        <!-- User info -->
        <span class="text-gray-600">{{ Auth::guard('staff')->user()->name }}</span>
        <img src="" class="w-10 h-10 rounded-full border-2 border-[#3b612a]" alt="Profile">
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

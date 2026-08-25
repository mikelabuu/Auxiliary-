{{-- Sidebar — AAIS shell: light airy surface, emerald accents, seal head, user card footer --}}
@php
  // Same source as the JSON the console polls for the bell, so the number
  // rendered here and the number JS writes over it cannot disagree. See
  // StaffAlerts::pendingCounts() for why these stopped being inline queries.
  $queues = \App\Support\StaffAlerts::pendingCounts();
  $staffUser = Auth::guard('staff')->user();
@endphp

<aside class="shell-sidebar" :class="{ 'open': sidebarOpen }">

  {{-- Brand head --}}
  <div class="sidebar-head">
    <div class="sidebar-seal">
      <x-img src="image/clsu.logo.png" alt="CLSU Seal" sizes="76px" />
    </div>
    <div class="sidebar-brand-wrap">
      <p class="sidebar-brand-title">Farmers Hostel</p>
      <p class="sidebar-brand-sub">Admin Console</p>
    </div>
  </div>

  {{-- Scrollable nav --}}
  <div class="sidebar-scroll">
    @php
      $bookingsActive = request()->routeIs('staff.bookings.index') || request()->routeIs('staff.completedbookings.index') || request()->routeIs('staff.bookinglogs.index');
    @endphp

    <p class="sidebar-section-label">Overview</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-link :href="route('staff.dashboard')" :active="request()->routeIs('staff.dashboard')" badge-key="bookings" :badge="$queues['bookings']">
        <x-slot name="icon">
          <x-admin.ui.icon name="dashboard" />
        </x-slot>
        Dashboard
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.reports.index')" :active="request()->routeIs('staff.reports.index')">
        <x-slot name="icon">
          <x-admin.ui.icon name="chart-bar" />
        </x-slot>
        Reports
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">Reservations</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-dropdown title="Bookings" :active="$bookingsActive">
        <x-slot name="icon">
          <x-admin.ui.icon name="clipboard" />
        </x-slot>

        <x-admin.layout.sidebar-dropdown-item :href="route('staff.bookings.index')" :active="request()->routeIs('staff.bookings.index')">
          <x-slot name="icon">
            <x-admin.ui.icon name="list" />
          </x-slot>
          All bookings
        </x-admin.layout.sidebar-dropdown-item>
        <x-admin.layout.sidebar-dropdown-item :href="route('staff.completedbookings.index')" :active="request()->routeIs('staff.completedbookings.index')">
          <x-slot name="icon">
            <x-admin.ui.icon name="clipboard-check" />
          </x-slot>
          Completed Bookings
        </x-admin.layout.sidebar-dropdown-item>
        <x-admin.layout.sidebar-dropdown-item :href="route('staff.bookinglogs.index')" :active="request()->routeIs('staff.bookinglogs.index')">
          <x-slot name="icon">
            <x-admin.ui.icon name="history" />
          </x-slot>
          Booking Logs
        </x-admin.layout.sidebar-dropdown-item>
      </x-admin.layout.sidebar-dropdown>

      {{-- Sits with bookings, not with Financials: what this queue decides is
           which nights a stay covers. The money it moves is settled at the desk
           by hand, so it is a consequence rather than the subject. --}}
      <x-admin.layout.sidebar-link :href="route('staff.reschedules.index')" :active="request()->routeIs('staff.reschedules.*')" badge-key="reschedules" :badge="$queues['reschedules']">
        <x-slot name="icon">
          <x-admin.ui.icon name="calendar" />
        </x-slot>
        Reschedules
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.manualbooking')" :active="request()->routeIs('staff.manualbooking')">
        <x-slot name="icon">
          <x-admin.ui.icon name="calendar-plus" />
        </x-slot>
        Manual Booking
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.rooms')" :active="request()->routeIs('staff.rooms')">
        <x-slot name="icon">
          <x-admin.ui.icon name="bed" />
        </x-slot>
        Rooms
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">Financials</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-link :href="route('staff.paymentlogs.index')" :active="request()->routeIs('staff.paymentlogs.index')">
        <x-slot name="icon">
          <x-admin.ui.icon name="credit-card" />
        </x-slot>
        Payments
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.paymentverification.index')" :active="request()->routeIs('staff.paymentverification.*')" badge-key="proofs" :badge="$queues['proofs']">
        <x-slot name="icon">
          <x-admin.ui.icon name="check-circle" />
        </x-slot>
        Verify Payments
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.discounts.index')" :active="request()->routeIs('staff.discounts.*')" badge-key="discounts" :badge="$queues['discounts']">
        <x-slot name="icon">
          <x-admin.ui.icon name="tag" />
        </x-slot>
        Discounts
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">Accounts</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-link :href="route('staff.userrecords.index')" :active="request()->routeIs('staff.userrecords.index')">
        <x-slot name="icon">
          <x-admin.ui.icon name="users" />
        </x-slot>
        Users
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.staffrecords.index')" :active="request()->routeIs('staff.staffrecords.index')">
        <x-slot name="icon">
          <x-admin.ui.icon name="id-card" />
        </x-slot>
        Staff
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">System</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-link :href="route('staff.audit.index')" :active="request()->routeIs('staff.audit.index')">
        <x-slot name="icon">
          <x-admin.ui.icon name="list-check" />
        </x-slot>
        Audit Logs
      </x-admin.layout.sidebar-link>
    </nav>
  </div>

  {{-- Footer: user card + sign out --}}
  <div class="sidebar-footer">
    <div class="sidebar-user-card">
      <span class="sidebar-user-role">{{ $staffUser->role }}</span>
      <p class="sidebar-user-name">{{ $staffUser->name }}</p>
      <p class="sidebar-user-date">{{ now()->format('l, M d, Y') }}</p>
    </div>
    <form method="POST" action="{{ route('staff.logout') }}">
      @csrf
      <button type="submit" class="sidebar-action-btn">
        <x-admin.ui.icon name="log-out" />
        <span>Sign out</span>
      </button>
    </form>
  </div>
</aside>

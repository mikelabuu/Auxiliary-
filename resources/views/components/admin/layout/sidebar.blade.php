{{-- Sidebar — AAIS shell: light airy surface, emerald accents, seal head, user card footer --}}
@php
  $pendingBookingsCount = \App\Models\Booking::whereIn('status', ['pending_payment', 'pending_discount'])->count();
  $pendingDiscountsCount = \App\Models\Discount::where('status', 'pending')->count();
  $pendingProofsCount = \App\Models\Payment::whereNotNull('proof_path')->awaitingVerification()->count();
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
      <x-admin.layout.sidebar-link :href="route('staff.dashboard')" :active="request()->routeIs('staff.dashboard')" :badge="$pendingBookingsCount > 0 ? $pendingBookingsCount : null">
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

      <x-admin.layout.sidebar-link :href="route('staff.paymentverification.index')" :active="request()->routeIs('staff.paymentverification.*')" :badge="$pendingProofsCount > 0 ? $pendingProofsCount : null">
        <x-slot name="icon">
          <x-admin.ui.icon name="check-circle" />
        </x-slot>
        Verify Payments
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.discounts.index')" :active="request()->routeIs('staff.discounts.*')" :badge="$pendingDiscountsCount > 0 ? $pendingDiscountsCount : null">
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

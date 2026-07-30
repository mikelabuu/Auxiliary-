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
      <img src="{{ asset('image/clsu.logo.png') }}" alt="CLSU Seal">
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        </x-slot>
        Dashboard
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.reports.index')" :active="request()->routeIs('staff.reports.index')">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/></svg>
        </x-slot>
        Reports
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">Reservations</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-dropdown title="Bookings" :active="$bookingsActive">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg>
        </x-slot>

        <x-admin.layout.sidebar-dropdown-item :href="route('staff.bookings.index')" :active="request()->routeIs('staff.bookings.index')">
          <x-slot name="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          </x-slot>
          All bookings
        </x-admin.layout.sidebar-dropdown-item>
        <x-admin.layout.sidebar-dropdown-item :href="route('staff.completedbookings.index')" :active="request()->routeIs('staff.completedbookings.index')">
          <x-slot name="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </x-slot>
          Completed Bookings
        </x-admin.layout.sidebar-dropdown-item>
        <x-admin.layout.sidebar-dropdown-item :href="route('staff.bookinglogs.index')" :active="request()->routeIs('staff.bookinglogs.index')">
          <x-slot name="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><polyline points="3 3 3 8 8 8"/><polyline points="12 7 12 12 16 14"/></svg>
          </x-slot>
          Booking Logs
        </x-admin.layout.sidebar-dropdown-item>
      </x-admin.layout.sidebar-dropdown>

      <x-admin.layout.sidebar-link :href="route('staff.manualbooking')" :active="request()->routeIs('staff.manualbooking')">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="12" y1="13" x2="12" y2="17"/><line x1="10" y1="15" x2="14" y2="15"/></svg>
        </x-slot>
        Manual Booking
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.rooms')" :active="request()->routeIs('staff.rooms')">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6"/><path d="M2 18h20"/><path d="M6 10V7a2 2 0 0 1 2-2h3v5"/></svg>
        </x-slot>
        Rooms
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">Financials</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-link :href="route('staff.paymentlogs.index')" :active="request()->routeIs('staff.paymentlogs.index')">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </x-slot>
        Payments
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.paymentverification.index')" :active="request()->routeIs('staff.paymentverification.*')" :badge="$pendingProofsCount > 0 ? $pendingProofsCount : null">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c1.9 0 3.66.59 5.11 1.59"/></svg>
        </x-slot>
        Verify Payments
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.discounts.index')" :active="request()->routeIs('staff.discounts.*')" :badge="$pendingDiscountsCount > 0 ? $pendingDiscountsCount : null">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 12l9 9 10-10V2z"/><circle cx="7.5" cy="7.5" r="1.4"/></svg>
        </x-slot>
        Discounts
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">Accounts</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-link :href="route('staff.userrecords.index')" :active="request()->routeIs('staff.userrecords.index')">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.4"/><path d="M15.8 14.3c2.4.4 4.7 2 4.7 5.7"/></svg>
        </x-slot>
        Users
      </x-admin.layout.sidebar-link>

      <x-admin.layout.sidebar-link :href="route('staff.staffrecords.index')" :active="request()->routeIs('staff.staffrecords.index')">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </x-slot>
        Staff
      </x-admin.layout.sidebar-link>
    </nav>

    <p class="sidebar-section-label sidebar-section-label-spaced">System</p>
    <nav class="sidebar-nav">
      <x-admin.layout.sidebar-link :href="route('staff.audit.index')" :active="request()->routeIs('staff.audit.index')">
        <x-slot name="icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6h11M9 12h11M9 18h11"/><path d="m3 6 1.3 1.3L6.5 5"/><path d="m3 12 1.3 1.3 2.2-2.3"/><path d="m3 18 1.3 1.3 2.2-2.3"/></svg>
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>Sign out</span>
      </button>
    </form>
  </div>
</aside>

<!-- Sidebar -->
@php
  $pendingBookingsCount = \App\Models\Booking::whereIn('status', ['pending_payment', 'pending_discount'])->count();
  $pendingDiscountsCount = \App\Models\Discount::where('status', 'pending')->count();
@endphp

<aside id="sidebar" 
       class="fixed inset-y-0 left-0 w-64 field-rows flex flex-col z-30 shadow-[6px_0_28px_-10px_rgba(8,36,20,0.35)] transition-all duration-200"
       :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
  
  <!-- Brand Header -->
  <div class="relative flex flex-col items-center pt-6 pb-6 px-5 border-b border-palay-500/20 shrink-0 select-none brand-header">
    <button id="collapseToggle" aria-expanded="false" aria-label="Collapse sidebar" 
            class="absolute top-4 right-4 hidden lg:flex w-6 h-6 shrink-0 items-center justify-center rounded-md text-clsu-200/70 hover:text-white hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60"
            @click="collapsed = !collapsed; localStorage.setItem('sidebar_collapsed', collapsed)">
      <svg class="icon w-3.5 h-3.5 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    
    <!-- Full logo: shown when sidebar is expanded -->
    <img src="{{ asset('image/FHLogo2.png') }}" alt="Farmers Hostel Logo" class="brand-logo-full h-20 w-auto object-contain transition-all duration-200">
    
    <!-- Collapsed sunflower: shown when sidebar is collapsed -->
    <x-booking.ui.logo-mark class="brand-logo-collapsed h-12 w-12 hidden transition-all duration-200" />
    
    <p class="mt-3.5 text-[9px] font-bold text-palay-400 tracking-[0.22em] text-center uppercase label-fade leading-none">Operations Hub</p>
  </div>

  <!-- Nav -->
  <nav id="sidebar-nav" class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-6">
    @php
      $bookingsActive = request()->routeIs('staff.bookings.index') || request()->routeIs('staff.completedbookings.index') || request()->routeIs('staff.bookinglogs.index');
    @endphp

    <!-- SECTION: MAIN -->
    <div>
      <p class="label-fade px-3 text-[10px] font-black text-palay-300 tracking-[0.2em] mb-2 uppercase">Navigation</p>
      <x-admin.sidebar-link :href="route('staff.dashboard')" :active="request()->routeIs('staff.dashboard')" :badge="$pendingBookingsCount > 0 ? $pendingBookingsCount : null">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
        </x-slot>
        Dashboard
      </x-admin.sidebar-link>

      <x-admin.sidebar-link :href="route('staff.reports.index')" :active="request()->routeIs('staff.reports.index')">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="20" x2="4" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="20" y1="20" x2="20" y2="14"/></svg>
        </x-slot>
        Reports
      </x-admin.sidebar-link>
    </div>

    <!-- SECTION: RESERVATIONS -->
    <div>
      <p class="label-fade px-3 text-[10px] font-black text-palay-300 tracking-[0.2em] mb-2 uppercase">Reservations</p>
      
      <!-- Bookings Dropdown -->
      <x-admin.sidebar-dropdown title="Bookings" :active="$bookingsActive">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg>
        </x-slot>
        
        <x-admin.sidebar-dropdown-item :href="route('staff.bookings.index')" :active="request()->routeIs('staff.bookings.index')">
          <x-slot name="icon">
            <svg class="w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          </x-slot>
          All bookings
        </x-admin.sidebar-dropdown-item>
        <x-admin.sidebar-dropdown-item :href="route('staff.completedbookings.index')" :active="request()->routeIs('staff.completedbookings.index')">
          <x-slot name="icon">
            <svg class="w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </x-slot>
          Completed Bookings
        </x-admin.sidebar-dropdown-item>
        <x-admin.sidebar-dropdown-item :href="route('staff.bookinglogs.index')" :active="request()->routeIs('staff.bookinglogs.index')">
          <x-slot name="icon">
            <svg class="w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><polyline points="3 3 3 8 8 8"/><polyline points="12 7 12 12 16 14"/></svg>
          </x-slot>
          Booking Logs
        </x-admin.sidebar-dropdown-item>
      </x-admin.sidebar-dropdown>

      <x-admin.sidebar-link :href="route('staff.manualbooking')" :active="request()->routeIs('staff.manualbooking')">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="12" y1="13" x2="12" y2="17"/><line x1="10" y1="15" x2="14" y2="15"/></svg>
        </x-slot>
        Manual Booking
      </x-admin.sidebar-link>

      <x-admin.sidebar-link :href="route('staff.rooms')" :active="request()->routeIs('staff.rooms')">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6"/><path d="M2 18h20"/><path d="M6 10V7a2 2 0 0 1 2-2h3v5"/></svg>
        </x-slot>
        Rooms
      </x-admin.sidebar-link>
    </div>

    <!-- SECTION: FINANCIALS -->
    <div>
      <p class="label-fade px-3 text-[10px] font-black text-palay-300 tracking-[0.2em] mb-2 uppercase">Financials</p>
      
      <x-admin.sidebar-link :href="route('staff.paymentlogs.index')" :active="request()->routeIs('staff.paymentlogs.index')">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </x-slot>
        Payments
      </x-admin.sidebar-link>

      <x-admin.sidebar-link :href="route('staff.discounts.index')" :active="request()->routeIs('staff.discounts.*')" :badge="$pendingDiscountsCount > 0 ? $pendingDiscountsCount : null">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 12l9 9 10-10V2z"/><circle cx="7.5" cy="7.5" r="1.4"/></svg>
        </x-slot>
        Discounts
      </x-admin.sidebar-link>
    </div>

    <!-- SECTION: ACCOUNTS -->
    <div>
      <p class="label-fade px-3 text-[10px] font-black text-palay-300 tracking-[0.2em] mb-2 uppercase">Accounts</p>
      
      <x-admin.sidebar-link :href="route('staff.userrecords.index')" :active="request()->routeIs('staff.userrecords.index')">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.4"/><path d="M15.8 14.3c2.4.4 4.7 2 4.7 5.7"/></svg>
        </x-slot>
        Users
      </x-admin.sidebar-link>

      <x-admin.sidebar-link :href="route('staff.staffrecords.index')" :active="request()->routeIs('staff.staffrecords.index')">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </x-slot>
        Staff
      </x-admin.sidebar-link>
    </div>

    <!-- SECTION: SYSTEM -->
    <div>
      <p class="label-fade px-3 text-[10px] font-black text-palay-300 tracking-[0.2em] mb-2 uppercase">System</p>
      
      <x-admin.sidebar-link :href="route('staff.audit.index')" :active="request()->routeIs('staff.audit.index')">
        <x-slot name="icon">
          <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6h11M9 12h11M9 18h11"/><path d="m3 6 1.3 1.3L6.5 5"/><path d="m3 12 1.3 1.3 2.2-2.3"/><path d="m3 18 1.3 1.3 2.2-2.3"/></svg>
        </x-slot>
        Audit Logs
      </x-admin.sidebar-link>
    </div>

    <!-- WORKSPACE PANEL (evokes the image layout) -->
    <div class="label-fade px-3 pt-4 border-t border-white/10">
      <div class="rounded-2xl border border-dashed border-palay-400/30 bg-black/10 p-4">
        <p class="text-[10px] font-black text-palay-400 tracking-[0.2em] uppercase mb-1.5">Workspace</p>
        <p class="text-[11px] font-medium text-clsu-100/75 leading-relaxed">
          Manage reservations, front desk check-ins, room cleaning, and transactions.
        </p>
      </div>
    </div>
  </nav>

  <!-- Footer Profile & Logout -->
  <div class="p-3 border-t border-white/10 shrink-0">
    <div class="flex items-center gap-2.5 px-2 py-2 mb-2 rounded-xl hover:bg-white/5 transition-colors">
      <div class="w-9 h-9 rounded-full bg-gradient-to-br from-palay-300 to-palay-600 text-clsu-950 font-bold text-xs flex items-center justify-center shrink-0 ring-2 ring-white/10">
        {{ strtoupper(substr(Auth::guard('staff')->user()->name, 0, 2)) }}
      </div>
      <div class="label-fade leading-tight min-w-0">
        <p class="text-sm font-semibold text-white truncate">{{ Auth::guard('staff')->user()->name }}</p>
        <p class="text-[10px] font-semibold text-palay-400 tracking-wide uppercase">{{ Auth::guard('staff')->user()->role }}</p>
      </div>
    </div>

    <form method="POST" action="{{ route('staff.logout') }}">
      @csrf
      <button type="submit" class="mt-1.5 w-full flex items-center justify-center gap-2 text-sm font-medium text-clsu-200 border border-white/10 rounded-xl py-2 hover:bg-white/5 hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-clsu-950 cursor-pointer">
        <svg class="icon w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span class="label-fade">Logout</span>
      </button>
    </form>
  </div>
</aside>

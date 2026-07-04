<header id="topheader" class="fixed top-0 left-0 lg:left-64 right-0 h-16 bg-white/85 backdrop-blur-md border-b border-stone-200/70 flex items-center gap-3 sm:gap-4 px-4 sm:px-8 z-10 transition-[left] duration-200">
  
  <!-- Mobile Menu Toggle -->
  <button id="mobileMenuToggle" aria-label="Open menu" aria-expanded="false" 
          class="lg:hidden -ml-1 w-9 h-9 shrink-0 flex items-center justify-center rounded-lg text-stone-500 hover:bg-stone-100 hover:text-clsu-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400"
          @click="mobileOpen = !mobileOpen">
    <svg class="icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>

  <!-- Title & Status Badge -->
  <div class="flex items-center gap-2.5 shrink-0">
    <h1 class="text-[15px] font-bold tracking-wide text-stone-900 hidden sm:block">
      @yield('page-title', 'DASHBOARD')
    </h1>
    <span class="hidden sm:flex items-center gap-1.5 text-[10px] font-bold text-clsu-700 bg-clsu-50 rounded-full pl-1.5 pr-2.5 py-1 tracking-wide">
      <span class="relative flex w-1.5 h-1.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-clsu-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full w-1.5 h-1.5 bg-clsu-500"></span>
      </span>
      LIVE
    </span>
  </div>
  <div class="w-px h-5 bg-stone-200 shrink-0 hidden sm:block"></div>

  <!-- Search Bar -->
  <div class="relative flex-1 max-w-md" x-data="{
      init() {
          document.addEventListener('keydown', (e) => {
              const tag = document.activeElement && document.activeElement.tagName;
              if (e.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA') {
                  e.preventDefault();
                  this.$refs.searchInput.focus();
              }
          });
      }
  }">
    <svg class="icon w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input x-ref="searchInput" type="text" placeholder="Search guests, bookings, rooms…" class="w-full text-sm bg-stone-50 border border-stone-200 rounded-full pl-10 pr-9 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors" />
    <kbd class="hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 items-center justify-center text-[10px] font-semibold text-stone-400 bg-white border border-stone-200 rounded px-1.5 py-0.5 pointer-events-none">/</kbd>
  </div>

  <!-- Clock & Dropdowns -->
  <div class="ml-auto flex items-center gap-1.5 sm:gap-3">
    <div id="liveClock" class="hidden md:flex items-center gap-1.5 text-xs font-medium text-stone-500 bg-stone-50 rounded-full px-3 py-1.5 tabnum font-data" aria-live="off"></div>

    <!-- Notifications Dropdown -->
    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
      <button @click="open = !open" aria-haspopup="true" :aria-expanded="open" aria-label="Notifications" class="relative w-9 h-9 rounded-full flex items-center justify-center text-stone-500 hover:bg-stone-100 hover:text-clsu-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400">
        <svg class="icon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-palay-500 ring-2 ring-white"></span>
      </button>
      <div x-show="open" 
           x-transition 
           class="absolute right-0 mt-2 w-[19rem] bg-white rounded-2xl border border-stone-200/70 shadow-card-lg overflow-hidden py-1">
        <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
          <p class="text-sm font-semibold text-stone-900">Notifications</p>
          <button class="text-xs font-semibold text-clsu-600 hover:text-clsu-800">Mark all read</button>
        </div>
        <div class="divide-y divide-stone-100 max-h-72 overflow-y-auto">
          <a href="#" class="flex gap-3 px-4 py-3 hover:bg-clsu-50/60 transition-colors !no-underline">
            <div class="w-8 h-8 rounded-full bg-palay-50 text-palay-700 flex items-center justify-center shrink-0"><svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg></div>
            <div class="min-w-0"><p class="text-xs font-medium text-stone-800 leading-snug">New booking request awaiting confirmation</p><p class="text-[11px] text-stone-400 mt-0.5">10 minutes ago</p></div>
          </a>
          <a href="#" class="flex gap-3 px-4 py-3 hover:bg-clsu-50/60 transition-colors !no-underline">
            <div class="w-8 h-8 rounded-full bg-clsu-50 text-clsu-700 flex items-center justify-center shrink-0"><svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg></div>
            <div class="min-w-0"><p class="text-xs font-medium text-stone-800 leading-snug">Maintenance check completed — Room D-06</p><p class="text-[11px] text-stone-400 mt-0.5">2 hours ago</p></div>
          </a>
          <a href="#" class="flex gap-3 px-4 py-3 hover:bg-clsu-50/60 transition-colors !no-underline">
            <div class="w-8 h-8 rounded-full bg-stone-100 text-stone-500 flex items-center justify-center shrink-0"><svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg></div>
            <div class="min-w-0"><p class="text-xs font-medium text-stone-800 leading-snug">This week's report is ready to view</p><p class="text-[11px] text-stone-400 mt-0.5">Yesterday</p></div>
          </a>
        </div>
      </div>
    </div>

    <div class="w-px h-6 bg-stone-200 hidden sm:block"></div>

    <!-- Profile Dropdown -->
    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
      <button @click="open = !open" aria-haspopup="true" :aria-expanded="open" class="flex items-center gap-2.5 rounded-full hover:bg-stone-50 pl-1 pr-1 sm:pr-2.5 py-1 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400">
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-clsu-400 to-clsu-700 text-white font-bold text-xs flex items-center justify-center ring-2 ring-white shadow-sm">
          {{ strtoupper(substr(Auth::guard('staff')->user()->name, 0, 2)) }}
        </div>
        <div class="text-left leading-tight hidden sm:block">
          <p class="text-sm font-semibold text-stone-900">{{ Auth::guard('staff')->user()->name }}</p>
          <p class="text-[10px] font-semibold text-clsu-600 tracking-wide uppercase">{{ Auth::guard('staff')->user()->role }}</p>
        </div>
        <svg class="icon w-3.5 h-3.5 text-stone-400 hidden sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div x-show="open" 
           x-transition 
           class="absolute right-0 mt-2 w-56 bg-white rounded-2xl border border-stone-200/70 shadow-card-lg overflow-hidden py-1.5">
        <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-600 hover:bg-clsu-50 hover:text-clsu-800 transition-colors !no-underline"><svg class="icon w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>View profile</a>
        <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-600 hover:bg-clsu-50 hover:text-clsu-800 transition-colors !no-underline"><svg class="icon w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 1.5-1.7V13a2 2 0 1 0-4 0v.3a1.7 1.7 0 0 0 1.5 1.7"/><circle cx="12" cy="12" r="9"/></svg>Account settings</a>
        <div class="h-px bg-stone-100 my-1.5"></div>
        <form method="POST" action="{{ route('staff.logout') }}" class="m-0">
          @csrf
          <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-ember-600 hover:bg-ember-50 transition-colors cursor-pointer text-left"><svg class="icon w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Log out</button>
        </form>
      </div>
    </div>
  </div>
</header>

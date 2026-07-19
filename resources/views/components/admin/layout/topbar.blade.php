{{-- Topbar — AAIS glassy shell-topbar with green→gold hairline --}}
<header class="shell-topbar">

  <div class="topbar-leading">
    {{-- Mobile menu toggle --}}
    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Toggle sidebar">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    {{-- Desktop rail collapse toggle --}}
    <button class="sidebar-collapse-btn" @click="toggleSidebarCollapsed()" :aria-expanded="(!sidebarCollapsed).toString()" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'" aria-label="Toggle sidebar width">
      <svg :class="{ 'is-collapsed': sidebarCollapsed }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><line x1="9.5" y1="4" x2="9.5" y2="20"/><path d="m15.5 10-2 2 2 2"/></svg>
    </button>

    <div>
      <p class="topbar-title">@yield('page-title', 'Dashboard')</p>
      <p class="topbar-sub">Farmers Hostel · Admin workspace</p>
    </div>
  </div>

  {{-- Global search --}}
  <div class="topbar-search"
       x-data="{
          q: '',
          open: false,
          loading: false,
          active: -1,
          results: { bookings: [], users: [], rooms: [] },
          controller: null,
          get hasResults() { return this.results.bookings.length + this.results.users.length + this.results.rooms.length > 0 },
          get showQuick() { return this.q.trim().length < 2 },
          search() {
              this.setActive(-1);
              const term = this.q.trim();
              if (term.length < 2) { this.loading = false; this.results = { bookings: [], users: [], rooms: [] }; return; }
              this.loading = true;
              this.open = true;
              if (this.controller) this.controller.abort();
              this.controller = new AbortController();
              fetch('{{ route('staff.search') }}?q=' + encodeURIComponent(term), {
                      signal: this.controller.signal,
                      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                  })
                  .then(r => r.json())
                  .then(data => { this.results = data; this.loading = false; })
                  .catch(e => { if (e.name !== 'AbortError') this.loading = false; });
          },
          goFirst() {
              const first = this.results.bookings[0] || this.results.users[0] || this.results.rooms[0];
              if (first) window.location.href = first.url;
          },
          {{-- Keyboard flow: arrows walk every visible row (quick nav or results), Enter opens it --}}
          navRows() {
              return Array.from(this.$refs.panel.querySelectorAll('[data-nav-row]')).filter(r => r.offsetParent !== null);
          },
          setActive(i) {
              const rows = this.navRows();
              rows.forEach((r, idx) => r.classList.toggle('is-active', idx === i));
              this.active = i;
              if (i >= 0 && rows[i]) rows[i].scrollIntoView({ block: 'nearest' });
          },
          move(d) {
              this.open = true;
              const rows = this.navRows();
              if (!rows.length) return;
              let i = this.active + d;
              if (i < 0) i = rows.length - 1;
              if (i >= rows.length) i = 0;
              this.setActive(i);
          },
          openActive() {
              const rows = this.navRows();
              if (this.active >= 0 && rows[this.active]) { rows[this.active].click(); return; }
              this.goFirst();
          },
          init() {
              document.addEventListener('keydown', (e) => {
                  const tag = document.activeElement && document.activeElement.tagName;
                  if (e.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA') {
                      e.preventDefault();
                      this.$refs.searchInput.focus();
                  }
                  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                      e.preventDefault();
                      this.$refs.searchInput.focus();
                  }
              });
          }
       }"
       @click.outside="open = false"
       @keydown.escape="open = false">
    <svg class="scan-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input x-ref="searchInput" x-model="q"
           @input.debounce.300ms="search()"
           @focus="open = true"
           @keydown.enter.prevent="openActive()"
           @keydown.down.prevent="move(1)"
           @keydown.up.prevent="move(-1)"
           type="text" placeholder="Search guests, bookings, rooms…"
           class="topbar-search-input" />
    <kbd class="topbar-search-kbd">Ctrl K</kbd>

    {{-- Results dropdown (entry animated by CSS @starting-style; fast leave) --}}
    <div x-show="open" x-ref="panel" x-transition:leave.opacity.duration.120ms x-cloak class="topbar-search-panel">
      <div x-show="loading" class="px-4 py-3 text-xs text-faint flex items-center gap-2">
        <svg class="w-3.5 h-3.5 spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
        Searching…
      </div>

      {{-- Quick navigation (empty query) --}}
      <div x-show="!loading && showQuick">
        <p class="search-group-label">Quick navigation</p>
        <div class="quick-nav-grid">
          <a href="{{ route('staff.dashboard') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
          </a>
          <a href="{{ route('staff.bookings.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg>
            All bookings
          </a>
          <a href="{{ route('staff.manualbooking') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="12" y1="13" x2="12" y2="17"/><line x1="10" y1="15" x2="14" y2="15"/></svg>
            Manual booking
          </a>
          <a href="{{ route('staff.rooms') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6"/><path d="M2 18h20"/><path d="M6 10V7a2 2 0 0 1 2-2h3v5"/></svg>
            Rooms
          </a>
          <a href="{{ route('staff.paymentlogs.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Payments
          </a>
          <a href="{{ route('staff.discounts.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 12l9 9 10-10V2z"/><circle cx="7.5" cy="7.5" r="1.4"/></svg>
            Discounts
          </a>
          <a href="{{ route('staff.userrecords.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
            Users
          </a>
          <a href="{{ route('staff.reports.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/></svg>
            Reports
          </a>
        </div>
      </div>

      <div x-show="!loading && !showQuick && !hasResults" class="notif-empty">
        <p class="notif-empty-title">No matches found</p>
        <p class="notif-empty-copy">Try a guest name, booking # or room number</p>
      </div>

      <div x-show="!loading && !showQuick && hasResults" class="max-h-96 overflow-y-auto">
        {{-- Bookings --}}
        <div x-show="results.bookings.length">
          <p class="search-group-label">Bookings</p>
          <template x-for="b in results.bookings" :key="'b' + b.id">
            <a :href="b.url" data-nav-row class="search-result-row !no-underline">
              <div class="search-result-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink truncate"><span class="font-mono" x-text="'#' + b.id"></span> · <span x-text="b.guest"></span></p>
                <p class="text-[11px] text-faint" x-text="b.dates"></p>
              </div>
              <span class="chip chip-green shrink-0" x-text="b.status"></span>
            </a>
          </template>
        </div>

        {{-- Guests --}}
        <div x-show="results.users.length">
          <p class="search-group-label">Guests</p>
          <template x-for="u in results.users" :key="'u' + u.email">
            <a :href="u.url" data-nav-row class="search-result-row !no-underline">
              <div class="search-result-icon search-result-icon-muted">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-ink truncate" x-text="u.name"></p>
                <p class="text-[11px] text-faint truncate" x-text="u.email"></p>
              </div>
            </a>
          </template>
        </div>

        {{-- Rooms --}}
        <div x-show="results.rooms.length">
          <p class="search-group-label">Rooms</p>
          <template x-for="r in results.rooms" :key="'r' + r.number">
            <a :href="r.url" data-nav-row class="search-result-row !no-underline">
              <div class="search-result-icon search-result-icon-muted">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6"/><path d="M2 18h20"/><path d="M6 10V7a2 2 0 0 1 2-2h3v5"/></svg>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink font-mono" x-text="r.number"></p>
                <p class="text-[11px] text-faint" x-text="r.type"></p>
              </div>
              <span class="chip shrink-0"
                    :class="{
                        'chip-green': r.status === 'available',
                        'chip-gold': r.status === 'occupied',
                        'chip-muted': !['available','occupied'].includes(r.status)
                    }"
                    x-text="r.status"></span>
            </a>
          </template>
        </div>
      </div>

      {{-- Keyboard hints --}}
      <div class="search-hints">
        <span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
        <span><kbd>↵</kbd> open</span>
        <span><kbd>esc</kbd> close</span>
      </div>
    </div>
  </div>

  {{-- Clock & dropdowns --}}
  <div class="topbar-actions">
    <div id="liveClock" class="topbar-date font-mono tabnum hidden md:block" aria-live="off"></div>

    {{-- Table density toggle (persisted on <body> via layouts/admin) --}}
    <button @click="toggleDensity()" class="btn btn-ghost btn-sm btn-icon hidden md:inline-flex"
            :title="densityCompact ? 'Switch to comfortable density' : 'Switch to compact density'"
            aria-label="Toggle table density">
      <svg x-show="!densityCompact" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      <svg x-show="densityCompact" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="5" x2="21" y2="5"/><line x1="3" y1="9.5" x2="21" y2="9.5"/><line x1="3" y1="14" x2="21" y2="14"/><line x1="3" y1="18.5" x2="21" y2="18.5"/></svg>
    </button>

    {{-- Notifications --}}
    <div class="user-menu-root"
         x-data="{
            open: false,
            stamps: {{ $notifStamps->toJson() }},
            seenAt: Number(localStorage.getItem('admin_notif_seen_at') || 0),
            get unread() { return this.stamps.filter(t => t > this.seenAt).length },
            markRead() {
                this.seenAt = Math.floor(Date.now() / 1000);
                localStorage.setItem('admin_notif_seen_at', this.seenAt);
            }
         }"
         @click.outside="open = false">
      <button @click="open = !open" aria-haspopup="true" :aria-expanded="open" aria-label="Notifications" class="btn btn-ghost btn-sm btn-icon topbar-alert-btn">
        <svg class="topbar-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
        <span x-show="unread > 0" x-cloak class="topbar-alert-dot" x-text="unread > 9 ? '9+' : unread"></span>
      </button>
      <div x-show="open" x-transition:leave.opacity.duration.120ms x-cloak class="user-menu-panel user-menu-panel-wide">
        <div class="notif-head">
          <p class="notif-title">Notifications</p>
          <button x-show="unread > 0" @click="markRead()" class="notif-mark-read">Mark all read</button>
        </div>
        <div class="notif-list">
          @forelse($notifications as $notif)
            <a href="{{ $notif['url'] }}" class="notif-row !no-underline">
              @if($notif['type'] === 'discount')
                <div class="notif-icon notif-icon-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 12l9 9 10-10V2z"/><circle cx="7.5" cy="7.5" r="1.4"/></svg></div>
              @elseif($notif['type'] === 'maintenance')
                <div class="notif-icon notif-icon-rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
              @else
                <div class="notif-icon notif-icon-gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg></div>
              @endif
              <div class="min-w-0">
                <p class="notif-text">{{ $notif['text'] }}</p>
                <p class="notif-time">{{ $notif['time']?->diffForHumans() }}</p>
              </div>
            </a>
          @empty
            <div class="notif-empty">
              <p class="notif-empty-title">You're all caught up</p>
              <p class="notif-empty-copy">No pending items right now</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Profile --}}
    <div class="user-menu-root" x-data="{ userMenu: false }" @click.outside="userMenu = false">
      <button @click="userMenu = !userMenu" class="user-menu-trigger" aria-haspopup="true" :aria-expanded="userMenu.toString()" aria-label="Open user menu">
        <span class="user-menu-avatar">{{ strtoupper(substr(Auth::guard('staff')->user()->name, 0, 2)) }}</span>
        <span class="user-menu-id hidden sm:block">
          <span class="user-menu-id-name block">{{ Auth::guard('staff')->user()->name }}</span>
          <span class="user-menu-id-role block">{{ Auth::guard('staff')->user()->role }}</span>
        </span>
        <svg class="user-menu-chevron hidden sm:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div x-show="userMenu" x-transition:leave.opacity.duration.120ms x-cloak class="user-menu-panel">
        <div class="user-menu-head">
          <p class="user-menu-name">{{ Auth::guard('staff')->user()->name }}</p>
          <p class="user-menu-role">{{ Auth::guard('staff')->user()->role }}</p>
        </div>
        <a href="{{ route('staff.staffrecords.index') }}" class="user-menu-link !no-underline">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> My staff record
        </a>
        <a href="{{ route('staff.audit.index') }}" class="user-menu-link !no-underline">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11"/><path d="m3 6 1.3 1.3L6.5 5"/><path d="m3 12 1.3 1.3 2.2-2.3"/><path d="m3 18 1.3 1.3 2.2-2.3"/></svg> My activity
        </a>
        <form method="POST" action="{{ route('staff.logout') }}" class="m-0">
          @csrf
          <button type="submit" class="user-menu-link user-menu-link-danger">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg> Log out
          </button>
        </form>
      </div>
    </div>
  </div>
</header>

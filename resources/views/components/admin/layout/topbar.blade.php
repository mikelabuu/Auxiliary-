{{-- Topbar — AAIS glassy shell-topbar with green→gold hairline --}}
<header class="shell-topbar">

  <div class="topbar-leading">
    {{-- Mobile menu toggle --}}
    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Toggle sidebar">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div>
      <p class="topbar-title">@yield('page-title', 'Dashboard')</p>
      <p class="topbar-sub">Farmers Hostel · Admin workspace</p>
    </div>

    <span class="chip chip-green hidden sm:inline-flex" style="gap:6px;">
      <span class="relative flex w-1.5 h-1.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-g-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full w-1.5 h-1.5 bg-g-500"></span>
      </span>
      Live
    </span>
  </div>

  {{-- Global search --}}
  <div class="topbar-search"
       x-data="{
          q: '',
          open: false,
          loading: false,
          results: { bookings: [], users: [], rooms: [] },
          controller: null,
          get hasResults() { return this.results.bookings.length + this.results.users.length + this.results.rooms.length > 0 },
          search() {
              const term = this.q.trim();
              if (term.length < 2) { this.open = false; this.loading = false; return; }
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
          init() {
              document.addEventListener('keydown', (e) => {
                  const tag = document.activeElement && document.activeElement.tagName;
                  if (e.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA') {
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
           @focus="if (q.trim().length >= 2) open = true"
           @keydown.enter.prevent="goFirst()"
           type="text" placeholder="Search guests, bookings, rooms…"
           class="topbar-search-input" />
    <kbd class="topbar-search-kbd">/</kbd>

    {{-- Results dropdown --}}
    <div x-show="open" x-transition x-cloak class="topbar-search-panel">
      <div x-show="loading" class="px-4 py-3 text-xs text-faint flex items-center gap-2">
        <svg class="w-3.5 h-3.5 spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
        Searching…
      </div>

      <div x-show="!loading && !hasResults" class="notif-empty">
        <p class="notif-empty-title">No matches found</p>
        <p class="notif-empty-copy">Try a guest name, booking # or room number</p>
      </div>

      <div x-show="!loading && hasResults" class="max-h-96 overflow-y-auto">
        {{-- Bookings --}}
        <div x-show="results.bookings.length">
          <p class="search-group-label">Bookings</p>
          <template x-for="b in results.bookings" :key="'b' + b.id">
            <a :href="b.url" class="search-result-row !no-underline">
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
            <a :href="u.url" class="search-result-row !no-underline">
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
            <a :href="r.url" class="search-result-row !no-underline">
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
    </div>
  </div>

  {{-- Clock & dropdowns --}}
  <div class="topbar-actions">
    <div id="liveClock" class="topbar-date font-mono tabnum hidden md:block" aria-live="off"></div>

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
      <div x-show="open" x-transition.opacity.duration.200ms x-cloak class="user-menu-panel user-menu-panel-wide">
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
      <div x-show="userMenu" x-transition.opacity.duration.200ms x-cloak class="user-menu-panel">
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

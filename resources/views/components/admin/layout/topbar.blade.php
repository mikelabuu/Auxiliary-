{{-- Topbar — AAIS glassy shell-topbar with green→gold hairline --}}
<header class="shell-topbar">

  <div class="topbar-leading">
    {{-- Mobile menu toggle --}}
    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Toggle sidebar">
      <x-admin.ui.icon name="menu" />
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
              if (!first) return;
              {{-- Navigating from script skips the click handler that normally
                   raises the loading curtain (partials/page-loader), so ask for
                   it directly — otherwise Enter here is the one navigation in
                   the console with no feedback at all. --}}
              if (window.showPageLoader) window.showPageLoader();
              window.location.href = first.url;
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
    <x-admin.ui.icon name="search" class="scan-icon" />
    <input x-ref="searchInput" x-model="q"
           @input.debounce.300ms="search()"
           @focus="open = true"
           @keydown.enter.prevent="openActive()"
           @keydown.down.prevent="move(1)"
           @keydown.up.prevent="move(-1)"
           type="text" placeholder="Search guests, bookings, rooms…"
           class="topbar-search-input" />
    <kbd class="topbar-search-kbd">Ctrl K</kbd>

    {{-- Results dropdown. Opens instantly — this panel is bound to Ctrl/Cmd+K
         and `/`, and animating a keyboard action puts a delay between the
         keystroke and the field the user is already typing into. Only the
         leave is animated (see .topbar-search-panel in admin/05-motion-ux). --}}
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
            <x-admin.ui.icon name="dashboard" />
            Dashboard
          </a>
          <a href="{{ route('staff.bookings.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <x-admin.ui.icon name="clipboard" />
            All bookings
          </a>
          <a href="{{ route('staff.manualbooking') }}" data-nav-row class="quick-nav-item !no-underline">
            <x-admin.ui.icon name="calendar-plus" />
            Manual booking
          </a>
          <a href="{{ route('staff.rooms') }}" data-nav-row class="quick-nav-item !no-underline">
            <x-admin.ui.icon name="bed" />
            Rooms
          </a>
          <a href="{{ route('staff.paymentlogs.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <x-admin.ui.icon name="credit-card" />
            Payments
          </a>
          <a href="{{ route('staff.discounts.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <x-admin.ui.icon name="tag" />
            Discounts
          </a>
          <a href="{{ route('staff.userrecords.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <x-admin.ui.icon name="user" />
            Users
          </a>
          <a href="{{ route('staff.reports.index') }}" data-nav-row class="quick-nav-item !no-underline">
            <x-admin.ui.icon name="chart-bar" />
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
                <x-admin.ui.icon name="clipboard" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink truncate"><span class="font-mono" x-text="'#' + b.id"></span> · <span x-text="b.guest"></span></p>
                <p class="text-2xs text-faint" x-text="b.dates"></p>
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
                <x-admin.ui.icon name="user" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-ink truncate" x-text="u.name"></p>
                <p class="text-2xs text-faint truncate" x-text="u.email"></p>
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
                <x-admin.ui.icon name="bed" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink font-mono" x-text="r.number"></p>
                <p class="text-2xs text-faint" x-text="r.type"></p>
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
    {{-- xl, not md: the topbar also carries a search field and the user menu,
         and at md there is not room for all three. The clock is the one of the
         three nobody needs, so it is the one that waits for space. --}}
    <div id="liveClock" class="topbar-date font-mono tabnum hidden xl:block" aria-live="off"></div>

    {{-- Table density cycle — compact → comfortable → large (persisted on <body> via layouts/admin) --}}
    <button @click="cycleDensity()" class="btn btn-ghost btn-sm btn-icon hidden md:inline-flex"
            :title="density === 'compact' ? 'Switch to comfortable rows' : (density === 'normal' ? 'Switch to large rows' : 'Switch to compact rows')"
            aria-label="Cycle table size (compact / comfortable / large)">
      {{-- compact: 4 tight lines --}}
      <svg x-show="density === 'compact'" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="5" x2="21" y2="5"/><line x1="3" y1="9.5" x2="21" y2="9.5"/><line x1="3" y1="14" x2="21" y2="14"/><line x1="3" y1="18.5" x2="21" y2="18.5"/></svg>
      {{-- comfortable: 3 lines --}}
      <svg x-show="density === 'normal'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      {{-- large: 2 spaced lines --}}
      <svg x-show="density === 'large'" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="8" x2="21" y2="8"/><line x1="3" y1="16" x2="21" y2="16"/></svg>
    </button>

    {{-- Notifications --}}
    {{--
        Read state is per item, not one global "seen" line. The feed is derived
        fresh on every request (see AppServiceProvider), so there is no row to
        write to — instead each entry has a stable id and the ids that have been
        opened live in localStorage. Opening one marks only that one.

        `readIds` is pruned to the ids currently on screen, so the store cannot
        grow without bound as old alerts fall out of the feed.

        The list is rendered from `items` rather than a Blade @forelse because
        it has two sources: this server backfill, and alerts pushed live over
        Reverb (App\Events\StaffNotification → resources/js/admin-notifications.js
        → a `staff-alert` window event). Both produce the same object shape, so
        an alert that arrives live and the same alert re-rendered on the next
        page load are one row, read state and all.
    --}}
    <div class="user-menu-root"
         x-data="{
            open: false,
            items: {{ Js::from($notifications) }},
            seenAt: Number(localStorage.getItem('admin_notif_seen_at') || 0),
            readIds: [],
            freshIds: [],
            now: Math.floor(Date.now() / 1000),

            init() {
                let stored = [];
                try { stored = JSON.parse(localStorage.getItem('admin_notif_read_ids') || '[]'); } catch (e) { stored = []; }
                const live = this.items.map(i => i.id);
                this.readIds = stored.filter(id => live.includes(id));
                this.persist();

                {{-- Timestamps are rendered client-side so they keep counting
                     up on a console left open all shift. --}}
                setInterval(() => { this.now = Math.floor(Date.now() / 1000); }, 30000);

                window.addEventListener('staff-alert', (e) => this.receive(e.detail));
            },

            {{-- A live alert. Newest first, never duplicated, list stays at 8. --}}
            receive(a) {
                if (!a || !a.id || this.items.some(i => i.id === a.id)) return;
                this.items = [a, ...this.items].slice(0, 8);
                this.freshIds = [a.id, ...this.freshIds].slice(0, 8);
                this.now = Math.floor(Date.now() / 1000);

                {{-- 'Mark all read' works by timestamp, so an alert that lands
                     after it must not be swept up by it. Persisted, not just
                     held in memory: otherwise the next page load re-reads the
                     old, higher line from localStorage and silently marks this
                     alert read without anyone having opened it. --}}
                if (a.at && a.at <= this.seenAt) {
                    this.seenAt = a.at - 1;
                    localStorage.setItem('admin_notif_seen_at', this.seenAt);
                }

                this.ring();
            },

            ring() {
                const btn = this.$refs.bell;
                const dot = this.$refs.dot;
                [[btn, 'is-ringing'], [dot, 'is-bumped']].forEach(([el, cls]) => {
                    if (!el) return;
                    el.classList.remove(cls);
                    void el.offsetWidth;   {{-- restart if one is mid-flight --}}
                    el.classList.add(cls);
                });
            },

            persist() {
                localStorage.setItem('admin_notif_read_ids', JSON.stringify(this.readIds));
            },

            isRead(id) {
                if (this.readIds.includes(id)) return true;
                const item = this.items.find(i => i.id === id);
                // 'Mark all read' covers everything that existed at the time.
                return !!item && item.at <= this.seenAt;
            },

            get unread() { return this.items.filter(i => !this.isRead(i.id)).length },

            markOne(id) {
                if (!this.readIds.includes(id)) {
                    this.readIds.push(id);
                    this.persist();
                }
            },

            markAllRead() {
                this.seenAt = Math.floor(Date.now() / 1000);
                localStorage.setItem('admin_notif_seen_at', this.seenAt);
                this.readIds = this.items.map(i => i.id);
                this.persist();
            },

            {{-- Replaces Carbon's diffForHumans(), which froze at render time. --}}
            ago(at) {
                if (!at) return '';
                const s = Math.max(0, this.now - at);
                if (s < 60)    return 'just now';
                if (s < 3600)  return Math.floor(s / 60) + 'm ago';
                if (s < 86400) return Math.floor(s / 3600) + 'h ago';
                return Math.floor(s / 86400) + 'd ago';
            },

            tile(type) {
                return { booking: 'notif-icon-gold', payment: 'notif-icon-green', discount: 'notif-icon-green', maintenance: 'notif-icon-rose', checkout_due: 'notif-icon-gold', reschedule: 'notif-icon-gold' }[type] || 'notif-icon-gold';
            }
         }"
         @click.outside="open = false">
      <button x-ref="bell" @click="open = !open" aria-haspopup="true" :aria-expanded="open"
              :aria-label="unread > 0 ? `Notifications, ${unread} unread` : 'Notifications'"
              class="btn btn-ghost btn-sm btn-icon topbar-alert-btn">
        <x-admin.ui.icon name="bell" class="topbar-alert-icon" />
        <span x-ref="dot" x-show="unread > 0" x-cloak class="topbar-alert-dot" x-text="unread > 9 ? '9+' : unread"></span>
      </button>
      <div x-show="open" x-transition:leave.opacity.duration.120ms x-cloak class="user-menu-panel user-menu-panel-wide">
        <div class="notif-head">
          <p class="notif-title">Notifications</p>
          <button x-show="unread > 0" @click="markAllRead()" class="notif-mark-read">Mark all read</button>
        </div>
        <div class="notif-list">
          <template x-for="n in items" :key="n.id">
            {{-- @click fires before navigation, so the id is stored even
                 though the browser leaves this page immediately after. --}}
            <a :href="n.url"
               class="notif-row !no-underline"
               :class="{ 'is-read': isRead(n.id), 'is-fresh': freshIds.includes(n.id) }"
               @click="markOne(n.id)">
              <div class="notif-icon" :class="tile(n.type)">
                {{-- x-html, but every branch is a constant defined right here —
                     no alert field ever reaches it. --}}
                <template x-if="n.type === 'discount'"><x-admin.ui.icon name="tag" /></template>
                <template x-if="n.type === 'maintenance'"><x-admin.ui.icon name="wrench" /></template>
                <template x-if="n.type === 'payment'"><x-admin.ui.icon name="credit-card" /></template>
                <template x-if="n.type === 'booking'"><x-admin.ui.icon name="clipboard" /></template>
                <template x-if="n.type === 'checkout_due'"><x-admin.ui.icon name="clock" /></template>
                <template x-if="n.type === 'reschedule'"><x-admin.ui.icon name="calendar" /></template>
              </div>
              <div class="min-w-0 flex-1">
                {{-- x-text, not x-html: n.text carries a guest-supplied name. --}}
                <p class="notif-text"><span class="font-extrabold" x-text="n.title"></span> · <span x-text="n.text"></span></p>
                <p class="notif-time" x-text="ago(n.at)"></p>
              </div>
              {{-- The dot is the unread marker; it disappears on click. --}}
              <span x-show="!isRead(n.id)" x-cloak class="notif-unread-dot" aria-label="Unread"></span>
            </a>
          </template>
          <div x-show="items.length === 0" x-cloak class="notif-empty">
            <p class="notif-empty-title">You're all caught up</p>
            <p class="notif-empty-copy">No pending items right now</p>
          </div>
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
        <x-admin.ui.icon name="chevron-down" class="user-menu-chevron hidden sm:block" />
      </button>
      <div x-show="userMenu" x-transition:leave.opacity.duration.120ms x-cloak class="user-menu-panel">
        <div class="user-menu-head">
          <p class="user-menu-name">{{ Auth::guard('staff')->user()->name }}</p>
          <p class="user-menu-role">{{ Auth::guard('staff')->user()->role }}</p>
        </div>
        <a href="{{ route('staff.staffrecords.index') }}" class="user-menu-link !no-underline">
          <x-admin.ui.icon name="id-card" /> My staff record
        </a>
        <a href="{{ route('staff.audit.index') }}" class="user-menu-link !no-underline">
          <x-admin.ui.icon name="list-check" /> My activity
        </a>
        <form method="POST" action="{{ route('staff.logout') }}" class="m-0">
          @csrf
          <button type="submit" class="user-menu-link user-menu-link-danger">
            <x-admin.ui.icon name="log-out" /> Log out
          </button>
        </form>
      </div>
    </div>
  </div>
</header>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Farmers Hostel · Admin Console')</title>
  <link rel="icon" type="image/png" href="{{ asset('image/clsu.logo.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300..800&family=Geist+Mono:wght@400;500;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  @vite(['resources/css/admin.css', 'resources/js/app.js'])
  @livewireStyles
  @stack('styles')
</head>
<body class="shell-root bg-surface text-ink antialiased"
      x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('adminSidebarCollapsed') === '1',
        toggleSidebarCollapsed() {
          this.sidebarCollapsed = !this.sidebarCollapsed;
          localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
        },
        density: (localStorage.getItem('adminDensity') || (localStorage.getItem('adminDensityCompact') === '1' ? 'compact' : 'normal')),
        cycleDensity() {
          const order = ['compact', 'normal', 'large'];
          this.density = order[(order.indexOf(this.density) + 1) % order.length];
          localStorage.setItem('adminDensity', this.density);
        }
      }"
      :class="{ 'sidebar-collapsed': sidebarCollapsed, 'density-compact': density === 'compact', 'density-large': density === 'large' }">

  {{-- Apply saved rail/density state before Alpine boots so nothing flashes --}}
  <script>
    if (localStorage.getItem('adminSidebarCollapsed') === '1') {
      document.body.classList.add('sidebar-collapsed');
    }
    (function () {
      var d = localStorage.getItem('adminDensity');
      if (!d) d = localStorage.getItem('adminDensityCompact') === '1' ? 'compact' : 'normal';
      if (d === 'compact') document.body.classList.add('density-compact');
      else if (d === 'large') document.body.classList.add('density-large');
    })();
  </script>

  <div class="grid-overlay"></div>

  {{-- Mobile overlay --}}
  <div class="sidebar-overlay" :class="{ 'open': sidebarOpen }" @click="sidebarOpen = false"></div>

  <x-admin.layout.sidebar />
  <x-admin.layout.topbar />

  {{-- Main content --}}
  <main class="shell-main">
    <div class="shell-content-wrap stagger-enter space-y-6">
      @yield('content')
    </div>
  </main>

  {{-- Back to top --}}
  <button id="backToTop" class="back-to-top" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
  </button>

  <script>
    // ── Shared modal helpers (animated open/close) ──
    // Pages call openModal(id)/closeModal(id); the wrapper toggles hidden/flex
    // while [data-closing] lets the CSS run a fast exit before display:none.
    window.__lastModalFocus = null;
    window.openModal = function (id) {
      const el = document.getElementById(id);
      if (!el) return;
      window.__lastModalFocus = document.activeElement;
      el.removeAttribute('data-closing');
      el.classList.remove('hidden');
      el.classList.add('flex');
      const focusable = el.querySelector('input:not([type="hidden"]), select, textarea, button:not([data-modal-close]):not([aria-label="Close"])')
        || el.querySelector('[role="dialog"]');
      if (focusable) { try { focusable.focus({ preventScroll: true }); } catch (e) {} }
    };
    window.closeModal = function (id) {
      const el = document.getElementById(id);
      if (!el || el.classList.contains('hidden') || el.hasAttribute('data-closing')) return;
      el.setAttribute('data-closing', '');
      setTimeout(function () {
        el.classList.add('hidden');
        el.classList.remove('flex');
        el.removeAttribute('data-closing');
        if (window.__lastModalFocus) {
          try { window.__lastModalFocus.focus({ preventScroll: true }); } catch (e) {}
          window.__lastModalFocus = null;
        }
      }, 150);
    };

    // Entrance keyframes (fadeInUp/popIn/rowIn) run with fill:forwards, and a
    // filled opacity/transform animation keeps a stacking context forever —
    // which traps page-level fixed modals below the sidebar (z-50) so the
    // backdrop never dims the chrome. Clear the animation once it finishes;
    // the inline opacity also stops Livewire morphs re-flashing entrances.
    document.addEventListener('animationend', function (e) {
      const n = e.animationName;
      if (n === 'fadeInUp' || n === 'popIn' || n === 'rowIn') {
        e.target.style.animation = 'none';
        if (n !== 'rowIn') e.target.style.opacity = '1';
      }
    }, true);

    // Live clock
    function updateClock() {
      const now = new Date();
      const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
      const date = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
      const el = document.getElementById('liveClock');
      if (el) el.textContent = time + ' · ' + date;
    }
    updateClock();
    setInterval(updateClock, 30000);

    // ── Admin UX layer: spotlight tracking, KPI count-ups, back-to-top ──
    (function () {
      // Cursor-tracked spotlight on cards (CSS reads --spot-x/--spot-y)
      let spotRaf = null;
      document.addEventListener('pointermove', function (e) {
        if (spotRaf) return;
        spotRaf = requestAnimationFrame(function () {
          spotRaf = null;
          const t = e.target.closest && e.target.closest('.card, .stat-card, .mini-stat, .quick-action');
          if (!t) return;
          const r = t.getBoundingClientRect();
          t.style.setProperty('--spot-x', (e.clientX - r.left) + 'px');
          t.style.setProperty('--spot-y', (e.clientY - r.top) + 'px');
        });
      }, { passive: true });

      // Animated count-up for plain numeric KPI values (skips mixed markup)
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('.stat-value, .mini-stat-value').forEach(function (el) {
          if (el.children.length > 0) return;
          const m = el.textContent.trim().match(/^([₱$]?)([\d,]+)(\.\d+)?(%?)$/);
          if (!m) return;
          const target = parseFloat(m[2].replace(/,/g, '') + (m[3] || ''));
          if (!isFinite(target) || target === 0) return;
          const dec = m[3] ? m[3].length - 1 : 0;
          const dur = 560, start = performance.now();
          function fmt(v) {
            return m[1] + v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + m[4];
          }
          (function tick(t) {
            const p = Math.min(1, (t - start) / dur);
            el.textContent = fmt(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick);
          })(start);
        });
      }

      // Back-to-top visibility + smooth scroll
      const btt = document.getElementById('backToTop');
      if (btt) {
        const onScroll = function () { btt.classList.toggle('visible', window.scrollY > 480); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        btt.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
      }

      // Copy reference codes to clipboard (any .copy-ref[data-copy] in the console)
      document.addEventListener('click', function (e) {
        const btn = e.target.closest && e.target.closest('.copy-ref');
        if (!btn) return;
        const value = btn.getAttribute('data-copy');
        if (!value) return;
        const done = function () {
          const original = btn.innerHTML;
          btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px"><polyline points="20 6 9 17 4 12"/></svg> Copied';
          setTimeout(function () { btn.innerHTML = original; }, 1400);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(value).then(done).catch(function () {});
        } else {
          const t = document.createElement('textarea');
          t.value = value; document.body.appendChild(t); t.select();
          try { document.execCommand('copy'); done(); } catch (err) {}
          document.body.removeChild(t);
        }
      });

      // Guest name → the booking detail modal (details + timeline + guest
      // history — the same modal as the View button, not a separate dialog).
      // Password-gated exactly like View, then fetched and injected.
      function openGuestBookingModal(bookingId) {
        fetch('{{ url('staff/bookings') }}/' + bookingId + '/guest-history', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (!res || !res.success) return;
            let host = document.getElementById('guestBookingHost');
            if (!host) {
              host = document.createElement('div');
              host.id = 'guestBookingHost';
              document.body.appendChild(host);
            }
            host.innerHTML = res.html;
            window.openModal('guestBookingModal');
            const pop = document.getElementById('roomMapPopover');
            if (pop) pop.classList.add('hidden');
          })
          .catch(function () {});
      }

      document.addEventListener('click', function (e) {
        const closer = e.target.closest && e.target.closest('[data-modal-close="guestBookingModal"]');
        if (closer) {
          window.closeModal('guestBookingModal');
          return;
        }

        const link = e.target.closest && e.target.closest('.guest-history-link');
        if (!link) return;
        const bookingId = link.getAttribute('data-booking-id');
        if (!bookingId) return;
        e.preventDefault();

        // Guest history is read-only — open it directly (no password re-auth).
        openGuestBookingModal(bookingId);
      });
      document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        const dlg = document.getElementById('guestBookingModal');
        if (dlg && !dlg.classList.contains('hidden')) {
          window.closeModal('guestBookingModal');
        }
      });
    })();
  </script>

  {{-- Session flashes surface as toasts (engine in resources/js/app.js).
       Pages must not render their own success banners or this double-fires;
       validation-error lists stay inline next to their forms. --}}
  @if(session('success') || session('error'))
  <script>
    window.addEventListener('DOMContentLoaded', function () {
      @if(session('success')) window.toast(@json(session('success')), 'success'); @endif
      @if(session('error')) window.toast(@json(session('error')), 'error'); @endif
    });
  </script>
  @endif
  @livewireScripts
  @stack('scripts')
</body>
</html>

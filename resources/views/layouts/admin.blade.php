<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Farmers Hostel · Admin Console')</title>

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
        densityCompact: localStorage.getItem('adminDensityCompact') === '1',
        toggleDensity() {
          this.densityCompact = !this.densityCompact;
          localStorage.setItem('adminDensityCompact', this.densityCompact ? '1' : '0');
        }
      }"
      :class="{ 'sidebar-collapsed': sidebarCollapsed, 'density-compact': densityCompact }">

  {{-- Apply saved rail/density state before Alpine boots so nothing flashes --}}
  <script>
    if (localStorage.getItem('adminSidebarCollapsed') === '1') {
      document.body.classList.add('sidebar-collapsed');
    }
    if (localStorage.getItem('adminDensityCompact') === '1') {
      document.body.classList.add('density-compact');
    }
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
          const dur = 800, start = performance.now();
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
            const dlg = document.getElementById('guestBookingModal');
            if (dlg) { dlg.classList.remove('hidden'); dlg.classList.add('flex'); }
            const pop = document.getElementById('roomMapPopover');
            if (pop) pop.classList.add('hidden');
          })
          .catch(function () {});
      }

      document.addEventListener('click', function (e) {
        const dlg = document.getElementById('guestBookingModal');

        const closer = e.target.closest && e.target.closest('[data-modal-close="guestBookingModal"]');
        if (closer && dlg) {
          dlg.classList.add('hidden'); dlg.classList.remove('flex');
          return;
        }

        const link = e.target.closest && e.target.closest('.guest-history-link');
        if (!link) return;
        const bookingId = link.getAttribute('data-booking-id');
        if (!bookingId) return;
        e.preventDefault();

        if (typeof Swal === 'undefined') { openGuestBookingModal(bookingId); return; }

        Swal.fire({
          title: 'Enter your password',
          input: 'password',
          inputAttributes: { placeholder: 'Password', autocapitalize: 'off' },
          showCancelButton: true,
          confirmButtonText: 'Verify',
          showLoaderOnConfirm: true,
          preConfirm: function (password) {
            return fetch('{{ route('staff.bookings.verify-password') }}', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              body: JSON.stringify({ password: password })
            })
              .then(function (r) { return r.json(); })
              .then(function (res) {
                if (!res || !res.success) throw new Error(res && res.message ? res.message : 'Incorrect password');
                return true;
              })
              .catch(function (err) { Swal.showValidationMessage(err.message || 'Verification failed'); });
          },
          allowOutsideClick: function () { return !Swal.isLoading(); }
        }).then(function (result) {
          if (result.isConfirmed && result.value) openGuestBookingModal(bookingId);
        });
      });
      document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        const dlg = document.getElementById('guestBookingModal');
        if (dlg && !dlg.classList.contains('hidden')) {
          dlg.classList.add('hidden'); dlg.classList.remove('flex');
        }
      });
    })();
  </script>
  @livewireScripts
  @stack('scripts')
</body>
</html>

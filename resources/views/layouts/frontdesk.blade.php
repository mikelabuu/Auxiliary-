<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Farmers Hostel · Front Desk')</title>
  <link rel="icon" type="image/png" href="{{ asset('image/clsu.logo.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300..800&family=Geist+Mono:wght@400;500;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  {{-- Chart.js is loaded per-page (dashboard @push) — not every desk page charts --}}

  {{-- Same design-system bundle as the admin console: Fresh Meadow tokens,
       cards, tables, status pills, modals — plus the .fd-* band layer. --}}
  @vite(['resources/css/admin.css', 'resources/js/app.js'])
  @livewireStyles
  @stack('styles')
</head>
<body class="bg-surface text-ink antialiased">

  {{-- Meadow Nightfall band: greeting, clock, pill nav (Finexa format) --}}
  <x-frontdesk.hero />

  {{-- Workspace: light Fresh Meadow cards overlapping the band --}}
  <main class="fd-main">
    <div class="stagger-enter space-y-6">
      @yield('content')
    </div>
  </main>

  <script>
    // ── Shared modal helpers (same contract as the admin console) ──
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

    // Any [data-modal-close="id"] dismisses its modal (backdrop + X buttons)
    document.addEventListener('click', function (e) {
      const closer = e.target.closest && e.target.closest('[data-modal-close]');
      if (closer) window.closeModal(closer.getAttribute('data-modal-close'));
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      document.querySelectorAll('.fixed.inset-0.flex [role="dialog"]').forEach(function (dlg) {
        const wrap = dlg.closest('.fixed.inset-0');
        if (wrap && wrap.id && !wrap.classList.contains('hidden')) window.closeModal(wrap.id);
      });
    });

    // Entrance keyframes fill forwards and would trap page-level fixed
    // modals inside a stale stacking context — clear them once done.
    document.addEventListener('animationend', function (e) {
      const n = e.animationName;
      if (n === 'fadeInUp' || n === 'popIn' || n === 'rowIn') {
        e.target.style.animation = 'none';
        if (n !== 'rowIn') e.target.style.opacity = '1';
      }
    }, true);

    // ── Band clock (desk time = Manila wall clock on the machine) ──
    (function () {
      const timeEl = document.getElementById('fdClock');
      const dateEl = document.getElementById('fdClockDate');
      if (!timeEl) return;
      function tick() {
        const now = new Date();
        let h = now.getHours();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        timeEl.textContent = h + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0') + ' ' + ampm;
        if (dateEl) dateEl.textContent = now.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
      }
      tick();
      setInterval(tick, 1000);
    })();

    // ── Nav glide pill: a soft highlight follows the hovered link ──
    (function () {
      const nav = document.querySelector('.fd-nav');
      if (!nav || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      const glide = nav.querySelector('.fd-nav-glide');
      if (!glide) return;
      function place(link) {
        glide.style.width = link.offsetWidth + 'px';
        glide.style.height = link.offsetHeight + 'px';
        glide.style.transform = 'translateX(' + link.offsetLeft + 'px) translateY(' + link.offsetTop + 'px)';
      }
      nav.querySelectorAll('.fd-nav-link').forEach(function (link) {
        link.addEventListener('mouseenter', function () {
          if (link.classList.contains('is-active')) { nav.classList.remove('glide-on'); return; }
          place(link);
          nav.classList.add('glide-on');
        });
      });
      nav.addEventListener('mouseleave', function () { nav.classList.remove('glide-on'); });
    })();

    // ── Cursor spotlight on cards (CSS reads --spot-x/--spot-y) ──
    (function () {
      let raf = null;
      document.addEventListener('pointermove', function (e) {
        if (raf) return;
        raf = requestAnimationFrame(function () {
          raf = null;
          const t = e.target.closest && e.target.closest('.card, .stat-card, .mini-stat, .quick-action');
          if (!t) return;
          const r = t.getBoundingClientRect();
          t.style.setProperty('--spot-x', (e.clientX - r.left) + 'px');
          t.style.setProperty('--spot-y', (e.clientY - r.top) + 'px');
        });
      }, { passive: true });
    })();

    // ── Animated count-up for plain numeric KPI values ──
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
  </script>

  {{-- Session flashes surface as toasts (engine in resources/js/app.js).
       <x-frontdesk.flash /> keeps only the inline validation-error list. --}}
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

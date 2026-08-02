<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Farmers Hostel · Front Desk')</title>
  {{-- 96px derivative, not the 2500px / 1.26 MB source. --}}
  <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('image/derived/clsu.logo-96.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300..800&family=Geist+Mono:wght@400;500;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
  {{-- Same self-hosted Font Awesome Free the admin console loads — see the note
       in layouts/admin. Components go through <x-admin.ui.icon> (inlined SVG). --}}
  <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('vendor/fontawesome/webfonts/fa-solid-900.woff2') }}">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">

  <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
  {{-- Chart.js is loaded per-page (dashboard @push) — not every desk page charts --}}

  {{-- Same design-system bundle as the admin console: Fresh Meadow tokens,
       cards, tables, status pills, modals — plus the .fd-* band layer. --}}
  @vite(['resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])
  @livewireStyles
  @stack('styles')
</head>
{{-- Gate for the private `staff.alerts` Reverb subscription — see
     resources/js/admin-notifications.js. The desk gets the same live alerts as
     the admin console; it is usually the screen someone is actually sitting at. --}}
<body class="bg-surface text-ink antialiased" data-staff-alerts>

  {{-- Keyboard users land here first: one Tab jumps past the band's pill nav
       to the page content. Off-screen until focused (.skip-link, 02-base.css). --}}
  <a href="#main-content" class="skip-link">Skip to main content</a>

  {{-- Meadow Nightfall band: greeting, clock, pill nav (Finexa format) --}}
  <x-frontdesk.hero />

  {{-- Workspace: light Fresh Meadow cards overlapping the band --}}
  <main id="main-content" class="fd-main" tabindex="-1">
    <div class="stagger-enter space-y-6">
      @yield('content')
    </div>
  </main>

  <script>
    // Modal open/close, [data-modal-close] dismissal, Escape, the scroll lock
    // and focus trapping all come from resources/js/admin-modals.js (bundled
    // into the app.js this layout already loads). This file used to carry its
    // own byte-for-byte copy of those helpers; two implementations meant the
    // frontdesk console quietly missed every fix made on the admin side.

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

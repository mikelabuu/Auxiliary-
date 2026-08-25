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
  @include('partials.reverb')
  @vite(['resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])
  @livewireStyles
  @stack('styles')
</head>
{{-- Gate for the private `staff.alerts` Reverb subscription — see
     resources/js/admin-notifications.js. The desk gets the same live alerts as
     the admin console; it is usually the screen someone is actually sitting at. --}}
<body class="bg-surface text-ink antialiased" data-staff-alerts
      x-data="{
        density: (localStorage.getItem('adminDensity') || (localStorage.getItem('adminDensityCompact') === '1' ? 'compact' : 'normal')),
        setDensity(v) {
          if (this.density === v) return;
          this.density = v;
          localStorage.setItem('adminDensity', v);
          // Brief opacity dip masks the one-frame reflow (05-motion-ux.css)
          document.body.classList.add('density-switching');
          setTimeout(() => document.body.classList.remove('density-switching'), 160);
        }
      }"
      :class="{ 'density-compact': density === 'compact', 'density-large': density === 'large' }">

  {{-- Both first in the body on purpose — see the note in layouts/admin.
       Curtain for full document loads, bar for in-page Livewire work. --}}
  @include('partials.page-loader')
  @include('partials.page-progress')

  {{-- Apply the saved row size before Alpine boots so nothing flashes --}}
  <script>
    (function () {
      var d = localStorage.getItem('adminDensity');
      if (!d) d = localStorage.getItem('adminDensityCompact') === '1' ? 'compact' : 'normal';
      if (d === 'compact') document.body.classList.add('density-compact');
      else if (d === 'large') document.body.classList.add('density-large');
    })();
  </script>



  {{-- Meadow Nightfall band: greeting, clock, pill nav (Finexa format) --}}
  <x-frontdesk.hero />

  {{-- Workspace: light Fresh Meadow cards overlapping the band --}}
  <main id="main-content" class="fd-main" tabindex="-1">
    <div class="stagger-enter space-y-6">
      @yield('content')
    </div>
  </main>

  {{-- Modal open/close, [data-modal-close] dismissal, Escape, the scroll lock
       and focus trapping all come from resources/js/admin-modals.js. The
       entrance-animation cleanup, band clock, card spotlight and KPI count-up
       come from resources/js/staff-console.js — both bundled into the assets
       this layout already loads.

       This file used to carry its own byte-for-byte copy of all of it. Two
       implementations meant the frontdesk console quietly missed every fix
       made on the admin side; the glide pill below is genuinely desk-only and
       is the one thing that stays. --}}
  <script>
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
  {{-- Parity with layouts/admin: a desk page that pushed a modal here would
       otherwise have it silently dropped. --}}
  @stack('modals')
  @stack('scripts')
</body>
</html>

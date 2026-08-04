<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  {{-- Laravel Echo reads this to sign the POST /broadcasting/auth handshake
       that private channels require; without it the `staff.alerts`
       subscription 419s. The frontdesk layout has always carried it. --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Farmers Hostel · Admin Console')</title>
  {{-- 96px derivative, not the 2500px / 1.26 MB source. --}}
  <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('image/derived/clsu.logo-96.png') }}">

  {{-- AnimatedContent (reactbits.dev/animations/animated-content) gate: hide
       the content-block entrances before first paint so GSAP (app.js) can
       reveal them on scroll with no flash. Skipped under reduced motion; if the
       script never runs the CSS gate never arms, so content renders visible. --}}
  <script>
    try {
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.add('js-anim');
      }
    } catch (e) {}
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300..800&family=Geist+Mono:wght@400;500;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
  {{-- Font Awesome Free, self-hosted (scripts/sync-vendor.mjs), replacing the
       Material Icons CDN font. Components render icons as inlined SVG through
       <x-admin.ui.icon>, so this stylesheet is only needed for views that write
       `<i class="fa-solid fa-…">` directly — hence the preload of the one face
       that actually gets used. --}}
  <link rel="preload" as="font" type="font/woff2" crossorigin
        href="{{ asset('vendor/fontawesome/webfonts/fa-solid-900.woff2') }}">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  {{-- Self-hosted from public/vendor (see scripts/sync-vendor.mjs). These stay
       synchronous and ahead of @vite: inline blocks in several admin views call
       $(function(){…}) at parse time, which deferred module scripts would miss. --}}
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
  <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
  {{-- Chart.js is NOT here. Its only consumer is the Bookings Insights modal
       (partials/dashboard/insights-modal), which already pulls it in its own
       @push('scripts') — so loading it here meant every admin page paid 204 KB
       for a chart it does not draw, and the dashboard fetched it twice. --}}
  @vite(['resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])
  @livewireStyles
  @stack('styles')
</head>
{{-- data-staff-alerts is the gate for the private `staff.alerts` Reverb
     subscription (resources/js/admin-notifications.js). It is only ever on a
     layout that has already passed a staff auth middleware, so the module never
     attempts the authorisation handshake from a page with no staff session. --}}
<body class="shell-root bg-surface text-ink antialiased" data-staff-alerts
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
          // Brief opacity dip masks the one-frame reflow (05-motion-ux.css)
          document.body.classList.add('density-switching');
          setTimeout(() => document.body.classList.remove('density-switching'), 160);
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

  {{-- Keyboard users land here first: one Tab jumps past the sidebar and topbar
       to the page content. Off-screen until focused (.skip-link, 02-base.css). --}}
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <div class="grid-overlay"></div>

  {{-- Mobile overlay --}}
  <div class="sidebar-overlay" :class="{ 'open': sidebarOpen }" @click="sidebarOpen = false"></div>

  <x-admin.layout.sidebar />
  <x-admin.layout.topbar />

  {{-- Main content --}}
  {{-- .animate-children hands each top-level block to AnimatedContent
       (resources/js/animated-content.js) for a GSAP scroll-reveal, replacing
       the old CSS .stagger-enter load cascade. --}}
  <main id="main-content" class="shell-main" tabindex="-1">
    <div class="shell-content-wrap animate-children space-y-6">
      @yield('content')
    </div>
  </main>

  {{-- GradualBlur (reactbits.dev/animations/gradual-blur): the page content
       dissolves under a soft frosted fade at the bottom of the viewport. Fixed +
       pointer-events:none; the sidebar/topbar (higher z) cover their own regions. --}}
  <x-admin.ui.gradual-blur mode="fixed" height="4.5rem" :strength="1.8" class="gb-page" />

  {{-- Back to top --}}
  <button id="backToTop" class="back-to-top" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
  </button>

  {{-- Modal open/close, scroll lock, Escape and focus trapping live in
       resources/js/admin-modals.js. The entrance-animation cleanup, card
       spotlight, KPI count-up, live clock, back-to-top and copy-ref handler
       live in resources/js/staff-console.js — both bundled into the assets
       this layout already loads, and both shared with layouts/frontdesk, which
       used to carry a byte-identical inline copy of all of it. --}}
  <script>
    // ── Admin-only: the booking-detail dossier ──
    (function () {
      // The booking-detail modal: details + timeline + guest history, fetched
      // on demand and injected. Two callers share it — the guest name in a
      // table row, and the row's View button (livewire/bookings-table) — so it
      // is a global rather than a private closure. View used to render the
      // same partial through a Livewire property instead, which re-rendered
      // the whole table on every open and on every 15s poll thereafter.
      window.openBookingDetail = function (bookingId) {
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
      };

      // Dismissing this modal ([data-modal-close] and Escape) is handled
      // generically by the modal engine — nothing modal-specific here.
      document.addEventListener('click', function (e) {
        const link = e.target.closest && e.target.closest('.guest-history-link');
        if (!link) return;
        const bookingId = link.getAttribute('data-booking-id');
        if (!bookingId) return;
        e.preventDefault();

        // Guest history is read-only — open it directly (no password re-auth).
        window.openBookingDetail(bookingId);
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
  @stack('modals')
  @stack('scripts')
</body>
</html>

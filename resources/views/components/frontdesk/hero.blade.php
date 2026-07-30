{{--
    Meadow Nightfall band — the front desk's one dark, photographic moment
    (Finexa format: brand row → greeting → pill nav). Everything below it
    is the light Fresh Meadow workspace. Skin lives in admin.css (.fd-*).
--}}

@php
    $staff = Auth::guard('staff')->user();
    $firstName = explode(' ', trim($staff->name ?? 'Staff'))[0];
    // The desk is at CLSU; the app clock may be UTC, so pin the greeting
    // and date to Manila time.
    $manila = now()->timezone('Asia/Manila');
    $hour = (int) $manila->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    // Per-character reveal (text-effect): one running delay index across
    // the whole headline, emitted without whitespace between spans.
    $chIndex = 0;
    $renderChars = function (string $text) use (&$chIndex) {
        $out = '';
        foreach (mb_str_split($text) as $ch) {
            $out .= '<span class="fd-ch" style="--ch:' . $chIndex++ . '">' . e($ch) . '</span>';
        }
        return $out;
    };

    $navItems = [
        ['route' => 'frontdesk.dashboard.index', 'icon' => 'grid',          'label' => 'Dashboard'],
        ['route' => 'frontdesk.walkin.create',   'icon' => 'calendar-plus', 'label' => 'Manual Booking'],
        ['route' => 'frontdesk.room.index',      'icon' => 'bed',           'label' => 'Rooms'],
        ['route' => 'frontdesk.booking',         'icon' => 'clipboard',     'label' => 'Bookings'],
        ['route' => 'staff.paymentverification.index', 'icon' => 'receipt', 'label' => 'Verify Payments'],
    ];
@endphp

<header class="fd-hero">
    <div aria-hidden="true">
        <img src="{{ asset('image/hostel1.jpg') }}" alt="" class="fd-hero-photo">
        <div class="fd-hero-scrim"></div>
        <div class="fd-glow fd-glow-a"></div>
        <div class="fd-glow fd-glow-b"></div>
    </div>

    <div class="fd-hero-inner">
        {{-- Top row: brand · clock · staff · logout --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('image/FHLogo2.png') }}" class="h-10 w-10 object-contain" alt="Farmers Hostel logo">
                <div class="leading-tight">
                    <p class="fd-brand-title">Farmers Hostel</p>
                    <p class="fd-brand-sub fd-shine">Front Desk</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden text-right leading-tight sm:block">
                    <p class="fd-clock" id="fdClock">--:--:--</p>
                    <p class="fd-clock-date" id="fdClockDate"></p>
                </div>
                <div class="fd-chip">
                    <span class="fd-avatar">{{ strtoupper(substr($firstName, 0, 1)) }}</span>
                    <span class="hidden leading-tight md:block">
                        <span class="fd-chip-name block max-w-40 truncate">{{ $staff->name ?? 'Staff' }}</span>
                        <span class="fd-chip-role block">Front Desk</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('staff.logout') }}">
                    @csrf
                    <button type="submit" class="fd-iconbtn" title="Log out" aria-label="Log out">
                        <x-admin.ui.icon name="log-out" stroke-width="2" />
                    </button>
                </form>
            </div>
        </div>

        {{-- Greeting --}}
        <div class="pt-9">
            <p class="fd-eyebrow">{{ $manila->format('l, j F Y') }}</p>
            <h1 class="fd-greeting" aria-label="{{ $greeting }}, {{ $firstName }}">
                <span aria-hidden="true">{!! $renderChars($greeting . ', ') !!}<span class="fd-accent">{!! $renderChars($firstName) !!}</span></span>
            </h1>
        </div>

        {{-- Pill nav --}}
        <nav class="mt-7" aria-label="Front desk">
            <div class="fd-nav">
                <div class="fd-nav-glide" aria-hidden="true"></div>
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="fd-nav-link {{ $active ? 'is-active' : '' }}"
                       @if($active) aria-current="page" @endif>
                        <x-admin.ui.icon :name="$item['icon']" stroke-width="2" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </div>
</header>

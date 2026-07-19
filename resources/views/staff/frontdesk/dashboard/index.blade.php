@extends('layouts.frontdesk')

@section('title', 'Front Desk · Dashboard')
@section('content')

{{-- KPI row: the desk's day at a glance --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
    <x-admin.ui.stat-card icon="arrival" label="Arrivals Today" delay="0">
        {{ $arrivalsToday }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="departure" color="palay" label="Departures Today" delay="40">
        {{ $departuresToday }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="users" label="In-House Stays" delay="80">
        {{ $inHouse }}
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="bed" label="Available Tonight" delay="120">
        {{ $availableTonight }}
        <x-slot:footnote><p class="mt-1 text-xs text-faint">of {{ $totalRooms }} rooms</p></x-slot:footnote>
    </x-admin.ui.stat-card>
    <x-admin.ui.stat-card icon="credit-card" dark badge="TODAY" label="Collected" delay="160" class="col-span-2 lg:col-span-1">
        ₱{{ number_format($collectedToday, 2) }}
    </x-admin.ui.stat-card>
</div>

{{-- Quick actions --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-admin.ui.quick-action icon="calendar-plus" title="New walk-in booking" subtitle="Create a manual booking" :href="route('frontdesk.walkin.create')" />
    <x-admin.ui.quick-action icon="bed" title="Room board" subtitle="Status, rates and occupancy" :href="route('frontdesk.room.index')" />
    <x-admin.ui.quick-action icon="clipboard" title="Find a booking" subtitle="Search, view and check out" :href="route('frontdesk.booking')" />
</div>

{{-- Arrivals & departures + occupancy (shared Livewire components) --}}
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <livewire:dashboard.arrivals-departures />
    </div>
    <div class="lg:col-span-1">
        <livewire:dashboard.occupancy-snapshot />
    </div>
</div>

{{-- Calendar + bookings chart --}}
<div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h3 class="card-title">
                <x-admin.ui.icon name="calendar" />
                Calendar
            </h3>
            <div class="card-header-actions">
                <button id="prev" class="btn btn-outline btn-sm btn-icon" aria-label="Previous month">
                    <x-admin.ui.icon name="chevron-left" class="h-4 w-4" stroke-width="2" />
                </button>
                <span id="monthYear" class="min-w-32 text-center text-sm font-bold text-ink tabnum"></span>
                <button id="next" class="btn btn-outline btn-sm btn-icon" aria-label="Next month">
                    <x-admin.ui.icon name="chevron-right" class="h-4 w-4" stroke-width="2" />
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-7 gap-1 text-center">
                @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                    <div class="py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-faint">{{ $d }}</div>
                @endforeach
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1" id="calendarDays"></div>
        </div>
    </div>

    <div class="card lg:col-span-3">
        <div class="card-header">
            <h3 class="card-title">
                <x-admin.ui.icon name="chart-bar" />
                Bookings per month
            </h3>
            <span class="section-label">{{ now()->timezone('Asia/Manila')->year }}</span>
        </div>
        <div class="card-body">
            <canvas id="bookingsChart" height="220"></canvas>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('bookingsChart').getContext('2d');

  new Chart(ctx, {
      type: 'bar',
      data: {
          labels: @json($labels),
          datasets: [{
              label: 'Bookings',
              data: @json($values),
              backgroundColor: 'rgba(22, 179, 100, 0.72)',
              hoverBackgroundColor: 'rgba(9, 146, 80, 0.92)',
              borderColor: 'rgba(8, 116, 67, 1)',
              borderRadius: 8,
              borderWidth: 1
          }]
      },
      options: {
          responsive: true,
          plugins: {
              tooltip: {
                  callbacks: {
                      label: (context) => context.parsed.y + ' bookings'
                  }
              },
              legend: { display: false }
          },
          scales: {
              y: {
                  beginAtZero: true,
                  ticks: { stepSize: 1, font: { family: "'Geist', sans-serif" }, color: '#8ba295' },
                  grid: { color: 'rgba(20, 32, 26, 0.06)' }
              },
              x: {
                  ticks: { font: { family: "'Geist', sans-serif" }, color: '#51655a' },
                  grid: { display: false }
              }
          }
      }
  });
</script>
<script>
    const monthYear = document.getElementById("monthYear");
    const calendarDays = document.getElementById("calendarDays");
    const prevBtn = document.getElementById("prev");
    const nextBtn = document.getElementById("next");

    let date = new Date();

    // Day cell classes (Tailwind): resting, out-of-month, and today
    const DAY = "grid h-9 place-items-center rounded-full text-sm text-stone-600";
    const DAY_INACTIVE = "grid h-9 place-items-center rounded-full text-sm text-stone-300";
    const DAY_TODAY = "grid h-9 place-items-center rounded-full text-sm bg-g-600 font-semibold text-white shadow-[0_6px_16px_-6px_rgba(9,146,80,0.55)]";

    const renderCalendar = () => {
      const year = date.getFullYear();
      const month = date.getMonth();

      const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
      ];

      monthYear.textContent = `${months[month]} ${year}`;

      const firstDay = new Date(year, month, 1).getDay();
      const lastDate = new Date(year, month + 1, 0).getDate();
      const prevLastDate = new Date(year, month, 0).getDate();

      let daysHTML = "";

      for (let i = firstDay; i > 0; i--) {
        daysHTML += `<div class="${DAY_INACTIVE}">${prevLastDate - i + 1}</div>`;
      }

      for (let i = 1; i <= lastDate; i++) {
        const today = new Date();
        const isToday =
          i === today.getDate() &&
          month === today.getMonth() &&
          year === today.getFullYear();

        daysHTML += `<div class="${isToday ? DAY_TODAY : DAY}">${i}</div>`;
      }

      const totalCells = firstDay + lastDate;
      const nextDays = 7 - (totalCells % 7);
      if (nextDays < 7) {
        for (let i = 1; i <= nextDays; i++) {
          daysHTML += `<div class="${DAY_INACTIVE}">${i}</div>`;
        }
      }

      calendarDays.innerHTML = daysHTML;
    };

    prevBtn.addEventListener("click", () => {
      date.setMonth(date.getMonth() - 1);
      renderCalendar();
    });

    nextBtn.addEventListener("click", () => {
      date.setMonth(date.getMonth() + 1);
      renderCalendar();
    });

    renderCalendar();
</script>
@endpush

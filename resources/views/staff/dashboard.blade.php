@extends('layouts.admin')

@section('title', 'Admin - Dashboard')
@section('page-title', 'Dashboard')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
<div class="dashboard-container">

    <!-- Analytics Cards -->
    <div class="analytics-grid">
        <div class="card">
            <div class="card-header">
                <i class="icon fas fa-bed"></i>
                <h2>Total Rooms</h2>
            </div>
            <p class="value">{{ $totalRooms  }}</p>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="icon fas fa-book"></i>
                <h2>Total Bookings</h2>
            </div>
            <p class="value">{{ $totalBookings }}</p>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="icon fas fa-users"></i>
                <h2>Users</h2>
            </div>
            <p class="value">{{ $totalUsers }}</p>
        </div>

        <div class="card">
            <div class="card-header">
                <i>₱</i>
                <h2>Revenue</h2>
            </div>
            <p class="value"><p class="value"><p>₱{{ number_format($totalRevenue, 2) }}</p></p>
        </div>
    </div>

    {{-- New row: Arrivals/Departures + Occupancy --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <livewire:dashboard.arrivals-departures />
        </div>

        <div class="lg:col-span-1">
            <livewire:dashboard.occupancy-snapshot />
        </div>      
    </div>

    <!-- Charts -->
    <div class="charts-section">
        <div class="calendar">
            <header>
                <button id="prev">&lt;</button>
                <h2 id="monthYear"></h2>
                <button id="next">&gt;</button>
            </header>

            <div class="days">
            <div class="day-name">Sun</div>
            <div class="day-name">Mon</div>
            <div class="day-name">Tue</div>
            <div class="day-name">Wed</div>
            <div class="day-name">Thu</div>
            <div class="day-name">Fri</div>
            <div class="day-name">Sat</div>
        </div>
        <div class="days" id="calendarDays"></div>
    </div>

      <div class="chart-card">
          <canvas id="bookingsChart"></canvas>
      </div>
    </div>
</div>  

<script>
  const ctx = document.getElementById('bookingsChart').getContext('2d');

  const bookingsChart = new Chart(ctx, {
      type: 'bar',
      data: {
              labels: @json($labels),
              datasets: [{
                  label: 'Bookings',
                  data: @json($values),
                  backgroundColor: [
                      'rgba(54, 162, 235, 0.7)',
                      'rgba(255, 99, 132, 0.7)',
                      'rgba(255, 206, 86, 0.7)',
                      'rgba(75, 192, 192, 0.7)',
                      'rgba(153, 102, 255, 0.7)',
                      'rgba(255, 159, 64, 0.7)',
                      'rgba(199, 199, 199, 0.7)',
                      'rgba(255, 99, 71, 0.7)',
                      'rgba(100, 181, 246, 0.7)',
                      'rgba(255, 138, 101, 0.7)',
                      'rgba(174, 213, 129, 0.7)',
                      'rgba(240, 98, 146, 0.7)',
                  ],
                  borderRadius: 8,
                  borderWidth: 1
              }]
          },
          options: {
              responsive: true,
              plugins: {
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              return context.parsed.y + ' bookings';
                          }
                      }
                  },
                  legend: {
                      display: false
                  },
                  title: {
                      display: true,
                      text: 'Bookings per Month',
                      font: {
                          size: 16,
                          weight: 'bold'
                      },
                      padding: {
                          top: 10,
                          bottom: 20
                      }
                  }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      ticks: {
                          stepSize: 1
                      }
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

    const renderCalendar = () => {
      const year = date.getFullYear();
      const month = date.getMonth();

      // month names
      const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
      ];

      // set header
      monthYear.textContent = `${months[month]} ${year}`;

      // get first day of month
      const firstDay = new Date(year, month, 1).getDay();
      const lastDate = new Date(year, month + 1, 0).getDate();
      const prevLastDate = new Date(year, month, 0).getDate();

      let daysHTML = "";

      // previous month dates
      for (let i = firstDay; i > 0; i--) {
        daysHTML += `<div class="day inactive">${prevLastDate - i + 1}</div>`;
      }

      // current month dates
      for (let i = 1; i <= lastDate; i++) {
        const today = new Date();
        const isToday =
          i === today.getDate() &&
          month === today.getMonth() &&
          year === today.getFullYear();

        daysHTML += `<div class="day ${isToday ? "today" : ""}">${i}</div>`;
      }

      // next month dates
      const totalCells = firstDay + lastDate;
      const nextDays = 7 - (totalCells % 7);
      if (nextDays < 7) {
        for (let i = 1; i <= nextDays; i++) {
          daysHTML += `<div class="day inactive">${i}</div>`;
        }
      }

      calendarDays.innerHTML = daysHTML;
    };

    // navigation
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
@endsection

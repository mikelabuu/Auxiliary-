@extends('layouts.admin')
@section('title', 'Advanced Reports & Analytics')
@section('page-title', 'Advanced Reports and Analytics')
@section('content')
<div class="space-y-6">
    {{-- Charts Section --}}
    <div class="grid grid-cols-2 gap-6 mt-6">
        <div class="card p-4">
            <h3 class="font-semibold mb-2">Bookings Per Month</h3>
            <canvas id="bookingsChart"></canvas>
        </div>

        <div class="card p-4">
            <h3 class="font-semibold mb-2">Revenue Per Month</h3>
            <canvas id="revenueChart"></canvas>
        </div>

        <div class="card p-4">
            <h3 class="font-semibold mb-2">Payment Methods</h3>
            <canvas id="paymentMethodChart"></canvas>
        </div>

        <div class="card p-4">
            <h3 class="font-semibold mb-2">Discounts Summary</h3>
            <canvas id="discountsChart"></canvas>
        </div>
        {{-- Top Booked Room Types --}}
        <div class="card p-4">
            <h3 class="font-semibold mb-2">Top Booked Room Types</h3>
            <canvas id="topRoomsChart"></canvas>
        </div>

        {{-- Discount Approvals Trend --}}
        <div class="card p-4">
            <h3 class="font-semibold mb-2">Discount Approvals Trend</h3>
            <canvas id="discountTrendChart"></canvas>
        </div>

        {{-- Cancelled Booking Rate --}}
        <div class="card p-4">
            <h3 class="font-semibold mb-2">Cancelled Booking Rate</h3>
            <canvas id="cancelledRateChart"></canvas>
        </div>

        {{-- Average Occupancy Rate --}}
        <div class="card p-4">
            <h3 class="font-semibold mb-2">Average Occupancy Rate</h3>
            <canvas id="avgOccupancyChart"></canvas>
        </div>

        {{-- Peak Booking Times --}}
        <div class="card p-4">
            <h3 class="font-semibold mb-2">Peak Booking Times</h3>
            <canvas id="peakBookingChart"></canvas>
        </div>

        {{-- Revenue Contribution by Room Type --}}
        <div class="card p-4">
            <h3 class="font-semibold mb-2">Revenue Contribution by Room Type</h3>
            <canvas id="revenueByRoomChart"></canvas>
        </div>
    </div>
    <h1 class="text-lg font-semibold mb-2">Advanced Reports</h1>
        {{-- Bookings Export --}}
    <div class="card p-4">
        <h2 class="text-lg font-semibold mb-2">Export Bookings</h2>
        <form method="GET" action="{{ route('reports.central.bookings') }}" class="flex flex-wrap space-x-4 items-end">
            <div>
                <label>From:</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input">
            </div>
            <div>
                <label>To:</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input">
            </div>
            <div>
                <label>Booking Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                    <option value="pending_discount" {{ request('status') == 'pending_discount' ? 'selected' : '' }}>Pending Discount</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="no_show" {{ request('status') == 'no_show' ? 'selected' : '' }}>No Show</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Export Bookings</button>
            </div>
        </form>
    </div>

    {{-- Revenue Export --}}
    <div class="card p-4">
        <h2 class="text-lg font-semibold mb-2">Export Revenue</h2>
        <form method="GET" action="{{ route('reports.central.revenue') }}" class="flex flex-wrap space-x-4 items-end">
            <div>
                <label>From:</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input">
            </div>
            <div>
                <label>To:</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input">
            </div>
            <div>
                <label>Payment Gateway</label>
                <select name="payment_method" class="form-select">
                    <option value="">All</option>
                    <option value="sandbox" {{ request('payment_method') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-success">Export Revenue</button>
            </div>
        </form>
    </div>

    {{-- Occupancy Export --}}
    <div class="card p-4">
        <h2 class="text-lg font-semibold mb-2">Export Occupancy</h2>
        <form method="GET" action="{{ route('reports.central.occupancy') }}" class="flex flex-wrap space-x-4 items-end">
            <div>
                <label>From:</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input">
            </div>
            <div>
                <label>To:</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input">
            </div>
            <div>
                <button type="submit" class="btn btn-warning">Export Occupancy</button>
            </div>
        </form>
    </div>

    {{-- Payments Export --}}
    <div class="card p-4">
        <h2 class="text-lg font-semibold mb-2">Export Payments</h2>
        <form method="GET" action="{{ route('reports.central.payments') }}" class="flex flex-wrap space-x-4 items-end">
            <div>
                <label>From:</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input">
            </div>
            <div>
                <label>To:</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input">
            </div>
            <div>
                <label>Payment Gateway</label>
                <select name="payment_method" class="form-select">
                    <option value="">All</option>
                    <option value="sandbox" {{ request('payment_method') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-info">Export Payments</button>
            </div>
        </form>
    </div>

    {{-- Discounts Export --}}
    <div class="card p-4">
        <h2 class="text-lg font-semibold mb-2">Export Discounts</h2>
        <form method="GET" action="{{ route('reports.central.discounts') }}" class="flex flex-wrap space-x-4 items-end">
            <div>
                <label>From:</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-input">
            </div>
            <div>
                <label>To:</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-input">
            </div>
            <div>
                <button type="submit" class="btn btn-danger">Export Discounts</button>
            </div>
        </form>
    </div>

</div>

<script>
    // --- Grab data from controller ---
    const bookingsData = @json($bookingsPerMonth);
    const revenueData = @json($revenuePerMonth);
    const paymentsData = @json($paymentsByMethod);
    const discountsData = @json($discountsSummary);
    const topRoomsData = @json($topRoomsChart);
    const discountTrendData = @json($discountTrend);
    const cancelledRate = @json($cancelledRate);
    const avgOccupancy = @json($avgOccupancy);
    const peakBookingTimesData = @json($peakBookingTimes);
    const revenueByRoomData = @json($revenueByRoom);

    // --- Bookings per Month ---
    new Chart(document.getElementById('bookingsChart'), {
        type: 'bar',
        data: {
            labels: bookingsData.map(b => `Month ${b.month}`),
            datasets: [{
                label: 'Total Bookings',
                data: bookingsData.map(b => b.total),
                backgroundColor: '#3b82f6'
            }]
        },
        options: { responsive: true }
    });

    // --- Revenue per Month ---
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: revenueData.map(r => `Month ${r.month}`),
            datasets: [{
                label: 'Revenue',
                data: revenueData.map(r => r.revenue),
                backgroundColor: '#10b981'
            }]
        },
        options: { responsive: true }
    });

    // --- Payment Methods Pie ---
    new Chart(document.getElementById('paymentMethodChart'), {
        type: 'pie',
        data: {
            labels: paymentsData.map(p => p.gateway),
            datasets: [{
                data: paymentsData.map(p => p.total),
                backgroundColor: ['#f97316', '#8b5cf6']
            }]
        },
        options: { responsive: true }
    });

    // --- Discounts Summary Pie ---
    new Chart(document.getElementById('discountsChart'), {
        type: 'pie',
        data: {
            labels: discountsData.map(d => d.status),
            datasets: [{
                data: discountsData.map(d => d.total),
                backgroundColor: ['#facc15', '#10b981', '#ef4444']
            }]
        },
        options: { responsive: true }
    });

    // --- Top Booked Room Types ---
    new Chart(document.getElementById('topRoomsChart'), {
        type: 'bar',
        data: {
            labels: topRoomsData.map(r => r.room_type),
            datasets: [{
                label: 'Bookings',
                data: topRoomsData.map(r => r.total),
                backgroundColor: '#f59e0b'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                tooltip: { enabled: true }
            }
        }
    });

    // --- Discount Approvals Trend ---
    new Chart(document.getElementById('discountTrendChart'), {
        type: 'line',
        data: {
            labels: discountTrendData.map(d => `Month ${d.month}`),
            datasets: [{
                label: 'Approved Discounts',
                data: discountTrendData.map(d => d.total),
                borderColor: '#10b981',
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: {
                y: { beginAtZero: true } // ensures Y-axis starts at 0
            }
        }
    });

    // --- Cancelled Booking Rate ---
    new Chart(document.getElementById('cancelledRateChart'), {
        type: 'doughnut',
        data: {
            labels: ['Cancelled', 'Completed/Other'],
            datasets: [{
                data: [cancelledRate, 100 - cancelledRate],
                backgroundColor: ['#ef4444', '#10b981']
            }]
        },
        options: { responsive: true }
    });

    // --- Average Occupancy Rate ---
    new Chart(document.getElementById('avgOccupancyChart'), {
        type: 'doughnut',
        data: {
            labels: ['Occupied', 'Vacant'],
            datasets: [{
                data: [avgOccupancy, 100 - avgOccupancy],
                backgroundColor: ['#3b82f6', '#9ca3af']
            }]
        },
        options: { responsive: true }
    });

    // --- Peak Booking Times ---
    new Chart(document.getElementById('peakBookingChart'), {
        type: 'bar',
        data: {
            labels: peakBookingTimesData.map(p => `${p.hour}:00`),
            datasets: [{
                label: 'Bookings',
                data: peakBookingTimesData.map(p => p.total),
                backgroundColor: '#8b5cf6'
            }]
        },
        options: { responsive: true }
    });

    // --- Revenue Contribution by Room Type ---
    new Chart(document.getElementById('revenueByRoomChart'), {
        type: 'pie',
        data: {
            labels: revenueByRoomData.map(r => r.room_type),
            datasets: [{
                data: revenueByRoomData.map(r => r.revenue),
                backgroundColor: ['#f97316','#10b981','#3b82f6','#facc15','#8b5cf6']
            }]
        },
        options: { responsive: true }
    });
</script>

@endsection



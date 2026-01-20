 let charts = {}; // store all chart instances

    function renderCharts(data) {
        const { bookingsData, revenueData, paymentsData, discountsData } = data;

        // Utility: destroy previous chart instance
        function destroyChart(id) {
            if (charts[id]) {
                charts[id].destroy();
                charts[id] = null;
            }
        }

        // Helper: create a new chart
        function createChart(id, config) {
            destroyChart(id); // ensure no previous instance
            const ctx = document.getElementById(id).getContext('2d');
            charts[id] = new Chart(ctx, config);
        }

        // Bookings Bar
        createChart('bookingsChart', {
            type: 'bar',
            data: {
                labels: bookingsData.map(b => `Month ${b.month}`),
                datasets: [{
                    label: 'Total Bookings',
                    data: bookingsData.map(b => b.total),
                    backgroundColor: '#3b82f6'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Revenue Bar
        createChart('revenueChart', {
            type: 'bar',
            data: {
                labels: revenueData.map(r => `Month ${r.month}`),
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(r => r.revenue),
                    backgroundColor: '#10b981'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Payments Pie
        createChart('paymentMethodChart', {
            type: 'pie',
            data: {
                labels: paymentsData.map(p => p.gateway),
                datasets: [{
                    data: paymentsData.map(p => p.total),
                    backgroundColor: ['#f97316', '#8b5cf6']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Discounts Pie
        createChart('discountsChart', {
            type: 'pie',
            data: {
                labels: discountsData.map(d => d.status),
                datasets: [{
                    data: discountsData.map(d => d.total),
                    backgroundColor: ['#facc15', '#10b981', '#ef4444']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // Initial render on page load
    renderCharts({
        bookingsData: @json($bookingsPerMonth ?? []),
        revenueData: @json($revenuePerMonth ?? []),
        paymentsData: @json($paymentsByMethod ?? []),
        discountsData: @json($discountsSummary ?? [])
    });
<div class="bg-white shadow rounded-lg p-4">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">
            Occupancy For: {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
        </h3>
        <button wire:click="recalculate" class="text-sm px-3 py-1 border rounded hover:bg-gray-50">
            Refresh
        </button>
    </div>

    <div wire:poll.{{ $pollInterval }}s.keep-alive>
        <div class="flex items-center space-x-4">


            <div class="flex-1">
                <div class="text-sm text-gray-500">Occupied / Total</div>
                <div class="text-2xl font-bold">
                    {{ $occupied }} / {{ $total }}
                    <span class="text-sm text-gray-500">({{ $percent }}%)</span>
                </div>

                <div class="mt-3">
                    <div class="w-full bg-gray-100 h-3 rounded overflow-hidden">
                        <div style="width: {{ $percent }}%" class="h-3 bg-green-500"></div>
                    </div>
                </div>

                <div class="mt-3 text-sm text-gray-600">
                    <div>Available: <strong>{{ $available }}</strong></div>
                    <div class="mt-1">Occupied: <strong>{{ $occupied }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("livewire:navigated", initChart);
    document.addEventListener("livewire:load", initChart);

    let donutChart = null;

    function initChart() {
        const canvas = document.getElementById('occupancyDonut');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // If already initialized, destroy first (to avoid duplicates)
        if (donutChart) {
            donutChart.destroy();
        }

        donutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Available'],
                datasets: [{
                    data: [{{ $occupied }}, {{ max(0, $total - $occupied) }}],
                    backgroundColor: ['#16a34a', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                },
                maintainAspectRatio: false,
            }
        });

        // Listen for Livewire updates
        window.addEventListener('occupancy-updated', (e) => {
            const payload = e.detail || {};
            const occupied = payload.occupied || 0;
            const available = (payload.total || 0) - occupied;

            if (donutChart) {
                donutChart.data.datasets[0].data = [occupied, available];
                donutChart.update();
            }
        });
    }
</script>
@endpush

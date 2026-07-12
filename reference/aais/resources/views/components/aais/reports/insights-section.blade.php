@props([
    'officeBreakdown' => [],
])

<div class="reports-insights-grid">
    <div class="card">
        <x-aais.ui.card-header
            title="Weekly Trend"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M18 20V10M12 20V4M6 20v-6'/></svg>"
        />
        <div class="card-body">
            <div class="chart-container reports-chart">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <x-aais.ui.card-header
            title="Office Breakdown"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M3 21V8l9-5 9 5v13M9 21v-6h6v6'/></svg>"
        />
        <div class="card-body">
            <div class="reports-breakdown-chart-wrap">
                <div class="chart-container reports-chart reports-chart-donut">
                    <canvas id="officeBreakdownChart"></canvas>
                </div>
                <div class="reports-breakdown-list reports-breakdown-list-compact">
                    @foreach ($officeBreakdown as $office)
                        <x-aais.reports.office-breakdown-row :name="$office['name']" :count="$office['count']" :percent="$office['percent']" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@props([
    'kpis' => [],
])

<div class="reports-kpi-grid">
    @foreach ($kpis as $kpi)
        <x-aais.reports.kpi-card :period="$kpi['period']" :value="$kpi['value']" :label="$kpi['label']" />
    @endforeach
</div>

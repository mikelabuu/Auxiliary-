@props([
    'kpis' => [],
])

<div class="dashboard-kpi-grid">
    @foreach ($kpis as $kpi)
        <x-aais.ui.stat-card
            :value="$kpi['value']"
            :label="$kpi['label']"
            :icon="$kpi['icon']"
            :bg="$kpi['bg']"
            :trend="$kpi['trend']"
            :up="$kpi['up']"
            class="{{ str_contains(strtolower($kpi['label']), 'pickup') ? 'stat-card-pickup' : '' }}"
        />
    @endforeach
</div>

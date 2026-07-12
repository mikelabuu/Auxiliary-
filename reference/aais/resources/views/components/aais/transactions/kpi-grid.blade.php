@props([
    'kpis' => [],
])

<div class="transactions-kpi-grid">
    @foreach ($kpis as $kpi)
        @php
            $label = strtolower((string) ($kpi['label'] ?? ''));
            $trend = strtolower((string) ($kpi['trend'] ?? ''));
            $isPickup = str_contains($label, 'pickup');
            $isNeedsAction = str_contains($trend, 'needs action') || str_contains($label, 'in-process') || str_contains($label, 'in process');
        @endphp
        <x-aais.ui.stat-card
            :value="$kpi['value']"
            :label="$kpi['label']"
            :icon="$kpi['icon']"
            :bg="$kpi['bg']"
            :trend="$kpi['trend']"
            :up="$kpi['up']"
            class="{{ $isPickup ? 'stat-card-pickup' : ($isNeedsAction ? 'stat-card-needs-action' : '') }}"
        />
    @endforeach
</div>

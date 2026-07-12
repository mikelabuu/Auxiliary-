@props([
    'recent' => [],
    'statusLabels' => [],
    'title' => 'Recent Receptions - Today',
])

<div class="card card-overflow-hidden">
    <x-aais.ui.card-header
        :title="$title"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 16 14'/></svg>"
    >
        <x-slot:actions>
            <span class="badge badge-green">{{ count($recent) }}</span>
        </x-slot:actions>
    </x-aais.ui.card-header>

    <div class="scroll-x">
        <table class="data-table">
            <thead><tr><th>Ref Code</th><th>Client</th><th>Document Type</th><th>Time</th><th>Route To</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($recent as $r)
                    <tr>
                        <td><span class="ref-code">{{ $r['ref'] }}</span></td>
                        <td class="cell-strong">{{ $r['name'] }}</td>
                        <td>{{ $r['type'] }}</td>
                        <td class="text-muted">{{ $r['time'] }}</td>
                        <td>
                            @if ($r['next'] !== '—')
                                <div class="portal-route-inline">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                    <span>{{ $r['next'] }}</span>
                                </div>
                            @else
                                <span class="text-faint">—</span>
                            @endif
                        </td>
                        <td>
                            <x-aais.ui.status-badge :status="$r['status']" :label="$statusLabels[$r['status']] ?? ucfirst($r['status'])" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@props([
    'steps' => [],
    'title' => 'Document Timeline',
])

<div class="card" style="overflow:hidden;">
    <x-aais.ui.card-header
        :title="$title"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 16 14'/></svg>"
    />

    <div class="card-body" style="padding:30px;">
        <div class="timeline">
            @foreach ($steps as $s)
                <x-aais.ui.timeline-item
                    :title="$s['title']"
                    :meta="$s['meta']"
                    :done="$s['done']"
                    :active="($s['active'] ?? false)"
                    :last="($s['last'] ?? false)"
                    :status="($s['active'] ?? false) ? 'process' : null"
                    :status-label="($s['active'] ?? false) ? 'Current' : null"
                />
            @endforeach
        </div>
    </div>
</div>

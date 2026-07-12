<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:22px;margin-bottom:24px;">
    <div class="card" style="overflow:hidden;">
        <x-aais.ui.card-header
            title="Processing Volume (Last 7 Days)"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'/></svg>"
        />
        <div style="padding:16px;">
            <canvas id="volumeChart" style="width:100%;height:220px;"></canvas>
        </div>
    </div>

    <div class="card" style="overflow:hidden;">
        <x-aais.ui.card-header
            title="Document Status"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z'/><path d='M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'/></svg>"
        />
        <div style="padding:16px;display:flex;align-items:center;justify-content:center;">
            <canvas id="statusChart" style="max-height:220px;"></canvas>
        </div>
    </div>
</div>

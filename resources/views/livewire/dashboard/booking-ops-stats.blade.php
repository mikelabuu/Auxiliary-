{{-- display:contents so the pills stay direct flex children of .ops-pills --}}
<div wire:poll.15s style="display:contents">
    <span class="ops-pill"><span class="ops-pill-num">{{ $visibleNow }}</span> visible now</span>
    <span class="ops-pill"><span class="ops-pill-dot warn"></span><span class="ops-pill-num">{{ $inProcess }}</span> in process</span>
    <span class="ops-pill"><span class="ops-pill-dot"></span><span class="ops-pill-num">{{ $activeStays }}</span> active stays</span>
    <span class="ops-pill"><span class="ops-pill-dot info"></span><span class="ops-pill-num">{{ $arrivingToday }}</span> arriving today</span>
</div>

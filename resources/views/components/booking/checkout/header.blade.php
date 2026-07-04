@props([
    'title' => 'Checkout',
    'subtitle' => null,
    'backUrl' => '#',
    'backLabel' => 'Back',
])

<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-sm font-semibold text-slate-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    <a href="{{ $backUrl }}" class="text-sm font-bold text-[#0a4f2d] hover:underline flex items-center gap-1">
        <span class="material-icons text-[16px]">arrow_back</span> {{ $backLabel }}
    </a>
</div>
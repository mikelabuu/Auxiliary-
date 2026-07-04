@props([
    'title',
    'beds',
    'price',
    'typeId',
    'image',
    'capacity' => '',
    'badge' => null,
    'amenities' => [],
])

@php
    $formattedPrice = number_format($price);
@endphp

<div class="relative bg-white rounded-[24px] overflow-hidden shadow-[0_8px_24px_rgba(0,0,0,0.04)] hover:shadow-[0_20px_40px_rgba(10,79,45,0.08)] hover:-translate-y-1 transition-all duration-500 flex flex-col h-full group border border-slate-200/80 p-2">
    <!-- Image -->
    <div class="relative h-64 overflow-hidden flex-shrink-0 rounded-[20px] bg-slate-100">
        <img src="{{ asset($image) }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700 ease-out" alt="{{ $title }}" loading="lazy">
        
        @if ($badge)
            <div class="absolute top-3 left-3 bg-[#d4af37] text-[#0a4f2d] px-3 py-1 rounded-[9999px] text-[10px] font-bold uppercase tracking-[0.15em] shadow-sm">
                {{ $badge }}
            </div>
        @endif

        <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md px-3 py-1.5 rounded-[9999px] text-[10px] font-bold text-slate-800 shadow-sm flex items-center gap-1.5">
            <span class="material-icons text-[14px] text-[#0a4f2d]">people</span>
            {{ $capacity }}
        </div>
    </div>

    <!-- Body -->
    <div class="px-3 pt-4 pb-2 flex flex-col flex-1">
        <h4 class="text-xl font-bold tracking-tight text-slate-900" style="font-family: var(--font-serif);">{{ $title }}</h4>
        
        <!-- Price -->
        <div class="flex items-baseline justify-between mt-1">
            <div class="flex items-baseline gap-1">
                <span class="text-lg font-bold text-[#0a4f2d]">₱{{ $formattedPrice }}</span>
                <span class="text-xs font-semibold text-slate-500">/ night</span>
            </div>
            <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                <span class="material-icons text-[13px] text-slate-400">bed</span>
                {{ $beds }} pax max
            </span>
        </div>

        <!-- Amenities (Pills) -->
        <div class="mt-4 flex-1">
            <div class="flex flex-wrap gap-1.5 text-[11px] font-bold">
                @forelse ($amenities as $amenity)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-[9999px] bg-slate-50 border border-slate-200 text-slate-600">
                        <span class="material-icons text-[13px] text-[#0a4f2d]">{{ $amenity['icon'] ?? 'check_circle' }}</span>
                        {{ $amenity['label'] ?? $amenity }}
                    </span>
                @empty
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-[9999px] bg-slate-50 border border-slate-200 text-slate-600">
                        <span class="material-icons text-[13px] text-[#0a4f2d]">wifi</span>
                        Free Wi-Fi
                    </span>
                @endforelse
            </div>
        </div>

        <!-- CTA Buttons -->
        <div class="mt-6 flex flex-col sm:flex-row gap-2">
            <button type="button"
               onclick="bookRoomDirect('{{ $typeId }}')"
               class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-full text-sm font-bold text-white transition-all duration-300 relative group overflow-hidden cursor-pointer shadow-[0_4px_12px_rgba(10,79,45,0.3)] hover:shadow-[0_6px_16px_rgba(10,79,45,0.4)] hover:-translate-y-0.5"
               style="background-color: var(--color-clsu-green);"
            >
                <span class="material-icons text-[16px]">calendar_month</span>
                Book
            </button>
            <button type="button"
               onclick="window.dispatchEvent(new CustomEvent('open-room-detail', { detail: { roomId: '{{ $typeId }}' } }))"
               class="flex-[0.8] inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-full text-sm font-bold text-[#0a4f2d] bg-slate-50 border border-slate-200 hover:bg-slate-100 transition-all duration-300 cursor-pointer"
            >
                Details
            </button>
        </div>
    </div>
</div>

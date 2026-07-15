<div class="animate-in bg-white rounded-2xl border border-stone-200 shadow-card hover:shadow-card-lg transition-shadow duration-200 p-6 flex flex-col" style="animation-delay:240ms">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-lg bg-clsu-100 text-clsu-700 flex items-center justify-center">
        <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12A9 9 0 1 1 12 3v9z"/></svg>
      </div>
      <p class="font-semibold text-stone-900 text-sm">Occupancy</p>
    </div>
    <button wire:click="forceRecalculate" class="text-stone-300 hover:text-clsu-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clsu-500/40 focus-visible:ring-offset-2 rounded-full p-1" aria-label="Refresh occupancy">
      <svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    </button>
  </div>

  {{-- The poll must name a method: a bare wire:poll only re-renders the
       already-mounted properties, so the card never actually updated. --}}
  <div wire:poll.{{ $pollInterval }}s.keep-alive="recalculate" class="flex flex-col items-center py-2">
    <div class="relative w-[140px] h-[140px]">
      @php
          $circumference = 364.42;
          $dashOffset = $circumference - ($percent / 100) * $circumference;
      @endphp
      <svg width="140" height="140" viewBox="0 0 140 140" class="gauge-ring">
        <defs>
          <linearGradient id="occGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3ccb7f"/>
            <stop offset="55%" stop-color="#16b364"/>
            <stop offset="100%" stop-color="#099250"/>
          </linearGradient>
        </defs>
        <!-- Background track -->
        <circle cx="70" cy="70" r="58" fill="none" stroke="#e6f7ed" stroke-width="13" />
        <!-- Progress track -->
        <circle cx="70" cy="70" r="58" fill="none" stroke="url(#occGrad)" stroke-width="13"
                stroke-linecap="round" class="gauge-value"
                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}" />
      </svg>
      <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
        <p class="text-[28px] font-extrabold font-data tabnum text-stone-900 leading-none">{{ round($percent) }}<span class="text-lg text-stone-400">%</span></p>
        <p class="text-[9.5px] font-bold text-stone-400 tracking-[0.15em] mt-1">OCCUPIED</p>
      </div>
    </div>
    
    <div class="mt-5 space-y-1.5 w-full">
      <div class="flex items-center justify-between text-xs">
        <span class="flex items-center gap-1.5 text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-100 border border-clsu-300"></span>Available</span>
        <span class="font-bold font-data text-stone-700 tabnum">{{ $available }}</span>
      </div>
      <div class="flex items-center justify-between text-xs">
        <span class="flex items-center gap-1.5 text-stone-500"><span class="w-2 h-2 rounded-full bg-clsu-500"></span>Occupied</span>
        <span class="font-bold font-data text-stone-700 tabnum">{{ $occupied }}</span>
      </div>
      @if($outOfService > 0)
      <div class="flex items-center justify-between text-xs">
        <span class="flex items-center gap-1.5 text-stone-500"><span class="w-2 h-2 rounded-full bg-ember-100 border border-ember-400"></span>Out of service</span>
        <span class="font-bold font-data text-stone-700 tabnum">{{ $outOfService }}</span>
      </div>
      @endif
    </div>
  </div>

  <div class="mt-5 pt-4 border-t border-stone-100 space-y-2.5">
    <p class="text-[10px] font-bold text-stone-400 tracking-widest uppercase">BY ROOM TYPE</p>
    <div>
      <div class="flex items-center justify-between text-xs mb-1">
        <span class="text-stone-600 font-medium">Dorm Beds</span>
        <span class="text-stone-400 font-data tabnum">{{ $dormOccupied }} / {{ $dormTotal }}</span>
      </div>
      <div class="h-1.5 rounded-full bg-clsu-100 overflow-hidden">
        <div class="h-full bg-gradient-to-r from-clsu-500 to-clsu-700 rounded-full transition-all duration-500" style="width:{{ $dormPercent }}%"></div>
      </div>
    </div>
    <div>
      <div class="flex items-center justify-between text-xs mb-1">
        <span class="text-stone-600 font-medium">Standard Rooms</span>
        <span class="text-stone-400 font-data tabnum">{{ $standardOccupied }} / {{ $standardTotal }}</span>
      </div>
      <div class="h-1.5 rounded-full bg-clsu-100 overflow-hidden">
        <div class="h-full bg-gradient-to-r from-clsu-500 to-clsu-700 rounded-full transition-all duration-500" style="width:{{ $standardPercent }}%"></div>
      </div>
    </div>
    <div>
      <div class="flex items-center justify-between text-xs mb-1">
        <span class="text-stone-600 font-medium">Deluxe Rooms</span>
        <span class="text-stone-400 font-data tabnum">{{ $deluxeOccupied }} / {{ $deluxeTotal }}</span>
      </div>
      <div class="h-1.5 rounded-full bg-clsu-100 overflow-hidden">
        <div class="h-full bg-gradient-to-r from-clsu-500 to-clsu-700 rounded-full transition-all duration-500" style="width:{{ $deluxePercent }}%"></div>
      </div>
    </div>
  </div>

  <div class="bg-gradient-to-br from-clsu-50 to-white border border-clsu-100 rounded-xl p-3.5 mt-4">
    <p class="text-xs text-clsu-800 leading-snug">
        @if($occupied == 0)
            No guests checked in yet. Rooms are ready to go.
        @else
            {{ $occupied }} room(s) currently occupied.
        @endif
    </p>
    <a href="{{ route('staff.rooms') }}" class="mt-2 text-xs font-bold text-clsu-700 flex items-center gap-1 hover:gap-1.5 transition-all !no-underline w-fit">
      Manage Rooms
      <svg class="icon w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
  </div>
</div>

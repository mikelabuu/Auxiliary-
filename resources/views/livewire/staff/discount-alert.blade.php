<div wire:poll.60s class="relative">
    <a href="{{ route('staff.discounts.index') }}" class="relative text-gray-700 hover:text-green-700 transition duration-200">
        <i class="fa-solid fa-tag text-xl"></i>
        @if($pendingCount > 0)
            <span class="absolute -top-1 -right-2 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-full animate-pulse">
                {{ $pendingCount }}
            </span>
        @endif
    </a>
</div>
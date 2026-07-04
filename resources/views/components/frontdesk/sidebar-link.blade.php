@props([
    'href',
    'icon',
    'active' => false,
    'isPinned' => true
])

<a href="{{ $href }}" 
   {{ $attributes->merge([
       'class' => 'mx-2 group/item flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 relative ' .
                  ($active ? 'bg-gradient-to-r from-emerald-800/40 to-emerald-950/20 text-amber-300 shadow-inner border border-emerald-700/20' : 'text-emerald-100/70 hover:text-white hover:bg-white/5 hover:translate-x-1')
   ]) }}>
    <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-lg bg-amber-400 transition-all duration-300"
          :class="{{ $active ? 'true' : 'false' }} ? 'opacity-100 scale-100' : 'opacity-0 scale-50'"></span>
    
    <span class="material-icons text-xl transition-transform duration-300 group-hover/item:scale-110 min-w-[1.75rem]
                 {{ $active ? 'text-amber-300' : 'text-emerald-400/80 group-hover/item:text-white' }}">{{ $icon }}</span>
    
    <span class="ml-3 transition-all duration-300 whitespace-nowrap"
          :class="isPinned ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden pointer-events-none group-hover:opacity-100 group-hover:w-auto'">
        {{ $slot }}
    </span>
</a>

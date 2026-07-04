@props([
    'href',
    'active' => false
])

<a href="{{ $href }}" 
   {{ $attributes->merge([
       'class' => 'group flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-clsu-950 !no-underline ' .
                  ($active ? 'bg-white/10 text-white font-semibold border-l-2 border-palay-400' : 'text-clsu-200/80 hover:bg-white/5 hover:text-white')
   ]) }}>
    
    {{ $icon }}
    
    <span class="label-fade truncate">{{ $slot }}</span>
</a>

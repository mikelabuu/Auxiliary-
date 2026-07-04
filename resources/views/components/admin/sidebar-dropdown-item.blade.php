@props([
    'href',
    'active' => false
])

<a href="{{ $href }}" 
   class="group/sub flex items-center px-3 py-2 text-xs font-medium rounded-lg transition duration-150 my-0.5 !no-underline
          {{ $active ? 'bg-white/10 text-white font-semibold' : 'text-clsu-200/80 hover:bg-white/5 hover:text-white' }}">
  <span class="transition-colors mr-3 shrink-0">
    {{ $icon }}
  </span> 
  <span class="label-fade truncate">{{ $slot }}</span>
</a>

@props([
    'title',
    'active' => false
])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="space-y-0.5">
  <button @click="open = !open" 
          class="w-full group flex items-center justify-between px-3 py-2.5 rounded-xl text-clsu-200/80 hover:bg-white/5 hover:text-white font-medium text-sm transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-palay-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-clsu-950 !no-underline cursor-pointer"
          title="{{ $title }}">
    <span class="flex items-center gap-3 min-w-0">
        {{ $icon }}
        <span class="label-fade truncate">{{ $title }}</span>
    </span>
    <svg class="label-fade icon w-3.5 h-3.5 text-clsu-500 group-hover:text-clsu-300 transition-transform duration-300 shrink-0" 
         :class="open ? 'rotate-180' : 'rotate-0'"
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="6 9 12 15 18 9"/>
    </svg>
  </button>

  <!-- Dropdown items -->
  <div x-show="open" 
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="transform opacity-0 -translate-y-2"
       x-transition:enter-end="transform opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-75"
       x-transition:leave-start="transform opacity-100 translate-y-0"
       x-transition:leave-end="transform opacity-0 -translate-y-2"
       class="my-1 space-y-0.5"
       :class="collapsed ? 'pl-0 border-l-0 ml-0' : 'pl-6 border-l border-white/10 ml-5'">
    {{ $slot }}
  </div>
</div>

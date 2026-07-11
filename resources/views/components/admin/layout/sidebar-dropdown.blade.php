@props([
    'title',
    'active' => false
])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
  <button @click="open = !open" type="button"
          class="sidebar-link {{ $active ? 'active' : '' }}"
          title="{{ $title }}" :aria-expanded="open.toString()">
    {{ $icon ?? '' }}
    <span class="truncate flex-1 text-left">{{ $title }}</span>
    <svg class="sidebar-link-caret" :class="{ 'open': open }"
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="6 9 12 15 18 9"/>
    </svg>
  </button>

  {{-- Dropdown items --}}
  <div x-show="open"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="transform opacity-0 -translate-y-2"
       x-transition:enter-end="transform opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-75"
       x-transition:leave-start="transform opacity-100 translate-y-0"
       x-transition:leave-end="transform opacity-0 -translate-y-2"
       class="sidebar-subnav">
    {{ $slot }}
  </div>
</div>

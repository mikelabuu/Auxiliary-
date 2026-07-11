@props([
    'href',
    'active' => false
])

<a href="{{ $href }}" class="sidebar-link !no-underline {{ $active ? 'active' : '' }}">
  {{ $icon ?? '' }}
  <span class="truncate flex-1">{{ $slot }}</span>
</a>

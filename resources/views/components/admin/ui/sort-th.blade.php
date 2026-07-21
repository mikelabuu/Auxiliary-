@props(['field', 'active' => null, 'dir' => 'asc'])

{{-- Server-side sortable header for Livewire tables using the WithSorting trait.
     Renders the .sortable arrow and toggles asc/desc via sortBy(). --}}
<th wire:click="sortBy('{{ $field }}')" {{ $attributes->class([
    'sortable',
    'sort-asc' => $active === $field && $dir === 'asc',
    'sort-desc' => $active === $field && $dir === 'desc',
]) }}>{{ $slot }}</th>

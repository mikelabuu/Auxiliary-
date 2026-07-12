@props(['items' => []])

{{--
    Breadcrumb trail for detail / nested pages, so a deep-linked user
    knows where they are and can climb back up.

    Each item: ['label' => 'Bookings', 'href' => route('staff.bookings.index')]
    The LAST item is the current page and is rendered as plain text
    (no href needed), marked aria-current="page".

    <x-admin.ui.breadcrumb :items="[
        ['label' => 'Bookings', 'href' => route('staff.bookings.index')],
        ['label' => '#' . $booking->id],
    ]" />
--}}

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'admin-breadcrumb']) }}>
    <ol>
        @foreach($items as $item)
            <li>
                @if(!empty($item['href']) && !$loop->last)
                    <a href="{{ $item['href'] }}" class="!no-underline">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page">{{ $item['label'] }}</span>
                @endif
                @unless($loop->last)
                    <svg class="admin-breadcrumb-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>

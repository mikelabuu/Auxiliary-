{{--
    Row-size control for a data table.

    This used to be one icon button in the topbar that cycled compact →
    comfortable → large. Two problems with that: an unlabelled stack of lines
    does not say what it changes, and a cycle cannot show which of the three
    you are currently on — you pressed it and watched the page to find out.
    It also sat several hundred pixels away from the table it resized, on a bar
    shared with search and notifications.

    So: named options, current one filled, sitting in the table's own filter
    toolbar. The state still lives on <body> (layouts/admin, layouts/frontdesk)
    and is read by body.density-compact / .density-large in admin/05-motion-ux,
    so several of these on one page stay in step and the choice survives a
    reload.

    Usage — drop it in a .filter-toolbar, usually right after the spacer:

        <div class="filter-toolbar-spacer"></div>
        <x-admin.ui.density-switch />
--}}

@props([
    /* The visible caption. Say what it resizes when the control is not
       adjacent to an obvious table (e.g. inside a card header). */
    'label' => 'Row size',
])

<div {{ $attributes->merge(['class' => 'density-switch']) }} role="group" aria-label="{{ $label }}">
    <span class="density-switch-label">{{ $label }}</span>
    <div class="density-switch-track">
        @foreach ([
            ['value' => 'compact', 'text' => 'Compact', 'hint' => 'More rows on screen'],
            ['value' => 'normal',  'text' => 'Default', 'hint' => 'The standard row height'],
            ['value' => 'large',   'text' => 'Large',   'hint' => 'Bigger type and taller rows'],
        ] as $opt)
            {{-- type="button" matters: several of these toolbars are a GET
                 <form>, and a bare <button> in one submits it. --}}
            <button type="button"
                    class="density-switch-opt"
                    @click="setDensity('{{ $opt['value'] }}')"
                    :class="{ 'is-on': density === '{{ $opt['value'] }}' }"
                    :aria-pressed="(density === '{{ $opt['value'] }}').toString()"
                    title="{{ $opt['text'] }} rows — {{ $opt['hint'] }}">{{ $opt['text'] }}</button>
        @endforeach
    </div>
</div>

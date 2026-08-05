@props([
    'name' => null,
    'control' => 'input',   // input | select | textarea
])

{{--
    One admin form control: the shared styling, plus the error state derived
    from the field's own name.

    <x-admin.ui.field name="price" type="number" step="0.01" :value="old('price')" />
    <x-admin.ui.field name="status" control="select">…options…</x-admin.ui.field>
    <x-admin.ui.field control="textarea" rows="3" />          (no name: JS-driven)

    NOTE: never nest a Blade comment inside this block. Blade matches its
    comment delimiters non-greedily, so an inner closing delimiter terminates
    the outer comment early and every line after it renders onto the page as
    literal text. That is exactly what happened here once.

    Why the error state is derived rather than passed
    -------------------------------------------------
    The border/ring swap for an invalid field was written inline as the same
    ternary five times:

        border {{ '{{' }} $errors->has('wing') ? 'border-ember-300 …' : 'border-stone-200 …' }}

    Repeated conditional styling is not the same kind of duplication as a
    repeated class string. A class string that drifts looks slightly wrong; a
    conditional that is *omitted* looks completely fine and silently stops
    telling the user which field they got wrong. That had already happened once
    — `status` on the Add Room form is validated against Room::SETTABLE_STATUSES
    but was the one field of six with neither the error border nor a message.

    Passing `name` is the whole opt-in. A field that names itself cannot forget
    to react to its own errors.

    Controls with no `name` are the JS-driven Edit Room and Room Type modals,
    which post over AJAX and never populate Laravel's error bag. They take the
    styling and skip the binding, rather than sitting outside the component and
    drifting on their own.
--}}

@php
    $invalid = $name && $errors->has($name);

    $classes = trim(implode(' ', [
        'w-full px-4 py-2.5 rounded-xl border bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 transition-colors',
        $invalid
            ? 'border-ember-300 focus:ring-ember-300 focus:border-ember-300'
            : 'border-stone-200 focus:ring-clsu-500/25 focus:border-clsu-500',
        // Selects keep the pointer; textareas lose the drag handle. Both match
        // what the hand-written versions did.
        match ($control) { 'select' => 'cursor-pointer', 'textarea' => 'resize-none', default => '' },
    ]));

    // Defaults, not overrides. merge() concatenates `class` and lets an
    // explicitly passed attribute win for everything else — so a field that
    // needs its own id (#room-type, which admin-rooms.js looks up) keeps it,
    // and the rest fall back to the field name. Emitting id separately as well
    // is what produced a duplicate id= on those two controls.
    $defaults = ['class' => $classes];

    if ($control === 'input') {
        $defaults['type'] = 'text';
    }

    if ($name) {
        $defaults['name'] = $name;
        $defaults['id'] = $name;
    }
@endphp

@if ($control === 'select')
<select {{ $attributes->except(['type', 'value'])->merge($defaults) }}>{{ $slot }}</select>
@elseif ($control === 'textarea')
<textarea {{ $attributes->except(['type', 'value'])->merge($defaults) }}>{{ $attributes->get('value') ?? $slot }}</textarea>
@else
<input {{ $attributes->merge($defaults) }}>
@endif

{{-- Same markup the hand-written @error blocks used, so converting a field
     changes nothing about how its message looks. --}}
@if ($invalid)
<p class="text-ember-600 text-xs mt-1.5">{{ $errors->first($name) }}</p>
@endif

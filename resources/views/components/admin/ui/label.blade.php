@props(['for' => null, 'hint' => null])

{{--
    Field label for admin forms.

    <x-admin.ui.label for="room_number">Room Number</x-admin.ui.label>
    <x-admin.ui.label hint="(optional)">Notes</x-admin.ui.label>

    The class string was written out 15 times across the staff views, plus one
    copy that had drifted to a different size and tracking. Collecting it is
    worth less than the form controls below — the copies genuinely did agree —
    but it is what stops the sixteenth from being typed slightly differently.

    `hint` renders the muted "(optional)" style suffix some labels carry, so
    that stays consistent too rather than being hand-spanned per field.
--}}

<label @if($for) for="{{ $for }}" @endif
       {{ $attributes->merge(['class' => 'block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5']) }}>
    {{ $slot }}
    @if($hint)
        <span class="text-faint font-normal normal-case">{{ $hint }}</span>
    @endif
</label>

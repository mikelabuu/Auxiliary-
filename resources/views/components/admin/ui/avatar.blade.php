@props([
    /* 'sm' (32px round) | 'md' (34px, default) | 'lg' (72px, booking dossier) */
    'size' => 'md',
    /* 'suspended' | 'owner' | null — see .avatar-user--* in admin/07-ops-log-table */
    'state' => null,
])

{{--
    The person marker that sits beside a name in tables, lists and the profile
    chip.

    It draws a user glyph, not initials. Initials read as data without being
    any: in a guest table they collide constantly (two "DC"s on screen at once
    is the normal case, not the edge case), a one-word name yields a single
    letter, and a screen reader announced "D C" immediately before reading the
    full name out of the very next element. The name beside it identifies the
    row; this only has to mark it as a person, so it is aria-hidden.

    Sizes and states are props rather than utility overrides on the call site,
    because the glyph has to be resized with the box — the two pages that were
    hand-rolling this avatar had already drifted to their own colours.

    Usage: <x-admin.ui.avatar />
           <x-admin.ui.avatar size="lg" />
           <x-admin.ui.avatar :state="$user->is_suspended ? 'suspended' : null" />
--}}

@php
    $classes = 'avatar-user'
        . match ($size) {
            'sm'    => ' avatar-user--sm',
            'lg'    => ' avatar-user--lg',
            default => '',
        }
        . match ($state) {
            'suspended' => ' avatar-user--suspended',
            'owner'     => ' avatar-user--owner',
            default     => '',
        };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }} aria-hidden="true">
    <x-admin.ui.icon name="user" />
</span>

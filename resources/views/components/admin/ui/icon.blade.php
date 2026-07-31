@props([
    'name',
    /*
     * Kept only so the ~90 existing `stroke-width="2.5"` call sites keep
     * parsing. The set is filled now, so weight comes from the glyph, not a
     * stroke — declaring the prop absorbs the attribute instead of letting it
     * land on the <svg> as dead markup.
     */
    'strokeWidth' => null,
])

{{--
    Central icon registry for the admin console.

    Backed by Font Awesome Free 7 (icons CC BY 4.0), inlined as SVG from
    app/Support/AdminIcons — a generated file. To change or add a glyph, edit
    MAP in scripts/build-icon-registry.mjs and run `npm run icons:build`; never
    edit AdminIcons.php directly.

    Usage: <x-admin.ui.icon name="bed" class="w-4 h-4" />

    Icons address intent ("arrival", "trend-up"), not vendor names, so a glyph
    can be re-chosen in one place. Names are listed by AdminIcons::names().

    Inlined rather than <i class="fa-solid fa-bed"> because every call site sizes
    icons with Tailwind box utilities (w-4 h-4), which a webfont glyph ignores —
    and inlining costs no font request. The webfont is still loaded by the
    layouts, so bare `<i class="fa-…">` works in any view that wants it.
--}}

@php
    [$viewBox, $path] = \App\Support\AdminIcons::get($name);
@endphp

<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="{{ $viewBox }}" fill="currentColor"
     aria-hidden="true" focusable="false"><path d="{{ $path }}"/></svg>

{{-- Responsive image. Serves the AVIF/WebP derivatives built by
     `npm run images:build`, falling back to the original asset when a source
     has not been through the builder — so this is safe to adopt one view at a
     time.

     <x-img src="image/hostel-front.png" alt="…" sizes="100vw" fetchpriority="high" class="fh-hero-build" />

     Every attribute other than the props below lands on the <img>, so existing
     class hooks, data-prlx-* and ids keep working untouched.

     The <picture> carries `display: contents` because several call sites are
     absolutely positioned or size themselves with percentages (.fh-hero-wash,
     .fh-hero-build, .fd-hero-photo). `display: contents` removes the wrapper
     from the layout tree entirely, so the <img> keeps the exact containing
     block and box it had before it was wrapped. --}}
@props([
    'src',
    'alt' => '',
    'sizes' => '100vw',
])

@php
    $entry = \App\Support\ResponsiveImage::lookup($src);
    $fallback = $entry ? \App\Support\ResponsiveImage::fallbackSrc($entry) : null;

    // Intrinsic size prevents the layout shift these images used to cause.
    // Only supplied when the caller has not set its own dimensions.
    $dimensions = $entry && ! $attributes->has('width') && ! $attributes->has('height')
        ? ['width' => $entry['width'], 'height' => $entry['height']]
        : [];
@endphp

@if ($entry && $fallback)
    <picture style="display: contents">
        @foreach (config('images.formats', ['avif', 'webp']) as $format)
            @php $set = \App\Support\ResponsiveImage::srcset($entry, $format); @endphp
            @if ($set)
                <source type="image/{{ $format }}" srcset="{{ $set }}" sizes="{{ $sizes }}">
            @endif
        @endforeach
        <img src="{{ $fallback }}"
             srcset="{{ \App\Support\ResponsiveImage::srcset($entry, $entry['fallback']) }}"
             sizes="{{ $sizes }}"
             alt="{{ $alt }}"
             {{ $attributes->merge($dimensions) }}>
    </picture>
@else
    <img src="{{ asset($src) }}" alt="{{ $alt }}" {{ $attributes }}>
@endif

{{-- Right-hand photograph column for the single-purpose auth screens.
     Login carries its own variant inline.

     $title — string, escaped
     $lede  — one short editorial line, escaped

     Deliberately says nothing about how authentication works. An earlier
     version listed the emailed-code step and the exact failed-attempt limit;
     both are useful only to someone attacking the form, so they are gone. --}}
<aside class="fha-plate">
    {{-- Optimised variants live in public/image/auth (generated from the
         originals, which stay untouched for the rest of the site). The source
         hostel1.jpg is 9 MB at 2390px — far more than this panel can use. --}}
    <picture>
        <source type="image/webp"
                srcset="{{ asset('image/auth/hostel-900.webp') }} 900w,
                        {{ asset('image/auth/hostel-1600.webp') }} 1600w"
                sizes="(min-width: 1080px) 50vw, 100vw">
        <source type="image/jpeg"
                srcset="{{ asset('image/auth/hostel-900.jpg') }} 900w,
                        {{ asset('image/auth/hostel-1600.jpg') }} 1600w"
                sizes="(min-width: 1080px) 50vw, 100vw">
        <img src="{{ asset('image/auth/hostel-1600.jpg') }}" alt=""
             class="fha-plate-photo" aria-hidden="true"
             width="1600" height="1200" decoding="async" fetchpriority="low">
    </picture>

    <span class="fha-plate-scrim" aria-hidden="true"></span>

    <span class="fha-plate-eyebrow">Est. 1998 &middot; Science City of Muñoz</span>
    <h2 class="fha-plate-title">{{ $title }}</h2>
    <p class="fha-plate-lede">{{ $lede }}</p>

    @include('public.auth.partials.seal')
</aside>

{{-- Property line at the head of every auth board. Optimised variant: the
     original fh-mark.png is 512px / 269 KB for a mark that renders at 30px. --}}
<a href="{{ route('home') }}" class="fha-house">
    <picture>
        <source type="image/webp" srcset="{{ asset('image/auth/mark-90.webp') }}">
        <img src="{{ asset('image/auth/mark-90.png') }}" alt="" class="fha-house-mark"
             aria-hidden="true" width="90" height="90" decoding="async">
    </picture>
    <span>
        <span class="fha-house-name">Farmers Hostel</span>
        <span class="fha-house-unit">Auxiliary Services &middot; CLSU</span>
    </span>
</a>

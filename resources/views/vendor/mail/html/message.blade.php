<x-mail::layout>
@isset($preheader)
<x-slot:preheader>
{{ $preheader }}
</x-slot:preheader>
@endisset

{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')" logo-src="cid:farmers-hostel-logo@clsu">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>

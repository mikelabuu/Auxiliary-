@props([
    'title' => 'Reference Code Not Found',
    'messagePrefix' => 'We could not find a document matching',
    'messageSuffix' => 'Please check your reference code.',
    'ctaLabel' => 'Submit a New Document',
    'kioskUrl' => null,
])

@php
    $kioskHref = $kioskUrl ?? route('aais.client.kiosk');
@endphp

<div class="card tracker-notfound-card" x-show="notFound" x-transition>
    <svg class="tracker-notfound-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <h2 class="tracker-notfound-title">{{ $title }}</h2>
    <p class="tracker-notfound-copy">
        {{ $messagePrefix }} <strong x-text="code" class="font-mono tracker-notfound-ref"></strong>. {{ $messageSuffix }}
    </p>
    <a href="{{ $kioskHref }}" class="btn btn-outline btn-lg">{{ $ctaLabel }}</a>
</div>

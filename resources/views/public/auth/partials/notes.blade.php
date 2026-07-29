{{-- Feedback for every auth board. `status` and `message` are both used as
     success keys across the auth controllers, so both are rendered here. --}}
@if ($errors->any() || session('error'))
    <div class="fha-note fha-note--bad" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
            <path d="M12 9v4"/><path d="M12 17h.01"/>
        </svg>
        <div>
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            @if (session('error'))<p>{{ session('error') }}</p>@endif
        </div>
    </div>
@endif

@if (session('status') || session('message'))
    <div class="fha-note fha-note--ok" role="status">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        <div>
            @if (session('status'))<p>{{ session('status') }}</p>@endif
            @if (session('message'))<p>{{ session('message') }}</p>@endif
        </div>
    </div>
@endif

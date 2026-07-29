{{-- Caps Lock warning. Held in flow at zero opacity so switching it on cannot
     push the rest of the form down mid-password. Paired with an input carrying
     data-caps-for="{{ $id }}" (see partials/form-js). --}}
<p class="fha-caps" id="{{ $id }}" role="status">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
        <path d="M12 9v4"/><path d="M12 17h.01"/>
    </svg>
    Caps Lock is on
</p>

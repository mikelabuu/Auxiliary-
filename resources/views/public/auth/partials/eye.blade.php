{{-- Reveal-control icons. Exactly one is visible at a time; login.blade.php's
     reveal handler swaps them by [data-eye]. "off" shows while the password is
     masked (click to reveal); "on" shows while it is visible (click to hide). --}}
<svg data-eye="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
    <circle cx="12" cy="12" r="3"/>
</svg>
<svg data-eye="on" class="hidden-form" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M3 3l18 18"/>
    <path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/>
    <path d="M9.4 5.2A9.9 9.9 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1"/>
    <path d="M6.2 6.6A17.4 17.4 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 3.2-.5"/>
</svg>

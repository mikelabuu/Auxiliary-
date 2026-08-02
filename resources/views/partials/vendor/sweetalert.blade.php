{{-- SweetAlert (v1 `swal()`, not sweetalert2 - the staff consoles use that one).
     Every caller is behind a user event, and each guards with
     `typeof swal !== 'undefined'`, so defer is safe. --}}
@once
    <script src="{{ asset('vendor/sweetalert/sweetalert.min.js') }}" defer></script>
@endonce

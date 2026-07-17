{{-- Validation error summary — renders nothing when the error bag is empty. --}}
@if ($errors->any())
    <div {{ $attributes }}>
        <x-booking.ui.alert type="danger">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-booking.ui.alert>
    </div>
@endif

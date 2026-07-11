@props([
    'closeTarget',
    'submitLabel' => 'Save',
])

{{--
    Standard Cancel + primary-submit pair for modal form footers.

    <div class="flex gap-2.5 justify-end pt-2">
        <x-admin.ui.modal-footer close-target="addRoomModal" submit-label="Add Room" />
    </div>
--}}

<button type="button" data-modal-close="{{ $closeTarget }}" class="btn btn-outline">Cancel</button>
<button type="submit" {{ $attributes->merge(['class' => 'btn btn-primary']) }}>{{ $submitLabel }}</button>

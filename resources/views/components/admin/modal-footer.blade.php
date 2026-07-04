@props([
    'closeTarget',
    'submitLabel' => 'Save',
])

{{--
    The standard Cancel + primary-submit button pair repeated in every modal
    form footer. Caller keeps its own wrapping <div> (footer layout/border
    differs slightly between modals), this just avoids re-typing the two
    button class strings at every call site.

    <div class="flex gap-2.5 justify-end pt-2">
        <x-admin.modal-footer close-target="addRoomModal" submit-label="Add Room" />
    </div>
--}}

<button type="button" data-modal-close="{{ $closeTarget }}" class="text-sm font-medium text-stone-600 border border-stone-200 bg-white rounded-xl px-4 py-2.5 hover:bg-stone-50 transition-colors cursor-pointer">Cancel</button>
<button type="submit" {{ $attributes->merge(['class' => 'text-sm font-semibold text-white bg-gradient-to-b from-clsu-600 to-clsu-800 rounded-xl px-5 py-2.5 shadow-card hover:shadow-card-lg hover:from-clsu-700 hover:to-clsu-900 active:scale-[0.98] transition-all cursor-pointer']) }}>{{ $submitLabel }}</button>

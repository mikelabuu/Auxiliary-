{{--
    Inline validation errors, Fresh Meadow style.
    <x-frontdesk.flash /> — renders nothing when there is nothing to say.
    Session success/error flashes surface as toasts from the layout.
--}}

@if($errors->any())
    <div class="flash-note rounded-[var(--radius)] border border-ember-200 bg-ember-50 px-4 py-3">
        <div class="flex items-start gap-3">
            <x-admin.ui.icon name="block" class="mt-0.5 h-4 w-4 shrink-0 text-ember-600" stroke-width="2" />
            <ul class="space-y-1 text-sm font-medium text-ember-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

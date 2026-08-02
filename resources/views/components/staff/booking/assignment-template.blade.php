{{--
    Cloned by the page script once per picked room. Everything the JS reaches
    for is a `data-slot`; nothing here is rendered until a room is tapped.
--}}
<template id="assignment-card-template">
    <div class="assignment-card animate-pop space-y-4 rounded-2xl border border-emerald-deep/10 bg-white/80 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span data-slot="number" class="grid h-11 w-14 shrink-0 place-items-center rounded-xl bg-emerald-deep font-data text-sm font-bold text-cream shadow-sm"></span>
                <div class="min-w-0">
                    <p data-slot="type" class="truncate text-sm font-bold text-ink"></p>
                    <p data-slot="meta" class="text-[11px] font-medium text-stone-500"></p>
                </div>
            </div>
            <button type="button" data-slot="remove" class="press inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-ember-200 bg-ember-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ember-600 transition-colors hover:bg-ember-100">
                Remove
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <x-staff.booking.label class="tracking-[0.18em]">Guests in room</x-staff.booking.label>
                <x-staff.booking.stepper label="guests in room" size="sm">
                    <x-staff.booking.input type="number" data-slot="guests" min="1" value="1" align="center" required />
                </x-staff.booking.stepper>
                <p data-slot="capacity-hint" class="mt-1.5 text-[10px] font-medium text-stone-400"></p>
            </div>
            <div>
                <x-staff.booking.label class="tracking-[0.18em]">Seniors / PWD</x-staff.booking.label>
                <x-staff.booking.stepper label="seniors" size="sm" hint="Cannot exceed guests in this room">
                    <x-staff.booking.input type="number" data-slot="seniors" min="0" value="0" align="center" />
                </x-staff.booking.stepper>
            </div>
        </div>

        <span data-slot="hidden-inputs"></span>
    </div>
</template>

@props([
    'icon',
    'step',
    'title',
    'of' => 3,
    'delay' => 0,
])

{{--
    One numbered panel of the staff booking form ("Step 2 of 3 · Guest
    Details"). The `aside` slot holds whatever sits opposite the title —
    the nights badge on step 1, the assignment progress bar on step 3.
--}}
<div class="animate-in rounded-xl bg-cream-warm p-6 ring-1 ring-emerald-deep/10 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sm:p-7" style="animation-delay:{{ $delay }}ms">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-deep/5 text-brand-ink-deep ring-1 ring-emerald-deep/10">
                <x-admin.ui.icon :name="$icon" class="w-4 h-4" />
            </span>
            <div>
                <span class="block text-2xs font-black uppercase tracking-[0.2em] leading-none text-faint">Step {{ $step }} of {{ $of }}</span>
                <h4 class="mt-1 text-lg font-semibold leading-none text-ink">{{ $title }}</h4>
            </div>
        </div>
        {{ $aside ?? '' }}
    </div>

    {{ $slot }}
</div>

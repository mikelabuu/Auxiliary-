@extends('layouts.public.base')
@section('title', 'Senior / PWD Discount | Farmers Hostel')

{{-- swal() warns about missing/oversized ID uploads before submit. --}}
@push('vendor')
    @include('partials.vendor.sweetalert')
@endpush
@section('content')
@php
    $eligibleReservations = $booking->reservations->where('num_seniors', '>', 0);
    $nights = max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)));
@endphp

<div class="min-h-screen bg-canvas pt-28 pb-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald mb-3">
                <span class="h-px w-8 bg-emerald/50"></span> Verification
            </span>
            <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">Senior &amp; PWD <span class="italic text-gold">discount</span></h1>
            <p class="text-sm font-medium text-ink/55 mt-3 max-w-xl">Upload a valid Senior Citizen or PWD ID for each declared guest. Once our staff verifies them, a 20% discount per approved ID is applied to your bill.</p>
        </div>

        @if ($booking->num_seniors > 0)

            <!-- Booking context strip -->
            <div class="mb-8 grid grid-cols-2 sm:grid-cols-4 rounded-3xl bg-cream-warm ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] divide-x divide-emerald-deep/5 text-center overflow-hidden">
                <div class="px-3 py-4">
                    <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">Booking</span>
                    <span class="block text-sm font-extrabold text-ink mt-1 tabnum">#{{ $booking->id }}</span>
                </div>
                <div class="px-3 py-4">
                    <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">Stay</span>
                    <span class="block text-sm font-extrabold text-ink mt-1 tabnum">{{ $booking->check_in->format('M d') }} → {{ $booking->check_out->format('M d') }}</span>
                </div>
                <div class="px-3 py-4">
                    <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">Nights</span>
                    <span class="block text-sm font-extrabold text-ink mt-1 tabnum">{{ $nights }}</span>
                </div>
                <div class="px-3 py-4">
                    <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">Declared Seniors / PWD</span>
                    <span class="block text-sm font-extrabold text-emerald-deep mt-1 tabnum">{{ $booking->num_seniors }}</span>
                </div>
            </div>

            <!-- How it works -->
            <div class="mb-8 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="flex items-start gap-3 bg-cream-warm ring-1 ring-emerald-deep/5 rounded-2xl px-4 py-3.5">
                    <span class="w-7 h-7 rounded-full bg-emerald-deep text-cream font-display italic text-xs flex items-center justify-center shrink-0">1</span>
                    <p class="text-xs font-bold text-stone-700 leading-relaxed">Add a clear photo of each ID, one per declared senior/PWD guest.</p>
                </div>
                <div class="flex items-start gap-3 bg-cream-warm ring-1 ring-emerald-deep/5 rounded-2xl px-4 py-3.5">
                    <span class="w-7 h-7 rounded-full bg-emerald-deep text-cream font-display italic text-xs flex items-center justify-center shrink-0">2</span>
                    <p class="text-xs font-bold text-stone-700 leading-relaxed">Our staff reviews every document, usually within the day.</p>
                </div>
                <div class="flex items-start gap-3 bg-cream-warm ring-1 ring-emerald-deep/5 rounded-2xl px-4 py-3.5">
                    <span class="w-7 h-7 rounded-full bg-gold text-ink font-display italic text-xs flex items-center justify-center shrink-0">3</span>
                    <p class="text-xs font-bold text-stone-700 leading-relaxed">20% off per approved ID is deducted before you pay.</p>
                </div>
            </div>

            <form action="{{ route('discount.store', $booking->id) }}" method="POST" enctype="multipart/form-data" id="discountForm" class="space-y-6">
                @csrf

                @foreach ($eligibleReservations as $reservation)
                    <div class="upload-card bg-cream-warm rounded-3xl p-6 sm:p-7 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]"
                         data-reservation="{{ $reservation->id }}" data-max="{{ $reservation->num_seniors }}">

                        <div class="flex flex-wrap items-center justify-between gap-3 mb-5 pb-4 border-b border-emerald-deep/10">
                            <div class="flex items-center gap-3">
                                <span class="grid h-11 w-14 shrink-0 place-items-center rounded-xl bg-emerald-deep font-data text-sm font-bold text-cream shadow-sm">{{ $reservation->room_number }}</span>
                                <div>
                                    <p class="text-sm font-bold text-ink capitalize">{{ $reservation->room_type }} Room</p>
                                    <p class="text-[11px] font-semibold text-stone-500">{{ $reservation->num_seniors }} declared senior{{ $reservation->num_seniors > 1 ? 's' : '' }} / PWD</p>
                                </div>
                            </div>
                            <span class="slot-counter inline-flex items-center gap-1.5 rounded-full border border-gold/40 bg-gold-soft/30 px-3.5 py-1.5 text-[11px] font-bold text-ink/75 tabnum">
                                <i class="fa-solid fa-id-badge text-[14px] text-gold"></i>
                                <span class="slot-count">0</span> of {{ $reservation->num_seniors }} ID{{ $reservation->num_seniors > 1 ? 's' : '' }} added
                            </span>
                        </div>

                        {{-- The real input keeps the backend contract; the dropzone drives it --}}
                        <input type="file" name="discount_files[{{ $reservation->id }}][]" class="discount-file-input sr-only"
                               multiple accept="image/jpeg,image/png,image/jpg" tabindex="-1" aria-hidden="true">

                        <div class="dropzone group relative flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-emerald-deep/20 bg-white/60 px-6 py-8 text-center transition-[color,background-color,border-color,box-shadow] duration-200 cursor-pointer hover:border-gold hover:bg-gold-soft/15"
                             role="button" tabindex="0" aria-label="Upload verification IDs for room {{ $reservation->room_number }}">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10 transition-transform duration-200 group-hover:-translate-y-0.5">
                                <i class="fa-solid fa-file-arrow-up text-[24px]"></i>
                            </span>
                            <p class="text-sm font-bold text-ink">Drop ID photos here <span class="font-medium text-stone-400">or</span> <span class="gold-underline text-emerald-deep">browse files</span></p>
                            <p class="text-[11px] font-medium text-stone-400">JPG or PNG · up to 2MB each · max {{ $reservation->num_seniors }} for this room</p>
                        </div>

                        <div class="preview-grid mt-4 hidden grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"></div>
                    </div>
                @endforeach

                <!-- Legibility reminder -->
                <div class="flex items-start gap-2.5 rounded-2xl border border-gold/40 bg-gold-soft/25 px-5 py-4 text-xs font-bold text-ink/75 leading-relaxed">
                    <i class="fa-solid fa-lightbulb text-[18px] text-gold shrink-0"></i>
                    Make sure the ID number, name, and photo are clearly readable. Blurred or cropped documents are usually rejected and will slow down your discount.
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <a href="{{ route('booking.show', $booking->id) }}" class="press w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-full border border-emerald-deep/20 bg-cream px-6 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-deep transition-colors hover:bg-emerald-deep hover:text-cream !no-underline">
                        Cancel &amp; go back
                    </a>
                    <button type="submit" id="submitDiscount" disabled
                            class="press focus-ring w-full sm:w-auto inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-emerald-deep px-8 py-3.5 text-[12px] font-semibold uppercase tracking-[0.2em] text-cream cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_25%,transparent)] disabled:opacity-50 disabled:pointer-events-none">
                        <i class="fa-solid fa-circle-check text-[18px]"></i>
                        Submit for review
                    </button>
                </div>
                <p id="submitHint" class="text-right text-[11px] font-semibold text-stone-400 -mt-2">Add at least one ID for every room above to submit.</p>
            </form>

        @else
            <div class="bg-cream-warm rounded-3xl ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] px-8 py-14 text-center">
                <span class="grid h-14 w-14 mx-auto place-items-center rounded-2xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                    <i class="fa-solid fa-id-badge text-[28px]"></i>
                </span>
                <h3 class="font-display text-2xl text-ink mt-5">No seniors declared</h3>
                <p class="text-sm font-medium text-ink/55 mt-2 max-w-sm mx-auto">This booking has no declared Senior Citizen or PWD guests, so the 20% discount doesn't apply here.</p>
                <a href="{{ route('booking.show', $booking->id) }}" class="press mt-7 inline-flex items-center justify-center gap-2 rounded-full bg-emerald-deep px-7 py-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-cream transition-colors hover:bg-emerald !no-underline">
                    <i class="fa-solid fa-arrow-left text-[16px]"></i>
                    Back to booking summary
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const MAX_BYTES = 2 * 1024 * 1024;
    const OK_TYPES = ['image/jpeg', 'image/png', 'image/jpg'];
    const form = document.getElementById('discountForm');
    if (!form) return;
    const submitBtn = document.getElementById('submitDiscount');
    const submitHint = document.getElementById('submitHint');

    function warn(title, text) {
        typeof swal !== 'undefined' ? swal(title, text, 'warning') : alert(title + '\n' + text);
    }

    function refreshSubmitState() {
        const cards = Array.from(document.querySelectorAll('.upload-card'));
        const ready = cards.every(card => card.querySelector('.discount-file-input').files.length > 0);
        submitBtn.disabled = !ready;
        submitHint.classList.toggle('hidden', ready);
    }

    document.querySelectorAll('.upload-card').forEach(card => {
        const input = card.querySelector('.discount-file-input');
        const dropzone = card.querySelector('.dropzone');
        const previewGrid = card.querySelector('.preview-grid');
        const slotCount = card.querySelector('.slot-count');
        const counterPill = card.querySelector('.slot-counter');
        const max = parseInt(card.dataset.max, 10) || 1;

        // Sync the working list into the real input via DataTransfer so the
        // form still posts plain discount_files[reservation][] uploads.
        let files = [];
        function syncInput() {
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            input.files = dt.files;

            slotCount.textContent = files.length;
            counterPill.classList.toggle('border-gold/40', files.length < max);
            counterPill.classList.toggle('bg-gold-soft/30', files.length < max);
            counterPill.classList.toggle('border-emerald/40', files.length >= max);
            counterPill.classList.toggle('bg-emerald/10', files.length >= max);
            previewGrid.classList.toggle('hidden', files.length === 0);
            previewGrid.classList.toggle('grid', files.length > 0);
            renderPreviews();
            refreshSubmitState();
        }

        function renderPreviews() {
            previewGrid.innerHTML = '';
            files.forEach((file, i) => {
                const url = URL.createObjectURL(file);
                const tile = document.createElement('div');
                tile.className = 'animate-pop relative overflow-hidden rounded-2xl ring-1 ring-emerald-deep/10 bg-white';
                tile.innerHTML = `
                    <img src="${url}" alt="ID preview ${i + 1}" class="h-28 w-full object-cover" onload="URL.revokeObjectURL(this.src)">
                    <button type="button" data-remove="${i}" aria-label="Remove this ID"
                            class="absolute right-1.5 top-1.5 grid h-6 w-6 place-items-center rounded-full bg-ink/70 text-cream backdrop-blur transition-colors hover:bg-ember-600 cursor-pointer">
                        <i class="fa-solid fa-xmark text-[14px]"></i>
                    </button>
                    <span class="absolute bottom-1.5 left-1.5 rounded-full bg-ink/60 px-2 py-0.5 text-[10px] font-bold text-cream backdrop-blur">${(file.size / 1024 / 1024).toFixed(1)} MB</span>`;
                previewGrid.appendChild(tile);
            });
        }

        function addFiles(list) {
            for (const file of list) {
                if (!OK_TYPES.includes(file.type)) {
                    warn('Unsupported file', `"${file.name}" isn't a JPG or PNG image.`);
                    continue;
                }
                if (file.size > MAX_BYTES) {
                    warn('File too large', `"${file.name}" is over the 2MB limit.`);
                    continue;
                }
                if (files.some(f => f.name === file.name && f.size === file.size)) continue;
                if (files.length >= max) {
                    warn('Upload limit reached', `You can add up to ${max} ID${max > 1 ? 's' : ''} for this room, one per declared senior/PWD guest.`);
                    break;
                }
                files.push(file);
            }
            syncInput();
        }

        previewGrid.addEventListener('click', e => {
            const btn = e.target.closest('[data-remove]');
            if (!btn) return;
            files.splice(parseInt(btn.dataset.remove, 10), 1);
            syncInput();
        });

        dropzone.addEventListener('click', () => input.click());
        dropzone.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
        });
        input.addEventListener('change', () => {
            addFiles(Array.from(input.files));
        });

        ['dragenter', 'dragover'].forEach(ev => dropzone.addEventListener(ev, e => {
            e.preventDefault();
            dropzone.classList.add('border-gold', 'bg-gold-soft/20');
        }));
        ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => {
            e.preventDefault();
            dropzone.classList.remove('border-gold', 'bg-gold-soft/20');
        }));
        dropzone.addEventListener('drop', e => addFiles(Array.from(e.dataTransfer.files)));
    });

    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
            </svg>
            Uploading documents…`;
    });

    refreshSubmitState();
});
</script>
@endpush

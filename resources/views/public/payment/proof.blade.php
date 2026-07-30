@extends('layouts.public.base')
@section('title', 'Upload Proof of Payment | Farmers Hostel')
@section('content')
@php
    $nights = max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)));
    // Demo payee details. Swap for the hostel's real account before go-live.
    $payee = [
        'gcash' => ['label' => 'GCash', 'name' => 'CLSU Farmers Hostel', 'number' => '0917 000 0000'],
        'bank_transfer' => ['label' => 'Land Bank of the Philippines', 'name' => 'CLSU Farmers Hostel', 'number' => '0000 1111 2222'],
    ];
@endphp

<div class="min-h-screen bg-canvas pt-28 pb-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald mb-3">
                <span class="h-px w-8 bg-emerald/50"></span> Proof of Payment
            </span>
            <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">Upload your <span class="italic text-gold">receipt</span></h1>
            <p class="text-sm font-medium text-ink/55 mt-3 max-w-xl">Send the payment first, then attach the receipt here. Your booking is confirmed once our front desk has verified it against the transfer.</p>
        </div>

        @if ($errors->any())
            <div class="animate-shake mb-8 rounded-2xl border border-ember-300/50 bg-ember-50 px-5 py-4 text-xs font-bold text-ember-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="flex items-start gap-2"><span class="material-icons text-[16px] mt-0.5 shrink-0">error_outline</span>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Booking context strip -->
        <div class="mb-8 grid grid-cols-2 sm:grid-cols-4 rounded-3xl bg-cream-warm ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] divide-x divide-emerald-deep/5 text-center overflow-hidden">
            <div class="px-3 py-4">
                <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Booking</span>
                <span class="block text-sm font-extrabold text-ink mt-1 tabnum">#{{ $booking->id }}</span>
            </div>
            <div class="px-3 py-4">
                <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Stay</span>
                <span class="block text-sm font-extrabold text-ink mt-1 tabnum">{{ $booking->check_in->format('M d') }} → {{ $booking->check_out->format('M d') }}</span>
            </div>
            <div class="px-3 py-4">
                <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Nights</span>
                <span class="block text-sm font-extrabold text-ink mt-1 tabnum">{{ $nights }}</span>
            </div>
            <div class="px-3 py-4">
                <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Amount due</span>
                <span class="block text-sm font-extrabold text-emerald-deep mt-1 tabnum">₱{{ number_format($amount, 2) }}</span>
            </div>
        </div>

        <form action="{{ route('bookings.pay.proof.store', $booking->id) }}" method="POST" enctype="multipart/form-data" id="proofForm" class="space-y-6">
            @csrf

            <!-- Step 1 — where to send it -->
            <div class="bg-cream-warm rounded-3xl p-6 sm:p-7 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-emerald-deep/10">
                    <span class="w-8 h-8 rounded-full bg-emerald-deep text-cream font-display italic text-sm flex items-center justify-center shrink-0">1</span>
                    <div>
                        <h2 class="text-sm font-bold text-ink">Send the payment</h2>
                        <p class="text-[11px] font-semibold text-stone-500">Choose how you paid — the account details update to match.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($methods as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="proof_method" value="{{ $value }}" class="peer sr-only method-radio"
                                   {{ old('proof_method', 'gcash') === $value ? 'checked' : '' }} required>
                            <span class="flex items-center gap-3 rounded-2xl border-2 border-emerald-deep/15 bg-white/60 px-4 py-3.5 transition-[color,background-color,border-color,box-shadow] peer-checked:border-gold peer-checked:bg-gold-soft/20 peer-focus-visible:ring-2 peer-focus-visible:ring-gold">
                                <span class="material-icons text-[20px] text-emerald-deep">{{ $value === 'gcash' ? 'smartphone' : 'account_balance' }}</span>
                                <span class="text-sm font-bold text-ink">{{ $label }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @foreach ($payee as $key => $details)
                    <div class="payee-panel mt-4 rounded-2xl border border-emerald-deep/10 bg-white/60 px-5 py-4 {{ old('proof_method', 'gcash') === $key ? '' : 'hidden' }}" data-method="{{ $key }}">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center sm:text-left">
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Send to</span>
                                <span class="block text-sm font-extrabold text-ink mt-1">{{ $details['label'] }}</span>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Account name</span>
                                <span class="block text-sm font-extrabold text-ink mt-1">{{ $details['name'] }}</span>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Account number</span>
                                <span class="block text-sm font-extrabold text-emerald-deep mt-1 font-data tabnum">{{ $details['number'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Step 2 — the reference number -->
            <div class="bg-cream-warm rounded-3xl p-6 sm:p-7 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-emerald-deep/10">
                    <span class="w-8 h-8 rounded-full bg-emerald-deep text-cream font-display italic text-sm flex items-center justify-center shrink-0">2</span>
                    <div>
                        <h2 class="text-sm font-bold text-ink">Reference number</h2>
                        <p class="text-[11px] font-semibold text-stone-500">The number printed on your own receipt — staff match it against the transfer.</p>
                    </div>
                </div>

                <label for="proof_reference" class="block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 mb-1.5">Reference / Transaction Number</label>
                <input id="proof_reference" name="proof_reference" type="text" maxlength="60" required
                       value="{{ old('proof_reference') }}" placeholder="e.g. 0123 4567 8901"
                       class="w-full px-4 py-3 rounded-2xl border border-emerald-deep/15 bg-white/70 text-ink text-sm font-bold tracking-wide font-data focus:bg-white focus:border-gold focus:ring-2 focus:ring-gold/30 outline-none transition-[color,background-color,border-color,box-shadow]">
            </div>

            <!-- Step 3 — the receipt image -->
            <div class="bg-cream-warm rounded-3xl p-6 sm:p-7 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-emerald-deep/10">
                    <span class="w-8 h-8 rounded-full bg-gold text-ink font-display italic text-sm flex items-center justify-center shrink-0">3</span>
                    <div>
                        <h2 class="text-sm font-bold text-ink">Attach the receipt</h2>
                        <p class="text-[11px] font-semibold text-stone-500">A screenshot or photo showing the amount, reference and date.</p>
                    </div>
                </div>

                <input type="file" name="proof" id="proofInput" class="sr-only" accept="image/jpeg,image/png,image/jpg" required tabindex="-1" aria-hidden="true">

                <div id="proofDropzone"
                     class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-emerald-deep/20 bg-white/60 px-6 py-10 text-center transition-[color,background-color,border-color,box-shadow] duration-200 cursor-pointer hover:border-gold hover:bg-gold-soft/15"
                     role="button" tabindex="0" aria-label="Upload proof of payment">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10 transition-transform duration-200 group-hover:-translate-y-0.5">
                        <span class="material-icons text-[24px]">upload_file</span>
                    </span>
                    <p class="text-sm font-bold text-ink">Drop your receipt here <span class="font-medium text-stone-400">or</span> <span class="gold-underline text-emerald-deep">browse files</span></p>
                    <p class="text-[11px] font-medium text-stone-400">JPG or PNG · up to 4MB</p>
                </div>

                <div id="proofPreview" class="mt-4 hidden">
                    <div class="relative inline-block rounded-2xl overflow-hidden ring-1 ring-emerald-deep/10 bg-white">
                        <img id="proofPreviewImg" src="" alt="Receipt preview" class="max-h-72 w-auto block">
                        <button type="button" id="proofRemove"
                                class="absolute top-2 right-2 grid h-8 w-8 place-items-center rounded-full bg-ember-600 text-white shadow-lg cursor-pointer hover:bg-ember-700 transition-colors"
                                aria-label="Remove receipt">
                            <span class="material-icons text-[16px]">close</span>
                        </button>
                    </div>
                    <p id="proofFileName" class="mt-2 text-[11px] font-bold text-stone-500"></p>
                </div>
            </div>

            <div class="flex items-start gap-2.5 rounded-2xl border border-gold/40 bg-gold-soft/25 px-5 py-4 text-xs font-bold text-ink/75 leading-relaxed">
                <span class="material-icons text-[18px] text-gold shrink-0">tips_and_updates</span>
                Make sure the amount, reference number and date are readable. Blurred or cropped receipts are usually rejected and will delay your confirmation.
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('bookings.pay', $booking->id) }}" class="press w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-full border border-emerald-deep/20 bg-cream px-6 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-deep transition-colors hover:bg-emerald-deep hover:text-cream !no-underline">
                    Back
                </a>
                <button type="submit" id="submitProof" disabled
                        class="press focus-ring w-full sm:w-auto inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-emerald-deep px-8 py-3.5 text-[12px] font-semibold uppercase tracking-[0.2em] text-cream cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_25%,transparent)] disabled:opacity-50 disabled:pointer-events-none">
                    <span class="material-icons text-[18px]">verified</span>
                    Submit for verification
                </button>
            </div>
            <p id="proofHint" class="text-right text-[11px] font-semibold text-stone-400 -mt-2">Attach your receipt and enter its reference number to submit.</p>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input     = document.getElementById('proofInput');
    const dropzone  = document.getElementById('proofDropzone');
    const preview   = document.getElementById('proofPreview');
    const previewImg= document.getElementById('proofPreviewImg');
    const fileName  = document.getElementById('proofFileName');
    const removeBtn = document.getElementById('proofRemove');
    const reference = document.getElementById('proof_reference');
    const submit    = document.getElementById('submitProof');
    const hint      = document.getElementById('proofHint');
    const form      = document.getElementById('proofForm');

    // Swap the payee panel to match the selected method.
    document.querySelectorAll('.method-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.payee-panel').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.dataset.method !== radio.value);
            });
        });
    });

    function syncSubmit() {
        const ready = input.files.length > 0 && reference.value.trim().length > 0;
        submit.disabled = !ready;
        hint.classList.toggle('hidden', ready);
    }

    function showPreview(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            fileName.textContent = file.name;
            preview.classList.remove('hidden');
            dropzone.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });

    ['dragenter', 'dragover'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropzone.classList.add('border-gold', 'bg-gold-soft/15');
        });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropzone.classList.remove('border-gold', 'bg-gold-soft/15');
        });
    });
    dropzone.addEventListener('drop', function (e) {
        const file = e.dataTransfer.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        // DataTransfer is the only way to write back into a file input.
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
        syncSubmit();
    });

    input.addEventListener('change', function () {
        if (input.files.length > 0) showPreview(input.files[0]);
        syncSubmit();
    });

    removeBtn.addEventListener('click', function () {
        input.value = '';
        preview.classList.add('hidden');
        dropzone.classList.remove('hidden');
        syncSubmit();
    });

    reference.addEventListener('input', syncSubmit);

    form.addEventListener('submit', function () {
        submit.disabled = true;
        submit.innerHTML = '<span class="material-icons text-[18px] animate-spin">progress_activity</span> Submitting…';
    });

    syncSubmit();
});
</script>
@endpush
@endsection

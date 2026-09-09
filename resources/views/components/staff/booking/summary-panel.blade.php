@props([
    /*
     * How far down the panel pins, as a CSS length. One number, because the
     * height cap below is derived from it — passing the offset as a Tailwind
     * class and the cap as a second literal is how the two drift apart.
     */
    'stickyOffset' => '6rem',
])

{{--
    The sticky live summary column. Every figure in here is written by the page
    script as rooms are picked; the server renders only the empty shell.
--}}
{{-- Never taller than the space it is pinned into.

    Once rooms are picked this panel runs past 720px. Pinned at the top of a
    614px viewport — what a 1366x768 screen reports at 125% display scaling —
    its lower half sat below the fold with no way to reach it: a stuck element
    does not move, and scrolling the page moves the form beside it instead. The
    total and the confirm button are in that lower half.

    Capping to the viewport and letting the panel scroll itself works at every
    height. Below lg the column is stacked and none of this applies. --}}
<div class="animate-in lg:sticky lg:col-span-4 lg:top-[var(--sticky-offset)] lg:max-h-[calc(100dvh-var(--sticky-offset)-1.5rem)] lg:overflow-y-auto"
     style="--sticky-offset: {{ $stickyOffset }}; animation-delay:200ms">
    <div class="card card-accent card-overflow-hidden">
        <div class="card-header">
            <h3 class="card-title">
                <x-admin.ui.icon name="receipt" class="w-[18px] h-[18px]" />
                Booking Summary
            </h3>
            <span id="summary-nights" class="hidden chip chip-green"></span>
        </div>

        <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
            {{-- Dates + guests --}}
            <div>
                <div class="flex items-center justify-between gap-3">
                    <span class="kv-label" style="margin:0;">Stay dates</span>
                    <span id="summary-dates" class="text-sm font-semibold text-ink">—</span>
                </div>
                <div id="summary-guests" class="mt-1.5 text-xs font-medium text-muted text-right">—</div>
            </div>

            {{-- Room lines --}}
            <div id="summary-rooms" class="space-y-2.5 border-t border-[color:var(--color-border)] pt-4 text-sm">
                <p class="text-faint">Pick rooms on the board to build the summary.</p>
            </div>

            {{-- Subtotal --}}
            <div class="flex items-center justify-between border-t border-[color:var(--color-border)] pt-4 text-sm">
                <span class="text-muted">Subtotal</span>
                <span id="summary-subtotal" class="font-semibold tabnum text-ink">₱0</span>
            </div>

            <label for="extra_mattress" class="flex cursor-pointer items-start gap-2.5 text-sm text-ink">
                <input type="checkbox" name="extra_mattress" id="extra_mattress" value="1" data-price="{{ \App\Support\BookingCharges::MATTRESS_PRICE }}" class="row-check" @checked(old('extra_mattress'))>
                <span>Extra mattress · ₱500<small class="block text-xs text-muted">One per booking. Charged once for the stay; room guest limits still apply.</small></span>
            </label>
            <div class="flex justify-between text-sm" id="summary-mattress-row" hidden><span>Extra mattress × 1</span><span id="summary-mattress" class="tabnum">₱500</span></div>

            {{-- Senior / PWD flag --}}
            <label for="has_senior_pwd" class="flex cursor-pointer items-center gap-2.5 text-sm text-ink">
                <input type="checkbox" name="has_senior_pwd" id="has_senior_pwd" class="row-check">
                Apply Senior / PWD discount
            </label>

            {{-- Discount --}}
            <div class="form-group">
                <label class="form-label" for="discount_amount">Automatic discount (₱)</label>
                <input type="number" id="discount_amount" value="0.00" readonly aria-describedby="discount-hint" class="form-input tabnum">
                <p id="discount-hint" class="text-2xs text-muted">20% of each eligible guest’s share of the room rate (room rate ÷ capacity). Verify original IDs and set the Senior / PWD count in each room.</p>
            </div>
        </div>

        {{-- Total payable — emerald accent footer --}}
        <div style="padding:22px 26px;background:#0f8f51;color:#fff;">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-2xs font-bold uppercase tracking-[0.24em]" style="color:rgba(255,255,255,.72);">Total payable</p>
                    <p class="mt-1.5 font-display text-3xl font-extrabold leading-none">
                        <span id="summary-total" class="anim-number tabnum" style="display:inline-block;overflow:hidden;"><span>₱0</span></span>
                    </p>
                </div>
                <span class="text-2xs font-bold uppercase tracking-[0.16em]" style="color:rgba(255,255,255,.7);">Manual · Paid</span>
            </div>

            <button type="submit" id="submit-booking" class="btn btn-center mt-5" style="width:100%;background:#fff;color:var(--color-g-800);font-weight:700;">
                <x-admin.ui.icon name="check-circle" class="w-4 h-4" />
                Create Booking
            </button>
        </div>
    </div>
</div>

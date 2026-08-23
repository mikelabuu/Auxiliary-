document.addEventListener('DOMContentLoaded', function () {
  const bookingForm = document.getElementById('bookingForm');
  const check_in = document.getElementById('check_in');
  const check_out = document.getElementById('check_out');
  const check_in_hidden = document.getElementById('check_in_hidden');
  const check_out_hidden = document.getElementById('check_out_hidden');
  const bookingFormAlert = document.getElementById('bookingFormAlert');
  const expectedGuestsInput = document.getElementById('expected_guests');
  const maxSeniorsLabel = document.getElementById('maxSeniorsLabel');
  const num_seniors_hidden = document.getElementById('num_seniors');
  const reservationContainer = document.getElementById('reservationBlocks');
  const tpl = document.getElementById('reservationBlockTemplate');
  const bookingStateHost = bookingForm;
  const initialRoomType = bookingStateHost?.dataset.initialRoomType || window.INITIAL_ROOM_TYPE || '';
  const initialGuests = parseInt(bookingStateHost?.dataset.initialGuests || window.INITIAL_GUESTS || '1', 10);
  // Set before anything can touch the field: Blade renders old('expected_guests')
  // into it, and a value already there beats the one in the URL.
  const hasOldGuestCount = (parseInt(document.getElementById('expected_guests')?.value, 10) || 1) > 1;

  if (!reservationContainer || !tpl) return; // Only run on checkout page

  function formatPrice(num) { return '₱' + Number(num).toLocaleString(); }

  // How many of each style are free for the chosen dates, and what each style
  // is called. Filled by updateTypeAvailability; read by oversubscribed() when
  // deciding whether the guest has asked for more rooms than exist, and by the
  // room picker for its ceilings and its suggestion. Declared up here because
  // the picker sits above the availability code and `const` would otherwise be
  // in its temporal dead zone.
  const availableByType = {};
  const typeTitles = {};

  // Icon markup for the fragments below that are built as HTML strings. The
  // public layout no longer loads the Font Awesome webfont, so an `<i
  // class="fa-solid …">` written here would draw nothing; checkout renders the
  // inline SVGs into #bookingIcons and this reads them back, which keeps
  // App\Support\AdminIcons the single place a glyph is defined.
  const iconBank = document.getElementById('bookingIcons');
  const icon = (name) => iconBank?.content?.querySelector(`[data-icon="${name}"]`)?.innerHTML || '';

  // Animated currency count-up for summary totals
  function animateCurrency(el, from, to, ms = 380) {
    if (!el) return;
    if (from === to || !window.requestAnimationFrame) { el.textContent = formatPrice(to); return; }
    const start = performance.now();
    function frame(now) {
      const t = Math.min(1, (now - start) / ms);
      const eased = 1 - Math.pow(1 - t, 3);
      el.textContent = formatPrice(Math.round(from + (to - from) * eased));
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }
  let lastSummaryTotal = 0;

  // Generic ± steppers: any .btn-step inside a .stepper wrapper adjusts the
  // wrapped input and fires 'input' so existing clamps/summary logic reacts.
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-step');
    if (!btn) return;
    const wrap = btn.closest('.stepper');
    const input = wrap ? wrap.querySelector('input') : null;
    if (!input) return;
    const step = parseInt(btn.dataset.step, 10) || 0;
    const min = input.min !== '' ? parseInt(input.min, 10) : 0;
    const max = input.max !== '' ? parseInt(input.max, 10) : Infinity;
    let v = (parseInt(input.value, 10) || 0) + step;
    v = Math.min(max, Math.max(min, v));
    input.value = v;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  });

  // Every count on this page has a ceiling — a room's beds, the guests in it,
  // the 40 a booking may hold — and the clamp above enforced all of them
  // silently. Pressing + on a full room did nothing at all, which reads as a
  // broken button rather than a limit. Spending a bound now disables that
  // button, so the stepper shows where it stops before it is pressed; the
  // readout and note beside it say what the bound is and what to do instead.
  function syncStepperButtons(input) {
    const wrap = input && input.closest ? input.closest('.stepper') : null;
    if (!wrap) return;
    const min = input.min !== '' ? parseInt(input.min, 10) : -Infinity;
    const max = input.max !== '' ? parseInt(input.max, 10) : Infinity;
    const v = parseInt(input.value, 10);
    wrap.querySelectorAll('.btn-step').forEach(function (btn) {
      const step = parseInt(btn.dataset.step, 10) || 0;
      // A blank field (a room with no style chosen yet) has no position to be
      // at either end of, so neither button is spent.
      btn.disabled = !isNaN(v) && (step < 0 ? v <= min : v >= max);
    });
  }

  // Bubbling, not capturing: the per-field handlers that re-derive `max` from
  // the chosen room style are bound on the inputs themselves, so this has to
  // run after them or it would read the previous room's capacity.
  document.addEventListener('input', function (e) {
    const t = e.target;
    if (t && t.closest && t.closest('.stepper')) syncStepperButtons(t);
  });

  // Update nights stay badge on top dates widget
  function updateNightsStayBadge() {
    const badge = document.getElementById('nights_duration_badge');
    if (!badge) return;
    const d1Val = check_in?.value;
    const d2Val = check_out?.value;
    if (d1Val && d2Val) {
      const d1 = new Date(d1Val);
      const d2 = new Date(d2Val);
      const nights = Math.max(1, Math.round((d2 - d1) / 86400000));
      if (nights > 0) {
        badge.textContent = `${nights} Night${nights > 1 ? 's' : ''} Stay`;
        badge.classList.remove('hidden');
        return;
      }
    }
    badge.classList.add('hidden');
  }

  // Sync flatpickr dates to hidden inputs for form submission
  function syncDates() {
    if (check_in_hidden && check_in) check_in_hidden.value = check_in.value;
    if (check_out_hidden && check_out) check_out_hidden.value = check_out.value;
    updateNightsStayBadge();
    generateBookingSummary();
  }

  function setMinDates() {
    if (!check_in || !check_out) return;
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2,'0');
    const dd = String(today.getDate()).padStart(2,'0');
    const min = `${yyyy}-${mm}-${dd}`;
    check_in.setAttribute('min', min);
    check_out.setAttribute('min', min);
  }
  setMinDates();

  function updateCapsDisplay() {
    // Raising the party size used to leave every room where it was, so the
    // guest met a form that knew it was short and made them fix it by hand.
    reseatRooms();
    // Each room's ceiling is partly a fact about the party, so it moves when
    // the party does. Without this the + button on a room stayed disabled after
    // the guest raised the total, and the room it would have filled looked
    // broken.
    refreshRoomCaps();
    generateBookingSummary();
  }

  /** Re-apply every room's guest ceiling. See applyCapacityLimits(). */
  function refreshRoomCaps() {
    document.querySelectorAll('.reservation-block').forEach(function (b) {
      const input = b.querySelector('.res-num-guests');
      if (!input) return;
      const beds = parseInt(b.querySelector('.res-beds')?.value, 10) || 0;
      const leftToSeat = unseatedExcluding(b);
      const cap = beds ? Math.min(beds, leftToSeat) : leftToSeat;
      if (cap > 0) input.max = cap; else input.removeAttribute('max');
      syncStepperButtons(input);
    });
  }

  /**
   * Write the total without announcing it.
   *
   * Dispatching 'input' here would re-enter reseatRooms(), which is entitled
   * to shuffle guests between untouched rooms — so a total nudged up by one
   * room could visibly renumber another. Nothing needs re-seating anyway: the
   * rooms are already what the new total is being set from.
   */
  function setTotalQuietly(n) {
    if (!expectedGuestsInput || String(n) === expectedGuestsInput.value) return;
    expectedGuestsInput.value = n;
  }

  // Typing can leave the field below its floor (the ± buttons cannot). Settle
  // it when the guest is done rather than on each keystroke, which would turn
  // "12" into "22" as the first digit was clamped out from under them.
  expectedGuestsInput?.addEventListener('change', function () {
    const floor = parseInt(expectedGuestsInput.min, 10) || 1;
    if ((parseInt(expectedGuestsInput.value, 10) || 0) < floor) {
      expectedGuestsInput.value = floor;
      expectedGuestsInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
    // Enter fires change without moving focus, so the guard in
    // updateProgressRail would still be holding off. Settle it here.
    if (clampRoomsToParty()) generateBookingSummary();
  });

  expectedGuestsInput?.addEventListener('input', updateCapsDisplay);
  document.getElementById('request_discount')?.addEventListener('change', generateBookingSummary);

  function bindMealInputs(block) {
    const guestInput = block.querySelector('.res-num-guests');
    const mealInputs = block.querySelectorAll('.meal-qty');
    function enforce() {
      const maxGuests = parseInt(guestInput.value, 10) || 0;
      let totalMeals = 0;
      mealInputs.forEach(input => { totalMeals += parseInt(input.value, 10) || 0; });
      if (totalMeals > maxGuests) {
        let excess = totalMeals - maxGuests;
        const active = document.activeElement;
        if (active && active.classList.contains('meal-qty')) {
          let currentVal = parseInt(active.value, 10) || 0;
          active.value = Math.max(0, currentVal - excess);
        } else {
          const last = mealInputs[mealInputs.length - 1];
          let currentVal = parseInt(last.value, 10) || 0;
          last.value = Math.max(0, currentVal - excess);
        }
      }
    }
    mealInputs.forEach(input => input.addEventListener('input', enforce));
    guestInput.addEventListener('input', enforce);
    enforce();
  }

  let nextBlockIndex = 0;
  // The set of room numbers claimed across blocks retired with the picker:
  // a guest never names a room now, so two blocks cannot collide over one.
  // BookingController::store draws each from a pool it shrinks as it assigns.

  window.addReservationBlock = function(prefill = {}) {
    const index = nextBlockIndex++;
    const html = tpl.innerHTML.replace(/__INDEX__/g, index);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    const block = wrapper.firstElementChild;
    block.dataset.index = index;
    block.classList.add('animate-pop'); // brief pop-in so added blocks don't teleport

    const roomTypeSelect = block.querySelector('.room-type-select');
    const resBeds = block.querySelector('.res-beds');
    const resPrice = block.querySelector('.res-price');
    const resPriceHidden = block.querySelector('.res-price-hidden');
    const numSeniorsInput = block.querySelector('.res-num-seniors');
    const btnRemove = block.querySelector('.btn-remove-block');
    const numGuestsInput = block.querySelector('.res-num-guests');
    const capacityHint = block.querySelector('.capacity-hint');
    const typeCards = block.querySelectorAll('.type-card');
    const guestsReadout = block.querySelector('.guests-readout');
    const guestsPips = block.querySelector('.guests-pips');
    const guestsNote = block.querySelector('.guests-note');
    const guestsAutoTag = block.querySelector('[data-auto-tag]');
    const seniorsReadout = block.querySelector('.seniors-readout');
    const seniorsNote = block.querySelector('.seniors-note');

    // Part of the style recap beside the price, so it states the style's
    // ceiling and nothing else. It used to read "Sleeps 1–3 guests in this
    // room" — a range, where the only number that matters is the top of it,
    // and the running count now has its own readout under the stepper.
    function refreshCapacityHint() {
      if (!capacityHint) return;
      // Deliberately empty. It read "Sleeps up to 2 guests" beside a room card
      // that already says "sleeps 2", above a readout showing 2 / 2 and two
      // bed pips. The rate chip next to it is the one fact in that row not
      // stated anywhere else, so it now has the row to itself.
      capacityHint.textContent = '';
    }

    // Push the chosen room style's capacity onto the number inputs so the ±
    // buttons stop AT the limit rather than overshooting and being yanked
    // back, and so the browser's own validation knows the bounds too.
    function applyCapacityLimits() {
      const beds = parseInt(resBeds.value, 10) || 0;

      if (numGuestsInput) {
        numGuestsInput.min = 1;

        // Two ceilings, and the room is held to the lower of them: the beds it
        // has, and the guests there are left to seat. The second one is new.
        // Without it a room could be filled past the party — the page answered
        // that by quietly raising the party to match, so topping up a room
        // invented a guest. Capping here means + simply stops when everyone is
        // seated, which is the same way the bed limit already behaved and needs
        // no explaining.
        const leftToSeat = unseatedExcluding(block);
        const cap = beds ? Math.min(beds, leftToSeat) : leftToSeat;

        if (cap > 0) {
          numGuestsInput.max = cap;
          if ((parseInt(numGuestsInput.value, 10) || 0) > cap) numGuestsInput.value = cap;
        } else {
          numGuestsInput.removeAttribute('max');
        }
        // The placeholder still advertises the room, not the party: it is a
        // fact about the style being offered.
        if (beds) numGuestsInput.placeholder = '1–' + beds;
        if ((parseInt(numGuestsInput.value, 10) || 0) < 1) numGuestsInput.value = 1;
      }

      // Seniors can never outnumber the guests actually in the room, which is
      // a tighter bound than the bed count once the room is under-filled.
      if (numSeniorsInput) {
        const guests = parseInt(numGuestsInput?.value, 10) || 0;
        const cap = beds ? Math.min(beds, guests || beds) : guests;
        numSeniorsInput.min = 0;
        if (cap) {
          numSeniorsInput.max = cap;
          if ((parseInt(numSeniorsInput.value, 10) || 0) > cap) numSeniorsInput.value = cap;
        } else {
          numSeniorsInput.removeAttribute('max');
        }
      }

      // Breakfasts are optional, but there can never be more of them than
      // there are guests to eat them.
      block.querySelectorAll('.meal-qty').forEach(function (input) {
        input.min = 0;
        input.max = parseInt(numGuestsInput?.value, 10) || 0;
      });

      refreshCounts();
    }

    // How many pips are worth drawing. Beyond this a row of dots stops being
    // countable at a glance, which was the only reason to draw it, and the
    // "4 / 12" readout carries the whole message on its own.
    const MAX_PIPS = 10;

    // The state of both counts, said three ways: the "2 / 3" readout, one pip
    // per bed, and a line naming the next move. Derived from the same two
    // numbers the submit handler checks, so the room cannot look bookable here
    // and be refused there.
    function refreshCounts() {
      const beds = parseInt(resBeds.value, 10) || 0;
      const guests = parseInt(numGuestsInput?.value, 10) || 0;
      const seniors = parseInt(numSeniorsInput?.value, 10) || 0;

      if (guestsReadout) {
        guestsReadout.textContent = beds ? guests + ' / ' + beds : '—';
        guestsReadout.dataset.state = !beds ? 'idle'
          : guests > beds ? 'over'
          : guests === beds ? 'full'
          : 'ok';
      }

      if (guestsPips) {
        if (!beds || beds > MAX_PIPS) {
          if (guestsPips.childElementCount) guestsPips.textContent = '';
        } else {
          if (guestsPips.childElementCount !== beds) {
            guestsPips.innerHTML = new Array(beds).fill('<span class="cap-pip"></span>').join('');
          }
          Array.prototype.forEach.call(guestsPips.children, function (pip, i) {
            pip.classList.toggle('is-filled', i < guests);
          });
        }
      }

      // Written for a number the form filled in, not one the guest is being
      // asked for: it reports where this room stands and leaves "and what
      // about the rest of the party" to the meter, which is watching the whole
      // booking rather than this one room.
      if (guestsNote) {
        // Silent unless there is something to do about it.
        //
        // A full room used to announce "Full — this style sleeps 2" under a
        // readout already showing 2 / 2, beside two pips already both filled,
        // and a + already disabled. Four ways of saying one thing, on every
        // room, and the note was the only one of the four a reader had to stop
        // and parse. It speaks now only while the room can still take somebody
        // — the one state where a sentence changes what the guest would do.
        if (!beds) {
          guestsNote.textContent = 'Pick a room style above and we’ll fill this in for you.';
        } else if (guests >= beds) {
          guestsNote.textContent = '';
        } else {
          const spare = beds - guests;
          guestsNote.textContent = spare + (spare > 1 ? ' beds spare' : ' bed spare') + ' in this room.';
        }
      }

      // The party size in step 1 is the guest's number; this one is ours until
      // they say otherwise. Shown only once a style is picked (before that the
      // note underneath is already saying the field is waiting on one), and
      // dropped for good the moment they move somebody by hand — from then on
      // the number is theirs and the tag would be untrue.
      if (guestsAutoTag) {
        guestsAutoTag.hidden = !beds || block.dataset.guestsTouched === '1';
      }

      // Seniors are bounded by the people actually in the room, not by the
      // beds — an under-filled triple cannot hold three seniors.
      const seniorCap = beds ? Math.min(beds, guests || beds) : guests;

      if (seniorsReadout) {
        seniorsReadout.textContent = seniorCap ? seniors + ' / ' + seniorCap : '—';
        seniorsReadout.dataset.state = !seniorCap ? 'idle' : seniors >= seniorCap ? 'full' : 'ok';
      }

      if (seniorsNote) {
        if (!seniorCap || seniors === 0) {
          seniorsNote.textContent = 'For the 20% Senior / PWD discount. Leave at 0 if none.';
        } else if (seniors >= seniorCap) {
          seniorsNote.textContent = seniorCap === 1
            ? 'The one guest in this room is Senior / PWD.'
            : 'Everyone in this room is Senior / PWD.';
        } else {
          seniorsNote.textContent = seniors + ' of the ' + guests + ' guests here — each needs an original ID at check-in.';
        }
      }

      if (numGuestsInput) syncStepperButtons(numGuestsInput);
      if (numSeniorsInput) syncStepperButtons(numSeniorsInput);
    }

    // Visual room-type cards drive the hidden select
    function syncTypeCards() {
      typeCards.forEach(card => {
        const on = card.dataset.typeValue === roomTypeSelect.value;
        card.classList.toggle('selected', on);
        const check = card.querySelector('.type-card-check');
        if (check) { check.classList.toggle('hidden', !on); check.classList.toggle('grid', on); }
      });
    }
    typeCards.forEach(card => {
      card.addEventListener('click', () => {
        // Don't let the guest pick a room style that's fully booked for the dates.
        if (card.dataset.full === '1') {
          showFormError('All ' + (card.dataset.typeTitle || 'rooms of this type') + ' are booked or unavailable for these dates. Try other dates or another room style.');
          return;
        }
        if (roomTypeSelect.value === card.dataset.typeValue) return;
        roomTypeSelect.value = card.dataset.typeValue;
        roomTypeSelect.dispatchEvent(new Event('change'));
      });
    });

    if (numGuestsInput) {
      numGuestsInput.addEventListener('input', () => {
        applyCapacityLimits();
        generateBookingSummary();
      });
    }

    if (numSeniorsInput) {
      numSeniorsInput.addEventListener('input', () => {
        applyCapacityLimits();
        generateBookingSummary();
      });
    }

    if (prefill.room_type) {
      roomTypeSelect.value = prefill.room_type;
      const opt = roomTypeSelect.selectedOptions[0];
      if (opt) {
        resBeds.value = opt.dataset.beds || '';
        resPriceHidden.value = opt.dataset.price || '';
        resPrice.value = formatPrice(opt.dataset.price || '0');
        // Arriving from "3 guests, Triple Room" on the landing page, this room
        // opens holding three. It used to open holding one.
        seatRemaining(block);
        refreshCapacityHint();
        applyCapacityLimits();
        syncTypeCards();
      }
    }

    roomTypeSelect.addEventListener('change', () => {
      const opt = roomTypeSelect.selectedOptions[0];
      resBeds.value = (opt && opt.dataset.beds) ? opt.dataset.beds : '';
      resPriceHidden.value = (opt && opt.dataset.price) ? opt.dataset.price : '';
      resPrice.value = formatPrice(resPriceHidden.value || 0);
      // The whole point of choosing a style: it seats people. Skipped once the
      // guest has set this room by hand, so re-picking a style keeps their split.
      if (block.dataset.guestsTouched !== '1') seatRemaining(block);
      refreshCapacityHint();
      applyCapacityLimits();
      syncTypeCards();
      updateAggregateHiddenInputs();
      // Re-scope the date pickers to the style now being shopped for. Without
      // this the calendar keeps answering a property-wide question: a hold on
      // room 112 leaves 21 of 22 rooms free, so the night reads wide open even
      // though it is one of only two doubles.
      if (typeof window.refreshCalendarAvailability === 'function') {
        window.refreshCalendarAvailability();
      }
      generateBookingSummary();
    });

    numSeniorsInput.addEventListener('input', () => {
      const cap = parseInt(resBeds.value) || 0;
      let v = parseInt(numSeniorsInput.value) || 0;
      if (v > cap) numSeniorsInput.value = cap;
      if (v < 0) numSeniorsInput.value = 0;
      updateAggregateHiddenInputs();
      generateBookingSummary();
    });

    btnRemove.addEventListener('click', () => {
      // Opacity-only exit (reduced-motion safe). Bookkeeping waits for the
      // node to leave the DOM — the summary/aggregate selectors would still
      // count the fading block's inputs otherwise.
      block.style.transition = 'opacity 0.15s ease';
      block.style.opacity = '0';
      block.style.pointerEvents = 'none';
      setTimeout(() => {
        block.remove();
        updateAggregateHiddenInputs();
        // The removed room's guests are unseated again; spread them over what
        // is left rather than leaving the booking quietly short.
        reseatRooms();
        generateBookingSummary();
        // Demand dropped with the block — a night blocked only because this
        // block wanted a second room of that style is bookable again.
        if (typeof window.refreshCalendarAvailability === 'function') {
          window.refreshCalendarAvailability();
        }
        document.querySelectorAll('.btn-remove-block').forEach(b => b.style.display = document.querySelectorAll('.reservation-block').length > 1 ? 'inline-block' : 'none');
      }, 150);
    });

    // Wire up custom plus/minus meal increment buttons
    block.querySelectorAll('.btn-minus-meal').forEach(btn => {
      btn.addEventListener('click', () => {
        const input = btn.nextElementSibling;
        let val = parseInt(input.value) || 0;
        if (val > 0) {
          val--;
          input.value = val;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    });

    block.querySelectorAll('.btn-plus-meal').forEach(btn => {
      btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        const guestInput = block.querySelector('.res-num-guests');
        const maxGuests = parseInt(guestInput.value) || 0;
        
        // Count total meals currently selected
        const mealInputs = block.querySelectorAll('.meal-qty');
        let totalMeals = 0;
        mealInputs.forEach(inp => totalMeals += parseInt(inp.value) || 0);

        if (totalMeals < maxGuests) {
          let val = parseInt(input.value) || 0;
          val++;
          input.value = val;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
          showFormError('Total breakfast selections cannot exceed the number of guests allocated to this room.');
        }
      });
    });

    if (block.querySelectorAll('.meal-qty').length) bindMealInputs(block);

    // A block with no style chosen never reached applyCapacityLimits(), so its
    // readouts would have sat at their server-rendered placeholders until the
    // guest touched something.
    refreshCounts();

    reservationContainer.appendChild(block);
    if (typeof Alpine !== 'undefined') Alpine.initTree(block);
    
    document.querySelectorAll('.btn-remove-block').forEach(b => b.style.display = document.querySelectorAll('.reservation-block').length > 1 ? 'inline-block' : 'none');
    
    // Re-read availability when a room arrives already knowing its dates and
    // its style.
    //
    // This used to call `btnCheck.click()`. There is no `btnCheck` — the
    // variable is not declared anywhere in this file, and has not been for as
    // long as the history goes back. It never threw because it was never
    // reached: blocks were added empty and typed afterwards, so `roomTypeSelect
    // .value` was always '' at this point. Rooms are added already typed now,
    // which walked straight into it and threw a ReferenceError on every add.
    if (check_in && check_out && check_in.value && check_out.value && roomTypeSelect.value) {
      setTimeout(function () { updateTypeAvailability(); }, 100);
    }
    generateBookingSummary();
    return block;
  };

  function updateAggregateHiddenInputs() {
    let totalSeniors = 0;
    document.querySelectorAll('.res-num-seniors').forEach(inp => { totalSeniors += parseInt(inp.value) || 0; });
    if (num_seniors_hidden) num_seniors_hidden.value = totalSeniors;
  }

  // ── Seating the party ────────────────────────────────────────────
  //
  // The guest used to state their party size in step 2 and then state it
  // again, in pieces, in step 3 — and the form only told them the two had to
  // agree by refusing to submit. That is bookkeeping, and bookkeeping is the
  // form's job: picking a room style now seats as many of the still-unseated
  // guests in it as it holds, and the count below the stepper is a number
  // already handled rather than a second sum to keep.
  //
  // The stepper stays because the split is sometimes a real choice — two
  // people who want a triple to themselves — and a room the guest has set by
  // hand is never rewritten underneath them afterwards. That is what
  // `guestsTouched` records: intent, set only from a real click or keystroke.

  /** Guests not seated in any room but this one. */
  function unseatedExcluding(block) {
    const expected = parseInt(expectedGuestsInput?.value, 10) || 0;
    let elsewhere = 0;
    document.querySelectorAll('.reservation-block').forEach(b => {
      if (b === block) return;
      elsewhere += parseInt(b.querySelector('.res-num-guests')?.value, 10) || 0;
    });
    return Math.max(0, expected - elsewhere);
  }

  /**
   * True while the form is seating guests itself.
   *
   * The writes below fire 'input' so the summary and the clamps react to them,
   * which makes them indistinguishable from the guest editing the field — and
   * the difference matters, because one is the form doing its job and the
   * other is the guest overriding it. Every programmatic write is wrapped in
   * this flag and dispatchEvent is synchronous, so the listener that records
   * intent always sees it set. (`event.isTrusted` would answer the same
   * question, but silently: if it were ever wrong, a guest's deliberate split
   * would be quietly overwritten on the next change and nothing would say so.)
   */
  let seatingGuests = false;

  function writeGuestCount(input, value) {
    if (String(value) === input.value) return;
    input.value = value;
    seatingGuests = true;
    try {
      input.dispatchEvent(new Event('input', { bubbles: true }));
    } finally {
      seatingGuests = false;
    }
  }

  /**
   * Seat what is left into `block`, capped by its beds.
   *
   * Never below 1: a room with nobody in it is not a room the server accepts,
   * so a block added after everyone already has a bed holds one guest and the
   * meter says the total is now short by one — which is true, and visible,
   * rather than an empty field that fails on submit.
   */
  function seatRemaining(block) {
    const input = block.querySelector('.res-num-guests');
    const beds = parseInt(block.querySelector('.res-beds')?.value, 10) || 0;
    if (!input || !beds) return;
    writeGuestCount(input, Math.max(1, Math.min(beds, unseatedExcluding(block))));
  }

  /**
   * Re-seat every room the guest has not set by hand — after the party size
   * changes, or after a room is removed and its guests need somewhere to go.
   * Rooms fill in order, which is the order they are on screen.
   */
  /**
   * True while reseatRooms() is part-way through.
   *
   * Each room it writes fires 'input', which redraws everything — including
   * the step that raises the total to meet the rooms. Half-way through a pass
   * the rooms are a mix of old and new numbers and briefly total more than
   * either state does, so that step would seize on a figure that exists for
   * one line of a loop: lowering a party of 5 to 1 across a 2-bed and a 3-bed
   * room settled on a total of 4.
   */
  let reseating = false;

  function reseatRooms() {
    const blocks = Array.from(document.querySelectorAll('.reservation-block'));
    let remaining = parseInt(expectedGuestsInput?.value, 10) || 0;

    // Hand-set rooms are spoken for before anything is shared out.
    blocks.forEach(b => {
      if (b.dataset.guestsTouched === '1') {
        remaining -= parseInt(b.querySelector('.res-num-guests')?.value, 10) || 0;
      }
    });

    // The rooms this pass will still have to fill after the current one. Each
    // needs a guest of its own, so the current room cannot take the last seat
    // and leave them empty: filling greedily put 5 guests into a 2-bed and a
    // 3-bed room as 2 + 3, but 2 guests into the same pair as 2 + 1 — three
    // people in a booking for two, which then had to be reported as an error.
    const fillable = blocks.filter(b =>
      b.dataset.guestsTouched !== '1'
      && (parseInt(b.querySelector('.res-beds')?.value, 10) || 0) > 0
      && b.querySelector('.res-num-guests'));

    reseating = true;
    try {
      fillable.forEach((b, i) => {
        const beds = parseInt(b.querySelector('.res-beds').value, 10) || 0;
        const reserved = fillable.length - 1 - i;
        const take = Math.max(1, Math.min(beds, remaining - reserved));
        remaining -= take;
        writeGuestCount(b.querySelector('.res-num-guests'), take);
      });
    } finally {
      reseating = false;
    }
  }

  /**
   * Raise the total to what the rooms actually hold, if they hold more.
   *
   * One room cannot hold nobody, so two rooms cannot hold one guest: past a
   * point the total is simply contradicted by the rooms, and the rooms are the
   * ones holding people. Returns whether it changed anything.
   */
  /**
   * Push guests back out of over-full rooms until the rooms hold no more than
   * the party.
   *
   * This used to run the other way round: if the rooms held more people than
   * the total, the total was quietly raised to match. Topping a room up from 3
   * to 4 therefore turned a party of five into a party of six without a word,
   * and the page then cheerfully reported "All 6 guests have a bed" — a number
   * the guest never typed, priced and planned for as though they had.
   *
   * The party size is the fact now, and the rooms are how it is seated, so the
   * correction goes the only direction that cannot invent a person. In practice
   * this rarely fires at all: the steppers and the typed-input clamp below stop
   * a room going past what is unseated in the first place. It stays as the
   * backstop for the paths that write counts without going through them —
   * restoring old input, or a room whose style shrinks under a guest already
   * seated in it.
   *
   * Returns whether it changed anything.
   */
  function clampRoomsToParty() {
    if (reseating) return false;
    const r = readiness();
    if (!r.blocks.length || r.assigned <= r.expected) return false;

    let over = r.assigned - r.expected;
    // Take from the last room first: it is the one most recently added or
    // raised, so it is the one the guest is least surprised to see give way.
    const blocks = Array.from(document.querySelectorAll('.reservation-block')).reverse();
    for (const b of blocks) {
      if (over <= 0) break;
      const input = b.querySelector('.res-num-guests');
      if (!input) continue;
      const have = parseInt(input.value, 10) || 0;
      // Never below one — an empty room is not a room the server accepts.
      const give = Math.min(over, Math.max(0, have - 1));
      if (give > 0) {
        writeGuestCount(input, have - give);
        over -= give;
      }
    }
    return true;
  }

  // Intent, recorded from anything that is not the form seating guests itself
  // — a press on the ± buttons, or typing straight into the field.
  //
  // The "Filled for you" tag comes down here rather than in refreshCounts(),
  // which the field's own listener has already run by the time these delegated
  // ones see the event: read from there the flag is still a keystroke behind,
  // and the tag would survive the first press that disproves it.
  function markGuestsTouched(block) {
    if (!block) return;
    block.dataset.guestsTouched = '1';
    const tag = block.querySelector('[data-auto-tag]');
    if (tag) tag.hidden = true;
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-step');
    if (!btn || seatingGuests) return;
    const input = btn.closest('.stepper')?.querySelector('input');
    if (!input || !input.classList.contains('res-num-guests')) return;
    markGuestsTouched(btn.closest('.reservation-block'));
  });

  document.addEventListener('input', function (e) {
    if (seatingGuests) return;
    const t = e.target;
    if (!t || !t.classList || !t.classList.contains('res-num-guests')) return;
    markGuestsTouched(t.closest('.reservation-block'));
  });

  // ── Real-time availability (Reverb) ──────────────────────────────────────
  // The admin panel broadcasts `RoomStatusChanged` on the public `rooms`
  // channel whenever any room's status changes (maintenance, cleaning, a new
  // booking, a check-in). We listen for it and re-count what is open.
  //
  // This used to re-fetch a tile grid per block and warn the guest when the
  // room they had picked was closed under them. There is no pick to lose any
  // more — the server assigns rooms at commit — so what is left to keep true
  // is the per-style count on the type cards and the dates the calendar
  // offers. Both are counts, and a count going down is not something a guest
  // needs interrupting for.
  let recheckTimer = null;
  function recheckAllBlocksAvailability() {
    clearTimeout(recheckTimer);
    recheckTimer = setTimeout(() => {
      updateTypeAvailability();
      if (typeof window.refreshCalendarAvailability === 'function') {
        window.refreshCalendarAvailability();
      }
    }, 400);
  }

  // Subscribe once. Guarded because Echo only exists if the Reverb assets loaded.
  // Room status changes AND bookings both affect availability, so listen to both.
  if (window.Echo) {
    window.Echo.channel('rooms').listen('.RoomStatusChanged', recheckAllBlocksAvailability);
    window.Echo.channel('bookings').listen('.BookingChanged', recheckAllBlocksAvailability);
  }

  // ── Readiness: one pass over the form, rendered three ways ───────
  //
  // The progress rail at the top of the page, the allocation meter inside
  // the Rooms card, and the blocker line above Confirm are three answers
  // to one question: is this bookable yet, and if not, what is missing?
  // They are computed together so they cannot drift apart, and the order
  // of `blocker` deliberately matches the order the submit handler below
  // rejects things in — so what the guest is told beforehand is what
  // would actually have stopped them.
  function readiness() {
    const datesDone = !!(check_in?.value && check_out?.value);

    const firstName = bookingForm?.querySelector('[name="first_name"]');
    const lastName  = bookingForm?.querySelector('[name="last_name"]');
    const phone     = bookingForm?.querySelector('[name="guest_phone"]');
    // The reference person is three required fields, and the rail has to count
    // them: marking Details done with the endorsement half-filled would tick a
    // step the browser is about to refuse to submit.
    const refName    = bookingForm?.querySelector('[name="referred_by"]');
    const refPhone   = bookingForm?.querySelector('[name="referred_by_phone"]');
    const refPurpose = bookingForm?.querySelector('[name="referred_by_purpose"]');
    const detailsDone = !!(
      firstName?.value.trim() && lastName?.value.trim() && phone?.value.trim()
      && refName?.value.trim() && refPhone?.value.trim() && refPurpose?.value.trim()
    );

    const blocks = Array.from(document.querySelectorAll('.reservation-block'));
    const expected = parseInt(expectedGuestsInput?.value, 10) || 0;

    let assigned = 0;
    let untyped = null;      // first block with no room style chosen
    let overfilled = null;   // first block with more guests than beds
    blocks.forEach(b => {
      // Was "no room number picked". The style is what a guest chooses now —
      // the number is assigned by the server when the booking commits.
      if (!untyped && !b.querySelector('.room-type-select')?.value) untyped = b;
      const beds = parseInt(b.querySelector('.res-beds')?.value, 10) || 0;
      const inRoom = parseInt(b.querySelector('.res-num-guests')?.value, 10) || 0;
      if (!overfilled && beds > 0 && inRoom > beds) overfilled = b;
      assigned += inRoom;
    });

    const oversold = oversubscribed();
    const balanced = blocks.length > 0 && assigned === expected;
    const roomsDone = blocks.length > 0 && !untyped && !overfilled && !oversold && balanced;

    // The terms box is `required`, so the browser stops the submit before our
    // own handler ever runs. Tracked here anyway: the blocker line's whole job
    // is to name the thing that would stop you, and silently failing to
    // mention it would put the guest in front of a browser tooltip instead.
    const termsBox = document.getElementById('accept_terms');
    const termsDone = !termsBox || termsBox.checked;

    return { datesDone, detailsDone, blocks, expected, assigned, untyped, overfilled, oversold, balanced, roomsDone, termsDone, firstName };
  }

  // ── Progress rail (checkout header) ──────────────────────────────
  function setStep(key, done) {
    document.querySelector(`[data-progress-step="${key}"]`)?.classList.toggle('done', !!done);
  }

  // ── Allocation meter (inside the Rooms card) ─────────────────────
  function updateAllocationMeter(r) {
    const meter = document.getElementById('allocationMeter');
    if (!meter) return;

    const fill = document.getElementById('allocMeterFill');
    const hint = document.getElementById('allocMeterHint');
    const assignedEl = document.getElementById('allocAssigned');
    const expectedEl = document.getElementById('allocExpected');
    const pipsEl = document.getElementById('allocPips');

    if (assignedEl) assignedEl.textContent = r.assigned;
    if (expectedEl) expectedEl.textContent = r.expected;

    // One pip per guest, filled as they are seated. The same trick the
    // per-room bed pips use, at the scale of the whole party — a count you can
    // see beats one you have to read. Above a dozen the dots stop being
    // countable at a glance, so the figures carry it alone.
    const countEl = document.getElementById('allocCount');
    const usePips = r.expected > 0 && r.expected <= 12;

    if (pipsEl) {
      if (usePips) {
        let html = '';
        for (let i = 0; i < r.expected; i++) {
          html += '<i class="alloc-pip' + (i < r.assigned ? ' is-on' : '') + '"></i>';
        }
        pipsEl.innerHTML = html;
        pipsEl.hidden = false;
      } else {
        pipsEl.innerHTML = '';
        pipsEl.hidden = true;
      }
    }
    // The figures are the fallback for a party too big to count as dots, not a
    // second copy of the dots.
    if (countEl) countEl.hidden = usePips;

    // Three states, not four. "Too many guests assigned" is gone because it can
    // no longer happen: the steppers stop at the party size, so the rooms
    // cannot outrun it. What is left is the honest progression — none seated,
    // some seated, all seated — and each says the one thing to do next.
    let state = 'empty';
    let msg = 'Pick a room style below and we’ll seat your guests in it.';

    if (r.blocks.length && r.expected > 0) {
      if (r.assigned === 0) {
        state = 'empty';
      } else if (r.assigned < r.expected) {
        const short = r.expected - r.assigned;
        state = 'under';
        // The old copy always offered "or raise a room's count", including when
        // every room was full and that button was disabled. Offer it only when
        // a room really can take someone — which happens when the guest has set
        // a room by hand and left a bed spare, since reseating never overrides
        // a count they chose. Otherwise adding a room is the only move, and
        // saying so is shorter and always true.
        const spare = Array.from(document.querySelectorAll('.reservation-block')).some(function (b) {
          const beds = parseInt(b.querySelector('.res-beds')?.value, 10) || 0;
          const have = parseInt(b.querySelector('.res-num-guests')?.value, 10) || 0;
          return beds > 0 && have > 0 && have < beds;
        });
        msg = '<b>' + short + (short > 1 ? ' guests' : ' guest') + '</b> still ' +
              (short > 1 ? 'need a bed' : 'needs a bed') +
              (spare ? ' — raise a room with a spare bed, or add another.' : '.');
      } else {
        state = 'balanced';
        // Everyone is seated, and the picker may well have gone quiet: a room
        // needs a guest in it, so the party size is also the room ceiling.
        // That was being enforced in silence — every + greyed out with nothing
        // saying why, and this line congratulating the guest while it happened.
        // If the cap is what stopped them, the cap is what to say.
        const capped = r.blocks.length >= r.expected;
        msg = (r.expected === 1
          ? 'Your guest has a bed.'
          : 'All ' + r.expected + ' guests have a bed.');
        if (capped) {
          msg += r.expected === 1
            ? ' A room needs someone in it, so one guest is one room — <b>add a guest</b> in step 1 if you want a second.'
            : ' A room needs someone in it, so ' + r.expected + ' guests can hold at most <b>' + r.expected + ' rooms</b>.';
        }
      }
    }

    meter.dataset.state = state;
    if (hint) hint.innerHTML = msg;
    if (fill) {
      // Capped at 100% so an over-assignment still reads as "full and then
      // some" rather than overflowing the track.
      const pct = r.expected > 0 ? Math.min(100, (r.assigned / r.expected) * 100) : 0;
      fill.style.transform = 'scaleX(' + (pct / 100) + ')';
    }
  }

  // ── Blocker line (above the Confirm button) ──────────────────────
  //
  // Clicking it goes to the card that raised it, on the same scroll-and-
  // flash contract the progress rail and the summary rows already use.
  const blockerLine = document.getElementById('bookingBlocker');
  const followBlocker = function () {
    if (blockerLine.dataset.target) jumpToCardById(blockerLine.dataset.target);
  };
  blockerLine?.addEventListener('click', followBlocker);
  blockerLine?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); followBlocker(); }
  });

  function updateBlockerLine(r) {
    const line = document.getElementById('bookingBlocker');
    const text = document.getElementById('bookingBlockerText');
    if (!line || !text) return;

    let msg = null;
    // Which card the guest has to reach to clear this. The line names a
    // step ("in step 3") and, until now, offered no way of getting to it —
    // from the Confirm button that card is a screenful away, behind the
    // whole personal-details form.
    let goTo = null;

    if (!r.datesDone) {
      msg = 'Start by choosing your stay dates.';
      goTo = 'stepCardDates';
    } else if (!r.blocks.length) {
      msg = 'Choose your rooms in step 3.';
      goTo = 'stepCardRooms';
    } else if (!r.detailsDone) {
      msg = 'Fill in your name and contact number.';
      goTo = 'stepCardDetails';
    } else if (r.overfilled) {
      msg = 'One room has more guests than it sleeps.';
      goTo = 'stepCardRooms';
    } else if (r.untyped) {
      msg = 'Choose a room style for each room you are booking.';
      goTo = 'stepCardRooms';
    } else if (r.oversold) {
      goTo = 'stepCardRooms';
      msg = r.oversold.available === 0
        ? `No ${r.oversold.title} rooms are free for these dates.`
        : `Only ${r.oversold.available} ${r.oversold.title} left for these dates — you have asked for ${r.oversold.wanted}.`;
    } else if (!r.balanced) {
      goTo = 'stepCardRooms';
      const diff = Math.abs(r.expected - r.assigned);
      // Same words as the status line above the rooms — "needs a bed" — so the
      // two places that can raise this are plainly talking about one thing.
      // The over-assigned branch is a backstop: the steppers stop at the party
      // size, so reaching it means a count was written by some path that did
      // not go through them.
      msg = r.assigned < r.expected
        ? `${diff} guest${diff > 1 ? 's' : ''} still ${diff > 1 ? 'need' : 'needs'} a bed.`
        : `Your rooms hold ${diff} more than you are bringing.`;
    } else if (!r.termsDone) {
      // Last, because it is the last thing standing between the guest and a
      // booking once everything else is filled in.
      msg = 'Tick the booking terms below to confirm.';
    }

    if (msg) {
      line.removeAttribute('data-ready');
      text.textContent = msg;
    } else {
      line.setAttribute('data-ready', '');
      text.textContent = 'Everything checks out — you can confirm.';
    }

    // Nothing to jump to once it reads as ready.
    if (goTo && msg) {
      line.dataset.target = goTo;
      line.setAttribute('role', 'button');
      line.setAttribute('tabindex', '0');
    } else {
      delete line.dataset.target;
      line.removeAttribute('role');
      line.removeAttribute('tabindex');
    }
  }

  // ── Party size ───────────────────────────────────────────────────
  //
  // Lives in step 1 with the dates: how many people are coming is a fact about
  // the trip, not a room setting, and it is what the room suggestion in step 3
  // is computed from. It sat inside the Rooms card for a while, immediately
  // above the styles it fills — but that put it between a room picker and a
  // seating meter, wearing the same stepper as the per-room count below it,
  // and nothing distinguished the one number the guest owns from the two the
  // form works out. The status line in step 3 carries the running commentary,
  // so this note says the only thing the field itself needs to: who counts.
  function updateTotalGuestsCount(r) {
    if (!expectedGuestsInput) return;

    const readout = document.getElementById('totalGuestsReadout');
    const note = document.getElementById('totalGuestsNote');
    const summary = document.getElementById('guestSummary');
    const max = parseInt(expectedGuestsInput.max, 10) || 0;

    // One guest per room is the floor: the rooms already on the page each hold
    // at least one person, so the party cannot be smaller than the number of
    // rooms without emptying one.
    const floor = Math.max(1, r.blocks.length);
    expectedGuestsInput.min = floor;

    if (readout) {
      // Just the number. It used to read "5 / 40", and 40 is the largest
      // booking the server will take — a fact of no interest to somebody
      // booking for five, printed next to their number all the same. It shows
      // up only on the approach, where it starts to explain why + is slowing
      // to a stop.
      const near = max && r.expected >= max - 3;
      readout.textContent = near ? r.expected + ' / ' + max : String(r.expected);
      readout.dataset.state = max && r.expected >= max ? 'full' : 'ok';
    }

    // What the collapsed field says when the popover is shut. This is the only
    // place the party size is visible most of the time, so it is written here
    // — off the field's real value — rather than from an 'input' listener that
    // setTotalQuietly() deliberately never fires.
    if (summary) {
      const n = r.expected || parseInt(expectedGuestsInput.value, 10) || 1;
      summary.textContent = n + (n === 1 ? ' guest' : ' guests');
    }

    syncStepperButtons(expectedGuestsInput);

    if (!note) return;

    // The one case worth a word: − has stopped, and the reason is not the
    // field. Everything else about the allocation is in the status line below.
    if (r.expected === floor && r.blocks.length > 1) {
      note.textContent = 'That is one guest per room — remove a room to go lower.';
      note.dataset.state = 'warn';
    } else {
      note.textContent = 'Everyone staying, including children. We fit them into rooms in step 3.';
      note.dataset.state = 'idle';
    }
  }


  // ── Room cards, answered against the party ───────────────────────
  //
  // A card said "sleeps 3", which is a fact about the room and not an answer
  // to the question being asked. With four guests still to seat, what the
  // guest wants to know of each style is whether it takes all of them, and if
  // not how far it gets — so that is what the card says now.
  function updateTypeFit() {
    // Nobody without a bed means no fitting question to answer. Without this
    // the grid kept badging every card once the party was seated — a room
    // holding three said "Fits all 3" against every style that sleeps three or
    // more, which is true, useless, and repeated across every card in every
    // block. Twelve labels appeared at the exact moment the guest was done.
    const r0 = readiness();
    const anyoneWaiting = r0.expected > r0.assigned;

    document.querySelectorAll('.reservation-block').forEach(function (block) {
      const want = unseatedExcluding(block);
      const filterRow = block.querySelector('[data-fit-filter]');
      const filterBox = block.querySelector('[data-fit-filter-box]');
      const filterLabel = block.querySelector('[data-fit-filter-label]');
      const cards = block.querySelectorAll('.type-card');

      let anyShort = false;
      let anyFits = false;

      cards.forEach(function (card) {
        const beds = parseInt(card.dataset.beds, 10) || 0;
        const fit = card.querySelector('[data-type-fit]');
        if (beds && beds < want) anyShort = true;
        if (beds && beds >= want) anyFits = true;

        if (fit) {
          if (!anyoneWaiting || !want || !beds) {
            fit.textContent = '';
            fit.dataset.state = '';
          } else if (beds >= want) {
            // Marked only when it settles the whole party. That is the answer
            // worth interrupting for, and it is usually true of one or two
            // cards out of seven.
            fit.textContent = want === 1 ? 'Fits your guest' : 'Fits all ' + want;
            fit.dataset.state = 'all';
          } else {
            // Everything else stays quiet. Every card used to carry its own
            // arithmetic — "Fits 2 of the 5", "Fits 3 of the 5", "Fits 4 of
            // the 5" — so the grid repeated the party size seven times over
            // and gave no card any emphasis over another. The line under each
            // title already says how many it sleeps; how far short of five
            // that leaves you is subtraction the guest does not need spelled
            // out beside every option.
            fit.textContent = '';
            fit.dataset.state = '';
          }
        }
      });

      // Only a question worth asking when both answers exist. With nothing
      // short of the party there is nothing to hide; with a party bigger than
      // the largest room there is nothing left once it hides — and a filter
      // that empties the grid is worse than no filter, because the guest reads
      // it as "no rooms" rather than "no single room that big".
      const offerFilter = anyoneWaiting && want > 1 && anyShort && anyFits;
      if (filterRow) filterRow.classList.toggle('hidden', !offerFilter);
      if (filterLabel) filterLabel.textContent = 'Only show rooms that fit all ' + want;
      if (!offerFilter && filterBox) filterBox.checked = false;

      const hideShort = !!(filterBox && filterBox.checked && offerFilter);
      cards.forEach(function (card) {
        const beds = parseInt(card.dataset.beds, 10) || 0;
        // Never hide the style this room is already set to: a card vanishing
        // out from under the selection it is showing is its own confusion.
        const isSelected = card.classList.contains('selected');
        card.classList.toggle('hidden', hideShort && beds > 0 && beds < want && !isSelected);
      });
    });
  }

  document.addEventListener('change', function (e) {
    if (e.target && e.target.matches && e.target.matches('[data-fit-filter-box]')) updateTypeFit();
  });

  // ── "Add Room" ───────────────────────────────────────────────────
  // -- The room picker -----------------------------------------------
  //
  // One list for the whole booking, with a quantity against each style. The
  // blocks below it are generated from these numbers and still carry the
  // reservations[] the server reads, so nothing downstream changed - but the
  // guest is no longer walked through a seven-card grid once per room.

  /** The catalogue, read off the picker the server rendered. */
  function roomCatalogue() {
    return Array.prototype.map.call(document.querySelectorAll('#roomPicker .room-card'), function (row) {
      const titleEl = row.querySelector('.room-card-title');
      return {
        type: row.dataset.roomType,
        beds: parseInt(row.dataset.beds, 10) || 0,
        price: parseFloat(row.dataset.price) || 0,
        title: titleEl ? titleEl.textContent.trim() : row.dataset.roomType,
        row: row
      };
    });
  }

  /**
   * The server's MAX_ROOMS_PER_BOOKING, so the picker stops where store() does
   * rather than letting the guest build an eleventh room and find out on
   * submit. Rendered onto the list; the fallback matches the PHP constant.
   */
  const maxRooms = parseInt(document.getElementById('roomPicker')?.dataset.maxRooms, 10) || 10;

  /** How many rooms of each style the booking currently holds. */
  function roomQuantities() {
    const q = {};
    document.querySelectorAll('.reservation-block').forEach(function (b) {
      const sel = b.querySelector('.room-type-select');
      const t = sel ? sel.value : '';
      if (t) q[t] = (q[t] || 0) + 1;
    });
    return q;
  }

  /**
   * Make the booking hold exactly `n` rooms of `type`.
   *
   * Adding goes through addReservationBlock, which seats whoever is still
   * waiting; removing takes the most recently added room of that style, so
   * earlier choices stay put. A block with no style yet is filled first - the
   * page opens with one, and it would otherwise sit there empty beside the
   * room the guest just asked for.
   */
  function setRoomQuantity(type, n) {
    if (!type) return;
    n = Math.max(0, n);
    let have = roomQuantities()[type] || 0;

    while (have < n) {
      const blank = Array.prototype.filter.call(
        document.querySelectorAll('.reservation-block'),
        function (b) { const sel = b.querySelector('.room-type-select'); return sel && !sel.value; }
      )[0];
      if (blank) {
        const sel = blank.querySelector('.room-type-select');
        sel.value = type;
        sel.dispatchEvent(new Event('change'));
      } else {
        window.addReservationBlock({ room_type: type });
      }
      have++;
    }

    while (have > n) {
      const mine = Array.prototype.filter.call(
        document.querySelectorAll('.reservation-block'),
        function (b) { const sel = b.querySelector('.room-type-select'); return sel && sel.value === type; }
      );
      const victim = mine[mine.length - 1];
      if (!victim) break;
      victim.remove();
      have--;
    }

    updateAggregateHiddenInputs();
    reseatRooms();
    generateBookingSummary();
    document.querySelectorAll('.btn-remove-block').forEach(function (b) {
      b.style.display = document.querySelectorAll('.reservation-block').length > 1 ? 'inline-block' : 'none';
    });
    if (typeof window.refreshCalendarAvailability === 'function') window.refreshCalendarAvailability();
    updateProgressRail();
  }

  const roomPickerEl = document.getElementById('roomPicker');
  if (roomPickerEl) {
    roomPickerEl.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-room-step]');
      if (!btn) return;
      const row = btn.closest('.room-card');
      const type = row ? row.dataset.roomType : '';
      const step = parseInt(btn.dataset.roomStep, 10) || 0;
      const have = roomQuantities()[type] || 0;

      if (step > 0) {
        const free = availableByType[type];
        if (free !== undefined && have >= free) {
          showFormError(free <= 0
            ? 'All ' + (typeTitles[type] || 'rooms of this style') + ' are booked for these dates.'
            : 'Only ' + free + ' ' + (typeTitles[type] || 'of this style') + (free > 1 ? ' are' : ' is') + ' free for these dates.');
          return;
        }
      }
      setRoomQuantity(type, have + step);
    });
  }

  /** Paint quantities, ceilings and the sold-out state onto the picker. */
  /**
   * Is the guest willing to be split across rooms smaller than the party?
   *
   * Off to begin with, which is what makes a Double unselectable for a party
   * of five: picking it cannot seat them, and offering it as an ordinary
   * choice was the thing that made this list misleading. It is a mode and not
   * a filter because three Doubles for six people is a real booking — usually
   * the cheaper one — so the small styles are held back, not removed.
   */
  let roomSplitMode = false;

  /** Styles that could seat everyone still waiting on their own, and are free. */
  function stylesThatFitAlone(need) {
    return roomCatalogue().filter(function (i) {
      const free = availableByType[i.type];
      return i.beds >= need && !(free !== undefined && free <= 0);
    });
  }

  function updateRoomPicker(r) {
    r = r || readiness();
    const q = roomQuantities();
    const expected = r.expected || 0;
    const rooms = r.blocks.length;
    // Beds still to fill.
    const unseated = Math.max(0, expected - r.assigned);

    // How many more rooms this booking may hold.
    //
    // This was `unseated <= 0`, which stopped the picker dead the moment every
    // guest had a bed — and reseatRooms() hands the beds out greedily, so that
    // moment is the first room. A lone guest could pick one room and never a
    // second; a couple who wanted a room each could not have one, because the
    // first room had already absorbed them both. Neither is a rule the server
    // holds: store() asks only that the per-room counts sum to the party and
    // that no room is left empty.
    //
    // So the ceiling is rooms against party, which is what "no empty room"
    // means at the limit — one guest apiece. reseatRooms() already reserves a
    // seat for every room it still has to fill, so the redistribution to 1 + 1
    // happens on its own.
    const roomsLeft = Math.max(0, Math.min(expected, maxRooms) - rooms);

    // Sized against who is still waiting rather than against the whole party.
    // Once one room is picked the guest is already splitting, and the style
    // that exactly seats the remainder is the obvious next choice — it was
    // being held back as "too small" for a party it was never asked to hold.
    const need = Math.max(1, unseated);

    const fitting = stylesThatFitAlone(need);
    // Nothing on the property holds this party in one room, so there is no
    // strict view to offer — split is the only way to book and saying so is
    // better than a list where every row is disabled.
    const mustSplit = expected > 0 && fitting.length === 0;
    if (mustSplit) roomSplitMode = true;
    const strict = expected > 0 && !roomSplitMode && !mustSplit;

    roomCatalogue().forEach(function (item) {
      const have = q[item.type] || 0;
      const free = availableByType[item.type];
      const soldOut = free !== undefined && free <= 0;
      const outOfStock = free !== undefined && have >= free;
      // Held back only while the guest has not opted into splitting, and never
      // for a style they have already taken — pulling a chosen room out from
      // under them would be worse than the problem this solves.
      const tooSmall = strict && item.beds < need && have === 0;

      const out = item.row.querySelector('[data-room-qty]');
      if (out) out.textContent = have;
      item.row.classList.toggle('is-chosen', have > 0);
      item.row.classList.toggle('is-sold-out', soldOut);
      item.row.classList.toggle('is-too-small', tooSmall);
      item.row.classList.toggle('is-full', !soldOut && !tooSmall && roomsLeft <= 0 && have === 0);

      // What this room does about THIS party, which is the question being
      // asked. "Sleeps 2" is on the pill beside it and is a fact about the
      // room; this line is the answer.
      const fit = item.row.querySelector('[data-room-fit]');
      if (fit) {
        let text = '';
        let state = '';
        if (soldOut) {
          text = '';
        } else if (expected <= 0) {
          text = '';
        } else if (unseated <= 0) {
          // Everyone has a bed. Another room is still allowed while there are
          // guests to spread across it, but no room "fits" anything from here
          // and saying so would be noise.
          text = '';
        } else if (item.beds >= need) {
          text = need === expected
            ? (expected === 1 ? 'Room for one' : 'Fits all ' + expected)
            : 'Fits the last ' + need;
          state = 'ok';
        } else if (tooSmall) {
          const roomsNeeded = Math.ceil(need / item.beds);
          text = 'Too small \u2014 need ' + roomsNeeded;
          state = 'no';
        } else {
          const takes = Math.min(item.beds, unseated || item.beds);
          text = 'Sleeps ' + takes + ' of your ' + expected;
          state = 'part';
        }
        fit.textContent = text;
        if (state) fit.dataset.state = state; else delete fit.dataset.state;
      }

      const note = item.row.querySelector('[data-room-note]');
      if (note) {
        // Scarcity is only worth saying about a room the guest could still
        // take; on a style already held back as too small it is one more line
        // of noise on a row they are not being offered.
        note.textContent = soldOut
          ? 'Fully booked for these dates'
          : (!tooSmall && free !== undefined && free <= 2 ? 'Only ' + free + ' left' : '');
      }

      item.row.querySelectorAll('[data-room-step]').forEach(function (b) {
        const step = parseInt(b.dataset.roomStep, 10) || 0;
        if (step < 0) {
          b.disabled = have <= 0;
          return;
        }
        // Everything that makes another room of this style pointless or
        // impossible, in one place. The last clause is the room ceiling, not
        // the bed budget — see roomsLeft.
        b.disabled = soldOut || outOfStock || tooSmall || (expected > 0 && roomsLeft <= 0);
      });
    });

    updateRoomSplitControl(expected, fitting.length, mustSplit);
  }

  /**
   * The line under the list that explains the strict view and undoes it.
   *
   * Hidden whenever there is nothing to explain: no party size yet, or every
   * style on the property already fits.
   */
  function updateRoomSplitControl(expected, fittingCount, mustSplit) {
    const box = document.getElementById('roomSplit');
    const text = document.getElementById('roomSplitText');
    const btn = document.getElementById('roomSplitToggle');
    if (!box || !text || !btn) return;

    const smaller = roomCatalogue().filter(function (i) { return i.beds < expected; }).length;

    if (expected <= 0 || smaller === 0) { box.hidden = true; return; }

    box.hidden = false;

    if (mustSplit) {
      text.textContent = 'No single room here sleeps ' + expected + ', so your party will be split across rooms.';
      btn.hidden = true;
      return;
    }

    btn.hidden = false;
    if (roomSplitMode) {
      text.textContent = 'Showing every room, including ones that hold part of your party.';
      btn.textContent = 'Only show rooms that fit all ' + expected;
    } else {
      text.textContent = smaller === 1
        ? 'One room style is too small for ' + expected + ' on its own.'
        : smaller + ' room styles are too small for ' + expected + ' on their own.';
      btn.textContent = 'Split across smaller rooms';
    }
  }

  document.getElementById('roomSplitToggle')?.addEventListener('click', function () {
    roomSplitMode = !roomSplitMode;
    updateProgressRail();
  });

  /**
   * The cheapest set of rooms that seats the whole party.
   *
   * Rooms are taken best-value-first - lowest cost per bed among the styles
   * still free - and never larger than what is still needed unless nothing
   * smaller is left, so a party of five is not sold a room for six because
   * the arithmetic happened to land there.
   */
  function suggestAllocation(expected) {
    const stock = roomCatalogue()
      .filter(function (i) {
        return i.beds > 0 && (availableByType[i.type] === undefined || availableByType[i.type] > 0);
      })
      .map(function (i) {
        const free = availableByType[i.type];
        return { type: i.type, beds: i.beds, price: i.price, title: i.title,
                 left: free === undefined ? 99 : free };
      });
    if (!stock.length || expected < 1) return null;

    const byValue = stock.slice().sort(function (a, b) { return (a.price / a.beds) - (b.price / b.beds); });
    const bySize = stock.slice().sort(function (a, b) { return a.beds - b.beds || a.price - b.price; });
    const picked = {};
    let seats = 0;
    let guard = 0;

    while (seats < expected && guard++ < 40) {
      const need = expected - seats;
      let choice = byValue.filter(function (i) { return i.left > (picked[i.type] || 0) && i.beds <= need; })[0];
      if (!choice) {
        choice = bySize.filter(function (i) { return i.left > (picked[i.type] || 0) && i.beds >= need; })[0];
      }
      if (!choice) return null;
      picked[choice.type] = (picked[choice.type] || 0) + 1;
      seats += choice.beds;
    }
    if (seats < expected) return null;

    const parts = [];
    let nightly = 0;
    Object.keys(picked).forEach(function (t) {
      const item = stock.filter(function (i) { return i.type === t; })[0];
      nightly += item.price * picked[t];
      parts.push(picked[t] > 1 ? picked[t] + ' ' + item.title + 's' : 'a ' + item.title);
    });

    return {
      picked: picked,
      nightly: nightly,
      text: parts.length === 1
        ? parts[0]
        : parts.slice(0, -1).join(', ') + ' and ' + parts[parts.length - 1]
    };
  }

  function updateRoomSuggestion(r) {
    const box = document.getElementById('roomSuggestion');
    if (!box) return;
    const text = document.getElementById('roomSuggestionText');

    // Nothing worth suggesting once everyone has a bed, or before there are
    // dates to price it against.
    if (!r.datesDone || r.expected < 1 || r.assigned >= r.expected) { box.hidden = true; return; }

    const plan = suggestAllocation(r.expected);
    if (!plan) { box.hidden = true; return; }

    // Never propose what the guest has already built.
    const q = roomQuantities();
    const keys = Object.keys(plan.picked).concat(Object.keys(q));
    const same = keys.every(function (t) { return (q[t] || 0) === (plan.picked[t] || 0); });
    if (same) { box.hidden = true; return; }

    box.hidden = false;
    box.dataset.plan = JSON.stringify(plan.picked);
    if (text) {
      const label = plan.text.charAt(0).toUpperCase() + plan.text.slice(1);
      text.innerHTML = '<b>' + label + '</b> \u00b7 ' + formatPrice(plan.nightly) +
                       ' a night \u2014 ' +
                       (r.expected === 1 ? 'room for one.' : 'sleeps all ' + r.expected + ' of you.');
    }
  }

  const suggestBtn = document.getElementById('roomSuggestionApply');
  if (suggestBtn) {
    suggestBtn.addEventListener('click', function () {
      const box = document.getElementById('roomSuggestion');
      let plan;
      try { plan = JSON.parse((box && box.dataset.plan) || '{}'); } catch (e) { return; }
      // Clear anything already chosen so the plan is what it says it is.
      roomCatalogue().forEach(function (i) { if (!plan[i.type]) setRoomQuantity(i.type, 0); });
      Object.keys(plan).forEach(function (t) { setRoomQuantity(t, plan[t]); });
      if (box) box.hidden = true;
    });
  }

  /** Name each block after the room it actually holds. */
  function updateBlockHeadings() {
    document.querySelectorAll('.reservation-block').forEach(function (b) {
      const sel = b.querySelector('.room-type-select');
      const name = b.querySelector('.block-room-name');
      const rate = b.querySelector('.block-room-rate');
      const opt = sel && sel.selectedOptions ? sel.selectedOptions[0] : null;
      const title = sel && sel.value && opt ? opt.textContent.replace(/\s*\(.*$/, '').trim() : '';
      if (name) name.textContent = title || 'Room';
      if (rate) rate.textContent = (sel && sel.value && opt && opt.dataset.price)
        ? formatPrice(opt.dataset.price) + ' / night' : '';
    });
  }

  function updateProgressRail() {
    let r = readiness();

    // Corrects the rooms, never the party. A total *above* what the rooms hold
    // is the ordinary half-finished state ("4 of 6 seated"), not an error, and
    // is left alone; the other direction is a room holding people the party
    // does not have, which the steppers now prevent and this catches.
    //
    // Held off only while the field has focus, which is the one moment a
    // rewrite would land mid-keystroke and turn a half-typed "12" into "22".
    // The change handler settles it the moment they are done.
    if (document.activeElement !== expectedGuestsInput && clampRoomsToParty()) {
      r = readiness();
    }

    if (document.getElementById('checkoutProgress')) {
      setStep('dates', r.datesDone);
      setStep('details', r.detailsDone);
      setStep('rooms', r.roomsDone);
    }

    updateAllocationMeter(r);
    updateTotalGuestsCount(r);
    // Every room's ceiling depends on what the other rooms hold, so one room
    // changing moves all of them.
    refreshRoomCaps();
    updateTypeFit();
    updateRoomPicker(r);
    updateRoomSuggestion(r);
    updateBlockHeadings();
    updateBlockerLine(r);
  }

  bookingForm?.addEventListener('input', function (e) {
    if (['first_name', 'last_name', 'guest_phone', 'accept_terms',
         'referred_by', 'referred_by_phone', 'referred_by_purpose'].includes(e.target?.name)) updateProgressRail();
    // Clearing the mark as soon as the guest edits the field keeps the red
    // from outliving the problem.
    e.target?.classList?.remove('field-invalid');
  });

  // Rail steps double as jump-links to their step cards — same scroll +
  // block-flash contract as the summary rows' deep links.
  const railTargets = { dates: 'stepCardDates', details: 'stepCardDetails', rooms: 'stepCardRooms' };
  /** Scroll a step card into view and flash it. Shared by the rail, the
   *  summary rows' deep links and the blocker line above Confirm. */
  function jumpToCardById(id) {
    const card = document.getElementById(id || '');
    if (!card) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    card.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
    card.classList.remove('block-flash');
    void card.offsetWidth; // restart the outline flash on repeat jumps
    card.classList.add('block-flash');
  }

  function jumpToStepCard(li) {
    jumpToCardById(railTargets[li.dataset.progressStep] || '');
  }
  const progressRail = document.getElementById('checkoutProgress');
  progressRail?.addEventListener('click', function (e) {
    const li = e.target.closest('[data-progress-step]');
    if (li) jumpToStepCard(li);
  });
  progressRail?.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const li = e.target.closest('[data-progress-step]');
    if (li) { e.preventDefault(); jumpToStepCard(li); }
  });

  // Checkout problems pop up rather than sitting in a banner at the top of a
  // long form. "That room was just taken" is only useful if the guest sees it
  // where they are looking — down at the room tiles — not 2000px up the page.
  // window.toast lives in resources/js/app.js and its styles are shared by
  // both bundles (resources/css/shared/toast.css).
  function showFormError(msg, target) {
    // A message that names a problem the guest then has to go find is only
    // half an error. When we know which field or block is at fault, take
    // them to it: flash the block, focus the input, and let the toast be
    // the caption rather than the whole instruction.
    if (target) {
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const block = target.closest ? target.closest('.reservation-block') : null;
      const scrollTo = block || target;

      scrollTo.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });

      if (block) {
        block.classList.remove('block-invalid');
        void block.offsetWidth; // restart if a previous shake is mid-flight
        block.classList.add('block-invalid');
        setTimeout(() => block.classList.remove('block-invalid'), 2400);
      }

      if (target.tagName && /^(INPUT|SELECT|TEXTAREA)$/.test(target.tagName)) {
        target.classList.add('field-invalid');
        // After the smooth scroll, or focus() fights it and jumps.
        setTimeout(() => target.focus({ preventScroll: true }), reduce ? 0 : 320);
      }
    }

    if (typeof window.toast === 'function') {
      window.toast(msg, 'error');
      return;
    }

    // Fallbacks, in case app.js failed to load: the old inline banner, and
    // then the browser's own dialog. A validation message must never be
    // silently swallowed.
    if (bookingFormAlert) {
      bookingFormAlert.innerText = msg;
      bookingFormAlert.classList.remove('d-none');
      bookingFormAlert.classList.remove('shake-now');
      void bookingFormAlert.offsetWidth;
      bookingFormAlert.classList.add('shake-now');
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      bookingFormAlert.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
    } else {
      alert(msg);
    }
  }

  // Success/neutral notices use the same popup channel.
  function showFormNotice(msg, type) {
    if (typeof window.toast === 'function') window.toast(msg, type || 'info');
  }

  // Summary room rows deep-link back to their reservation block
  document.getElementById('summaryInvoice')?.addEventListener('click', function (e) {
    const row = e.target.closest('[data-jump-block]');
    if (!row) return;
    const block = document.querySelector('.reservation-block[data-index="' + row.dataset.jumpBlock + '"]');
    if (!block) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    block.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
    block.classList.remove('block-flash');
    void block.offsetWidth;
    block.classList.add('block-flash');
  });

  let isSubmittingBooking = false;
  bookingForm && bookingForm.addEventListener('submit', function(e) {
    if (isSubmittingBooking) {
      e.preventDefault();
      return;
    }
    bookingFormAlert && bookingFormAlert.classList.add('d-none');
    const blocks = document.querySelectorAll('.reservation-block');
    if (!blocks || blocks.length === 0) { e.preventDefault(); showFormError('Please add at least one room type.'); return; }

    const expected = parseInt(expectedGuestsInput.value) || 0;
    let totalCapacity = 0;
    let totalSeniors = 0;
    let totalGuests = 0;

    for (const [i, b] of blocks.entries()) {
      const beds = parseInt(b.querySelector('.res-beds').value) || 0;
      const numSen = parseInt(b.querySelector('.res-num-seniors').value) || 0;
      const roomType = b.querySelector('.room-type-select')?.value || '';
      const numGuests = parseInt(b.querySelector('.res-num-guests')?.value) || 0;
      // Second argument: the control the guest has to change to fix it, so
      // showFormError can scroll them to it instead of only naming it.
      const guestsInput = b.querySelector('.res-num-guests');
      const seniorsInput = b.querySelector('.res-num-seniors');

      // Was "pick a room number". The style is the choice now — the number is
      // assigned server-side when the booking commits.
      if (!roomType) { e.preventDefault(); showFormError('Choose a room style for this room.', b); return; }
      if (numSen > beds) { e.preventDefault(); showFormError('Seniors in a room cannot exceed that room\'s capacity.', seniorsInput); return; }
      if (numGuests > beds) { e.preventDefault(); showFormError('Guests in a room cannot exceed that room’s capacity.', guestsInput); return; }
      if (numGuests < 1) { e.preventDefault(); showFormError('Each room must have at least 1 guest.', guestsInput); return; }

      // Breakfast is a free extra, not part of the booking contract — a guest
      // who wants none, or fewer than one each, may book anyway. The only
      // rule left is the cap, enforced as they click (you cannot order more
      // breakfasts than there are guests) and again server-side.
      let totalMeals = 0;
      b.querySelectorAll('.meal-qty').forEach(inp => totalMeals += parseInt(inp.value) || 0);
      if (totalMeals > numGuests) { e.preventDefault(); showFormError(`Room ${i + 1} has more breakfasts selected than guests staying in it.`, b); return; }

      totalGuests += numGuests;
      totalCapacity += beds;
      totalSeniors += numSen;
    }

    // The allocation rule, stated the way a person would say it and pointing
    // at the meter that has been tracking it all along — not as "Mismatch:
    // total assigned guests (3) must equal expected guests (2)".
    if (totalGuests !== expected) {
      e.preventDefault();
      const diff = Math.abs(expected - totalGuests);
      showFormError(
        totalGuests < expected
          ? `${diff} guest${diff > 1 ? 's' : ''} still need a room — you are booking for ${expected}.`
          : `You have assigned ${diff} guest${diff > 1 ? 's' : ''} more than the ${expected} you are bringing.`,
        document.getElementById('allocationMeter')
      );
      return;
    }
    if (totalCapacity < expected) { e.preventDefault(); showFormError(`These rooms sleep ${totalCapacity}, but you are booking for ${expected}.`, document.getElementById('allocationMeter')); return; }
    if (totalSeniors > expected) { e.preventDefault(); showFormError('More seniors than total guests.', expectedGuestsInput); return; }
    if (num_seniors_hidden && parseInt(num_seniors_hidden.value) !== totalSeniors) { e.preventDefault(); showFormError(`Mismatch: total seniors in reservations (${totalSeniors}) must equal total seniors for the booking.`); return; }

    // More rooms of a style than are free. The server refuses this too, under
    // a lock and with the real count — this only spares the round trip.
    const oversold = oversubscribed();
    if (oversold) {
      e.preventDefault();
      showFormError(
        oversold.available === 0
          ? `No ${oversold.title} rooms are free for these dates. Try other dates or another room style.`
          : `Only ${oversold.available} ${oversold.title} ${oversold.available === 1 ? 'room is' : 'rooms are'} free for these dates, and you have asked for ${oversold.wanted}.`,
        document.getElementById('reservationBlocks')
      );
      return;
    }

    // The "same room selected twice" check retired with the picker — two rooms
    // can no longer collide because the guest never names one. The server draws
    // each from a pool it removes as it assigns.
    if (num_seniors_hidden) num_seniors_hidden.value = totalSeniors;

    // All client checks passed — lock both submit buttons against double-submit
    isSubmittingBooking = true;
    ['btnSubmitBooking', 'btnSubmitBookingMobile'].forEach(id => {
      const b = document.getElementById(id);
      if (!b) return;
      b.classList.add('opacity-80', 'pointer-events-none');
      b.disabled = true;
      b.innerHTML = '<svg class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span class="ml-2">Placing booking…</span>';
    });
  });

  check_in && check_in.addEventListener('change', function() {
    const checkInDate = new Date(this.value);
    if (checkInDate) {
      checkInDate.setDate(checkInDate.getDate() + 1);
      const minCheckOut = checkInDate.toISOString().split('T')[0];
      check_out.min = minCheckOut;
      if (check_out.value && check_out.value < minCheckOut) {
        // Through flatpickr: writing '' to the input alone would empty the
        // value and leave the old date showing in the visible alt input.
        if (check_out._flatpickr) check_out._flatpickr.clear();
        else check_out.value = '';
      }
    }
    syncDates();
    autoCheckAllBlocks();
  });

  check_out && check_out.addEventListener('change', function() {
    syncDates();
    autoCheckAllBlocks();
  });

  // ── Date presets ────────────────────────────────────────────────
  //
  // Two flatpickr popovers is a lot of tapping to express "tonight", which
  // is what a good share of the traffic here wants — the hostel serves a
  // campus, and campus stays are short and imminent. These fill both
  // fields and then fire the same 'change' events a manual pick would, so
  // availability refresh, the summary and the rail all react normally
  // rather than needing their own path.
  const isoDate = (d) => {
    const pad = (n) => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  };

  // Human-readable face for the two date fields.
  //
  // The real input keeps `Y-m-d` — every read in this file, and the form POST,
  // still see exactly what they saw before — while flatpickr renders a second,
  // visible input in a format a person can check at a glance. The weekday is
  // there on purpose: the presets offer "This weekend", and this is the only
  // thing on the page that confirms which days that came out as.
  const ALT_DATE = {
    altInput: true,
    altFormat: 'D, j M',
    altInputClass: 'stay-field-input',
  };

  /** Give the visible input the id the field's <label for> points at. */
  const linkAltLabel = (el) => (_s, _d, fp) => {
    if (fp.altInput) {
      fp.altInput.id = el.id + '_display';
      fp.altInput.setAttribute('placeholder', el.getAttribute('placeholder') || 'Select date');
    }
  };

  function applyPreset(key) {
    const start = new Date();
    start.setHours(0, 0, 0, 0);
    let nights = 1;

    if (key === 'tomorrow') {
      start.setDate(start.getDate() + 1);
    } else if (key === 'weekend') {
      // The coming Friday. On a Saturday or Sunday "this weekend" is the one
      // you are standing in, so stay put rather than sending them a week out.
      const day = start.getDay();               // 0 Sun … 6 Sat
      if (day !== 0 && day !== 6) start.setDate(start.getDate() + ((5 - day + 7) % 7));
      nights = 2;
    } else if (key === 'week') {
      start.setDate(start.getDate() + 7);
      nights = 7;
    }

    const end = new Date(start);
    end.setDate(end.getDate() + nights);

    // flatpickr owns these inputs — setDate() keeps its calendar in step with
    // the value. Writing .value directly leaves the two disagreeing, so the
    // next time the guest opens the picker it highlights the wrong day.
    const setField = (el, date) => {
      if (!el) return;
      if (el._flatpickr) el._flatpickr.setDate(date, false);
      el.value = isoDate(date);
    };

    setField(check_in, start);
    // The check-out picker's minDate is armed by check_in's own onChange;
    // raise it here too so setDate is not clamped to a stale floor.
    if (check_out?._flatpickr) check_out._flatpickr.set('minDate', isoDate(new Date(start.getTime() + 86400000)));
    setField(check_out, end);

    check_in?.dispatchEvent(new Event('change', { bubbles: true }));
    check_out?.dispatchEvent(new Event('change', { bubbles: true }));
  }

  document.getElementById('datePresets')?.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-preset]');
    if (!btn) return;
    applyPreset(btn.dataset.preset);
  });

  // Dates changed, so what is open changed. Once this also re-loaded a tile
  // grid per block; with the picker gone the per-style counts on the type
  // cards are the whole of what the guest sees, and that is one request for
  // the page rather than one per block.
  function autoCheckAllBlocks() {
    if (!check_in || !check_out || !check_in.value || !check_out.value) return;
    updateTypeAvailability();
  }


  /**
   * The first style this booking asks for more of than are free, or null.
   * Returns { type, title, wanted, available } so the caller can say which.
   *
   * Advisory, not authoritative: the numbers are a snapshot and rooms move
   * while a form is open. BookingController::store re-counts under a lock and
   * is the thing that actually refuses — this just means the guest usually
   * finds out here, next to the control they need to change, instead of after
   * pressing Confirm.
   */
  function oversubscribed() {
    const wantedByType = {};
    document.querySelectorAll('.reservation-block').forEach(b => {
      const t = b.querySelector('.room-type-select')?.value;
      if (t) wantedByType[t] = (wantedByType[t] || 0) + 1;
    });

    for (const [type, wanted] of Object.entries(wantedByType)) {
      const available = availableByType[type];
      // Undefined means the summary has not landed yet — say nothing rather
      // than block a booking over a number we do not have.
      if (typeof available === 'number' && wanted > available) {
        return { type, title: typeTitles[type] || type, wanted, available };
      }
    }
    return null;
  }

  // Per-room-type availability for the chosen dates. Badges each type card so a
  // guest can see at a glance that, say, every Double Room is taken — before
  // they pick it. Uses the same /rooms/availability-summary the landing page does.
  async function updateTypeAvailability() {
    // Guest's chosen dates, or a default (tonight → tomorrow) so the type cards
    // show "Fully booked" / "Only N left" on load, before stay dates are picked.
    let ci = check_in?.value, co = check_out?.value;
    if (!ci || !co) {
      const pad = n => String(n).padStart(2, '0');
      const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
      const today = new Date();
      const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
      ci = iso(today); co = iso(tomorrow);
    }
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const resp = await fetch('/rooms/availability-summary', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ check_in: ci, check_out: co })
      });
      if (!resp.ok) return;
      const data = await resp.json();
      (data.summary || []).forEach(row => {
        // Kept so the form can tell a guest they are asking for four Doubles
        // when three are free. With the picker gone there is no grid running
        // out of tiles to make that obvious — the only other way to find out
        // was to press Confirm and be turned away.
        availableByType[row.room_type] = row.available;
        typeTitles[row.room_type] = row.title || row.room_type;

        const isFull = row.available <= 0;
        const badgeText = isFull ? 'Fully booked' : (row.available <= 2 ? ('Only ' + row.available + ' left') : '');
        document.querySelectorAll('.type-card[data-type-value="' + row.room_type + '"]').forEach(card => {
          card.dataset.full = isFull ? '1' : '0';
          card.classList.toggle('opacity-60', isFull);

          // Auto-deselect if it's currently selected and has become fully booked
          if (isFull && card.classList.contains('selected')) {
            card.classList.remove('selected');
            const check = card.querySelector('.type-card-check');
            if (check) { check.classList.add('hidden'); check.classList.remove('grid'); }

            const block = card.closest('.reservation-block');
            if (block) {
              const roomTypeSelect = block.querySelector('.room-type-select');
              if (roomTypeSelect && roomTypeSelect.value === row.room_type) {
                roomTypeSelect.value = '';
                roomTypeSelect.dispatchEvent(new Event('change'));
              }
            }
          }

          const badge = card.querySelector('[data-type-avail]');
          if (!badge) return;
          if (badgeText) {
            badge.textContent = badgeText;
            badge.className = 'type-card-avail absolute left-2 top-2 z-10 rounded-full px-2 py-0.5 text-[10px] font-bold shadow-sm ' +
              (isFull ? 'bg-ember-600 text-white' : 'bg-gold text-night');
          } else {
            badge.textContent = '';
            badge.className = 'type-card-avail hidden';
          }
        });
      });
    } catch (e) { console.error(e); }
  }

  function fmtShortDate(iso) {
    const d = new Date(iso + 'T00:00:00');
    if (isNaN(d)) return iso;
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
  }

  // Animate both the sidebar total and the mobile sticky-bar total.
  // No total yet → "—": "₱0 due" would be a false statement.
  function syncTotals(totalPrice) {
    const sideEl = document.getElementById('summaryTotalAmount');
    const mobEl  = document.getElementById('mobileTotalAmount');
    if (!totalPrice) {
      if (sideEl) sideEl.textContent = '—';
      if (mobEl)  mobEl.textContent  = '—';
      lastSummaryTotal = 0;
      return;
    }
    animateCurrency(sideEl, lastSummaryTotal, totalPrice);
    animateCurrency(mobEl, lastSummaryTotal, totalPrice);
    lastSummaryTotal = totalPrice;
  }

  // Tracks the summary's empty↔populated state so the invoice gets ONE
  // entrance pop when it first materializes — never on the routine
  // re-renders that follow every input (that would be motion noise).
  let summaryWasEmpty = true;

  function generateBookingSummary() {
    const container = document.getElementById('summaryInvoice');
    if (!container) return;
    const mobileMeta = document.getElementById('mobileMetaLine');

    const checkInVal  = check_in?.value  || '';
    const checkOutVal = check_out?.value || '';

    if (!checkInVal || !checkOutVal) {
      container.innerHTML = `
        <div class="text-center py-10 text-stone-500">
            ${icon('calendar-days')}
            <p class="font-semibold">Please select your stay dates.</p>
        </div>`;
      if (mobileMeta) mobileMeta.textContent = 'Pick your stay dates';
      summaryWasEmpty = true;
      syncTotals(0);
      updateProgressRail();
      return;
    }

    let nights = 1;
    const d1 = new Date(checkInVal);
    const d2 = new Date(checkOutVal);
    nights = Math.max(1, Math.round((d2 - d1) / 86400000));

    const blocks = document.querySelectorAll('.reservation-block');
    if (blocks.length === 0) {
      container.innerHTML = `
        <div class="text-center py-10 text-stone-500">
            ${icon('bed')}
            <p class="font-semibold">Your total appears once you pick rooms.</p>
        </div>`;
      if (mobileMeta) mobileMeta.textContent = 'Add a room to continue';
      summaryWasEmpty = true;
      syncTotals(0);
      updateProgressRail();
      return;
    }

    let totalPrice = 0;
    let roomRows = '';

    blocks.forEach(block => {
      const typeSelect = block.querySelector('.room-type-select');
      const typeName   = typeSelect?.selectedOptions[0]?.text?.split('(')[0]?.trim() || 'Unknown Room';
      const price      = parseFloat(block.querySelector('.res-price-hidden')?.value) || 0;
      const numGuests  = parseInt(block.querySelector('.res-num-guests')?.value)  || 0;
      const blockTotal = price * nights;
      totalPrice += blockTotal;

      // Breakfast chips for this room
      const mealChips = [];
      block.querySelectorAll('.meal-qty').forEach(inp => {
        const q = parseInt(inp.value, 10) || 0;
        if (q > 0) {
          const m = inp.name && inp.name.match(/\[meal\]\[([a-z0-9_]+)\]/i);
          if (m) mealChips.push(q + '× ' + m[1].charAt(0).toUpperCase() + m[1].slice(1));
        }
      });
      const mealLine = mealChips.length
        ? `<p class="text-[11px] font-semibold text-palay-800 mt-0.5">Breakfast: ${mealChips.join(', ')}</p>`
        : '';

      roomRows += `
        <div class="sum-row flex items-start justify-between gap-3 py-3.5 border-b border-emerald-deep/10 last:border-0" data-jump-block="${block.dataset.index}" title="Review this room">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-ink truncate">${typeName}</p>
            <p class="text-[11px] font-semibold text-stone-500 mt-0.5">${numGuests} ${numGuests === 1 ? 'guest' : 'guests'} &middot; room assigned on confirmation</p>
            <p class="text-[11px] font-semibold text-stone-500">${formatPrice(price)} &times; ${nights} ${nights === 1 ? 'night' : 'nights'}</p>
            ${mealLine}
          </div>
          <span class="text-sm font-extrabold text-palay-800 tabnum">${formatPrice(blockTotal)}</span>
        </div>`;
    });

    const discountNote = document.getElementById('request_discount')?.checked
      ? `
      <div class="mt-4 text-xs font-bold text-stone-700 bg-gold/10 rounded-xl px-4 py-3 border border-gold/30 leading-relaxed flex items-start gap-1.5">
          ${icon('circle-info')}
          <div>20% Senior/PWD discount will be calculated and applied at check-in upon verification.</div>
      </div>`
      : '';

    const guestsTotal = parseInt(expectedGuestsInput?.value) || 1;

    container.innerHTML = `
      <div class="grid grid-cols-3 items-center bg-white/60 ring-1 ring-emerald-deep/5 rounded-2xl px-2 py-3 text-center">
        <div>
          <span class="block font-label text-[10px] uppercase tracking-[0.22em] text-ink-faint">Check-in</span>
          <span class="block text-[13px] font-extrabold text-ink mt-0.5">${fmtShortDate(checkInVal)}</span>
        </div>
        <div class="border-x border-emerald-deep/10">
          <span class="block font-label text-[10px] uppercase tracking-[0.22em] text-ink-faint">Nights</span>
          <span class="block text-[13px] font-extrabold text-palay-800 mt-0.5">${nights}</span>
        </div>
        <div>
          <span class="block font-label text-[10px] uppercase tracking-[0.22em] text-ink-faint">Check-out</span>
          <span class="block text-[13px] font-extrabold text-ink mt-0.5">${fmtShortDate(checkOutVal)}</span>
        </div>
      </div>
      <div class="mt-1.5 text-center text-[11px] font-semibold text-stone-500">${guestsTotal} guest${guestsTotal > 1 ? 's' : ''} expected</div>
      <div class="space-y-1 bg-white/60 p-4 rounded-2xl ring-1 ring-emerald-deep/5 mt-3">
        ${roomRows}
      </div>
      <div class="mt-5 bg-emerald-deep p-5 rounded-2xl flex justify-between items-center">
        <div>
          <span class="font-label text-[10px] uppercase tracking-[0.24em] text-cream/60">Total Due</span>
          <span class="block text-[11px] font-semibold text-cream/70 mt-0.5">${blocks.length} ${blocks.length === 1 ? 'room' : 'rooms'} for ${nights} ${nights === 1 ? 'night' : 'nights'}</span>
        </div>
        <div class="font-display text-2xl text-gold-soft tabnum" id="summaryTotalAmount">${formatPrice(totalPrice)}</div>
      </div>
      ${discountNote}
    `;
    if (mobileMeta) mobileMeta.textContent = `${blocks.length} room${blocks.length > 1 ? 's' : ''} · ${nights} night${nights > 1 ? 's' : ''}`;
    if (summaryWasEmpty) {
      // Empty → populated: bridge the swap with the shared 150ms pop
      container.classList.remove('animate-pop');
      void container.offsetWidth; // restart if a previous pop is mid-flight
      container.classList.add('animate-pop');
      summaryWasEmpty = false;
    }
    syncTotals(totalPrice);
    updateProgressRail();
  }
  
  window.generateBookingSummary = generateBookingSummary;

  // Initialize page
  setTimeout(() => {
    // initialize flatpickr
    if (typeof flatpickr !== 'undefined') {
      const inEl = document.getElementById('check_in');
      const outEl = document.getElementById('check_out');
      if (inEl && outEl) {
        // ── Sold-out nights ────────────────────────────────────────────
        // The calendar used to know nothing about bookings: any future date
        // was pickable, and a guest only found out a week was gone once the
        // room grid came back empty. /rooms/calendar-availability answers
        // which NIGHTS have no sellable room left.
        //
        // Nights, not days: a stay is [check_in, check_out), so a full date is
        // struck off the check-in picker but is still a legal check-out. The
        // range constraint is enforced by capping the check-out picker at the
        // first full night on or after the chosen check-in — pick Aug 2 with
        // Aug 5 sold out and the calendar simply will not offer past Aug 5.
        let fullNights = new Set();
        let remaining = {};

        const isoDay = (d) => {
          const t = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
          return t.toISOString().slice(0, 10);
        };

        const isFull = (d) => fullNights.has(isoDay(d));

        const fpOut = flatpickr(outEl, {
          dateFormat: 'Y-m-d',
          minDate: 'today',
          disableMobile: true,
          ...ALT_DATE,
          onReady: linkAltLabel(outEl),
          onChange: function() {
            outEl.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });

        // The last night a stay starting on `from` could include, i.e. the
        // furthest check-out date still available. Undefined = no limit.
        const firstFullOnOrAfter = (from) => {
          const cursor = new Date(from.getTime());
          for (let i = 0; i < 366; i++) {
            if (isFull(cursor)) return new Date(cursor.getTime());
            cursor.setDate(cursor.getDate() + 1);
          }
          return null;
        };

        const applyCheckoutCeiling = (checkIn) => {
          const blocked = firstFullOnOrAfter(checkIn);
          // check_out === blocked is allowed: that stay's last night is the
          // day before, which is free.
          fpOut.set('maxDate', blocked || null);
          if (blocked && outEl.value && new Date(outEl.value) > blocked) {
            fpOut.clear();
            outEl.dispatchEvent(new Event('change', { bubbles: true }));
          }
        };

        const fpIn = flatpickr(inEl, {
          dateFormat: 'Y-m-d',
          minDate: 'today',
          disableMobile: true,
          ...ALT_DATE,
          onReady: linkAltLabel(inEl),
          onChange: function(dates) {
            if (!dates[0]) return;
            const nextDay = new Date(dates[0].getTime() + 86400000);
            fpOut.set('minDate', nextDay);
            applyCheckoutCeiling(dates[0]);
            if (outEl.value && new Date(outEl.value) < nextDay) {
              fpOut.clear();
              outEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
            inEl.dispatchEvent(new Event('change', { bubbles: true }));
            if (!outEl.value) {
              setTimeout(() => fpOut.open(), 120);
            }
          }
        });

        // Paint both calendars: sold-out nights are struck through, and the
        // last couple of rooms get a "nearly gone" dot. Colour is never the
        // only cue — full days are also unselectable and carry a title.
        //
        // `noun` names what the counts are counting. Unscoped it is "rooms";
        // once the guest has chosen a style it is that style, because "1 room
        // left" while eleven rooms of other types sit empty is a lie.
        let scopeNoun = 'rooms';

        const singular = (noun) => (noun.endsWith('s') ? noun.slice(0, -1) : noun);

        const paintDay = (dObj, dStr, fp, dayElem) => {
          const iso = isoDay(dayElem.dateObj);
          if (fullNights.has(iso)) {
            dayElem.classList.add('fp-sold-out');
            dayElem.title = 'No ' + scopeNoun + ' left this night';
            return;
          }
          const left = remaining[iso];
          if (left !== undefined && left > 0 && left <= 2) {
            dayElem.classList.add('fp-nearly-gone');
            dayElem.title = left + ' ' + (left === 1 ? singular(scopeNoun) : scopeNoun) + ' left this night';
          }
        };

        const typeTitle = (slug) => {
          const cfg = (window.ROOM_TYPES_CONFIG || {})[slug];
          return cfg && cfg.title ? cfg.title : slug;
        };

        // Which styles the guest has actually asked for, and how many rooms of
        // each. A block holds exactly one room number, so the demand for a type
        // is simply how many blocks name it — two "double" blocks need two
        // doubles free that night, not one.
        const requestedDemand = () => {
          const demand = {};
          document.querySelectorAll('.room-type-select').forEach((sel) => {
            if (sel.value) demand[sel.value] = (demand[sel.value] || 0) + 1;
          });
          return demand;
        };

        const fetchAvailability = (roomType) =>
          fetch('/rooms/calendar-availability' + (roomType ? '?room_type=' + encodeURIComponent(roomType) : ''),
                { headers: { 'Accept': 'application/json' } })
            .then((r) => (r.ok ? r.json() : null));

        // Bumped on every call so a slow reply for a style the guest has since
        // changed away from loses to the newer one instead of overwriting it.
        let calToken = 0;

        async function refreshCalendarAvailability() {
          const token = ++calToken;
          const demand = requestedDemand();
          const types = Object.keys(demand);

          let sets;
          try {
            sets = types.length
              ? await Promise.all(types.map(fetchAvailability))
              : [await fetchAvailability(null)];
          } catch (e) {
            return;
          }
          if (token !== calToken) return;

          const nextFull = new Set();
          const nextRemaining = {};

          sets.forEach((data, i) => {
            if (!data) return;
            const need = types.length ? demand[types[i]] : 1;

            Object.keys(data.remaining || {}).forEach((night) => {
              const left = data.remaining[night];
              // The tightest constraint wins: with a double and a triple block
              // open, the night is only as good as whichever is scarcer.
              if (nextRemaining[night] === undefined || left < nextRemaining[night]) {
                nextRemaining[night] = left;
              }
              // Short of what this booking needs is just as unbookable as zero.
              if (left < need) nextFull.add(night);
            });

            (data.full || []).forEach((night) => nextFull.add(night));
          });

          fullNights = nextFull;
          remaining = nextRemaining;
          scopeNoun = types.length === 1 ? typeTitle(types[0]).toLowerCase() + 's' : 'rooms';

          // Availability is advisory here — the authoritative check still
          // runs server-side on submit. If this request fails the calendar
          // simply behaves as it always did, which is why none of this is
          // awaited before the pickers are usable.
          fpIn.set('disable', [(d) => isFull(d)]);
          fpIn.set('onDayCreate', [paintDay]);
          fpOut.set('onDayCreate', [paintDay]);
          fpIn.redraw();
          fpOut.redraw();

          if (inEl.value) {
            const d = new Date(inEl.value);
            if (!isNaN(d.getTime())) applyCheckoutCeiling(d);
          }
        }

        // Re-scoped whenever a block's room style changes — see the
        // .room-type-select change handler in addReservationBlock().
        window.refreshCalendarAvailability = refreshCalendarAvailability;
        refreshCalendarAvailability();

        // Set initial minDate for check-out if check-in has a value on page load
        if (inEl.value) {
          const checkInDate = new Date(inEl.value);
          if (!isNaN(checkInDate.getTime())) {
            const nextDay = new Date(checkInDate.getTime() + 86400000);
            fpOut.set('minDate', nextDay);
          }
        }
      } else {
        flatpickr('.flatpickr-date', {
          dateFormat: 'Y-m-d',
          minDate: 'today',
          disableMobile: true,
          ...ALT_DATE
        });
      }
    }
    
    syncDates();
    
    // The party size the guest already gave the availability search, applied
    // whether or not they also picked a style there.
    //
    // This used to sit inside the `if (initialRoomType)` branch below, so
    // arriving on /checkout?guests=5 — which is every path that searched dates
    // and guests without opening a room first — dropped the 5 and started the
    // form at 1. The guest then had to say how many of them were coming for a
    // second time, having already been asked on the landing page.
    //
    // old() wins: a rejected submission must show what was typed, not what the
    // URL said several minutes ago.
    if (expectedGuestsInput && initialGuests > 1 && !hasOldGuestCount) {
      expectedGuestsInput.value = Math.min(
        initialGuests,
        parseInt(expectedGuestsInput.max, 10) || initialGuests
      );
    }

    // Auto-add first block if from URL params
    if (initialRoomType) {
      updateCapsDisplay();
      addReservationBlock({ room_type: initialRoomType });
    }
    // No blank room otherwise. The page used to open with an empty block
    // demanding a style, which is the picker's job now — an unstyled block
    // headed "Room" with a guest stepper in it says nothing and cannot be
    // acted on. Raising a quantity in the picker creates the first room.

    // Badge the room-type cards immediately on load — uses the guest's dates if
    // deep-linked, otherwise a default (tonight) so "Fully booked" shows upfront.
    updateTypeAvailability();
  }, 100);

});
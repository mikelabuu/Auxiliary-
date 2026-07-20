document.addEventListener('DOMContentLoaded', function () {
  const bookingForm = document.getElementById('bookingForm');
  const check_in = document.getElementById('check_in');
  const check_out = document.getElementById('check_out');
  const check_in_hidden = document.getElementById('check_in_hidden');
  const check_out_hidden = document.getElementById('check_out_hidden');
  const room_numbers_hidden = document.getElementById('selected_room_number');
  const bookingFormAlert = document.getElementById('bookingFormAlert');
  const expectedGuestsInput = document.getElementById('expected_guests');
  const maxSeniorsLabel = document.getElementById('maxSeniorsLabel');
  const num_seniors_hidden = document.getElementById('num_seniors');
  const reservationContainer = document.getElementById('reservationBlocks');
  const tpl = document.getElementById('reservationBlockTemplate');
  const bookingStateHost = bookingForm;
  const initialRoomType = bookingStateHost?.dataset.initialRoomType || window.INITIAL_ROOM_TYPE || '';
  const initialGuests = parseInt(bookingStateHost?.dataset.initialGuests || window.INITIAL_GUESTS || '1', 10);

  if (!reservationContainer || !tpl) return; // Only run on checkout page

  function formatPrice(num) { return '₱' + Number(num).toLocaleString(); }

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
    const v = parseInt(expectedGuestsInput?.value) || 1;
    if (maxSeniorsLabel) maxSeniorsLabel.textContent = v;
    generateBookingSummary();
  }

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
  const selectedRoomNumbersSet = new Set();

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
    const btnCheck = block.querySelector('.btn-check-availability');
    const roomTilesWrap = block.querySelector('.room-tiles-wrapper');
    const roomNumberHidden = block.querySelector('.res-room-number-hidden');
    const btnRemove = block.querySelector('.btn-remove-block');
    const numGuestsInput = block.querySelector('.res-num-guests');
    const capacityHint = block.querySelector('.capacity-hint');
    const typeCards = block.querySelectorAll('.type-card');

    function refreshCapacityHint() {
      if (!capacityHint) return;
      const beds = parseInt(resBeds.value, 10) || 0;
      capacityHint.textContent = beds
        ? 'Sleeps up to ' + beds + ' guest' + (beds > 1 ? 's' : '') + ' in this room'
        : '';
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
        const cap = parseInt(resBeds.value) || 0;
        let v = parseInt(numGuestsInput.value) || 0;
        if (v > cap) numGuestsInput.value = cap;
        if (v < 1) numGuestsInput.value = 1;
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
        refreshCapacityHint();
        syncTypeCards();
      }
    }

    roomTypeSelect.addEventListener('change', () => {
      const opt = roomTypeSelect.selectedOptions[0];
      resBeds.value = (opt && opt.dataset.beds) ? opt.dataset.beds : '';
      resPriceHidden.value = (opt && opt.dataset.price) ? opt.dataset.price : '';
      resPrice.value = formatPrice(resPriceHidden.value || 0);
      refreshCapacityHint();
      syncTypeCards();
      roomTilesWrap.innerHTML = '';
      const prevSelected = roomNumberHidden.value;
      if (prevSelected) selectedRoomNumbersSet.delete(prevSelected);
      roomNumberHidden.value = '';
      updateAggregateHiddenInputs();
      if (check_in && check_out && check_in.value && check_out.value && roomTypeSelect.value) {
        setTimeout(() => btnCheck.click(), 120);
      } else if (roomTypeSelect.value) {
        roomTilesWrap.innerHTML = '<div class="text-xs font-semibold text-ink/55 py-3 flex items-center gap-1.5"><span class="material-icons text-[15px] text-gold">event</span>Choose your check-in and check-out dates above to see open rooms.</div>';
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

    btnCheck.addEventListener('click', async () => {
      const roomType = roomTypeSelect.value;
      if (!roomType) { showFormError('Choose a room type first'); return; }
      if (!check_in.value || !check_out.value) { showFormError('Please choose check-in/out dates'); return; }
      
      // Beautiful animated SVG spinner
      roomTilesWrap.innerHTML = `
        <div class="flex flex-col items-center justify-center py-6 text-ink/50">
            <svg class="animate-spin h-7 w-7 text-gold mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-semibold uppercase tracking-wider text-ink/55">Retrieving open rooms...</span>
        </div>
      `;

      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const resp = await fetch('/rooms/available', {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-TOKEN': token},
          body: JSON.stringify({ room_type: roomType, check_in: check_in.value, check_out: check_out.value })
        });
        if (!resp.ok) throw new Error('Network response not ok');
        const data = await resp.json();
        renderRoomTilesForBlock(index, data.rooms || [], block);
      } catch (err) {
        roomTilesWrap.innerHTML = '<div class="text-sm font-bold text-ember-500 py-4">Error checking availability</div>';
        console.error(err);
      }
    });

    // One-tap room assignment: picks the first open room that no other
    // block has claimed. Acts as "fill if empty" — an existing pick stays.
    const btnAutopick = block.querySelector('.btn-autopick');
    btnAutopick && btnAutopick.addEventListener('click', () => {
      if (!roomTypeSelect.value) { showFormError('Pick a room style first, then we can assign an open room.'); return; }
      if (!check_in.value || !check_out.value) { showFormError('Choose your check-in and check-out dates first.'); return; }
      if (block.querySelector('.room-tile.selected')) return; // already assigned
      const tiles = block.querySelectorAll('.room-tile.available');
      if (!tiles.length) { showFormError('No open rooms are loaded for this block yet. Use Refresh or try other dates.'); return; }
      for (const t of tiles) {
        if (!selectedRoomNumbersSet.has(t.dataset.roomNumber)) { t.click(); return; }
      }
      showFormError('Every open room of this style is already selected in another block.');
    });

    btnRemove.addEventListener('click', () => {
      const rn = roomNumberHidden.value;
      if (rn) selectedRoomNumbersSet.delete(rn);
      // Opacity-only exit (reduced-motion safe). Bookkeeping waits for the
      // node to leave the DOM — the summary/aggregate selectors would still
      // count the fading block's inputs otherwise.
      block.style.transition = 'opacity 0.15s ease';
      block.style.opacity = '0';
      block.style.pointerEvents = 'none';
      setTimeout(() => {
        block.remove();
        updateAggregateHiddenInputs();
        generateBookingSummary();
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

    reservationContainer.appendChild(block);
    if (typeof Alpine !== 'undefined') Alpine.initTree(block);
    
    document.querySelectorAll('.btn-remove-block').forEach(b => b.style.display = document.querySelectorAll('.reservation-block').length > 1 ? 'inline-block' : 'none');
    
    // Auto check if dates exist
    if (check_in && check_out && check_in.value && check_out.value && roomTypeSelect.value) {
      setTimeout(() => btnCheck.click(), 100);
    }
    generateBookingSummary();
    return block;
  };

  // Returns { lostSelection } — the room number the guest had picked that is no
  // longer bookable after this render (or null). Callers use it to warn the guest.
  function renderRoomTilesForBlock(index, rooms, blockEl, opts = {}) {
    const tilesWrap = blockEl.querySelector('.room-tiles-wrapper');
    const hiddenInput = blockEl.querySelector('.res-room-number-hidden');
    const priorSelection = hiddenInput.value; // the guest's current pick, if any
    tilesWrap.innerHTML = '';
    if (!rooms.length) {
      tilesWrap.innerHTML = '<div class="text-sm font-semibold text-ink/55 py-4">No rooms available</div>';
      // Nothing open at all — drop any existing pick for this block.
      if (priorSelection) {
        hiddenInput.value = '';
        selectedRoomNumbersSet.delete(priorSelection);
        updateAggregateHiddenInputs();
        generateBookingSummary();
        return { lostSelection: priorSelection };
      }
      return { lostSelection: null };
    }
    const container = document.createElement('div');
    // Silent (live/Reverb) re-renders skip the entrance stagger — see app.css
    container.className = 'room-tiles' + (opts.silent ? ' no-anim' : '');

    let priorStillAvailable = false;

    rooms.forEach((r, i) => {
      const tile = document.createElement('div');
      tile.classList.add('room-tile');
      tile.style.setProperty('--i', i); // staggered entrance (see app.css)
      tile.innerText = r.room_number;
      tile.dataset.roomNumber = r.room_number;

      if (r.status === 'available') {
        tile.classList.add('available');

        // Re-apply the guest's existing selection so it survives a re-render
        // (manual "Refresh" or a real-time availability re-check).
        if (priorSelection && r.room_number === priorSelection) {
          priorStillAvailable = true;
          tile.classList.add('selected');
          const checkSpan = document.createElement('span');
          checkSpan.className = 'selected-check absolute top-1 right-1 text-[10px] font-black text-white material-icons';
          checkSpan.innerText = 'check';
          tile.appendChild(checkSpan);
        }

        tile.addEventListener('click', () => {
          const hiddenInput = blockEl.querySelector('.res-room-number-hidden');
          const prev = hiddenInput.value;
          if (prev === r.room_number) {
            hiddenInput.value = '';
            tile.classList.remove('selected');
            const check = tile.querySelector('.selected-check');
            if (check) check.remove();
            selectedRoomNumbersSet.delete(r.room_number);
          } else {
            if (selectedRoomNumbersSet.has(r.room_number)) { showFormError('That room is already selected in another block'); return; }
            const prevSelected = blockEl.querySelector('.room-tile.selected');
            if (prevSelected) {
              prevSelected.classList.remove('selected');
              const check = prevSelected.querySelector('.selected-check');
              if (check) check.remove();
              selectedRoomNumbersSet.delete(prevSelected.dataset.roomNumber);
            }
            hiddenInput.value = r.room_number;
            tile.classList.add('selected');
            
            // Add a check icon to selected tile
            const checkSpan = document.createElement('span');
            checkSpan.className = 'selected-check absolute top-1 right-1 text-[10px] font-black text-white material-icons';
            checkSpan.innerText = 'check';
            tile.appendChild(checkSpan);
            
            selectedRoomNumbersSet.add(r.room_number);
          }
          updateAggregateHiddenInputs();
          generateBookingSummary();
        });
      } else if (r.status === 'booked') { tile.classList.add('booked'); tile.title = 'Unavailable';
      } else if (r.status === 'cleaning') { tile.classList.add('cleaning'); tile.title = 'Cleaning';
      } else if (r.status === 'maintenance') { tile.classList.add('maintenance'); tile.title = 'Maintenance';
      } else { tile.classList.add('unavailable'); tile.title = 'Unavailable'; }
      container.appendChild(tile);
    });
    tilesWrap.appendChild(container);

    // The guest's pick disappeared from the open list (now booked/cleaning/
    // maintenance, or removed) — drop it and report it so the caller can warn.
    if (priorSelection && !priorStillAvailable) {
      hiddenInput.value = '';
      selectedRoomNumbersSet.delete(priorSelection);
      updateAggregateHiddenInputs();
      generateBookingSummary();
      return { lostSelection: priorSelection };
    }
    return { lostSelection: null };
  }

  function updateAggregateHiddenInputs() {
    const allSelected = Array.from(selectedRoomNumbersSet);
    if (room_numbers_hidden) room_numbers_hidden.value = allSelected.join(',');
    let totalSeniors = 0;
    document.querySelectorAll('.res-num-seniors').forEach(inp => { totalSeniors += parseInt(inp.value) || 0; });
    if (num_seniors_hidden) num_seniors_hidden.value = totalSeniors;
  }

  // ── Real-time availability (Reverb) ──────────────────────────────────────
  // The admin panel broadcasts `RoomStatusChanged` on the public `rooms`
  // channel whenever any room's status changes (maintenance, cleaning, a new
  // booking, a check-in). We listen for it and silently re-check every block's
  // open rooms, so a guest never sits on a room that was just closed.

  // Re-fetch one block's availability WITHOUT the loading spinner. Returns the
  // room number that got dropped (if the guest's pick is no longer bookable).
  async function recheckBlockAvailability(block) {
    const roomType = block.querySelector('.room-type-select')?.value;
    if (!roomType || !check_in.value || !check_out.value) return null;
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const resp = await fetch('/rooms/available', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ room_type: roomType, check_in: check_in.value, check_out: check_out.value })
      });
      if (!resp.ok) return null;
      const data = await resp.json();
      const result = renderRoomTilesForBlock(block.dataset.index, data.rooms || [], block, { silent: true });
      return result && result.lostSelection ? result.lostSelection : null;
    } catch (e) { console.error(e); return null; }
  }

  // Debounced so a burst of admin changes collapses into a single refresh.
  let recheckTimer = null;
  function recheckAllBlocksAvailability() {
    clearTimeout(recheckTimer);
    recheckTimer = setTimeout(async () => {
      updateTypeAvailability(); // refresh the per-type "Fully booked" badges too
      const dropped = [];
      for (const block of document.querySelectorAll('.reservation-block')) {
        const lost = await recheckBlockAvailability(block);
        if (lost) dropped.push(lost);
      }
      if (dropped.length) {
        showFormError('Room ' + dropped.join(', ') + ' was just closed by the front desk and removed from your selection. Please choose another.');
      }
    }, 400);
  }

  // Subscribe once. Guarded because Echo only exists if the Reverb assets loaded.
  // Room status changes AND bookings both affect availability, so listen to both.
  if (window.Echo) {
    window.Echo.channel('rooms').listen('.RoomStatusChanged', recheckAllBlocksAvailability);
    window.Echo.channel('bookings').listen('.BookingChanged', recheckAllBlocksAvailability);
  }

  // ── Progress rail (checkout header) ──────────────────────────────
  function setStep(key, done) {
    document.querySelector(`[data-progress-step="${key}"]`)?.classList.toggle('done', !!done);
  }

  function updateProgressRail() {
    if (!document.getElementById('checkoutProgress')) return;

    const datesDone = !!(check_in?.value && check_out?.value);

    const firstName = bookingForm?.querySelector('[name="first_name"]');
    const lastName  = bookingForm?.querySelector('[name="last_name"]');
    const phone     = bookingForm?.querySelector('[name="guest_phone"]');
    const detailsDone = !!(firstName?.value.trim() && lastName?.value.trim() && phone?.value.trim());

    const blocks = document.querySelectorAll('.reservation-block');
    let roomsDone = blocks.length > 0;
    let assigned = 0;
    blocks.forEach(b => {
      if (!b.querySelector('.res-room-number-hidden')?.value) roomsDone = false;
      assigned += parseInt(b.querySelector('.res-num-guests')?.value, 10) || 0;
    });
    if (assigned !== (parseInt(expectedGuestsInput?.value, 10) || 0)) roomsDone = false;

    setStep('dates', datesDone);
    setStep('details', detailsDone);
    setStep('rooms', roomsDone);
  }

  bookingForm?.addEventListener('input', function (e) {
    if (['first_name', 'last_name', 'guest_phone'].includes(e.target?.name)) updateProgressRail();
  });

  function showFormError(msg) {
    if (bookingFormAlert) {
      bookingFormAlert.innerText = msg;
      bookingFormAlert.classList.remove('d-none');
      // Restart the attention shake even when the banner is already visible
      bookingFormAlert.classList.remove('shake-now');
      void bookingFormAlert.offsetWidth;
      bookingFormAlert.classList.add('shake-now');
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      bookingFormAlert.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
    } else { alert(msg); }
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

  bookingForm && bookingForm.addEventListener('submit', function(e) {
    bookingFormAlert && bookingFormAlert.classList.add('d-none');
    const blocks = document.querySelectorAll('.reservation-block');
    if (!blocks || blocks.length === 0) { e.preventDefault(); showFormError('Please add at least one room type.'); return; }

    const expected = parseInt(expectedGuestsInput.value) || 0;
    let totalCapacity = 0;
    let totalSeniors = 0;
    let totalGuests = 0;
    const roomsSelected = [];

    for (const b of blocks) {
      const beds = parseInt(b.querySelector('.res-beds').value) || 0;
      const numSen = parseInt(b.querySelector('.res-num-seniors').value) || 0;
      const roomNum = b.querySelector('.res-room-number-hidden').value || '';
      const numGuests = parseInt(b.querySelector('.res-num-guests')?.value) || 0;

      if (!roomNum) { e.preventDefault(); showFormError('Please select a specific room from availability for each room type.'); return; }
      if (numSen > beds) { e.preventDefault(); showFormError('Seniors in a room cannot exceed that room\'s capacity.'); return; }
      if (numGuests > beds) { e.preventDefault(); showFormError('Guests in a room cannot exceed that room’s capacity.'); return; }
      if (numGuests < 1) { e.preventDefault(); showFormError('Each room must have at least 1 guest.'); return; }

      let totalMeals = 0;
      b.querySelectorAll('.meal-qty').forEach(inp => totalMeals += parseInt(inp.value) || 0);
      if (totalMeals !== numGuests) { e.preventDefault(); showFormError(`Meals in room ${roomNum} must equal the number of guests assigned to that room.`); return; }

      totalGuests += numGuests;
      totalCapacity += beds;
      totalSeniors += numSen;
      roomsSelected.push(roomNum);
    }

    if (totalGuests !== expected) { e.preventDefault(); showFormError(`Mismatch: total assigned guests (${totalGuests}) must equal expected guests (${expected}).`); return; }
    if (totalCapacity < expected) { e.preventDefault(); showFormError(`Total capacity ${totalCapacity} is less than expected ${expected}`); return; }
    if (totalSeniors > expected) { e.preventDefault(); showFormError('Total seniors exceed expected guests'); return; }
    if (num_seniors_hidden && parseInt(num_seniors_hidden.value) !== totalSeniors) { e.preventDefault(); showFormError(`Mismatch: total seniors in reservations (${totalSeniors}) must equal total seniors for the booking.`); return; }
    if (roomsSelected.length !== (new Set(roomsSelected)).size) { e.preventDefault(); showFormError('Duplicate rooms selected'); return; }

    if (room_numbers_hidden) room_numbers_hidden.value = roomsSelected.join(',');
    if (num_seniors_hidden) num_seniors_hidden.value = totalSeniors;

    // All client checks passed — lock both submit buttons against double-submit
    ['btnSubmitBooking', 'btnSubmitBookingMobile'].forEach(id => {
      const b = document.getElementById(id);
      if (!b) return;
      b.classList.add('opacity-80', 'pointer-events-none');
      b.innerHTML = '<svg class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span class="ml-2">Placing booking…</span>';
    });
  });

  check_in && check_in.addEventListener('change', function() {
    const checkInDate = new Date(this.value);
    if (checkInDate) {
      checkInDate.setDate(checkInDate.getDate() + 1);
      const minCheckOut = checkInDate.toISOString().split('T')[0];
      check_out.min = minCheckOut;
      if (check_out.value && check_out.value < minCheckOut) check_out.value = '';
    }
    syncDates();
    autoCheckAllBlocks();
  });

  check_out && check_out.addEventListener('change', function() {
    syncDates();
    autoCheckAllBlocks();
  });

  function autoCheckAllBlocks() {
    if (!check_in || !check_out || !check_in.value || !check_out.value) return;
    updateTypeAvailability();
    document.querySelectorAll('.reservation-block').forEach(block => {
      const select = block.querySelector('.room-type-select');
      if (select && select.value) {
        const btn = block.querySelector('.btn-check-availability');
        if (btn) setTimeout(() => btn.click(), 80);
      }
    });
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
            badge.className = 'type-card-avail absolute left-2 top-2 z-10 rounded-full px-2 py-0.5 text-[9px] font-bold shadow-sm ' +
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

  // Animate both the sidebar total and the mobile sticky-bar total
  function syncTotals(totalPrice) {
    animateCurrency(document.getElementById('summaryTotalAmount'), lastSummaryTotal, totalPrice);
    animateCurrency(document.getElementById('mobileTotalAmount'), lastSummaryTotal, totalPrice);
    lastSummaryTotal = totalPrice;
  }

  function generateBookingSummary() {
    const container = document.getElementById('summaryInvoice');
    if (!container) return;
    const mobileMeta = document.getElementById('mobileMetaLine');

    const checkInVal  = check_in?.value  || '';
    const checkOutVal = check_out?.value || '';

    if (!checkInVal || !checkOutVal) {
      container.innerHTML = `
        <div class="text-center py-10 text-ink/45">
            <span class="material-icons text-5xl mb-3 block text-white/10">event</span>
            <p class="font-semibold">Please select your stay dates.</p>
        </div>`;
      if (mobileMeta) mobileMeta.textContent = 'Pick your stay dates';
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
        <div class="text-center py-10 text-ink/45">
            <span class="material-icons text-5xl mb-3 block text-white/10">hotel</span>
            <p class="font-semibold">Please add a room to your allocation.</p>
        </div>`;
      if (mobileMeta) mobileMeta.textContent = 'Add a room to continue';
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
      const roomNum    = block.querySelector('.res-room-number-hidden')?.value || '--';
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
        ? `<p class="text-[11px] font-semibold text-gold/90 mt-0.5">Breakfast: ${mealChips.join(', ')}</p>`
        : '';

      roomRows += `
        <div class="sum-row flex items-start justify-between gap-3 py-3.5 border-b border-white/10 last:border-0" data-jump-block="${block.dataset.index}" title="Review this room">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-ink truncate">${typeName}</p>
            <p class="text-[11px] font-semibold text-ink/50 mt-0.5">Room ${roomNum} &middot; ${numGuests} guest(s)</p>
            <p class="text-[11px] font-semibold text-ink/50">${formatPrice(price)} &times; ${nights} night(s)</p>
            ${mealLine}
          </div>
          <span class="text-sm font-extrabold text-gold tabnum">${formatPrice(blockTotal)}</span>
        </div>`;
    });

    const discountNote = document.getElementById('request_discount')?.checked
      ? `
      <div class="mt-4 text-xs font-bold text-ink/80 bg-gold/10 rounded-xl px-4 py-3 border border-gold/30 leading-relaxed flex items-start gap-1.5">
          <span class="material-icons text-[16px] text-gold">info</span>
          <div>20% Senior/PWD discount will be calculated and applied at check-in upon verification.</div>
      </div>`
      : '';

    const guestsTotal = parseInt(expectedGuestsInput?.value) || 1;

    container.innerHTML = `
      <div class="grid grid-cols-3 items-center bg-white/5 ring-1 ring-white/10 rounded-2xl px-2 py-3 text-center">
        <div>
          <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-ink/45">Check-in</span>
          <span class="block text-[13px] font-extrabold text-ink mt-0.5">${fmtShortDate(checkInVal)}</span>
        </div>
        <div class="border-x border-white/10">
          <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-ink/45">Nights</span>
          <span class="block text-[13px] font-extrabold text-gold mt-0.5">${nights}</span>
        </div>
        <div>
          <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-ink/45">Check-out</span>
          <span class="block text-[13px] font-extrabold text-ink mt-0.5">${fmtShortDate(checkOutVal)}</span>
        </div>
      </div>
      <div class="mt-1.5 text-center text-[11px] font-semibold text-ink/45">${guestsTotal} guest${guestsTotal > 1 ? 's' : ''} expected</div>
      <div class="space-y-1 bg-white/5 p-4 rounded-2xl ring-1 ring-white/10 mt-3">
        ${roomRows}
      </div>
      <div class="mt-5 bg-emerald-deep p-5 rounded-2xl flex justify-between items-center">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-[0.24em] text-cream/60">Total Due</span>
          <span class="block text-[11px] font-semibold text-cream/45 mt-0.5">${blocks.length} room(s) for ${nights} night(s)</span>
        </div>
        <div class="font-display text-2xl text-gold tabnum" id="summaryTotalAmount">${formatPrice(totalPrice)}</div>
      </div>
      ${discountNote}
    `;
    if (mobileMeta) mobileMeta.textContent = `${blocks.length} room${blocks.length > 1 ? 's' : ''} · ${nights} night${nights > 1 ? 's' : ''}`;
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
        const fpOut = flatpickr(outEl, {
          dateFormat: 'Y-m-d',
          minDate: 'today',
          disableMobile: true,
          onChange: function() {
            outEl.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });

        const fpIn = flatpickr(inEl, {
          dateFormat: 'Y-m-d',
          minDate: 'today',
          disableMobile: true,
          onChange: function(dates) {
            if (!dates[0]) return;
            const nextDay = new Date(dates[0].getTime() + 86400000);
            fpOut.set('minDate', nextDay);
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
          disableMobile: true
        });
      }
    }
    
    syncDates();
    
    // Auto-add first block if from URL params
    if (initialRoomType) {
      if (expectedGuestsInput && initialGuests) {
        expectedGuestsInput.value = initialGuests;
        updateCapsDisplay();
      }
      addReservationBlock({ room_type: initialRoomType });
    } else if (reservationContainer.querySelectorAll('.reservation-block').length === 0) {
      addReservationBlock({});
    }

    // Badge the room-type cards immediately on load — uses the guest's dates if
    // deep-linked, otherwise a default (tonight) so "Fully booked" shows upfront.
    updateTypeAvailability();
  }, 100);

});
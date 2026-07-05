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

    function refreshCapacityHint() {
      if (!capacityHint) return;
      const beds = parseInt(resBeds.value, 10) || 0;
      capacityHint.textContent = beds
        ? 'Sleeps up to ' + beds + ' guest' + (beds > 1 ? 's' : '') + ' in this room'
        : '';
    }

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
      }
    }

    roomTypeSelect.addEventListener('change', () => {
      const opt = roomTypeSelect.selectedOptions[0];
      resBeds.value = (opt && opt.dataset.beds) ? opt.dataset.beds : '';
      resPriceHidden.value = (opt && opt.dataset.price) ? opt.dataset.price : '';
      resPrice.value = formatPrice(resPriceHidden.value || 0);
      refreshCapacityHint();
      roomTilesWrap.innerHTML = '';
      roomNumberHidden.value = '';
      if (check_in && check_out && check_in.value && check_out.value && roomTypeSelect.value) {
        setTimeout(() => btnCheck.click(), 120);
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
        <div class="flex flex-col items-center justify-center py-6 text-stone-400">
            <svg class="animate-spin h-7 w-7 text-emerald-deep mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-semibold uppercase tracking-wider text-stone-500">Retrieving open rooms...</span>
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
        roomTilesWrap.innerHTML = '<div class="text-sm font-bold text-ember-600 py-4">Error checking availability</div>';
        console.error(err);
      }
    });

    btnRemove.addEventListener('click', () => {
      const rn = roomNumberHidden.value;
      if (rn) selectedRoomNumbersSet.delete(rn);
      block.remove();
      updateAggregateHiddenInputs();
      generateBookingSummary();
      document.querySelectorAll('.btn-remove-block').forEach(b => b.style.display = document.querySelectorAll('.reservation-block').length > 1 ? 'inline-block' : 'none');
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

  function renderRoomTilesForBlock(index, rooms, blockEl) {
    const tilesWrap = blockEl.querySelector('.room-tiles-wrapper');
    tilesWrap.innerHTML = '';
    if (!rooms.length) {
      tilesWrap.innerHTML = '<div class="text-sm font-semibold text-stone-500 py-4">No rooms available</div>';
      return;
    }
    const container = document.createElement('div');
    container.className = 'room-tiles';

    rooms.forEach((r, i) => {
      const tile = document.createElement('div');
      tile.classList.add('room-tile');
      tile.style.setProperty('--i', i); // staggered entrance (see app.css)
      tile.innerText = r.room_number;
      tile.dataset.roomNumber = r.room_number;

      if (r.status === 'available') {
        tile.classList.add('available');
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
  }

  function updateAggregateHiddenInputs() {
    const allSelected = Array.from(selectedRoomNumbersSet);
    if (room_numbers_hidden) room_numbers_hidden.value = allSelected.join(',');
    let totalSeniors = 0;
    document.querySelectorAll('.res-num-seniors').forEach(inp => { totalSeniors += parseInt(inp.value) || 0; });
    if (num_seniors_hidden) num_seniors_hidden.value = totalSeniors;
  }

  function showFormError(msg) {
    if (bookingFormAlert) {
      bookingFormAlert.innerText = msg;
      bookingFormAlert.classList.remove('d-none');
      bookingFormAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else { alert(msg); }
  }

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
      b.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span class="ml-2">Placing booking…</span>';
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
    document.querySelectorAll('.reservation-block').forEach(block => {
      const select = block.querySelector('.room-type-select');
      if (select && select.value) {
        const btn = block.querySelector('.btn-check-availability');
        if (btn) setTimeout(() => btn.click(), 80);
      }
    });
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

    const checkInVal  = check_in?.value  || '';
    const checkOutVal = check_out?.value || '';

    if (!checkInVal || !checkOutVal) {
      container.innerHTML = `
        <div class="text-center py-10 text-stone-400">
            <span class="material-icons text-5xl mb-3 block text-stone-200">event</span>
            <p class="font-semibold">Please select your stay dates.</p>
        </div>`;
      syncTotals(0);
      return;
    }

    let nights = 1;
    const d1 = new Date(checkInVal);
    const d2 = new Date(checkOutVal);
    nights = Math.max(1, Math.round((d2 - d1) / 86400000));

    const blocks = document.querySelectorAll('.reservation-block');
    if (blocks.length === 0) {
      container.innerHTML = `
        <div class="text-center py-10 text-stone-400">
            <span class="material-icons text-5xl mb-3 block text-stone-200">hotel</span>
            <p class="font-semibold">Please add a room to your allocation.</p>
        </div>`;
      syncTotals(0);
      return;
    }

    let totalPrice = 0;
    let roomRows = '';

    blocks.forEach(block => {
      const typeSelect = block.querySelector('.room-type-select');
      const typeName   = typeSelect?.selectedOptions[0]?.text?.split('(')[0]?.trim() || 'Unknown Room';
      const price      = parseFloat(block.querySelector('.res-price-hidden')?.value) || 0;
      const roomNum    = block.querySelector('.res-room-number-hidden')?.value || '—';
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
        ? `<p class="text-[11px] font-semibold text-emerald mt-0.5">Breakfast: ${mealChips.join(', ')}</p>`
        : '';

      roomRows += `
        <div class="flex items-start justify-between gap-3 py-3.5 border-b border-emerald-deep/10 last:border-0">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-stone-800 truncate">${typeName}</p>
            <p class="text-[11px] font-semibold text-stone-500 mt-0.5">Room ${roomNum} &middot; ${numGuests} guest(s)</p>
            <p class="text-[11px] font-semibold text-stone-500">${formatPrice(price)} &times; ${nights} night(s)</p>
            ${mealLine}
          </div>
          <span class="text-sm font-extrabold text-emerald-deep tabnum">${formatPrice(blockTotal)}</span>
        </div>`;
    });

    const discountNote = document.getElementById('request_discount')?.checked
      ? `
      <div class="mt-4 text-xs font-bold text-ink/75 bg-gold-soft/30 rounded-xl px-4 py-3 border border-gold/40 leading-relaxed flex items-start gap-1.5">
          <span class="material-icons text-[16px] text-gold">info</span>
          <div>20% Senior/PWD discount will be calculated and applied at check-in upon verification.</div>
      </div>`
      : '';

    const guestsTotal = parseInt(expectedGuestsInput?.value) || 1;

    container.innerHTML = `
      <div class="grid grid-cols-3 items-center bg-cream ring-1 ring-emerald-deep/10 rounded-2xl px-2 py-3 text-center">
        <div>
          <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Check-in</span>
          <span class="block text-[13px] font-extrabold text-ink mt-0.5">${fmtShortDate(checkInVal)}</span>
        </div>
        <div class="border-x border-emerald-deep/10">
          <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Nights</span>
          <span class="block text-[13px] font-extrabold text-emerald-deep mt-0.5">${nights}</span>
        </div>
        <div>
          <span class="block text-[9px] font-bold uppercase tracking-[0.22em] text-emerald/70">Check-out</span>
          <span class="block text-[13px] font-extrabold text-ink mt-0.5">${fmtShortDate(checkOutVal)}</span>
        </div>
      </div>
      <div class="mt-1.5 text-center text-[11px] font-semibold text-stone-400">${guestsTotal} guest${guestsTotal > 1 ? 's' : ''} expected</div>
      <div class="space-y-1 bg-cream p-4 rounded-2xl ring-1 ring-emerald-deep/5 mt-3">
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
    syncTotals(totalPrice);
  }
  
  window.generateBookingSummary = generateBookingSummary;

  // Initialize page
  setTimeout(() => {
    // initialize flatpickr
    if (typeof flatpickr !== 'undefined') {
      flatpickr('.flatpickr-date', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disableMobile: true
      });
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
  }, 100);

});
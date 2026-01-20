document.addEventListener('DOMContentLoaded', function () {
  // DOM refs
  const bookingModalEl = document.getElementById('bookingModal');
  const bookingForm = document.getElementById('bookingForm');


  const openButtons = document.querySelectorAll('.btn-open-booking');
  const check_in = document.getElementById('check_in');
  const check_out = document.getElementById('check_out');
  const room_numbers_hidden = document.getElementById('selected_room_number');

  const bookingFormAlert = document.getElementById('bookingFormAlert');


  const maxSeniorsLabel = document.getElementById('maxSeniorsLabel');
  const expectedGuestsInput = document.getElementById('expected_guests');

  // template/container refs
  const reservationContainer = document.getElementById('reservationBlocksContainer');
  const tpl = document.getElementById('reservationBlockTemplate');
  const btnAddRoom = document.getElementById('btnAddRoom');

  // Hidden DB inputs
  const num_seniors_hidden = document.getElementById('num_seniors');


  // Utility: format price
  function formatPrice(num) { return '₱' + Number(num).toLocaleString(); }


  // Prevent selecting past dates
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

  // caps for seniors display
  function updateCapsDisplay() {
    const cap = getExpectedGuestsCap();
    if (maxSeniorsLabel) maxSeniorsLabel.textContent = cap;
    // also adjust per-block senior max inputs later
  }

  // helper: expected guests value
  function getExpectedGuestsCap() {
    if (expectedGuestsInput) {
    const v = parseInt(expectedGuestsInput.value);
    return (isNaN(v) || v < 1) ? 1 : v;
    }
    return 1;
  }

  expectedGuestsInput && expectedGuestsInput.addEventListener('input', () => {
    updateCapsDisplay();
  });

  // reset modal state
  function resetModalState() {
    // clear blocks
    reservationContainer && (reservationContainer.innerHTML = '');
    selectedRoomNumbersSet.clear();
    if (room_numbers_hidden) room_numbers_hidden.value = '';
    if (num_seniors_hidden) num_seniors_hidden.value = 0;
    if (expectedGuestsInput) expectedGuestsInput.value = 1;
    updateCapsDisplay();
  }

  function bindMealInputs(block) {
    const guestInput = block.querySelector('.res-num-guests');
    const mealInputs = block.querySelectorAll('.meal-qty');

    function enforce() {
      const maxGuests = parseInt(guestInput.value, 10) || 0;
      let totalMeals = 0;

      mealInputs.forEach(input => {
        let val = parseInt(input.value, 10) || 0;
        totalMeals += val;
      });

      // If meals exceed guests, trim the *last edited* field
      if (totalMeals > maxGuests) {
        let excess = totalMeals - maxGuests;

        // find the active (last changed) element
        const active = document.activeElement;
        if (active && active.classList.contains('meal-qty')) {
          let currentVal = parseInt(active.value, 10) || 0;
          active.value = Math.max(0, currentVal - excess);
        } else {
          // fallback: trim from the last input
          const last = mealInputs[mealInputs.length - 1];
          let currentVal = parseInt(last.value, 10) || 0;
          last.value = Math.max(0, currentVal - excess);
        }
      }
    }

    mealInputs.forEach(input => {
      input.addEventListener('input', enforce);
    });

    guestInput.addEventListener('input', enforce);
    enforce(); // run once on init
  }
  // --- Block system ---
  let nextBlockIndex = 0;
  const selectedRoomNumbersSet = new Set();

  function createReservationBlock(prefill = {}) {
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

    if (numGuestsInput) {
      numGuestsInput.addEventListener('input', () => {
        const cap = parseInt(resBeds.value) || 0;
        let v = parseInt(numGuestsInput.value) || 0;
        if (v > cap) numGuestsInput.value = cap;
        if (v < 1) numGuestsInput.value = 1;
      });
    }

    // prefill if provided
    if (prefill.room_type) {
      roomTypeSelect.value = prefill.room_type;
      const opt = roomTypeSelect.selectedOptions[0];

      if (opt) {
        resBeds.value = opt.dataset.beds || '';
        resPriceHidden.value = opt.dataset.price || '';
        resPrice.value = formatPrice(opt.dataset.price || '0');
      }
    }

    // update beds/price on room type change
    roomTypeSelect.addEventListener('change', () => {
      const opt = roomTypeSelect.selectedOptions[0];
      resBeds.value = (opt && opt.dataset.beds) ? opt.dataset.beds : '';
      resPriceHidden.value = (opt && opt.dataset.price) ? opt.dataset.price : '';
      resPrice.value = formatPrice(resPriceHidden.value || 0);
      roomTilesWrap.innerHTML = '';
      roomNumberHidden.value = '';
    });

    // limit per-block seniors to room capacity
    numSeniorsInput.addEventListener('input', () => {
      const cap = parseInt(resBeds.value) || 0;
      let v = parseInt(numSeniorsInput.value) || 0;
      if (v > cap) numSeniorsInput.value = cap;
      if (v < 0) numSeniorsInput.value = 0;
      updateAggregateHiddenInputs();
    });

    // per-block availability check
    btnCheck.addEventListener('click', async () => {
      const roomType = roomTypeSelect.value;
      if (!roomType) { showFormError('Choose a room type first'); return; }
      if (!check_in.value || !check_out.value) { showFormError('Please choose check-in/out dates'); return; }
      roomTilesWrap.innerHTML = '<div class="note">Checking availability...</div>';
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
        roomTilesWrap.innerHTML = '<div class="note text-danger">Error checking availability</div>';
        console.error(err);
      }
    });

    // remove block
    btnRemove.addEventListener('click', () => {
      const rn = roomNumberHidden.value;
      if (rn) selectedRoomNumbersSet.delete(rn);
      block.remove();
      updateAggregateHiddenInputs();
      // show/hide remove buttons depending on blocks count
      document.querySelectorAll('.btn-remove-block').forEach(b => b.style.display = document.querySelectorAll('.reservation-block').length > 1 ? 'inline-block' : 'none');
    });

    // bind per-block meal inputs
    const mealInputs = block.querySelectorAll('.meal-qty');
    if (mealInputs.length) {
      bindMealInputs(block);  // new function that enforces meal ≤ num_guests
    }

    reservationContainer.appendChild(block);
    // show remove only if >1 blocks
    document.querySelectorAll('.btn-remove-block').forEach(b => b.style.display = document.querySelectorAll('.reservation-block').length > 1 ? 'inline-block' : 'none');


    return block;
  }

  function renderRoomTilesForBlock(index, rooms, blockEl) {
    const tilesWrap = blockEl.querySelector('.room-tiles-wrapper');
    tilesWrap.innerHTML = '';

    if (!rooms.length) {
      tilesWrap.innerHTML = '<div class="note">No rooms</div>';
      return;
    }

    const container = document.createElement('div');
    container.className = 'room-tiles';

    rooms.forEach(r => {
      const tile = document.createElement('div');
      tile.classList.add('room-tile');
      tile.innerText = r.room_number;
      tile.dataset.roomNumber = r.room_number;

      // Assign classes and interactivity based on status
      if (r.status === 'available') {
        tile.classList.add('available');
        tile.addEventListener('click', () => {
          const hiddenInput = blockEl.querySelector('.res-room-number-hidden');
          const prev = hiddenInput.value;

          // Deselect if same room clicked
          if (prev === r.room_number) {
            hiddenInput.value = '';
            tile.classList.remove('selected');
            selectedRoomNumbersSet.delete(r.room_number);
          } else {
            // Prevent duplicate across blocks
            if (selectedRoomNumbersSet.has(r.room_number)) {
              alert('That room is already selected in another block');
              return;
            }
            // Clear previous selection in this block
            const prevSelected = blockEl.querySelector('.room-tile.selected');
            if (prevSelected) {
              prevSelected.classList.remove('selected');
              selectedRoomNumbersSet.delete(prevSelected.dataset.roomNumber);
            }
            // Set new selection
            hiddenInput.value = r.room_number;
            tile.classList.add('selected');
            selectedRoomNumbersSet.add(r.room_number);
          }

          updateAggregateHiddenInputs();
        });
      } else if (r.status === 'booked') {
        tile.classList.add('booked');
        tile.title = 'Unavailable for selected dates';
      } else if (r.status === 'cleaning') {
        tile.classList.add('cleaning');
        tile.title = 'Room under cleaning';
      } else if (r.status === 'maintenance') {
        tile.classList.add('maintenance');
        tile.title = 'Room under maintenance';
      } else {
        // fallback if unknown status
        tile.classList.add('unavailable');
        tile.title = 'Room unavailable';
      }

      container.appendChild(tile);
    });

    tilesWrap.appendChild(container);
  }

  function updateAggregateHiddenInputs() {
    // room_numbers CSV
    const allSelected = Array.from(selectedRoomNumbersSet);
    if (room_numbers_hidden) room_numbers_hidden.value = allSelected.join(',');

    // total seniors across blocks
    let totalSeniors = 0;
    document.querySelectorAll('.res-num-seniors').forEach(inp => { totalSeniors += parseInt(inp.value) || 0; });
    if (num_seniors_hidden) num_seniors_hidden.value = totalSeniors;

  }

  function showFormError(msg) {
    if (bookingFormAlert) {
    bookingFormAlert.innerText = msg;
    bookingFormAlert.classList.remove('d-none');
    } else {
    alert(msg);
    }
  }

  // initial block creation when opening modal
  openButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      resetModalState();
      // create one reservation block and prefill room type from button data
      const dataTypeId = btn.dataset.roomTypeId || '';
      const firstBlock = createReservationBlock({ room_type: dataTypeId });
      // show modal
      if (bookingModalEl) {
        const modal = new bootstrap.Modal(bookingModalEl);
        modal.show();
      }
    });
  });

  // add-room button
  btnAddRoom && btnAddRoom.addEventListener('click', () => createReservationBlock({}));

  // form submit validation
  bookingForm && bookingForm.addEventListener('submit', function(e) {
    bookingFormAlert && bookingFormAlert.classList.add('d-none');

    // ensure blocks exist
    const blocks = document.querySelectorAll('.reservation-block');
    if (!blocks || blocks.length === 0) { e.preventDefault(); showFormError('Please add at least one room.'); return; }


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

      if (!roomNum) { e.preventDefault(); showFormError('Please select a room in each reservation block.'); return; }
      // per-room senior <= beds
      if (numSen > beds) { e.preventDefault(); showFormError('Seniors in a room cannot exceed that room\'s capacity.'); return; }

      if (numGuests > beds) {
        e.preventDefault();
        showFormError('Guests in a room cannot exceed that room’s capacity.');
        return;
      }
      if (numGuests < 1) {
        e.preventDefault();
        showFormError('Each room must have at least 1 guest.');
        return;
      }

      // inside for (const b of blocks)
      let totalMeals = 0;
      b.querySelectorAll('.meal-qty').forEach(inp => totalMeals += parseInt(inp.value) || 0);

      if (totalMeals !== numGuests) {
        e.preventDefault();
        showFormError(`Meals in room ${roomNum || ''} must equal the number of guests assigned to that room.`);
        return;
      }

      totalGuests += numGuests;
      totalCapacity += beds;
      totalSeniors += numSen;
      roomsSelected.push(roomNum);
    }

    if (totalGuests !== expected) {
      e.preventDefault();
      showFormError(`Mismatch: total assigned guests (${totalGuests}) must equal expected guests (${expected}).`);
      return;
    }

    if (totalCapacity < expected) { e.preventDefault(); showFormError(`Total capacity ${totalCapacity} is less than expected ${expected}`); return; }
    if (totalSeniors > expected) { e.preventDefault(); showFormError('Total seniors exceed expected guests'); return; }

    if (num_seniors_hidden && parseInt(num_seniors_hidden.value) !== totalSeniors) {
      e.preventDefault();
      showFormError(`Mismatch: total seniors in reservations (${totalSeniors}) must equal total seniors for the booking (${num_seniors_hidden.value}).`);
      return;
    }


    // duplicates check
    if (roomsSelected.length !== (new Set(roomsSelected)).size) { e.preventDefault(); showFormError('Duplicate rooms selected'); return; }


    // final set hidden fields
    if (room_numbers_hidden) room_numbers_hidden.value = roomsSelected.join(',');
    if (num_seniors_hidden) num_seniors_hidden.value = totalSeniors;


    // allow submit to proceed
  });

  // helper: enforce checkout min date based on checkin
  check_in && check_in.addEventListener('change', function() {
    const checkInDate = new Date(this.value);
    if (checkInDate) {
      checkInDate.setDate(checkInDate.getDate() + 1);
      const minCheckOut = checkInDate.toISOString().split('T')[0];
      check_out.min = minCheckOut;
      if (check_out.value && check_out.value < minCheckOut) check_out.value = '';
    }
  });
});
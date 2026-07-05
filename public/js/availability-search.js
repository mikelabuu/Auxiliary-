/**
 * Landing-page live availability search.
 *
 * - Initialises the hero widget's flatpickr date pickers (check-in / check-out)
 * - "Search Rooms" calls POST /rooms/availability-summary and paints each
 *   room card with a live pill: open / only-N-left / fully booked
 * - Fully-booked types get dimmed with their Book button disabled
 */
document.addEventListener('DOMContentLoaded', function () {
  const inEl  = document.getElementById('widget_check_in');
  const outEl = document.getElementById('widget_check_out');
  const searchBtn = document.getElementById('btnSearchRooms');
  const banner = document.getElementById('availabilityBanner');
  const bannerText = document.getElementById('availabilityBannerText');
  const clearBtn = document.getElementById('btnClearAvailability');
  const guestsInput = document.getElementById('widget_guests');

  if (!inEl || !outEl || !searchBtn) return; // welcome page only

  /* ---------- Date pickers ---------- */
  let fpOut = null;
  let fpIn = null;

  if (typeof flatpickr !== 'undefined') {
    fpOut = flatpickr(outEl, {
      dateFormat: 'Y-m-d',
      minDate: new Date(Date.now() + 86400000),
      disableMobile: true,
    });

    fpIn = flatpickr(inEl, {
      dateFormat: 'Y-m-d',
      minDate: 'today',
      disableMobile: true,
      onChange: function (dates) {
        if (!dates[0]) return;
        const nextDay = new Date(dates[0].getTime() + 86400000);
        fpOut.set('minDate', nextDay);
        if (outEl.value && new Date(outEl.value) < nextDay) fpOut.clear();
        // Natural flow: picking check-in slides straight into check-out
        if (!outEl.value) setTimeout(() => fpOut.open(), 120);
      },
    });
  }

  /* ---------- Helpers ---------- */
  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function cards() {
    return document.querySelectorAll('[data-room-card]');
  }

  function scrollToRooms() {
    document.getElementById('rooms')?.scrollIntoView({ behavior: 'smooth' });
  }

  function fmtDate(iso) {
    const d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
  }

  function setPill(card, html) {
    const slot = card.querySelector('[data-avail-slot]');
    if (slot) slot.innerHTML = html;
  }

  function setCardFull(card, isFull) {
    const img = card.querySelector('[data-card-image]');
    const btn = card.querySelector('[data-book-btn]');
    const label = card.querySelector('[data-book-label]');
    if (label && label.dataset.original === undefined) {
      label.dataset.original = label.textContent;
    }
    if (isFull) {
      img && img.classList.add('grayscale', 'opacity-70');
      if (btn) btn.disabled = true;
      if (label) label.textContent = 'Fully Booked';
    } else {
      img && img.classList.remove('grayscale', 'opacity-70');
      if (btn) btn.disabled = false;
      if (label) label.textContent = label.dataset.original || 'Book';
    }
  }

  function clearResults() {
    cards().forEach(card => {
      setPill(card, '');
      setCardFull(card, false);
    });
    banner && banner.classList.add('hidden');
    window.LAST_AVAILABILITY = null;
  }

  function shakeWidget() {
    const widget = document.getElementById('bookingCapsule') || inEl.closest('div');
    if (!widget) return;
    widget.classList.remove('animate-shake');
    void widget.offsetWidth; // restart animation
    widget.classList.add('animate-shake');
    if (fpIn && !inEl.value) fpIn.open();
    else if (fpOut && !outEl.value) fpOut.open();
  }

  /* ---------- Search ---------- */
  let searching = false;

  async function runSearch() {
    if (searching) return;
    const checkIn = inEl.value;
    const checkOut = outEl.value;

    if (!checkIn || !checkOut) {
      shakeWidget();
      return;
    }

    searching = true;
    scrollToRooms();

    // Skeleton pills while loading
    cards().forEach(card => {
      setPill(card, '<span class="avail-pill avail-pill--loading"><span class="avail-shimmer"></span>Checking…</span>');
    });

    const labelHost = document.getElementById('btnSearchRoomsLabel');
    const originalLabel = labelHost ? labelHost.innerHTML : '';
    if (labelHost) {
      labelHost.innerHTML = '<svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Searching…';
    }

    try {
      const resp = await fetch('/rooms/availability-summary', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ check_in: checkIn, check_out: checkOut }),
      });
      if (!resp.ok) throw new Error('availability request failed: ' + resp.status);
      const data = await resp.json();

      window.LAST_AVAILABILITY = data;

      (data.summary || []).forEach(row => {
        const card = document.querySelector('[data-room-card="' + row.room_type + '"]');
        if (!card) return;

        if (row.available <= 0) {
          setPill(card, '<span class="avail-pill avail-pill--full"><span class="material-icons text-[13px]">event_busy</span>Fully booked</span>');
          setCardFull(card, true);
        } else if (row.available <= 2) {
          setPill(card, '<span class="avail-pill avail-pill--low"><span class="material-icons text-[13px]">local_fire_department</span>Only ' + row.available + ' left</span>');
          setCardFull(card, false);
        } else {
          setPill(card, '<span class="avail-pill avail-pill--open"><span class="material-icons text-[13px]">check_circle</span>' + row.available + ' rooms open</span>');
          setCardFull(card, false);
        }
      });

      if (banner && bannerText) {
        const guests = parseInt(guestsInput?.value || '1', 10);
        const nightsTxt = data.nights + ' night' + (data.nights > 1 ? 's' : '');
        bannerText.textContent = 'Live availability · ' + fmtDate(data.check_in) + ' → ' + fmtDate(data.check_out) + ' · ' + nightsTxt + ' · ' + guests + ' guest' + (guests > 1 ? 's' : '');
        banner.classList.remove('hidden');
      }
    } catch (err) {
      console.error(err);
      cards().forEach(card => setPill(card, ''));
      if (banner && bannerText) {
        bannerText.textContent = 'Could not check availability right now — please try again.';
        banner.classList.remove('hidden');
      }
    } finally {
      searching = false;
      if (labelHost) labelHost.innerHTML = originalLabel;
    }
  }

  searchBtn.addEventListener('click', runSearch);

  clearBtn && clearBtn.addEventListener('click', function () {
    fpIn ? fpIn.clear() : (inEl.value = '');
    fpOut ? fpOut.clear() : (outEl.value = '');
    clearResults();
  });
});

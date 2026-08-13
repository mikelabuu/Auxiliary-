/**
 * Landing-page live availability search.
 *
 * - Initialises the hero widget's flatpickr date pickers (check-in / check-out)
 * - "Search Rooms" calls POST /rooms/availability-summary and paints each
 *   room card with a live pill: open / only-N-left / fully booked
 * - Fully-booked types get dimmed with their Book button disabled
 */

// Inline lucide SVG for the three availability pills.
//
// These used to be Font Awesome <i> tags, and they were the ONLY Font Awesome
// on the landing page — every other icon here is inline SVG from
// x-booking.ui.icon. Three glyphs were therefore pulling the 88 KB icon
// stylesheet plus the 117 KB fa-solid-900.woff2 onto every single visit.
//
// The layout's `defer_icons` note argued this was cheap because the pills
// "in most visits never appear". That stopped being true: the search below
// runs unconditionally on DOMContentLoaded so the cards show availability
// without the guest touching the date picker, which means the pills — and so
// the font — were guaranteed on every load. Worse, `defer_icons` makes the
// sheet non-blocking, so the glyphs arrived late and popped in.
//
// Drawn to the same 24x24 lucide grid and stroke weight as the rest of the
// page's icons, sized to the 13px the <i> tags used.
const PILL_ICONS = {
  'flame':        '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
  'circle-check': '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
  'calendar-x':   '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m14 14-4 4"/><path d="m10 14 4 4"/>',
};

function pillIcon(name) {
  return '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" '
       + 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" '
       + 'style="flex:none">' + (PILL_ICONS[name] || '') + '</svg>';
}

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
      onChange: function (dates) {
        updateNightsChip();
        if (inEl.value && outEl.value) {
          runSearch({ silent: true });
        }
      }
    });

    fpIn = flatpickr(inEl, {
      dateFormat: 'Y-m-d',
      minDate: 'today',
      disableMobile: true,
      onChange: function (dates) {
        if (!dates[0]) return;
        const nextDay = new Date(dates[0].getTime() + 86400000);
        fpOut.set('minDate', nextDay);
        if (outEl.value && new Date(outEl.value) < nextDay) {
          fpOut.clear();
        }
        updateNightsChip();
        // Natural flow: picking check-in slides straight into check-out
        if (!outEl.value) {
          setTimeout(() => fpOut.open(), 120);
        } else {
          runSearch({ silent: true });
        }
      },
    });
  }

  // Capsule nights summary — "3 nights · Jul 20 → Jul 23" under the fields
  // once both dates are set; hides again when either side is cleared.
  function updateNightsChip() {
    const chip = document.getElementById('capsuleNights');
    const txt = document.getElementById('capsuleNightsText');
    if (!chip || !txt) return;
    if (inEl.value && outEl.value) {
      const nights = Math.round((new Date(outEl.value) - new Date(inEl.value)) / 86400000);
      if (nights > 0) {
        txt.textContent = nights + ' night' + (nights > 1 ? 's' : '') + ' · ' + fmtDate(inEl.value) + ' → ' + fmtDate(outEl.value);
        chip.hidden = false;
        return;
      }
    }
    chip.hidden = true;
  }

  /* ---------- Helpers ---------- */
  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function cards() {
    return document.querySelectorAll('[data-room-card]');
  }

  function roomItems() {
    return document.querySelectorAll('[data-room-item]');
  }

  // Constrain the room grid to types that can seat the requested party size.
  // Delegates to the shared RoomFilter (room-filters.js) so the browse pills
  // and this guest floor combine instead of overwriting each other's .hidden.
  // Falls back to a standalone toggle if that script isn't present.
  function applyGuestFilter(guests) {
    const party = Math.max(1, parseInt(guests || guestsInput?.value || '1', 10));

    // Keep the live-availability banner's guest count in sync when the party
    // size is changed after a search has already painted results.
    const data = window.LAST_AVAILABILITY;
    if (data && banner && bannerText && !banner.classList.contains('hidden')) {
      const nightsTxt = data.nights + ' night' + (data.nights > 1 ? 's' : '');
      bannerText.textContent = 'Live availability · ' + fmtDate(data.check_in) + ' → ' + fmtDate(data.check_out) + ' · ' + nightsTxt + ' · ' + party + ' guest' + (party > 1 ? 's' : '');
    }

    if (window.RoomFilter && typeof window.RoomFilter.setGuestFloor === 'function') {
      window.RoomFilter.setGuestFloor(party);
      return;
    }

    const items = roomItems();
    if (!items.length) return;
    let anyVisible = false;
    items.forEach(item => {
      const beds = parseInt(item.getAttribute('data-beds') || '0', 10);
      const fits = beds >= party;
      item.classList.toggle('hidden', !fits);
      if (fits) anyVisible = true;
    });
    const empty = document.getElementById('roomFilterEmpty');
    if (empty) empty.classList.toggle('hidden', anyVisible);
  }
  window.__applyGuestFilter = applyGuestFilter;

  function clearGuestFilter() {
    if (window.RoomFilter && typeof window.RoomFilter.setGuestFloor === 'function') {
      window.RoomFilter.setGuestFloor(0);
      return;
    }
    roomItems().forEach(item => item.classList.remove('hidden'));
    const empty = document.getElementById('roomFilterEmpty');
    if (empty) empty.classList.add('hidden');
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
    clearGuestFilter();
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

  // Guest's chosen dates, or a sensible default (tonight → tomorrow) so the room
  // cards can show availability before anyone touches the date picker.
  function effectiveDates() {
    if (inEl.value && outEl.value) {
      return { checkIn: inEl.value, checkOut: outEl.value, explicit: true };
    }
    const pad = n => String(n).padStart(2, '0');
    const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const today = new Date();
    const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
    return { checkIn: iso(today), checkOut: iso(tomorrow), explicit: false };
  }

  async function runSearch(opts = {}) {
    // `silent` = a background refresh (initial page load or a Reverb push): no
    // scroll-to, skeleton pills, or button spinner.
    const silent = opts.silent === true;
    if (searching) return;

    const { checkIn, checkOut, explicit } = effectiveDates();

    // A manual click needs real dates from the widget; a silent/default refresh
    // falls back to tonight so the cards always show something.
    if (!explicit && !silent) {
      shakeWidget();
      return;
    }

    searching = true;
    if (!silent) scrollToRooms();

    // Skeleton pills while loading (interactive search only)
    if (!silent) {
      cards().forEach(card => {
        setPill(card, '<span class="avail-pill avail-pill--loading"><span class="avail-shimmer"></span>Checking…</span>');
      });
    }

    const labelHost = silent ? null : document.getElementById('btnSearchRoomsLabel');
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
          setPill(card, '<span class="avail-pill avail-pill--full">' + pillIcon('calendar-x') + 'Fully booked</span>');
        } else if (row.available <= 2) {
          setPill(card, '<span class="avail-pill avail-pill--low">' + pillIcon('flame') + 'Only ' + row.available + ' left</span>');
        } else {
          setPill(card, '<span class="avail-pill avail-pill--open">' + pillIcon('circle-check') + row.available + ' rooms open</span>');
        }
        // Fully booked → lock the Book button. If the guest wants different
        // dates, picking them re-runs the search and re-enables the card.
        setCardFull(card, row.available <= 0);
      });

      // Only show room types that can seat the requested party size.
      const guests = parseInt(guestsInput?.value || '1', 10);
      applyGuestFilter(guests);

      // The "Live availability · dates" banner only makes sense once the guest
      // has actually searched — a default (tonight) paint stays quiet.
      if (explicit && banner && bannerText) {
        const nightsTxt = data.nights + ' night' + (data.nights > 1 ? 's' : '');
        bannerText.textContent = 'Live availability · ' + fmtDate(data.check_in) + ' → ' + fmtDate(data.check_out) + ' · ' + nightsTxt + ' · ' + guests + ' guest' + (guests > 1 ? 's' : '');
        banner.classList.remove('hidden');
      }
    } catch (err) {
      console.error(err);
      if (!silent) {
        cards().forEach(card => setPill(card, ''));
        if (banner && bannerText) {
          bannerText.textContent = 'Could not check availability right now — please try again.';
          banner.classList.remove('hidden');
        }
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
    updateNightsChip();
    clearResults();
  });

  // ── Real-time availability (Reverb) ──────────────────────────────────────
  // Once a guest has searched, keep the per-type pills ("Fully booked" / "N
  // left") fresh as rooms are closed or booked elsewhere — re-run the summary
  // silently on the same broadcasts the admin panel emits. Only fires when a
  // search is active (dates chosen), and is debounced against bursts.
  let liveTimer = null;
  function refreshLive() {
    if (!window.LAST_AVAILABILITY && !(inEl.value && outEl.value)) return;
    clearTimeout(liveTimer);
    liveTimer = setTimeout(function () { runSearch({ silent: true }); }, 400);
  }
  if (window.Echo) {
    window.Echo.channel('rooms').listen('.RoomStatusChanged', refreshLive);
    window.Echo.channel('bookings').listen('.BookingChanged', refreshLive);
  }

  // Paint availability on the cards immediately (default: tonight), so guests
  // see "Fully booked" / "Only N left" without touching the date picker. This
  // also primes LAST_AVAILABILITY so the real-time refresh above starts working.
  runSearch({ silent: true });
});

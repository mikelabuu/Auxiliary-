/**
 * Room capacity filter for the landing page "Living Quarters" section.
 *
 * This is the single source of truth for which room-type cards are visible.
 * Two inputs are combined so they never fight over the same `.hidden` class:
 *   1. The browse pills in #roomFilters (data-filter: all | 1-2 | 3-4 | 5plus | premium)
 *   2. A guest-capacity floor set by the hero availability search
 *      (window.RoomFilter.setGuestFloor(n)) — a card must seat at least n guests.
 *
 * Cards opt in with [data-room-item] plus data-beds / data-premium attributes
 * and fade/scale smoothly when filtered.
 */
document.addEventListener('DOMContentLoaded', function () {
  const items = document.querySelectorAll('[data-room-item]');
  if (!items.length) return; // rooms grid absent

  const bar = document.getElementById('roomFilters');
  const empty = document.getElementById('roomFilterEmpty');

  let activePill = 'all';
  let minBeds = 0; // capacity floor from the hero guest search (0 = no floor)

  function matchesPill(item, filter) {
    const beds = parseInt(item.dataset.beds, 10) || 0;
    const premium = item.dataset.premium === '1';
    switch (filter) {
      case '1-2':     return beds >= 1 && beds <= 2;
      case '3-4':     return beds >= 3 && beds <= 4;
      case '5plus':   return beds >= 5;
      case 'premium': return premium;
      default:        return true;
    }
  }

  function apply() {
    let shown = 0;
    items.forEach(item => {
      const beds = parseInt(item.dataset.beds, 10) || 0;
      const visible = matchesPill(item, activePill) && beds >= minBeds;
      if (visible) {
        shown++;
        item.classList.remove('hidden');
        // next frame so the transition from opacity-0 actually plays
        requestAnimationFrame(() => item.classList.remove('opacity-0', 'scale-95'));
      } else {
        item.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
          // guard: only hide if it wasn't re-shown by a quicker click
          if (item.classList.contains('opacity-0')) item.classList.add('hidden');
        }, 280);
      }
    });
    if (empty) empty.classList.toggle('hidden', shown > 0);
  }

  if (bar) {
    const pills = bar.querySelectorAll('[data-filter]');
    pills.forEach(pill => {
      pill.addEventListener('click', () => {
        pills.forEach(p => {
          p.classList.toggle('active', p === pill);
          p.setAttribute('aria-pressed', p === pill ? 'true' : 'false');
        });
        activePill = pill.dataset.filter || 'all';
        apply();
      });
    });
  }

  // Shared authority: the hero availability search layers a capacity floor on
  // top of whatever browse pill is active, without either clobbering the other.
  window.RoomFilter = {
    setGuestFloor(n) { minBeds = Math.max(0, parseInt(n, 10) || 0); apply(); },
    reset() { minBeds = 0; apply(); },
  };
});

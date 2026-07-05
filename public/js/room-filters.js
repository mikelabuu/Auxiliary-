/**
 * Room capacity filter pills (landing page "Living Quarters" section).
 *
 * Pills live in #roomFilters (data-filter: all | 1-2 | 3-4 | 5plus | premium).
 * Cards opt in with [data-room-item] plus data-beds / data-premium attributes
 * and fade/scale smoothly when filtered.
 */
document.addEventListener('DOMContentLoaded', function () {
  const bar = document.getElementById('roomFilters');
  if (!bar) return;

  const pills = bar.querySelectorAll('[data-filter]');
  const items = document.querySelectorAll('[data-room-item]');
  const empty = document.getElementById('roomFilterEmpty');

  function matches(item, filter) {
    const beds = parseInt(item.dataset.beds, 10) || 0;
    const premium = item.dataset.premium === '1';
    switch (filter) {
      case '1-2':    return beds >= 1 && beds <= 2;
      case '3-4':    return beds >= 3 && beds <= 4;
      case '5plus':  return beds >= 5;
      case 'premium': return premium;
      default:       return true;
    }
  }

  function applyFilter(filter) {
    let shown = 0;
    items.forEach(item => {
      if (matches(item, filter)) {
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

  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      pills.forEach(p => {
        p.classList.toggle('active', p === pill);
        p.setAttribute('aria-pressed', p === pill ? 'true' : 'false');
      });
      applyFilter(pill.dataset.filter || 'all');
    });
  });
});

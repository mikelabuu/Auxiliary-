document.addEventListener('DOMContentLoaded', function () {
  const items = document.querySelectorAll('[data-room-item]');
  if (!items.length) return; // rooms grid absent

  const bar = document.getElementById('roomFilters');
  const empty = document.getElementById('roomFilterEmpty');

  let activePill = 'all';
  let minBeds = 0; // capacity floor from the hero guest search (0 = no floor)

  // Assign unique view-transition-names to cards for browser-native morph animations
  items.forEach(item => {
    const card = item.querySelector('[data-room-card]');
    if (card) {
      const cardId = card.getAttribute('data-room-card');
      item.style.viewTransitionName = `room-card-${cardId}`;
    }
  });

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
    // Clean up any old stagger style tag
    const oldStyle = document.getElementById('view-transition-stagger');
    if (oldStyle) oldStyle.remove();

    let shown = 0;

    // View Transitions API (Modern browsers)
    if (document.startViewTransition) {
      // Dynamic stagger CSS generation for each card based on its filtered display index
      const style = document.createElement('style');
      style.id = 'view-transition-stagger';
      let css = '';
      let shownIndex = 0;

      items.forEach(item => {
        const card = item.querySelector('[data-room-card]');
        if (card) {
          const cardId = card.getAttribute('data-room-card');
          const beds = parseInt(item.dataset.beds, 10) || 0;
          const visible = matchesPill(item, activePill) && beds >= minBeds;
          if (visible) {
            css += `
              ::view-transition-group(room-card-${cardId}),
              ::view-transition-new(room-card-${cardId}) {
                animation-delay: ${shownIndex * 80}ms !important;
                animation-fill-mode: both !important;
              }
            `;
            shownIndex++;
          }
        }
      });
      style.innerHTML = css;
      document.head.appendChild(style);

      // Suppress the document-wide crossfade for this transition only.
      //
      // 10-view-transitions.css animates ::view-transition-old/new(root) so
      // public pages soft-fade into each other on navigation. Those selectors
      // also match same-page transitions like this one, so re-filtering the
      // room grid was fading the ENTIRE document out and back in — the hero
      // "flash" reported on every click of the guests stepper. `.vt-scoped`
      // switches the root snapshots to a static hand-off; the room cards keep
      // their own named transitions and still morph and stagger.
      document.documentElement.classList.add('vt-scoped');

      const transition = document.startViewTransition(() => {
        items.forEach(item => {
          const beds = parseInt(item.dataset.beds, 10) || 0;
          const visible = matchesPill(item, activePill) && beds >= minBeds;
          if (visible) {
            shown++;
            item.classList.remove('hidden', 'opacity-0', 'scale-95');
          } else {
            item.classList.add('hidden');
          }
        });
        if (empty) empty.classList.toggle('hidden', shown > 0);
      });

      // Clear dynamic style tag and release the scope flag once the transition
      // settles. `finished` rejects if the transition is skipped (a second
      // click landing mid-flight), so this has to run on both paths or the
      // class would stick and permanently disable the navigation crossfade.
      const cleanup = () => {
        const styleToClear = document.getElementById('view-transition-stagger');
        if (styleToClear) styleToClear.remove();
        document.documentElement.classList.remove('vt-scoped');
      };
      transition.finished.then(cleanup, cleanup);

      // `ready` is a second, separate promise on the same transition, and a
      // skipped transition rejects it too — with InvalidStateError. Nothing
      // reads it here, but an unattached rejection is still an unhandled one:
      // three fast clicks on the filter pills logged three uncaught
      // InvalidStateErrors, and page load logged two more. Swallowed rather
      // than handled, because the cleanup `finished` already runs is the only
      // work this transition owes anyone.
      transition.ready.catch(() => {});
    } else {
      // FLIP Layout Fallback with staggered transitions (Older browsers)
      const rects = new Map();
      items.forEach(item => {
        if (!item.classList.contains('hidden')) {
          rects.set(item, item.getBoundingClientRect());
        }
      });

      let shownIndex = 0;
      items.forEach(item => {
        const beds = parseInt(item.dataset.beds, 10) || 0;
        const visible = matchesPill(item, activePill) && beds >= minBeds;
        if (visible) {
          shown++;
          item.classList.remove('hidden');
          
          // Apply staggered transition delay for entrance
          item.style.transitionDelay = `${shownIndex * 80}ms`;
          shownIndex++;

          requestAnimationFrame(() => {
            item.classList.remove('opacity-0', 'scale-95');
          });
        } else {
          item.style.transitionDelay = '0ms';
          item.classList.add('opacity-0', 'scale-95');
          setTimeout(() => {
            if (item.classList.contains('opacity-0')) {
              item.classList.add('hidden');
            }
          }, 300);
        }
      });
      if (empty) empty.classList.toggle('hidden', shown > 0);

      // Invert & Play shifts for remaining visible cards
      requestAnimationFrame(() => {
        items.forEach(item => {
          if (item.classList.contains('hidden')) return;
          const oldRect = rects.get(item);
          if (!oldRect) return;

          const newRect = item.getBoundingClientRect();
          const dx = oldRect.left - newRect.left;
          const dy = oldRect.top - newRect.top;

          if (dx !== 0 || dy !== 0) {
            item.style.transition = 'none';
            item.style.transform = `translate3d(${dx}px, ${dy}px, 0)`;
            item.offsetHeight; // force reflow
            item.style.transition = 'transform 450ms cubic-bezier(0.34, 1.56, 0.64, 1)';
            item.style.transform = 'translate3d(0, 0, 0)';
            
            // Clean up style details after slide finishes
            setTimeout(() => {
              item.style.transform = '';
              item.style.transitionDelay = '';
            }, 450);
          } else {
            // Clean up transition delay for non-moving cards
            setTimeout(() => {
              item.style.transitionDelay = '';
            }, 450);
          }
        });
      });
    }
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

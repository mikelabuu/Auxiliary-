/**
 * Landing-page behaviours (moved out of the old inline welcome.blade.php
 * script block): direct room booking, hero guests stepper, stat counters,
 * testimonials Swiper, and the mobile sticky reserve bar.
 *
 * Loaded as a blocking script after booking.js / availability-search.js so
 * `bookRoomDirect` exists before any room card can be clicked.
 */

function bookRoomDirect(roomId) {
    if (!roomId) return;
    if (window.LAST_AVAILABILITY && window.LAST_AVAILABILITY.summary) {
        const row = window.LAST_AVAILABILITY.summary.find(s => s.room_type === roomId);
        if (row && row.available <= 0) {
            alert('This room type is fully booked for the selected dates.');
            return;
        }
    }
    const checkIn = document.getElementById('widget_check_in').value;
    const checkOut = document.getElementById('widget_check_out').value;
    const guests = document.getElementById('widget_guests').value;
    let url = `/checkout?room_type=${roomId}`;
    if (checkIn) url += `&check_in=${checkIn}`;
    if (checkOut) url += `&check_out=${checkOut}`;
    if (guests) url += `&guests=${guests}`;
    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function () {
    // Guests stepper
    const minusBtn = document.getElementById('btn_minus_guests');
    const plusBtn = document.getElementById('btn_plus_guests');
    const display = document.getElementById('guests_display');
    const plural = document.getElementById('guests_plural');
    const hiddenInput = document.getElementById('widget_guests');

    // Odometer roll (vengence-ui animated-number): outgoing value slides
    // out, incoming slides in from the opposite edge based on direction.
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function setGuests(val) {
        val = Math.min(40, Math.max(1, val));
        const prev = parseInt(hiddenInput.value) || 1;
        hiddenInput.value = val;
        plural && plural.classList.toggle('hidden', val === 1);
        // If results are already on screen, re-filter room types live.
        if (window.LAST_AVAILABILITY && window.__applyGuestFilter) window.__applyGuestFilter(val);
        if (val === prev) return;

        const current = display.querySelector('span:not(.is-leaving)');
        if (reduceMotion || !current || !current.animate) {
            display.textContent = '';
            const s = document.createElement('span');
            s.textContent = val;
            display.appendChild(s);
            return;
        }

        const dir = val > prev ? 1 : -1;
        const next = document.createElement('span');
        next.textContent = val;
        display.appendChild(next);

        const easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
        current.classList.add('is-leaving');
        current.animate([
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
            { transform: `translateY(${dir * -100}%)`, opacity: 0, filter: 'blur(2px)' },
        ], { duration: 260, easing, fill: 'forwards' }).onfinish = () => current.remove();
        next.animate([
            { transform: `translateY(${dir * 100}%)`, opacity: 0, filter: 'blur(2px)' },
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
        ], { duration: 260, easing });
    }
    if (minusBtn && plusBtn && display && hiddenInput) {
        minusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) - 1); });
        plusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) + 1); });
    }

    // Stats strip: numerals roll up the first time the strip enters the
    // viewport ("₱1,600" → counts to 1,600; "24/7" → counts the 24).
    // Static markup stays the no-JS / reduced-motion fallback.
    const statEls = document.querySelectorAll('.stat-value');
    if (statEls.length && !reduceMotion && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                obs.unobserve(entry.target);
                const el = entry.target;
                const m = (el.textContent || '').trim().match(/^([^\d]*)([\d,]+)(.*)$/);
                if (!m) return;
                const prefix = m[1], target = parseInt(m[2].replace(/,/g, ''), 10), suffix = m[3];
                if (!target) return;
                const t0 = performance.now(), dur = 1400;
                const ease = t => 1 - Math.pow(1 - t, 4);
                (function tick(now) {
                    const p = Math.min(1, ((now || performance.now()) - t0) / dur);
                    el.textContent = prefix + Math.round(target * ease(p)).toLocaleString('en-PH') + suffix;
                    if (p < 1) requestAnimationFrame(tick);
                })();
            });
        }, { threshold: 0.4 });
        statEls.forEach(el => io.observe(el));
    }

    // Testimonials Swiper — stacked card deck (bundle's `cards` effect):
    // the next quote peeks from behind with a slight fan, and the deck is
    // draggable as well as button-driven. Shadows off — the glass cards
    // carry their own night shadow. Reduced-motion keeps a plain slide.
    new Swiper('.testimonials-swiper', {
        effect: reduceMotion ? 'slide' : 'cards',
        cardsEffect: { perSlideOffset: 9, perSlideRotate: 2.2, slideShadows: false },
        grabCursor: true,
        // rewind, not loop: the cards effect positions slides by real index,
        // and loop's clone/reorder machinery deadlocks it (slideNext no-ops).
        rewind: true,
        speed: 650,
        autoplay: reduceMotion ? false : { delay: 7000, pauseOnMouseEnter: true, disableOnInteraction: false },
        navigation: {
            nextEl: '.swiper-button-next-custom',
            prevEl: '.swiper-button-prev-custom',
        },
    });

    // Mobile sticky bar appears after the hero
    const stickyBar = document.getElementById('mobileStickyBar');
    const heroSection = document.getElementById('firstsection');
    if (stickyBar && heroSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                stickyBar.classList.toggle('translate-y-full', entry.isIntersecting);
            });
        }, { threshold: 0.1 });
        observer.observe(heroSection);
    }
});

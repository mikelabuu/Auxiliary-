{{-- Behaviour shared by every auth plate: password reveal, Caps Lock warning
     and the submit busy state. Pushed into @stack('scripts') by each view. --}}
<script>
(() => {
    'use strict';

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── Heading rises word by word ──────────────────────────
       Technique from ReactBits "Split Text" (which needs GSAP + its SplitText
       plugin); this project has no React, so only the method travels:

         · each word gets its own overflow:hidden mask and rises out of it
         · transform + opacity only — the earlier blur version was not
           composited and that is what made it feel rough
         · long duration, tight stagger (900ms / 55ms), per Split Text
         · split only after document.fonts.ready, or the words are measured
           against the fallback face and re-flow mid-animation

       textContent is read and written, never innerHTML, so this cannot
       introduce markup. The visible string is unchanged, so the heading stays
       intact for screen readers and for copy-paste. */
    const titles = [...document.querySelectorAll('.fha-panel-title')];

    const revealTitles = () => {
        titles.forEach(title => {
            if (title.classList.contains('is-split')) return;
            const words = title.textContent.trim().split(/\s+/);

            if (!reduced && words.length <= 8) {
                title.textContent = '';
                words.forEach((word, i) => {
                    const mask = document.createElement('span');
                    mask.className = 'fha-word';
                    const inner = document.createElement('span');
                    inner.className = 'fha-word-in';
                    inner.style.setProperty('--i', i);
                    inner.textContent = i < words.length - 1 ? word + ' ' : word;
                    inner.addEventListener('animationend',
                        () => inner.classList.add('is-done'), { once: true });
                    mask.appendChild(inner);
                    title.appendChild(mask);
                });
            }
            title.classList.add('is-split');
        });
    };

    // Whichever resolves first. The timeout is the failsafe: a font that never
    // loads must not keep the heading hidden.
    if (titles.length) {
        const fonts = document.fonts && document.fonts.ready
            ? document.fonts.ready
            : Promise.resolve();
        Promise.race([
            fonts,
            new Promise(resolve => setTimeout(resolve, 900))
        ]).then(revealTitles).catch(revealTitles);
    }

    /* ── Password reveal ─────────────────────────────────────── */
    document.querySelectorAll('[data-reveal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.reveal);
            if (!input) return;
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            btn.setAttribute('aria-pressed', String(reveal));
            btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
            btn.querySelectorAll('svg').forEach(svg =>
                svg.classList.toggle('hidden-form', svg.dataset.eye !== (reveal ? 'on' : 'off')));
            input.focus({ preventScroll: true });
        });
    });

    /* ── Caps Lock ───────────────────────────────────────────
       A real hazard for anyone who signs in at the start of every shift and
       gets one identical "invalid email or password" back either way. */
    document.querySelectorAll('[data-caps-for]').forEach(input => {
        const note = document.getElementById(input.dataset.capsFor);
        if (!note) return;
        const sync = (e) => {
            if (typeof e.getModifierState !== 'function') return;
            note.classList.toggle('is-on', e.getModifierState('CapsLock'));
        };
        input.addEventListener('keydown', sync);
        input.addEventListener('keyup', sync);
        input.addEventListener('blur', () => note.classList.remove('is-on'));
    });

    /* ── Confirm-password match ──────────────────────────────
       Validated on blur, never on keystroke: flagging a mismatch while
       someone is still typing the word is noise, not help. Purely additive —
       the server still enforces `confirmed` either way. */
    document.querySelectorAll('[data-confirm-of]').forEach(confirmField => {
        const source = document.getElementById(confirmField.dataset.confirmOf);
        const note = document.getElementById(
            confirmField.getAttribute('aria-describedby') || '');
        if (!source || !note) return;

        const evaluate = () => {
            const mismatch = confirmField.value !== '' && confirmField.value !== source.value;
            note.classList.toggle('is-on', mismatch);
            confirmField.setAttribute('aria-invalid', mismatch ? 'true' : 'false');
        };

        confirmField.addEventListener('blur', evaluate);
        // Clear a shown error as soon as it stops being true; never introduce one mid-type.
        confirmField.addEventListener('input', () => {
            if (note.classList.contains('is-on')) evaluate();
        });
        source.addEventListener('input', () => {
            if (note.classList.contains('is-on')) evaluate();
        });
    });

    /* ── Focus after a failed submit ─────────────────────────
       The page re-renders server-side on error, so focus would otherwise land
       at the top of the document and the alert would be easy to miss. Moving
       it to the alert announces the problem and puts the fields one Tab away. */
    const alertNote = document.querySelector('.fha-note--bad');
    if (alertNote) {
        alertNote.setAttribute('tabindex', '-1');
        alertNote.focus({ preventScroll: true });
    }

    /* ── Submit ──────────────────────────────────────────────
       Guards the double-submit. The label swaps for a spinner in place, so
       the button keeps its width and the plate does not reflow in flight. */
    document.querySelectorAll('[data-busy-form]').forEach(form => {
        let sent = false;
        form.addEventListener('submit', (e) => {
            if (sent) { e.preventDefault(); return; }
            sent = true;
            const btn = form.querySelector('[data-busy-btn]');
            if (btn) {
                btn.classList.add('is-busy');
                btn.setAttribute('aria-busy', 'true');
            }
        });
    });
})();
</script>

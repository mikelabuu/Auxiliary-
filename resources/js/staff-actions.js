/**
 * Staff console — confirmation + in-flight state for irreversible actions.
 *
 * Replaces two per-page implementations that had drifted apart:
 *
 *  - staff/paymentverification used `window.confirm()` — a raw browser dialog
 *    on the single highest-stakes action in the app (it marks a booking paid
 *    and emails the guest an official receipt), while every other console
 *    dialog is SweetAlert2.
 *  - staff/discounts/show used Swal, but each page bound its own listener over
 *    a static NodeList, so neither covered a row that arrived by Livewire.
 *
 * Neither guarded against a double submit. Both called `form.submit()`, which
 * does *not* fire a submit event — so `[data-busy-form]` (resources/js/app.js)
 * could never arm, and an impatient second click on a slow connection posted
 * the approval twice. This dispatches `requestSubmit()` instead, which fires a
 * real submit event, runs native validation, and lets the busy guard take over.
 *
 * Contract — put it on the <form>:
 *
 *     <form method="POST" action="…"
 *           data-busy-form
 *           data-confirm="The 20% per approved ID applies to the booking total."
 *           data-confirm-title="Approve this discount?"
 *           data-confirm-action="Yes, approve"
 *           data-confirm-tone="danger">
 *
 * Only `data-confirm` is required. `tone="danger"` paints the confirm button
 * in ember for rejections and cancellations.
 */

const TONES = {
    // Matches --color-clsu-600 / --color-ember-600 in admin/01-tokens.css.
    default: '#167C39',
    danger: '#DC2626',
};

document.addEventListener('submit', (e) => {
    const form = e.target.closest('[data-confirm]');
    if (!form) return;

    // Second pass, dispatched by requestSubmit() below — let it through.
    if (form.dataset.confirmed === '1') return;

    e.preventDefault();

    // Don't ask someone to confirm a form they haven't filled in correctly.
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const tone = form.dataset.confirmTone === 'danger' ? 'danger' : 'default';

    const proceed = () => {
        form.dataset.confirmed = '1';
        // Fires a real submit event so [data-busy-form] arms the spinner and
        // the duplicate guard. form.submit() would bypass both.
        form.requestSubmit();
    };

    // SweetAlert2 is loaded by both staff layouts, ahead of the bundles. If it
    // somehow isn't there, fall back rather than letting the action through
    // unconfirmed.
    if (!window.Swal) {
        if (window.confirm(form.dataset.confirm)) proceed();
        return;
    }

    window.Swal.fire({
        title: form.dataset.confirmTitle || 'Are you sure?',
        text: form.dataset.confirm,
        icon: tone === 'danger' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmAction || 'Yes, continue',
        cancelButtonText: 'Cancel',
        confirmButtonColor: TONES[tone],
        reverseButtons: true,
        focusCancel: tone === 'danger',
    }).then((result) => {
        if (result.isConfirmed) proceed();
    });
});

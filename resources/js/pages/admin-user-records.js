/**
 * User Hub (staff/userrecords/index) — the guest detail modal and the
 * suspend / unsuspend / verify-email actions.
 *
 * Was ~160 lines inline in the Blade view. The endpoint base was hardcoded as
 * a literal '/staff/user-records' string; it now comes from #admin-user-records-data
 * so the route prefix stays a server concern.
 *
 * Depends on jQuery, SweetAlert and window.openModal/closeModal, all loaded by
 * layouts/admin ahead of the module bundle. No-ops off this page.
 */

function initAdminUserRecords() {
    const dataEl = document.getElementById('admin-user-records-data');
    if (!dataEl) return;

    const { base } = JSON.parse(dataEl.textContent);
    const csrf = () => $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf() } });

    // ── Modal helpers: shared animated open/close from layouts/admin ────────
    $(document).on('click', '[data-modal-close]', function () {
        window.closeModal($(this).data('modal-close'));
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') window.closeModal('userDetailModal');
    });

    // ── User detail modal ───────────────────────────────────────────────────
    const bookingStatusStyles = {
        paid:             'bg-clsu-50 text-clsu-700 border-clsu-200',
        completed:        'bg-clsu-50 text-clsu-700 border-clsu-200',
        checked_in:       'bg-sky-50 text-sky-700 border-sky-200',
        active:           'bg-sky-50 text-sky-700 border-sky-200',
        confirmed:        'bg-sky-50 text-sky-700 border-sky-200',
        pending_payment:  'bg-palay-100 text-palay-800 border-palay-200',
        pending_discount: 'bg-palay-100 text-palay-800 border-palay-200',
        cancelled:        'bg-ember-50 text-ember-700 border-ember-200',
        failed:           'bg-ember-50 text-ember-700 border-ember-200',
    };

    let currentUserId = null;

    $(document).on('click', '.view-user-btn', function () {
        currentUserId = $(this).data('user-id');
        $('#udLoading').removeClass('hidden');
        $('#udBody').addClass('hidden');
        window.openModal('userDetailModal');

        $.get(`${base}/${currentUserId}/details`)
            .done(renderUserDetails)
            .fail(() => {
                window.closeModal('userDetailModal');
                window.Swal.fire('Error', 'Could not load user details.', 'error');
            });
    });

    function renderUserDetails(u) {
        $('#udAvatar')
            .text((u.username || '?').charAt(0).toUpperCase())
            .toggleClass('bg-clsu-100 text-clsu-700', !u.is_suspended)
            .toggleClass('bg-ember-100 text-ember-700', u.is_suspended);
        $('#udName').text(`${u.username} · #${u.id}`);
        $('#udEmail').text(u.email);

        $('#udStanding')
            .attr('class', 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-bold border ' +
                (u.is_suspended ? 'bg-ember-50 text-ember-700 border-ember-200' : 'bg-clsu-50 text-clsu-700 border-clsu-200'))
            .text(u.is_suspended ? 'Suspended' : 'Active');
        $('#udVerifiedPill')
            .attr('class', 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-bold border ' +
                (u.verified ? 'bg-clsu-50 text-clsu-700 border-clsu-200' : 'bg-stone-100 text-muted border-stone-200'))
            .text(u.verified ? (u.verified_at ? `Verified ${u.verified_at}` : 'Verified') : 'Unverified');

        $('#udStatTotal').text(u.stats.total);
        $('#udStatCompleted').text(u.stats.completed);
        $('#udStatCancelled').text(u.stats.cancelled);
        $('#udStatSpend').text('₱' + u.stats.spend);

        $('#udPhone').text(u.phone || '—');
        $('#udJoined').text(u.joined);
        $('#udLastLogin').text(u.last_login || '—');
        $('#udLastCancelled').text(u.last_cancelled || '—');

        const $list = $('#udBookings').empty();
        $('#udNoBookings').toggleClass('hidden', u.recent_bookings.length > 0);
        $list.toggleClass('hidden', u.recent_bookings.length === 0);
        u.recent_bookings.forEach(b => {
            const pill = bookingStatusStyles[b.status] || 'bg-stone-100 text-stone-600 border-stone-200';
            const label = (b.status || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            // Built with .text() throughout — a guest controls their own room
            // labels and dates, so none of it may be interpolated as HTML.
            const $row = $('<div class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm bg-white"></div>');
            $row.append($('<span class="font-data tabnum text-muted shrink-0"></span>').text('#' + b.id));
            $row.append($('<span class="text-stone-700 truncate flex-1"></span>').text(`Room ${b.rooms} · ${b.dates}`));
            $row.append($(`<span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold border shrink-0 ${pill}"></span>`).text(label));
            $row.append($('<span class="font-data tabnum font-semibold text-stone-800 shrink-0"></span>').text('₱' + b.amount));
            $list.append($row);
        });

        $('#udVerifyBtn').toggleClass('hidden', u.verified);

        $('#udSuspendBtn')
            .data('user-id', u.id)
            .data('action', u.is_suspended ? 'unsuspend' : 'suspend')
            .attr('class', 'password-verify-btn text-xs font-semibold border bg-white rounded-lg px-3 py-2 transition-colors cursor-pointer ' +
                (u.is_suspended
                    ? 'text-clsu-700 border-clsu-200 hover:bg-clsu-50 hover:border-clsu-300'
                    : 'text-ember-700 border-ember-200 hover:bg-ember-50 hover:border-ember-300'))
            .text(u.is_suspended ? 'Unsuspend Account' : 'Suspend Account');

        $('#udLoading').addClass('hidden');
        $('#udBody').removeClass('hidden');
    }

    $('#udVerifyBtn').on('click', function () {
        if (!currentUserId) return;

        window.Swal.fire({
            target: 'body',
            title: 'Mark email as verified?',
            text: 'Use this only when the guest has confirmed ownership of the email through another channel.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Verify Email',
            scrollbarPadding: false,
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post(`${base}/${currentUserId}/verify-email`, { _token: csrf() })
                .done(data => {
                    window.Swal.fire({
                        icon: data.success ? 'success' : 'info',
                        title: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                })
                .fail(() => window.Swal.fire('Error', 'Action failed. Please try again.', 'error'));
        });
    });

    $(document).on('click', '.password-verify-btn', function (e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        const action = $(this).data('action');
        const isSuspend = action === 'suspend';

        // Password re-auth dropped — a plain confirm still guards the account change.
        window.Swal.fire({
            target: 'body', // ensures the dialog is appended to <body>, not the layout container
            title: isSuspend ? 'Suspend this user?' : 'Unsuspend this user?',
            text: isSuspend ? 'They will lose access until reinstated.' : 'They will regain access.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: isSuspend ? 'Yes, suspend' : 'Yes, unsuspend',
            customClass: isSuspend ? { confirmButton: 'is-danger' } : {},
            scrollbarPadding: false
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: `${base}/${userId}/${action}`,
                method: 'POST',
                data: { _token: csrf() },
                success: function (data) {
                    window.Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function () {
                    window.Swal.fire('Error', 'Action failed. Please try again.', 'error');
                }
            });
        });
    });
}

$(initAdminUserRecords);

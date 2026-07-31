<?php

use App\Models\Booking;
use App\Models\Staff;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * The staff alert feed (App\Events\StaffNotification): the only channel in this
 * app that carries a guest's name over the wire, so it is also the only one
 * that is authorised rather than public.
 *
 * `guards: ['staff']` is load-bearing. Every other channel here resolves the
 * default `web` guard, which holds *guests*; without this the callback would be
 * handed a guest account (or nobody) and staff would never authorise. Roles are
 * checked too: `housekeeping` has no console to pop an alert into, and a
 * suspended account keeps its session cookie until it expires, so neither
 * should keep receiving desk traffic.
 */
Broadcast::channel('staff.alerts', function (Staff $staff) {
    return ! $staff->is_suspended
        && in_array($staff->role, ['master_admin', 'admin', 'frontdesk'], true);
}, ['guards' => ['staff']]);

/*
 * A guest watching their own booking page (App\Events\BookingStatusChanged).
 * Only the account that owns the booking may subscribe — walk-in bookings have
 * a null user_id and are therefore not subscribable by anyone.
 */
Broadcast::channel('booking.{bookingId}', function ($user, $bookingId) {
    $ownerId = Booking::whereKey($bookingId)->value('user_id');

    return $ownerId !== null && (int) $ownerId === (int) $user->id;
});

/*
 * A guest's account-wide feed (App\Events\GuestBookingUpdated): anything that
 * happens to any booking they own, delivered while they sit on My Bookings.
 * Only the account itself may subscribe.
 */
Broadcast::channel('user.{userId}.bookings', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

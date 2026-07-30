<?php

use App\Models\Booking;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

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

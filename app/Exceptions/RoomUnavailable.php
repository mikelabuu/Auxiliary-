<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A room the caller asked for cannot be sold: it is already held by an
 * overlapping booking, or the front desk has taken it out of service.
 *
 * Typed rather than a bare \Exception so the booking flows can tell the one
 * outcome that is safe to show a person — "room 112 has just gone" — from
 * every other throwable that reaches the same catch. Both flows used to put
 * `$e->getMessage()` straight into a form error, which meant a driver-level
 * SQL failure was rendered verbatim to a guest.
 *
 * The message on this exception is written to be read by whoever is looking
 * at the form. Nothing else that lands in those catch blocks is.
 */
class RoomUnavailable extends RuntimeException
{
}

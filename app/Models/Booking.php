<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{

    use HasFactory;

    /**
     * Statuses that make a booking's rooms unavailable to other guests.
     * Single source of truth — used by both the availability endpoints and
     * the double-booking guard inside BookingController::store().
     */
    /**
     * Every status a booking can actually hold is written somewhere in the
     * app: pending_payment, pending_discount, paid, active, completed,
     * cancelled, expired, no_show. Keep this list to statuses that exist —
     * phantom entries ('confirmed', 'checked_in') hid a dead occupancy count
     * for months because queries against them silently matched nothing.
     */
    public const BLOCKING_STATUSES = [
        'pending_payment',
        'pending_discount',
        'paid',
        'active',
    ];

    /**
     * The blocking statuses that never lapse on their own. A booking in one of
     * these holds its rooms until a person or a lifecycle event moves it.
     * `pending_payment` is deliberately absent — it is the one hold with a
     * clock on it. See applyActiveHold().
     */
    public const SETTLED_BLOCKING_STATUSES = [
        'pending_discount',
        'paid',
        'active',
    ];

    /**
     * Narrow a query to the bookings that are holding their rooms *right now*.
     *
     * BLOCKING_STATUSES answers "which statuses can hold a room", which is not
     * the same question. A `pending_payment` booking holds its rooms only until
     * its payment window runs out — or until the guest was due to arrive, if
     * that comes sooner. Past either, the hold is already dead, and
     * `bookings:expire` is just the bookkeeping that writes it down.
     *
     * Reading the raw status list made that bookkeeping load-bearing. With the
     * scheduler not running — a deleted task, a moved folder, a reboot, or the
     * wrong path in a runbook — lapsed holds went on blocking their rooms
     * forever, and nothing on any screen said so. The room simply stopped being
     * sellable. Availability now reads the clock directly, so a dead scheduler
     * costs stale statuses and missing notifications, never inventory.
     *
     * Only sellability questions should use this. Staff-facing boards and
     * dashboards still list by raw status on purpose: until the command runs,
     * the booking genuinely is still `pending_payment`, and hiding it would
     * misreport the desk's own workload.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder  $query
     * @param  string  $table  Qualifier for the columns. Joined queries need
     *                         'bookings'; pass '' inside a whereHas closure.
     */
    public static function applyActiveHold($query, string $table = 'bookings')
    {
        $status  = $table === '' ? 'status' : "{$table}.status";
        $since   = $table === '' ? 'pending_payment_since' : "{$table}.pending_payment_since";
        $checkIn = $table === '' ? 'check_in' : "{$table}.check_in";

        $lapsedBefore = now()->subMinutes(\App\Support\PaymentWindow::minutes());

        // The other end of the same clock. An unpaid hold dies at its deadline
        // *or* at the moment its guests were due to arrive, whichever comes
        // first — see PaymentWindow::deadlineFor for why the second half
        // matters once the window is longer than a day.
        $liveFrom = \App\Support\PaymentWindow::earliestLiveCheckInDate();

        return $query->where(function ($q) use ($status, $since, $checkIn, $lapsedBefore, $liveFrom) {
            $q->whereIn($status, self::SETTLED_BLOCKING_STATUSES)
                ->orWhere(function ($q) use ($status, $since, $checkIn, $lapsedBefore, $liveFrom) {
                    $q->where($status, 'pending_payment')
                        // A missing stamp cannot be reasoned about. The status
                        // mutator below always sets one, so this should be
                        // unreachable — but treat it as still holding rather
                        // than release a room somebody may be paying for.
                        ->where(function ($q) use ($since, $lapsedBefore) {
                            $q->whereNull($since)
                                ->orWhere($since, '>', $lapsedBefore);
                        })
                        // whereDate, not a plain comparison, for the reason
                        // spelled out in BookingController::store: check_in is
                        // a DATE column and a driver that stores it with a
                        // midnight time component turns this into the wrong
                        // question.
                        ->whereDate($checkIn, '>=', $liveFrom);
                });
        });
    }

    /**
     * Every status a booking may legitimately hold. This is the single source
     * of truth now that `status` is a plain VARCHAR rather than a MySQL enum —
     * validate against this list instead of relying on the column type. Keep it
     * in sync with the lifecycle; do not add phantom values ('confirmed',
     * 'checked_in') that no code actually sets.
     */
    public const STATUSES = [
        'pending_discount',
        'pending_payment',
        'paid',
        'active',
        'completed',
        'cancelled',
        'expired',
        'no_show',
    ];

    /**
     * A short, guest-facing line saying where a booking now stands.
     *
     * Written for the status feed the guest pages poll. A poll carries no event
     * payload, so there is no App\Events\GuestBookingUpdated message to show,
     * and composing one in JavaScript would both duplicate this copy and risk
     * promising something that did not happen — the old banner said an official
     * receipt was on its way, which is only true when a payment was verified,
     * not merely because a booking reached `paid`.
     *
     * So this is deliberately narrower than that event's messages: it describes
     * the record, not the event. The event can say more ("your proof was not
     * accepted: <reason>") because it is built where the reason is known.
     *
     * Lives beside STATUSES so a status added above is noticed here too.
     */
    public static function statusLine(int $id, string $status): string
    {
        return match ($status) {
            'pending_discount' => "Booking #{$id} is waiting on a discount decision.",
            'pending_payment'  => "Booking #{$id} is waiting for payment.",
            'paid'             => "Payment accepted — booking #{$id} is confirmed.",
            'active'           => "Booking #{$id} is now checked in.",
            'completed'        => "Booking #{$id} is complete. Thank you for staying with us.",
            'cancelled'        => "Booking #{$id} was cancelled.",
            'expired'          => "Booking #{$id} expired before payment was received.",
            'no_show'          => "Booking #{$id} was marked as a no-show.",
            default            => "Booking #{$id} has been updated.",
        };
    }

    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_address',
        'guest_phone',
        'guest_phone_alt',
        // The reference person: who endorsed this guest, the number to ring
        // them on, and what the stay was endorsed for. See the migrations for
        // why the columns are nullable while the public form insists on them.
        'referred_by',
        'referred_by_phone',
        'referred_by_purpose',
        'check_in',
        'check_out',
        'arrival_time',
        'special_requests',
        'accepted_terms_at',
        'discount',
        'num_seniors',
        'total_price',
        'payable_amount',   // 
        'status',
        'wants_discount',
        'expected_guests',    //
        'payment_mode',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        // The guest-facing hold countdown (public/booking/show) needs the time
        // of day, not just the date, to work out how long is left on the
        // payment window — so this is a datetime, unlike check_in/check_out.
        'pending_payment_since' => 'datetime',
        'accepted_terms_at' => 'datetime',
        'num_seniors' => 'integer',
        'expected_guests' => 'integer',   //
        // Money, and the columns are decimal(10,2). Cast as float these went
        // through binary floating point on every read, so a total and the
        // payment settling it could compare unequal at the last centavo.
        // decimal:2 keeps them exact and keeps the scale the DB already has.
        'total_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
    ];


    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value;

        // If status is being set to pending_payment, record the timestamp
        if ($value === 'pending_payment' && empty($this->attributes['pending_payment_since'])) {
            $this->attributes['pending_payment_since'] = now();
        }

        // If status changes away from pending_payment, reset the column
        if ($value !== 'pending_payment') {
            $this->attributes['pending_payment_since'] = null;
        }
    }

    // Derived from reservations (the authoritative per-room source) so it can
    // never drift from a stored copy. Eager-load 'reservations' at read sites
    // that loop over many bookings to avoid N+1.
    public function getRoomNumbersAttribute()
    {
        return $this->reservations->pluck('room_number')->filter()->values()->all();
    }

    // Relationships

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rooms(): BelongsToMany
    {
    return $this->belongsToMany(Room::class, 'booking_room')->withTimestamps();
    }
    public function getRoomTypeAttribute()
    {
        return $this->rooms->pluck('room_type')->unique()->implode(', ');
    }

    public function discount()
    {
        return $this->hasOne(Discount::class);
    }

    public function discountRequest()
    {
        return $this->hasOne(Discount::class);
    }

    public function getDiscountStatusAttribute()
    {
        if (! $this->wants_discount) {
            return null; // user didn’t tick request box
        }

        $discount = $this->discountRequest;

        if (! $discount) {
            return 'not_submitted'; // ticked box but no upload yet
        }

        return $discount->status; // 'pending', 'approved', 'rejected'
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending_payment', 'pending_discount']);
    }

    public function checkouts()
    {
        return $this->hasMany(\App\Models\Checkout::class, 'booking_id');
    }

    /**
     * Asks to move this stay. Plural because a declined or withdrawn request
     * does not stop the guest trying again — only a *pending* one does, and
     * that guard lives in RescheduleRequest::isOpenFor().
     */
    public function rescheduleRequests()
    {
        return $this->hasMany(\App\Models\RescheduleRequest::class);
    }

    public function payments()
    {
        return $this->hasOne(\App\Models\Payment::class, 'booking_id')->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['paid', 'active'])
                    ->where('check_out', '>=', now(config('hostel.timezone'))->startOfDay());
    }
    
    public function balance()
    {
        return $this->hasOne(Balance::class);
    }
}   



<?php

namespace App\Models;

use App\Support\StaySchedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A guest asking to move a paid stay.
 *
 * The rule this table exists to serve: a paid booking cannot be cancelled, so
 * a guest who cannot arrive has exactly one thing to do — say so before
 * check-in time on their arrival day. Do that and the desk will move the stay.
 * Miss it and the booking is forfeited, with no refund, via
 * bookings:mark-no-show.
 *
 * The deadline is therefore not decoration. {@see isOpenFor()} is the single
 * expression of it, and both the controller and every screen that offers the
 * action read it — a button the server would refuse is worse than no button.
 */
class RescheduleRequest extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_DECLINED  = 'declined';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_DECLINED,
        self::STATUS_WITHDRAWN,
    ];

    /**
     * Booking statuses a guest may ask to move.
     *
     * Only `paid`. An unpaid booking can simply be cancelled, which is both
     * cheaper for the guest and less work for the desk; `active` means they
     * are already in the room, and `completed`, `cancelled`, `expired` and
     * `no_show` are all over.
     */
    public const RESCHEDULABLE_STATUSES = ['paid'];

    protected $fillable = [
        'booking_id',
        'user_id',
        'status',
        'original_check_in',
        'original_check_out',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'reviewed_by',
        'reviewed_at',
        'decision_note',
        'submitted_at',
    ];

    protected $casts = [
        'original_check_in'   => 'date',
        'original_check_out'  => 'date',
        'requested_check_in'  => 'date',
        'requested_check_out' => 'date',
        'reviewed_at'         => 'datetime',
        'submitted_at'        => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * How much notice a guest has to give: a full day before they were due.
     *
     * House rule, set by the client — "rebooking is allowed, but it must be
     * done at least 24 hours prior to the original check-in date". It is a
     * notice period, not a cut-off at the door, and the difference is the whole
     * point: the desk needs a day to resell a room it is about to lose, which
     * an hour's warning on the afternoon of arrival does not give them.
     */
    public const NOTICE_HOURS = 24;

    /**
     * The moment the door closes on a booking: {@see NOTICE_HOURS} before
     * check-in time on its arrival day, in the hostel's own timezone.
     *
     * `check_in` is a DATE, so on its own it says nothing about when in the day
     * the desk starts admitting people. Reading the configured check-in time
     * against it is what makes the notice period a real 24 hours rather than
     * "some time the day before" — a guest arriving at 2 PM Friday has until
     * 2 PM Thursday, not until Wednesday midnight.
     */
    public static function deadlineFor(Booking $booking): CarbonInterface
    {
        return static::checkInMomentFor($booking)->subHours(static::NOTICE_HOURS);
    }

    /** When the guest was actually due to arrive. */
    public static function checkInMomentFor(Booking $booking): CarbonInterface
    {
        return Carbon::parse(
            Carbon::parse($booking->check_in)->toDateString() . ' ' . config('hostel.checkin_time', '14:00'),
            StaySchedule::timezone()
        );
    }

    /**
     * Whether this booking can still be moved: right status, deadline not
     * passed, and nothing already in the queue for it.
     *
     * The third condition is not merely tidiness. Two open requests against one
     * booking means two staff can approve two different sets of dates, and the
     * second approval silently overwrites the first — including the availability
     * check the first one passed.
     */
    public static function isOpenFor(?Booking $booking): bool
    {
        if (! $booking || ! in_array($booking->status, self::RESCHEDULABLE_STATUSES, true)) {
            return false;
        }

        if (self::deadlineFor($booking)->isPast()) {
            return false;
        }

        return ! self::where('booking_id', $booking->id)->pending()->exists();
    }

    /** The open request for a booking, if there is one. */
    public static function openFor(?Booking $booking): ?self
    {
        if (! $booking) {
            return null;
        }

        return self::where('booking_id', $booking->id)->pending()->latest('id')->first();
    }

    /** Nights in the stay being asked for — the desk's first question. */
    public function getRequestedNightsAttribute(): int
    {
        return max(1, $this->requested_check_in->diffInDays($this->requested_check_out));
    }

    /** Nights in the stay as booked, for comparison against the above. */
    public function getOriginalNightsAttribute(): int
    {
        return max(1, $this->original_check_in->diffInDays($this->original_check_out));
    }
}

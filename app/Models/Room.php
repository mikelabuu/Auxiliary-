<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Room extends Model
{
    protected $table = 'rooms';

    /**
     * The order the building is walked in. Wings outside this list still show
     * — alphabetically, after these — so a wing added tomorrow is never
     * silently dropped from a board.
     */
    public const WING_ORDER = ['rooster', 'tumana', 'chev_re', 'torii'];

    /**
     * Housekeeping states staff set by hand. 'occupied' is deliberately absent:
     * it is derived from the booking lifecycle (check-in writes it, check-out
     * clears it), so a guest is always the reason a room is occupied.
     */
    public const SETTABLE_STATUSES = ['available', 'cleaning', 'maintenance'];

    /**
     * States the booking lifecycle owns. Never offered as a manual choice.
     */
    public const DERIVED_STATUSES = ['occupied'];

    protected $fillable = [
        'room_number',
        'room_type',
        'wing',
        'price',
        'status',
        'notes',
        'last_edited_by',
    ];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_room')
                    ->withTimestamps();
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type', 'slug');
    }

    public function lastEditedBy()
    {
        return $this->belongsTo(Staff::class, 'last_edited_by');
    }

    public function activeBookings()
    {
        return $this->bookings()->active();
    }

    public function reservations()
    {
        return $this->hasMany(\App\Models\Reservation::class, 'room_number', 'room_number');
    }

    /**
     * Rooms grouped into wings, in walking order.
     *
     * Both room boards lay themselves out this way, and this is the one place
     * that decides how. The dashboard map used to group by room type instead,
     * from filters it built itself — which is how it came to draw the deluxe
     * rooms twice under a heading that promised "all 22 rooms".
     *
     * Accepts models or the plain arrays App\Support\RoomBoard::state()
     * returns, since the two boards feed it different shapes.
     *
     * @param  iterable  $rooms
     * @return Collection<string, Collection> wing slug => its rooms
     */
    public static function groupByWing($rooms): Collection
    {
        $byWing = Collection::wrap($rooms)->groupBy(fn ($room) => data_get($room, 'wing') ?: 'unassigned');

        return collect(self::WING_ORDER)
            ->filter(fn ($wing) => $byWing->has($wing))
            // Anything not in WING_ORDER lands here rather than nowhere.
            ->concat($byWing->keys()->diff(self::WING_ORDER)->sort())
            ->mapWithKeys(fn ($wing) => [$wing => $byWing[$wing]]);
    }

    /** 'chev_re' => 'Chev Re'. */
    public static function wingLabel(?string $wing): string
    {
        return ucwords(str_replace('_', ' ', (string) $wing));
    }
}


<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\CancellationLog;
use App\Models\Checkin;
use App\Models\Checkout;
use App\Models\ExpiryLog;
use App\Models\NoShowLog;
use App\Models\Staff;
use Carbon\Carbon;

/**
 * Chronological event list for one booking, assembled from records the app
 * already keeps but never showed anywhere: Checkin, Checkout, NoShowLog,
 * ExpiryLog, CancellationLog, plus the payment and discount rows. Rendered
 * by <x-admin.bookings.timeline> inside the booking detail modal.
 *
 * Each event: ['at' => Carbon, 'label', 'detail', 'icon', 'color'] where
 * icon is an x-admin.ui.icon name and color is the badge class set.
 */
class BookingTimeline
{
    protected const COLOR_NEUTRAL = 'bg-stone-100 text-stone-500';
    protected const COLOR_GOOD    = 'bg-clsu-50 text-clsu-700';
    protected const COLOR_WAIT    = 'bg-palay-100 text-palay-800';
    protected const COLOR_BAD     = 'bg-ember-50 text-ember-700';

    public static function for(Booking $booking): array
    {
        $events = [];

        // Timestamp storage is mixed: Eloquent-managed columns (created_at,
        // updated_at, discount casts) hold UTC and need converting, while the
        // action logs (checked_in_at, checked_out_at, marked_at, expired_at)
        // were written with Carbon::now(config('hostel.timezone')) — i.e. Manila
        // wall-clock — and must be read back as such, not shifted again.
        $utc = fn ($v) => $v ? Carbon::parse($v)->timezone(config('hostel.timezone')) : null;
        $manila = fn ($v) => $v ? Carbon::parse($v, config('hostel.timezone')) : null;

        $push = function (?Carbon $at, string $label, ?string $detail, string $icon, string $color) use (&$events) {
            if (!$at) {
                return;
            }
            $events[] = [
                'at'     => $at,
                'label'  => $label,
                'detail' => $detail,
                'icon'   => $icon,
                'color'  => $color,
            ];
        };

        $push(
            $utc($booking->created_at),
            'Booking created',
            'Ref BK-' . str_pad($booking->id, 4, '0', STR_PAD_LEFT),
            'plus',
            self::COLOR_NEUTRAL
        );

        if ($discount = $booking->discountRequest) {
            $push(
                $utc($discount->submitted_at ?? $discount->created_at),
                'Discount requested',
                '₱' . number_format($discount->amount, 2),
                'tag',
                self::COLOR_WAIT
            );

            if (in_array($discount->status, ['approved', 'rejected'], true)) {
                $reviewer = $discount->reviewed_by ? Staff::find($discount->reviewed_by)?->name : null;
                $push(
                    $utc($discount->reviewed_at ?? $discount->updated_at),
                    'Discount ' . $discount->status,
                    $reviewer ? 'By ' . $reviewer : null,
                    $discount->status === 'approved' ? 'check-circle' : 'x',
                    $discount->status === 'approved' ? self::COLOR_GOOD : self::COLOR_BAD
                );
            }
        }

        if ($payment = $booking->payments) {
            $labels = [
                'success' => 'Payment received',
                'pending' => 'Payment initiated',
                'failed'  => 'Payment failed',
            ];
            $colors = [
                'success' => self::COLOR_GOOD,
                'pending' => self::COLOR_WAIT,
                'failed'  => self::COLOR_BAD,
            ];
            $detail = '₱' . number_format($payment->amount, 2)
                . ($payment->reference_no ? ' · Ref ' . $payment->reference_no : '');

            $push(
                // created_at is when it was initiated; updated_at is when it
                // reached its final state — use the latter for settled rows.
                $utc($payment->status === 'pending' ? $payment->created_at : $payment->updated_at),
                $labels[$payment->status] ?? 'Payment ' . $payment->status,
                $detail,
                'credit-card',
                $colors[$payment->status] ?? self::COLOR_NEUTRAL
            );
        }

        foreach (Checkin::with('staff')->where('booking_id', $booking->id)->get() as $checkin) {
            $push(
                $manila($checkin->checked_in_at),
                'Checked in',
                $checkin->staff ? 'By ' . $checkin->staff->name : null,
                'log-in',
                self::COLOR_GOOD
            );
        }

        foreach (Checkout::with('staff')->where('booking_id', $booking->id)->get() as $checkout) {
            $push(
                $manila($checkout->checked_out_at),
                'Checked out',
                $checkout->method === 'auto'
                    ? 'Automatic (2 PM checkout)'
                    : ($checkout->staff ? 'By ' . $checkout->staff->name : 'Manual'),
                'log-out',
                self::COLOR_GOOD
            );
        }

        foreach (NoShowLog::with('staff')->where('booking_id', $booking->id)->get() as $log) {
            $push(
                $manila($log->marked_at),
                'Marked as no-show',
                $log->staff ? 'By ' . $log->staff->name : ($log->reason ?: 'Automatic'),
                'x',
                self::COLOR_BAD
            );
        }

        foreach (ExpiryLog::where('booking_id', $booking->id)->get() as $log) {
            $push(
                $manila($log->expired_at),
                'Booking expired',
                $log->reason,
                'clock',
                self::COLOR_BAD
            );
        }

        foreach (CancellationLog::where('booking_id', $booking->id)->get() as $log) {
            $push(
                $utc($log->cancelled_at),
                'Booking cancelled',
                trim(($log->cancelled_by ? 'By ' . $log->cancelled_by : '') . ($log->reason ? ' — ' . $log->reason : ''), ' —') ?: null,
                'block',
                self::COLOR_BAD
            );
        }

        usort($events, fn ($a, $b) => $a['at'] <=> $b['at']);

        return $events;
    }
}

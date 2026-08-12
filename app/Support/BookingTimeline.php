<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\CancellationLog;
use App\Models\Checkin;
use App\Models\Checkout;
use App\Models\ExpiryLog;
use App\Models\NoShowLog;
use App\Models\RescheduleRequest;
use App\Models\Staff;
use App\Support\StaySchedule;
use Carbon\Carbon;

/**
 * Chronological event list for one booking, assembled from records the app
 * already keeps but never showed anywhere: Checkin, Checkout, NoShowLog,
 * ExpiryLog, CancellationLog, RescheduleRequest, plus the payment and discount
 * rows. Rendered by <x-admin.bookings.timeline> inside the booking detail modal.
 *
 * Each event: ['at' => Carbon, 'label', 'detail', 'icon', 'color'] where
 * icon is an x-admin.ui.icon name and color is the badge class set.
 */
class BookingTimeline
{
    protected const COLOR_NEUTRAL = 'bg-stone-100 text-muted';
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

        // Asking to move a paid stay, and what the desk said. Both belong here
        // because between them they explain the one thing a reader of this
        // timeline cannot otherwise account for: a booking whose check-in date
        // is not the date it was made for.
        foreach (RescheduleRequest::with('reviewer')->where('booking_id', $booking->id)->get() as $request) {
            $push(
                $utc($request->submitted_at ?? $request->created_at),
                'Reschedule requested',
                $request->requested_check_in->format('M d') . ' → ' . $request->requested_check_out->format('M d, Y')
                    . ' · ' . $request->reason,
                'calendar',
                self::COLOR_WAIT
            );

            if ($request->status === RescheduleRequest::STATUS_PENDING) {
                continue;
            }

            $by = $request->status === RescheduleRequest::STATUS_WITHDRAWN
                ? 'By the guest'
                : ($request->reviewer ? 'By ' . $request->reviewer->name : null);

            $push(
                $utc($request->reviewed_at ?? $request->updated_at),
                match ($request->status) {
                    RescheduleRequest::STATUS_APPROVED  => 'Stay moved',
                    RescheduleRequest::STATUS_DECLINED  => 'Reschedule declined',
                    default                             => 'Reschedule withdrawn',
                },
                trim(($by ?: '') . ($request->decision_note ? ' — ' . $request->decision_note : ''), ' —') ?: null,
                $request->status === RescheduleRequest::STATUS_APPROVED ? 'check-circle' : 'x',
                match ($request->status) {
                    RescheduleRequest::STATUS_APPROVED => self::COLOR_GOOD,
                    RescheduleRequest::STATUS_DECLINED => self::COLOR_BAD,
                    default                            => self::COLOR_NEUTRAL,
                }
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
            $by = $checkout->staff ? 'By ' . $checkout->staff->name : 'Manual';

            // An emergency check-out ended the stay early, which is the one
            // entry here someone will come back asking about — so it says so
            // plainly and carries the reason the desk gave at the time.
            $isEmergency = $checkout->method === 'emergency';

            $push(
                $manila($checkout->checked_out_at),
                $isEmergency ? 'Checked out early (emergency)' : 'Checked out',
                match (true) {
                    $isEmergency => $by . ($checkout->reason ? ' · ' . $checkout->reason : ''),
                    // Read, not spelled. This said "2 PM checkout" and would
                    // have gone on saying it after check-out moved to noon —
                    // on a record whose whole job is to be believed later.
                    $checkout->method === 'auto' => 'Automatic (' . StaySchedule::checkoutLabel() . ' checkout)',
                    default => $by,
                },
                'log-out',
                $isEmergency ? self::COLOR_BAD : self::COLOR_GOOD
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

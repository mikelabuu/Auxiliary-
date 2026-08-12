<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A paid booking cannot be cancelled — the money is not coming back, and the
 * room was taken off sale on the strength of it. The only thing a guest whose
 * plans change can do is ask to move the stay, and they have to ask before
 * check-in time on the day they were due to arrive. This is where that ask
 * lives.
 *
 * Deliberately a request rather than a self-service date change. Moving a stay
 * can change what it costs (a shorter stay, a busier week), can fail outright
 * because the rooms are spoken for on the new dates, and is the kind of
 * decision the desk already makes for discounts and payment proofs. Approval
 * is what actually moves `bookings.check_in` / `check_out`.
 *
 * The original dates are copied in at submission time rather than read back
 * off the booking, because approving the request is precisely the act that
 * overwrites them — without a copy, the record of what changed erases itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reschedule_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // Nullable for the same reason bookings.user_id is: a desk-entered
            // booking has no account behind it, and staff may file the request
            // on the guest's behalf over the phone.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // pending | approved | declined | withdrawn.
            // A plain string, matching the convention the rest of the schema
            // settled on in 2026_07_20_000001 — the statuses are validated in
            // the model, not by the column type.
            $table->string('status')->default('pending');

            $table->date('original_check_in');
            $table->date('original_check_out');
            $table->date('requested_check_in');
            $table->date('requested_check_out');

            $table->text('reason');

            // Set on approve/decline. `staff` keys are ON DELETE SET NULL
            // everywhere else in this schema — the record survives, only the
            // attribution is lost.
            $table->foreignId('reviewed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // The two questions asked of this table: "does this booking have an
            // open request?" (the guard on every guest-facing entry point) and
            // "what is in the queue?" (the staff list, oldest first).
            $table->index(['booking_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reschedule_requests');
    }
};

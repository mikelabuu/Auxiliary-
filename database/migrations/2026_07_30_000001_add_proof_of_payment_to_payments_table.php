<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual proof-of-payment: the guest settles over GCash or a bank transfer,
 * uploads the receipt they were given, and staff confirm it by eye before the
 * booking is marked paid. The sandbox gateway stays exactly as it was — this
 * is a second road to the same destination, not a replacement.
 *
 * `payments.status` is a plain VARCHAR with no CHECK constraint, so the two
 * new states ride on the existing column: awaiting_verification and rejected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Where the uploaded receipt image lives on the private disk.
            $table->string('proof_path')->nullable()->after('gateway_response');

            // gcash | bank_transfer — how the guest says they sent the money.
            $table->string('proof_method')->nullable()->after('proof_path');

            // The reference number printed on the guest's own receipt. Not
            // unique: guests mistype, and a rejected attempt is re-uploaded
            // with the same number.
            $table->string('proof_reference')->nullable()->after('proof_method');

            $table->timestamp('proof_submitted_at')->nullable()->after('proof_reference');

            // Who cleared it. Nullable and null-on-delete so removing a staff
            // account never destroys the payment record it approved.
            $table->foreignId('verified_by')->nullable()->after('proof_submitted_at')
                ->constrained('staff')->nullOnDelete();

            $table->timestamp('verified_at')->nullable()->after('verified_by');

            // Shown back to the guest so they know what to fix and re-upload.
            $table->text('rejection_reason')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'proof_path',
                'proof_method',
                'proof_reference',
                'proof_submitted_at',
                'verified_by',
                'verified_at',
                'rejection_reason',
            ]);
        });
    }
};

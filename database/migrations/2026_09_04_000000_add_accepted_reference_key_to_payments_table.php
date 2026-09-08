<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A receipt reference is a claim while it is awaiting review, so it cannot be
 * unique at submission time: a rejected typo must remain correctable. Once a
 * cashier accepts it, however, that bank transaction may settle one booking
 * only. The nullable key below records method + normalized reference only for
 * successful proof payments, and the unique index closes concurrent replays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('accepted_reference_key', 191)
                ->nullable()
                ->after('proof_reference');
        });

        // Bring historical successful proof payments under the same guard.
        // If historical duplicates already exist, preserve every payment but
        // key the first one only; future attempts still collide with it.
        $used = [];

        DB::table('payments')
            ->where('status', 'success')
            ->whereNotNull('proof_method')
            ->whereNotNull('proof_reference')
            ->orderBy('id')
            ->chunkById(200, function ($payments) use (&$used): void {
                foreach ($payments as $payment) {
                    $method = strtolower(trim((string) $payment->proof_method));
                    $reference = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $payment->proof_reference)));

                    if ($method === '' || $reference === '') {
                        continue;
                    }

                    $key = $method . ':' . $reference;

                    if (isset($used[$key])) {
                        continue;
                    }

                    DB::table('payments')->where('id', $payment->id)->update([
                        'accepted_reference_key' => $key,
                    ]);
                    $used[$key] = true;
                }
            });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('accepted_reference_key', 'payments_accepted_reference_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_accepted_reference_key_unique');
            $table->dropColumn('accepted_reference_key');
        });
    }
};

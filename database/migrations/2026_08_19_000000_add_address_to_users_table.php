<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The guest's home address, kept on the account instead of only on the stay.
 *
 * `bookings.guest_address` is a flattened label ("Bangkal, Makati City, Metro
 * Manila") built from the PSGC codes and then thrown away — which is the right
 * shape for the front desk to read, and useless for putting the four dropdowns
 * back where the guest left them. So the codes are stored here as well.
 *
 * Bare nine-digit codes, not the "CODE|NAME" composite the form posts: the
 * code is the half that is stable, and PsgcDirectory turns it back into the
 * official name at render time. Storing the label too would pin a place name
 * to whatever it was called on the day of the first booking, and a renamed
 * municipality would then match no option in the dropdown and silently show
 * as blank.
 *
 * province_code is nullable on purpose and not merely lenient: NCR cities hang
 * straight off the region and genuinely have no province.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('region_code', 9)->nullable()->after('phone');
            $table->string('province_code', 9)->nullable()->after('region_code');
            $table->string('city_code', 9)->nullable()->after('province_code');
            $table->string('barangay_code', 9)->nullable()->after('city_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['region_code', 'province_code', 'city_code', 'barangay_code']);
        });
    }
};

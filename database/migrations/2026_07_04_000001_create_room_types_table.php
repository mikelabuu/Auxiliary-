<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 100);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->timestamps();
        });

        // Backfill from existing rooms so current data keeps working immediately.
        $capacityDefaults = [
            'deluxe'     => 2,
            'double'     => 2,
            'triple'     => 3,
            'quadruple'  => 4,
            'dormitory1' => 5,
            'dormitory2' => 6,
        ];

        $existingTypes = DB::table('rooms')
            ->select('room_type', DB::raw('MAX(price) as max_price'))
            ->groupBy('room_type')
            ->get();

        $now = now();

        foreach ($existingTypes as $row) {
            DB::table('room_types')->insert([
                'slug'       => $row->room_type,
                'name'       => ucfirst($row->room_type),
                'base_price' => $row->max_price,
                'capacity'   => $capacityDefaults[$row->room_type] ?? 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};

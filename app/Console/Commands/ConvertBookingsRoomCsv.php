<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Room;


class ConvertBookingsRoomCsv extends Command
{
protected $signature = 'bookings:convert-room-csv';
protected $description = 'Convert bookings.room_numbers (CSV) into booking_room pivot rows';


public function handle(): int
{
$this->info('Starting conversion: building room map...');


// Map room_number -> id
$roomMap = Room::pluck('id', 'room_number')->toArray();


$totalInserted = 0;
$missing = [];


Booking::chunk(200, function ($bookings) use (&$roomMap, &$totalInserted, &$missing) {
$inserts = [];


foreach ($bookings as $b) {
$csv = trim($b->room_numbers ?? '');
if ($csv === '') continue;


// split by comma and trim spaces
$roomNumbers = preg_split('/\s*,\s*/', $csv);


foreach ($roomNumbers as $rn) {
if ($rn === '') continue;
if (isset($roomMap[$rn])) {
$inserts[] = [
'booking_id' => $b->id,
'room_id' => $roomMap[$rn],
'created_at' => now(),
'updated_at' => now(),
];
} else {
$missing[$rn] = ($missing[$rn] ?? 0) + 1;
$this->warn("Booking {$b->id}: room_number '{$rn}' not found in rooms table");
}
}
}


if (!empty($inserts)) {
foreach (array_chunk($inserts, 500) as $chunk) {
DB::table('booking_room')->insertOrIgnore($chunk);
$totalInserted += count($chunk);
}
$this->info('Inserted '.count($inserts).' pivot rows for current chunk.');
}
});


$this->info('Done. Total inserted (approx): ' . $totalInserted);
if (!empty($missing)) {
$this->warn('Some room_numbers were not found in rooms table. Summary (room_number => occurrences):');
foreach ($missing as $rn => $count) {
$this->line(" - {$rn} => {$count}");
}
}


$this->info('Conversion finished. Verify results before dropping the old CSV data.');
return 0;
}
}
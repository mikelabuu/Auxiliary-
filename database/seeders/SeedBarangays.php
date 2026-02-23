<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedBarangays extends Seeder
{
    public function run()
    {
        $file = database_path('seeders/data/psgc.csv');

        if (!file_exists($file)) {
            $this->command->error("PSGC CSV file not found.");
            return;
        }

        $handle = fopen($file, 'r');
        fgetcsv($handle); // skip header

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        $inserted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if ($row[3] !== 'Bgy') continue;

            $code = str_pad(trim($row[0]), 10, '0', STR_PAD_LEFT);
            $name = $row[1];

            // Try correspondence code first
            $parentCodeRaw = trim($row[2]);
            $cityCode = null;

            if (!empty($parentCodeRaw)) {
                $parentCode = str_pad($parentCodeRaw, 10, '0', STR_PAD_LEFT);
                if (DB::table('cities')->where('code', $parentCode)->exists()) {
                    $cityCode = $parentCode;
                }
            }

            // Fallback: derive from barangay PSGC first 6 digits + 4 zeros
            if (!$cityCode) {
                $derived = substr($code, 0, 6) . '0000';
                if (DB::table('cities')->where('code', $derived)->exists()) {
                    $cityCode = $derived;
                }
            }

            if ($cityCode) {
                DB::table('barangays')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'city_code' => $cityCode
                    ]
                );
                $inserted++;
            } else {
                $this->command->warn("Skipped barangay {$name} ({$code}): could not determine city");
                $skipped++;
            }
        }

        $this->command->info("Barangays imported. Inserted: {$inserted}, Skipped: {$skipped}");
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedCities extends Seeder
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
            $level = $row[3]; // Geographic Level
            if (!in_array($level, ['City', 'Mun'])) continue;

            $code = str_pad(trim($row[0]), 10, '0', STR_PAD_LEFT);
            $name = $row[1];

            // Try correspondence code first
            $parentCodeRaw = trim($row[2]);
            $provinceCode = null;

            if (!empty($parentCodeRaw)) {
                $parentCode = str_pad($parentCodeRaw, 10, '0', STR_PAD_LEFT);
                if (DB::table('provinces')->where('code', $parentCode)->exists()) {
                    $provinceCode = $parentCode;
                }
            }

            // Fallback: derive from city PSGC first 6 digits + 4 zeros
            if (!$provinceCode) {
                $derived = substr($code, 0, 6) . '0000';
                if (DB::table('provinces')->where('code', $derived)->exists()) {
                    $provinceCode = $derived;
                }
            }

            if ($provinceCode || $level === 'City' || $level === 'Mun') {
                DB::table('cities')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'province_code' => $provinceCode
                    ]
                );
                $inserted++;
            } else {
                $this->command->warn("Skipped city {$name} ({$code}): could not determine province");
                $skipped++;
            }
        }

        $this->command->info("Cities imported. Inserted: {$inserted}, Skipped: {$skipped}");
    }
}

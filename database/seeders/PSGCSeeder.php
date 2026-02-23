<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PSGCSeeder extends Seeder
{
    public function run()
    {
        $file = database_path('seeders/data/psgc.csv');

        if (!file_exists($file)) {
            $this->command->error("PSGC file not found.");
            return;
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle); // skip header

        // === STEP 1: Insert Regions ===
        rewind($handle);
        fgetcsv($handle); // skip header again
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[3] === 'Reg') { // Geographic Level
                DB::table('regions')->updateOrInsert(
                    ['code' => $row[0]],
                    ['name' => $row[1]]
                );
            }
        }

        // === STEP 2: Insert Provinces ===
        rewind($handle);
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[3] === 'Prov') {
                // Compute region code from first 2 digits of province PSGC
                $regionCode = substr($row[0], 0, 2) . '00000000';
                $regionExists = DB::table('regions')->where('code', $regionCode)->exists();
                if ($regionExists) {
                    DB::table('provinces')->updateOrInsert(
                        ['code' => $row[0]],
                        [
                            'name' => $row[1],
                            'region_code' => $regionCode
                        ]
                    );
                } else {
                    $this->command->warn("Skipped province {$row[1]} ({$row[0]}): region {$regionCode} not found");
                }
            }
        }

        // === STEP 3: Insert Cities / Municipalities ===
        rewind($handle);
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[3] === 'City' || $row[3] === 'Mun') {
                // Compute province code from first 4 digits of city PSGC
                $provinceCode = substr($row[0], 0, 4) . '000000';
                $provinceExists = DB::table('provinces')->where('code', $provinceCode)->exists();
                // NCR/HUC cities may not have a province
                $provinceCode = $provinceExists ? $provinceCode : null;

                DB::table('cities')->updateOrInsert(
                    ['code' => $row[0]],
                    [
                        'name' => $row[1],
                        'province_code' => $provinceCode
                    ]
                );
            }
        }

        // === STEP 4: Insert Barangays ===
        rewind($handle);
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[3] === 'Bgy') {
                // Compute city code from first 6 digits of barangay PSGC
                $cityCode = substr($row[0], 0, 6) . '0000';
                $cityExists = DB::table('cities')->where('code', $cityCode)->exists();
                if ($cityExists) {
                    DB::table('barangays')->updateOrInsert(
                        ['code' => $row[0]],
                        [
                            'name' => $row[1],
                            'city_code' => $cityCode
                        ]
                    );
                } else {
                    $this->command->warn("Skipped barangay {$row[1]} ({$row[0]}): city {$cityCode} not found");
                }
            }
        }

        fclose($handle);
        $this->command->info('PSGC data imported successfully using PSGC hierarchy.');
    }
}

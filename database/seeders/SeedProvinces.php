<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedProvinces extends Seeder
{
    public function run()
    {
        $file = database_path('seeders/data/psgc.csv');

        if (!file_exists($file)) {
            $this->command->error("PSGC CSV file not found.");
            return;
        }

        if (($handle = fopen($file, 'r')) === false) {
            $this->command->error("Cannot open PSGC CSV file.");
            return;
        }

        fgetcsv($handle); // skip header

        while (($row = fgetcsv($handle)) !== false) {
            $level = trim($row[3]);
            if ($level === 'Prov') {
                $code = str_pad(trim($row[0]), 10, '0', STR_PAD_LEFT);
                $name = trim($row[1]);

                // Region code from first 2 digits
                $regionCode = substr($code, 0, 2) . '00000000';
                $regionExists = DB::table('regions')->where('code', $regionCode)->exists();

                if ($regionExists) {
                    DB::table('provinces')->updateOrInsert(
                        ['code' => $code],
                        [
                            'name' => $name,
                            'region_code' => $regionCode
                        ]
                    );
                } else {
                    $this->command->warn("Skipped province {$name} ({$code}): region {$regionCode} not found");
                }
            }
        }

        fclose($handle);
        $this->command->info('Provinces imported successfully.');
    }
}

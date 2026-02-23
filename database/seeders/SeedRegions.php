<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedRegions extends Seeder
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
            if ($level === 'Reg') {
                $code = str_pad(trim($row[0]), 10, '0', STR_PAD_LEFT);
                $name = trim($row[1]);

                DB::table('regions')->updateOrInsert(
                    ['code' => $code],
                    ['name' => $name]
                );
            }
        }

        fclose($handle);
        $this->command->info('Regions imported successfully.');
    }
}

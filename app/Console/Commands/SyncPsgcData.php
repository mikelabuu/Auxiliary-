<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pull the Philippine Standard Geographic Code list into the repo.
 *
 * The address dropdowns used to call https://psgc.gitlab.io from inside a
 * Livewire mount, on the server, while rendering checkout. Measured on
 * 2026-08-09 that round-trip was ~1.8s, it ran again for every region a guest
 * picked, and a slow or unreachable psgc.gitlab.io meant nobody could finish a
 * booking. Geographic codes change a handful of times a year, so paying a
 * network call per dropdown was buying nothing.
 *
 * The output is committed. That matters: a fresh clone or a deploy that never
 * runs this command must still have working address fields, so the data is a
 * repo artefact and this command is only how it gets refreshed.
 *
 * Codes are kept as positional arrays rather than keyed objects because the
 * barangay list is 42k rows and the key names would be most of the file.
 */
class SyncPsgcData extends Command
{
    protected $signature = 'psgc:sync {--dry-run : Report what would change without writing}';

    protected $description = 'Refresh the bundled PSGC address data (regions, provinces, cities, barangays) from psgc.gitlab.io.';

    private const BASE = 'https://psgc.gitlab.io/api';

    public function handle(): int
    {
        $path = resource_path('data/psgc.json');

        $this->info('Fetching PSGC data. This hits the network four times.');

        try {
            $regions   = $this->fetch('/regions');
            $provinces = $this->fetch('/provinces');
            $cities    = $this->fetch('/cities-municipalities');
            $barangays = $this->fetch('/barangays');
        } catch (\Throwable $e) {
            $this->error('Fetch failed: ' . $e->getMessage());
            $this->line('The committed copy is untouched, so the site keeps working.');

            return self::FAILURE;
        }

        // Barangays are grouped by the city they belong to so a lookup is an
        // array index rather than a scan of 42k rows. A barangay carries
        // either cityCode or municipalityCode, never both.
        $byCity = [];
        foreach ($barangays as $b) {
            $key = $b['cityCode'] ?: ($b['municipalityCode'] ?? '');
            if ($key === '') {
                continue;
            }
            $byCity[$key][] = [$b['code'], $b['name']];
        }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'source'       => self::BASE,
            'regions'      => array_map(fn ($r) => [$r['code'], $r['name']], $regions),
            'provinces'    => array_map(fn ($p) => [$p['code'], $p['name'], $p['regionCode']], $provinces),
            // NCR has no provinces, so its cities carry an empty provinceCode
            // and are reached through regionCode instead. Both are kept.
            'cities'       => array_map(
                fn ($c) => [$c['code'], $c['name'], (string) ($c['provinceCode'] ?? ''), (string) ($c['regionCode'] ?? '')],
                $cities
            ),
            'barangays'    => $byCity,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->newLine();
        $this->line(sprintf(
            'regions %d · provinces %d · cities %d · barangays %d (%d city buckets)',
            count($payload['regions']),
            count($payload['provinces']),
            count($payload['cities']),
            count($barangays),
            count($byCity)
        ));
        $this->line('payload: ' . round(strlen($json) / 1024) . ' KB');

        if ($this->option('dry-run')) {
            $this->comment('Dry run, nothing written.');

            return self::SUCCESS;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, $json);
        $this->info('Wrote ' . $path);
        $this->comment('Run `php artisan cache:clear` if the old payload is still cached.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $endpoint): array
    {
        $response = Http::timeout(180)->get(self::BASE . $endpoint);

        if (! $response->successful()) {
            throw new \RuntimeException("{$endpoint} returned HTTP {$response->status()}");
        }

        $rows = $response->json();

        if (! is_array($rows) || $rows === []) {
            throw new \RuntimeException("{$endpoint} returned no rows");
        }

        $this->line('  ' . str_pad($endpoint, 24) . count($rows) . ' rows');

        return $rows;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * The address gazetteer, read as the server's own source of truth.
 *
 * Every booking form posts its address fields as "CODE|NAME" — the code the
 * guest picked, plus the label that sat next to it in the dropdown. The
 * controllers only ever read the label (`explode('|', ...)[1] ?? ''`), which
 * made the stored address entirely the client's word: a malformed value
 * silently produced an address with a level missing, and a hand-written POST
 * could put any text at all where a barangay belongs. Neither shows up until
 * the front desk needs the address to identify a guest.
 *
 * The code is the half worth trusting, because it can be checked. This looks
 * each one up in the committed PSGC payload and hands back the official name,
 * so a booking's address always matches the government list the dropdowns were
 * built from.
 *
 * The payload is the same file `php artisan psgc:sync` writes and
 * PsgcController serves; it is read through here so the two cannot drift.
 */
class PsgcDirectory
{
    /** Matches PsgcController: the payload only changes between deploys. */
    private const TTL = 86400;

    /** Every PSGC code is exactly nine digits, at all four levels. */
    private const CODE = '/^\d{9}$/';

    /** "030801001|Bangkal" -> "030801001". Tolerates a bare code. */
    public static function code(?string $value): string
    {
        return trim(explode('|', (string) $value, 2)[0]);
    }

    /** The label the client sent. Only ever used as a fallback. */
    public static function postedName(?string $value): string
    {
        return trim(explode('|', (string) $value, 2)[1] ?? '');
    }

    /**
     * Whether the gazetteer is actually loaded.
     *
     * Callers use this to decide whether "not in the list" means anything. If
     * `psgc:sync` has never run there is nothing to check against, and a
     * checkout that cannot be completed is worse than an address that was not
     * cross-checked — the same line PsgcController takes when it serves empty
     * dropdowns rather than a 500.
     */
    public function isAvailable(): bool
    {
        return $this->index()['regions'] !== [];
    }

    /** Is this code a real place at this level? */
    public function exists(string $level, ?string $value, ?string $cityValue = null): bool
    {
        return isset($this->names($level, $cityValue)[self::code($value)]);
    }

    /**
     * The official name for a posted "CODE|NAME" value.
     *
     * Falls back to the posted label only when the code cannot be resolved —
     * which, once PsgcCode has validated the field, means the gazetteer is
     * missing rather than the code being wrong.
     */
    public function name(string $level, ?string $value, ?string $cityValue = null): string
    {
        return $this->names($level, $cityValue)[self::code($value)]
            ?? self::postedName($value);
    }

    /** code => name for one level. Barangays are indexed per city. */
    public function names(string $level, ?string $cityValue = null): array
    {
        if ($level !== 'barangays') {
            return $this->index()[$level] ?? [];
        }

        $cityCode = self::code($cityValue);

        // Guard the cache key: this is the only place a request value reaches
        // anything stateful, exactly as in PsgcController::barangays().
        if (! preg_match(self::CODE, $cityCode)) {
            return [];
        }

        return Cache::remember("psgc.index.barangays.{$cityCode}", self::TTL, function () use ($cityCode) {
            return array_column($this->payload()['barangays'][$cityCode] ?? [], 1, 0);
        });
    }

    /**
     * The three flat levels as code => name.
     *
     * Cached as one small map rather than decoding 1.2 MB on every booking
     * POST. Rows are [code, name, ...parents], so column 1 keyed by column 0.
     */
    private function index(): array
    {
        return Cache::remember('psgc.index', self::TTL, function () {
            $psgc = $this->payload();

            return [
                'regions'   => array_column($psgc['regions'] ?? [], 1, 0),
                'provinces' => array_column($psgc['provinces'] ?? [], 1, 0),
                'cities'    => array_column($psgc['cities'] ?? [], 1, 0),
            ];
        });
    }

    /**
     * The whole payload, memoised for the life of the process.
     *
     * Returns an empty shape rather than throwing when the file is missing;
     * see isAvailable() for what callers do with that.
     */
    public function payload(): array
    {
        static $decoded = null;

        if ($decoded !== null) {
            return $decoded;
        }

        $path = resource_path('data/psgc.json');

        if (! is_readable($path)) {
            report(new \RuntimeException("PSGC payload missing at {$path}. Run `php artisan psgc:sync`."));

            return $decoded = ['regions' => [], 'provinces' => [], 'cities' => [], 'barangays' => []];
        }

        return $decoded = json_decode(file_get_contents($path), true) ?: [];
    }
}

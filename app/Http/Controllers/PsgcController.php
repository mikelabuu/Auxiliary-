<?php

namespace App\Http\Controllers;

use App\Services\PsgcDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Address data for the guest and staff booking forms.
 *
 * Reads the committed payload written by `php artisan psgc:sync`; nothing here
 * touches the network. The file is 1.2 MB, so it is decoded once per cache
 * period rather than per request — and the two endpoints are split because the
 * shapes have very different costs:
 *
 *   locations  ~152 KB  fetched once, drives region/province/city entirely in
 *                       the browser with no further requests
 *   barangays  ~0.7 KB  average per city (25 KB at the worst), fetched only
 *                       when a city is actually picked
 *
 * Both are immutable between deploys, so they carry long cache headers and the
 * browser asks for a given city's barangays exactly once.
 */
class PsgcController extends Controller
{
    /** Geographic codes change a few times a year; a day is plenty. */
    private const TTL = 86400;

    public function locations(): JsonResponse
    {
        $data = Cache::remember('psgc.locations', self::TTL, function () {
            $psgc = $this->payload();

            return [
                'regions'   => $psgc['regions'] ?? [],
                'provinces' => $psgc['provinces'] ?? [],
                'cities'    => $psgc['cities'] ?? [],
            ];
        });

        return $this->cached($data);
    }

    public function barangays(string $cityCode): JsonResponse
    {
        // Codes are digits. Rejecting anything else keeps a junk value from
        // being used as a cache key, which is the only way user input reaches
        // anything stateful here.
        if (! preg_match('/^\d{6,12}$/', $cityCode)) {
            return response()->json(['barangays' => []], 422);
        }

        $data = Cache::remember("psgc.barangays.{$cityCode}", self::TTL, function () use ($cityCode) {
            $psgc = $this->payload();

            return ['barangays' => $psgc['barangays'][$cityCode] ?? []];
        });

        return $this->cached($data);
    }

    /**
     * The whole file, memoised for the life of the request.
     *
     * Read through PsgcDirectory, which is also what the booking forms use to
     * turn a posted code into a name — one reader, so the endpoint that offers
     * an address and the validation that accepts it can never disagree about
     * which places exist. Returns an empty shape rather than throwing when the
     * file is missing: an address form with empty dropdowns is recoverable, a
     * 500 on checkout is not. `psgc:sync` is what puts the file there.
     */
    private function payload(): array
    {
        return app(PsgcDirectory::class)->payload();
    }

    private function cached(array $data): JsonResponse
    {
        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=' . self::TTL);
    }
}

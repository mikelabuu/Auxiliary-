<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Hits every parameterless GET route as a signed-in master_admin and reports
 * anything that is not a success or an intentional redirect.
 *
 * The point is coverage, not depth: a page that 500s on an empty database, or
 * because a query uses a function the driver does not have, is the kind of
 * defect that stays invisible until someone opens that tab in front of an
 * audience. Routes taking parameters are skipped — they need real fixtures.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_get_route_responds(): void
    {
        $staff = Staff::create([
            'name' => 'Smoke Tester',
            'email' => 'smoke@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'master_admin',
            'is_suspended' => false,
        ]);

        // UserFactory still writes the pre-2025_09_12 `name` column; build by hand.
        $guest = \App\Models\User::forceCreate([
            'username' => 'smoke-guest',
            'email' => 'smoke-guest@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        // A little data so pages are not all trivially empty.
        Room::forceCreate(['room_number' => '101', 'room_type' => 'deluxe', 'wing' => 'rooster', 'status' => 'available']);
        Room::forceCreate(['room_number' => '102', 'room_type' => 'double', 'wing' => 'rooster', 'status' => 'maintenance']);

        $results = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            // Parameterised routes need fixtures; skip rather than guess.
            if (str_contains($uri, '{')) {
                continue;
            }

            // Not pages: file downloads, feeds hit separately, framework internals.
            if (preg_match('#^(_|storage/|up$|sanctum/|livewire/)#', $uri)) {
                continue;
            }

            $status = 0;
            $note = '';

            // Sign in on the guard the route actually uses. `actingAs($u,'staff')`
            // also makes `staff` the DEFAULT guard, so using it everywhere makes
            // guest routes resolve Auth::user() to a Staff — which reports
            // failures that cannot happen in a browser, where the default guard
            // is always `web`.
            $middleware = $route->gatherMiddleware();
            $isStaffRoute = (bool) array_filter(
                $middleware,
                fn ($m) => is_string($m) && str_contains($m, 'auth:staff')
            );

            try {
                $response = $isStaffRoute
                    ? $this->actingAs($staff, 'staff')->get('/' . ltrim($uri, '/'))
                    : $this->actingAs($guest)->get('/' . ltrim($uri, '/'));
                $status = $response->getStatusCode();

                // Laravel converts exceptions into a 500 response rather than
                // rethrowing, so the cause is on the response, not in a catch.
                if ($status >= 500 && $response->exception) {
                    $note = class_basename($response->exception) . ': ' . substr($response->exception->getMessage(), 0, 130);
                }
            } catch (\Throwable $e) {
                $status = 500;
                $note = class_basename($e) . ': ' . substr($e->getMessage(), 0, 130);
            }

            $results[] = ['uri' => '/' . ltrim($uri, '/'), 'status' => $status, 'note' => $note];
        }

        /*
         * Known-unrunnable under SQLite, and only under SQLite: both dashboards
         * build their charts with MONTH()/YEAR()/DATE_FORMAT()/YEARWEEK(), which
         * are MySQL functions the SQLite driver does not have. They work in the
         * app (MySQL); they simply cannot be exercised by this suite.
         *
         * They are listed rather than silently skipped so the cost stays
         * visible: these are the two highest-traffic screens in the product and
         * nothing here can protect them from a regression. Porting those queries
         * to a driver-agnostic form would put them back under test.
         */
        $dbPortabilityBlocked = ['/staff/dashboard', '/front-desk/dashboard'];

        // Report everything, then fail only on server errors.
        $bad = array_values(array_filter(
            $results,
            fn ($r) => $r['status'] >= 500 && ! in_array($r['uri'], $dbPortabilityBlocked, true)
        ));
        $other = array_values(array_filter($results, fn ($r) => $r['status'] >= 400 && $r['status'] < 500));

        $render = function (array $rows) {
            return implode("\n", array_map(
                fn ($r) => sprintf('  %-3d  %-42s %s', $r['status'], $r['uri'], $r['note']),
                $rows
            ));
        };

        fwrite(STDERR, "\n\n=== ROUTE SMOKE TEST ===\n");
        fwrite(STDERR, 'checked: ' . count($results) . " GET routes\n");
        fwrite(STDERR, 'server errors (5xx): ' . count($bad) . "\n" . $render($bad) . "\n");
        fwrite(STDERR, 'client errors (4xx): ' . count($other) . "\n" . $render($other) . "\n\n");

        $this->assertSame([], $bad, "Routes returning 5xx:\n" . $render($bad));
    }
}

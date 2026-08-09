<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The address dropdowns and the data behind them.
 *
 * This replaced a Livewire component that called psgc.gitlab.io from inside
 * mount(), on the server, while rendering checkout — roughly 1.8s per dropdown
 * with a third party sitting on the checkout critical path. The replacement is
 * Blade + Alpine over a committed payload, so what needs protecting is:
 *
 *  - the wire contract the controllers parse ("CODE|NAME" under four fixed
 *    field names), because BookingController and CreatesStaffBooking both
 *    explode('|') and take [1] for the name;
 *  - old() repopulation, so a failed submit does not wipe the address;
 *  - the NCR special case, whose cities carry an EMPTY provinceCode. Matching
 *    on empty when no province is chosen yet offered Manila under Central
 *    Luzon, which is exactly the bug this guards.
 */
class AddressSelectorTest extends TestCase
{
    use RefreshDatabase;

    private function guest(): User
    {
        $user = User::create([
            'username' => 'addr',
            'email'    => 'addr@example.test',
            'phone'    => '09171234567',
            'password' => Hash::make('password-12345'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    public function test_checkout_renders_the_four_address_fields_without_livewire(): void
    {
        $response = $this->actingAs($this->guest())->get('/checkout');

        $response->assertOk();

        foreach (['region_code', 'province_code', 'city_code', 'barangay_code'] as $field) {
            $response->assertSee('name="' . $field . '"', false);
        }

        // Checkout was the last public page pulling the Livewire bundle, and it
        // did so only for this component. If Livewire returns here without the
        // @section('livewire') flag, the page ends up with two Alpines.
        $response->assertDontSee('livewireScripts', false);
        $response->assertDontSee('/livewire/livewire.js', false);
    }

    public function test_a_failed_submit_keeps_the_chosen_address(): void
    {
        $guest = $this->guest();

        $address = [
            'region_code'   => '030000000|Central Luzon',
            'province_code' => '034900000|Nueva Ecija',
            'city_code'     => '034922000|Science City of Munoz',
            'barangay_code' => '034922019|Mangandingay',
        ];

        // Deliberately incomplete: the address is valid, everything else is not,
        // so validation bounces and old() has to carry the address back.
        $this->actingAs($guest)
            ->from('/checkout')
            ->post('/booking', $address)
            ->assertRedirect('/checkout');

        $followed = $this->actingAs($guest)->get('/checkout');

        foreach ($address as $value) {
            $followed->assertSee($value, false);
        }
    }

    public function test_locations_endpoint_serves_the_bundled_payload(): void
    {
        $response = $this->getJson('/psgc/locations');

        $response->assertOk()
            ->assertJsonStructure(['regions', 'provinces', 'cities']);

        $data = $response->json();

        $this->assertCount(17, $data['regions'], 'PSGC has 17 regions');
        $this->assertNotEmpty($data['provinces']);
        $this->assertNotEmpty($data['cities']);
    }

    /**
     * Province-less cities are not an NCR-only case.
     *
     * Cotabato City and Isabela City are independent cities sitting inside
     * regions that DO have provinces. An earlier version of the component only
     * reached province-less cities when the whole region had no provinces,
     * which made those two impossible to select — Cotabato City alone is a city
     * of roughly 325,000 people.
     */
    public function test_every_province_less_city_is_reachable_through_its_region(): void
    {
        $cities = $this->getJson('/psgc/locations')->json('cities');

        // [code, name, provinceCode, regionCode]
        $independent = array_filter($cities, fn ($c) => $c[2] === '');

        $this->assertNotEmpty($independent);

        foreach ($independent as $city) {
            $this->assertNotSame('', $city[3], "{$city[1]} has neither a province nor a region");
        }

        $names = array_column($independent, 1);
        $this->assertContains('City of Cotabato', $names);
        $this->assertContains('City of Isabela', $names);

        // NCR is entirely independent cities, and there are 17 of them.
        $ncr = array_filter($cities, fn ($c) => $c[3] === '130000000');
        $this->assertCount(17, $ncr);
        foreach ($ncr as $city) {
            $this->assertSame('', $city[2], "{$city[1]} in NCR should not carry a province");
        }
    }

    /**
     * The client narrows cities by province, falling back to the region's
     * independent cities when no province is chosen. Matching on an empty
     * provinceCode ALONE would offer Manila under Central Luzon, because the
     * empty rows span three different regions.
     */
    public function test_independent_cities_do_not_leak_across_regions(): void
    {
        $cities = $this->getJson('/psgc/locations')->json('cities');

        $regionsWithIndependentCities = array_unique(
            array_column(array_filter($cities, fn ($c) => $c[2] === ''), 3)
        );

        $this->assertGreaterThan(1, count($regionsWithIndependentCities));

        // Central Luzon has no independent cities, so leaving province blank
        // there must offer nothing at all.
        $centralLuzon = array_filter($cities, fn ($c) => $c[2] === '' && $c[3] === '030000000');
        $this->assertEmpty($centralLuzon, 'Central Luzon must not surface province-less cities');
    }

    public function test_barangays_are_scoped_to_their_city(): void
    {
        // Science City of Munoz — CLSU's own municipality.
        $response = $this->getJson('/psgc/barangays/034922000');

        $response->assertOk()->assertJsonStructure(['barangays']);

        $barangays = $response->json('barangays');
        $this->assertNotEmpty($barangays);

        foreach ($barangays as $b) {
            $this->assertStringStartsWith('034922', $b[0], 'barangay leaked in from another city');
        }
    }

    public function test_a_malformed_city_code_is_rejected_rather_than_used_as_a_cache_key(): void
    {
        $this->getJson('/psgc/barangays/not-a-code')->assertStatus(422);
        $this->getJson('/psgc/barangays/1')->assertStatus(422);
    }
}

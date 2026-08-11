<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who may switch a staff account off, and whether the act is recorded.
 *
 * The staff records page has always drawn Suspend/Unsuspend behind
 * `$isMaster && $staff->role !== 'master_admin'`, but that rule lived only in
 * the Blade template. The endpoints themselves checked nothing, and the route
 * group admits `admin` as well as `master_admin` — so any admin could POST the
 * master account's id and disable it. EnsureStaffNotSuspended then made it
 * immediate: the master's session died on its next request, and the login
 * refused them afterwards.
 *
 * These tests pin the server-side rule to the one the buttons already state.
 */
class StaffSuspensionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, string $email): Staff
    {
        return Staff::create([
            'name' => ucfirst($role) . ' Account',
            'email' => $email,
            'password' => 'correct-horse-battery',
            'role' => $role,
            'is_suspended' => false,
        ]);
    }

    public function test_admin_cannot_suspend_the_master_admin(): void
    {
        $admin = $this->staff('admin', 'admin@example.test');
        $master = $this->staff('master_admin', 'master@example.test');

        $this->actingAs($admin, 'staff')
            ->postJson("/staff/staff-records/{$master->id}/suspend")
            ->assertStatus(403);

        $this->assertFalse((bool) $master->fresh()->is_suspended);
    }

    public function test_master_admin_cannot_be_suspended_even_by_another_master(): void
    {
        $master = $this->staff('master_admin', 'master@example.test');
        $other = $this->staff('master_admin', 'second-master@example.test');

        $this->actingAs($master, 'staff')
            ->postJson("/staff/staff-records/{$other->id}/suspend")
            ->assertStatus(403);

        $this->assertFalse((bool) $other->fresh()->is_suspended);
    }

    public function test_admin_cannot_suspend_their_own_account(): void
    {
        $admin = $this->staff('admin', 'admin@example.test');

        $this->actingAs($admin, 'staff')
            ->postJson("/staff/staff-records/{$admin->id}/suspend")
            ->assertStatus(403);

        $this->assertFalse((bool) $admin->fresh()->is_suspended);
    }

    public function test_admin_cannot_suspend_a_peer_admin(): void
    {
        $admin = $this->staff('admin', 'admin@example.test');
        $peer = $this->staff('admin', 'peer@example.test');

        $this->actingAs($admin, 'staff')
            ->postJson("/staff/staff-records/{$peer->id}/suspend")
            ->assertStatus(403);

        $this->assertFalse((bool) $peer->fresh()->is_suspended);
    }

    public function test_admin_cannot_unsuspend_anyone(): void
    {
        $admin = $this->staff('admin', 'admin@example.test');
        $desk = $this->staff('frontdesk', 'desk@example.test');
        $desk->update(['is_suspended' => true]);

        $this->actingAs($admin, 'staff')
            ->postJson("/staff/staff-records/{$desk->id}/unsuspend")
            ->assertStatus(403);

        $this->assertTrue((bool) $desk->fresh()->is_suspended);
    }

    public function test_master_admin_can_still_suspend_and_unsuspend_ordinary_staff(): void
    {
        $master = $this->staff('master_admin', 'master@example.test');
        $desk = $this->staff('frontdesk', 'desk@example.test');

        $this->actingAs($master, 'staff')
            ->postJson("/staff/staff-records/{$desk->id}/suspend")
            ->assertOk();

        $this->assertTrue((bool) $desk->fresh()->is_suspended);

        $this->actingAs($master, 'staff')
            ->postJson("/staff/staff-records/{$desk->id}/unsuspend")
            ->assertOk();

        $this->assertFalse((bool) $desk->fresh()->is_suspended);
    }

    public function test_suspending_staff_is_written_to_the_audit_log(): void
    {
        $master = $this->staff('master_admin', 'master@example.test');
        $desk = $this->staff('frontdesk', 'desk@example.test');

        $this->actingAs($master, 'staff')
            ->postJson("/staff/staff-records/{$desk->id}/suspend")
            ->assertOk();

        $log = AuditLog::where('action', 'staff_suspended')->latest('id')->first();

        $this->assertNotNull($log, 'suspending a staff account wrote no audit entry');
        $this->assertSame($master->id, $log->staff_id);
        $this->assertSame('Staff', $log->target_type);
        $this->assertSame($desk->id, $log->target_id);
    }

    public function test_unsuspending_staff_is_written_to_the_audit_log(): void
    {
        $master = $this->staff('master_admin', 'master@example.test');
        $desk = $this->staff('frontdesk', 'desk@example.test');
        $desk->update(['is_suspended' => true]);

        $this->actingAs($master, 'staff')
            ->postJson("/staff/staff-records/{$desk->id}/unsuspend")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff_unsuspended',
            'staff_id' => $master->id,
            'target_id' => $desk->id,
        ]);
    }

    /**
     * `housekeeping` has no landing route — redirectForRole() bounces it back
     * to the login — so an account created with it could never be signed into.
     * It must not be offered as an assignable role until it has somewhere to go.
     */
    public function test_housekeeping_is_not_an_assignable_role(): void
    {
        $this->assertNotContains('housekeeping', Staff::ASSIGNABLE_ROLES);

        $master = $this->staff('master_admin', 'master@example.test');

        $this->actingAs($master, 'staff')
            ->post('/staff/staff/create', [
                'name' => 'Cleaner Account',
                'email' => 'cleaner@example.test',
                'role' => 'housekeeping',
                'password' => 'correct-horse-battery',
                'password_confirmation' => 'correct-horse-battery',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('staff', ['email' => 'cleaner@example.test']);
    }
}

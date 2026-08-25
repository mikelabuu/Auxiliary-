<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\AuditLogger;

class StaffRecordsController extends Controller
{
    /**
     * The password floor for a staff account.
     *
     * Higher than the guest side's 8. These accounts reach the console —
     * bookings, payment verification, guest records, the audit log — there are
     * only a handful of them, and each is created by hand by the master admin,
     * so a longer minimum costs almost nothing to operate. `max:72` is not a
     * policy choice: bcrypt ignores everything past the 72nd byte, so without
     * it a longer password would not be the password on the account.
     */
    private const PASSWORD_RULES = ['string', 'min:10', 'max:72', 'confirmed'];

    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'latest'); // newest first by default
        $role = $request->input('role', 'all');
        $perPage = 10;

        $stats = [
            'total'     => Staff::count(),
            'active'    => Staff::where('is_suspended', false)->count(),
            'suspended' => Staff::where('is_suspended', true)->count(),
            'admins'    => Staff::whereIn('role', ['admin', 'master_admin'])->count(),
        ];

        $staffs = Staff::query()
            ->when($search, function($q) use ($search) {
                $q->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                });
            })
            ->when($role !== 'all', fn($q) => $q->where('role', $role))
            ->orderBy(
                'created_at',
                $sort === 'oldest' ? 'asc' : 'desc'
            )
            ->paginate($perPage)
            ->withQueryString();

        return view('staff.staffrecords.index', compact('staffs', 'search', 'sort', 'role', 'stats'));
    }

    // Handle signup post
    public function createStaff(Request $request)
    {   
        $creator = Auth::guard('staff')->user();
        if ($creator->role != 'master_admin'){
            return back()->withErrors(['Only Master Account is allowed to create staff accounts.'])->withInput();
        }
        // Validate input
        $request->validate([
            'name'     => 'required|string|min:3|max:50',
            'email'    => [
                'required',
                'email',
                'unique:staff,email',
                // Guests and staff share one login, so an address can only
                // resolve to one identity. Mirrors the check in signup().
                Rule::unique('users', 'email'),
            ],
            // CREATABLE_ROLES, not ASSIGNABLE_ROLES: the create form no longer
            // offers Admin, and a select is only a suggestion — without this
            // the same POST with role=admin still lands an admin account.
            'role'     => ['required', Rule::in(Staff::CREATABLE_ROLES)],
            'password' => ['required', ...self::PASSWORD_RULES],
        ], [
            'email.unique' => 'That email address is already in use by a guest or staff account.',
        ]);

        // Create the staff account
        $staff = Staff::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => Hash::make($request->password),
        ]);

        AuditLogger::log(
            'staff_created',
            $staff,
            null,
            $staff->only(['id', 'name', 'email', 'role']),
            "Staff {$creator->name} ({$creator->role}) created a new staff account: {$staff->name} ({$staff->role})."
        );

        return redirect()->back()->with('success', "Staff account created successfully.");
    }

    public function update(Request $request, Staff $staff)
    {
        // Only allow the logged-in staff to update their own account
        $loggedInStaff = auth()->guard('staff')->user();
        if ($loggedInStaff->id !== $staff->id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate input
        $request->validate([
            'name'                  => 'required|string|min:3|max:50',
            'email'                 => 'required|email|unique:staff,email,' . $staff->id,
            'password'              => ['nullable', ...self::PASSWORD_RULES],
            'current_password'      => 'required',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $staff->password)) {
            return back()->withErrors(['current_password' => 'Incorrect current password.'])->withInput();
        }

        $oldValues = $staff->only(['name', 'email']);

        // Update fields
        $staff->name = $request->name;
        $staff->email = $request->email;

        $passwordChanged = false;
        if ($request->filled('password')) {
            $staff->password = Hash::make($request->password);
            $passwordChanged = true;
        }

        $staff->save();

        $newValues = $staff->only(['name', 'email']);
        $description = 'Staff updated their own account.';
        if ($passwordChanged) {
            $description .= ' Password was changed.';
        }

        AuditLogger::log(
            action: 'staff_update_account',
            target: $staff,
            oldValues: $oldValues,
            newValues: $newValues,
            description: $description,
            staffModel: $loggedInStaff
        );

        return back()->with('success', 'Account updated successfully.');
    }

    public function activity(Staff $staff)
    {
        $logs = \App\Models\AuditLog::where('staff_id', $staff->id)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($log) => [
                'action'      => ucwords(str_replace('_', ' ', $log->action)),
                'description' => $log->description,
                'target'      => $log->target_type ? $log->target_type . ($log->target_id ? " #{$log->target_id}" : '') : null,
                'when'        => $log->created_at->timezone(config('hostel.timezone'))->format('M d, Y · h:i A'),
            ]);

        return response()->json([
            'name'       => $staff->name,
            'last_login' => $staff->last_login_at ? \Carbon\Carbon::parse($staff->last_login_at)->timezone(config('hostel.timezone'))->format('M d, Y · h:i A') : null,
            'logs'       => $logs,
        ]);
    }

    public function suspend(Staff $staff)
    {
        if ($denied = $this->denyUnlessMayManage($staff, 'change staff access', 'suspended')) {
            return $denied;
        }

        $staff->update(['is_suspended' => true]);

        $this->logSuspensionChange($staff, true);

        return response()->json(['success' => true, 'message' => 'User suspended successfully']);
    }

    public function unsuspend(Staff $staff)
    {
        if ($denied = $this->denyUnlessMayManage($staff, 'change staff access', 'suspended')) {
            return $denied;
        }

        $staff->update(['is_suspended' => false]);

        $this->logSuspensionChange($staff, false);

        return response()->json(['success' => true, 'message' => 'User unsuspended successfully']);
    }

    /**
     * Remove a staff account for good — the D the staff console never had.
     *
     * Suspension covers someone who has stepped away; this covers someone who
     * has left. Until now the only way to do it was straight in the database,
     * which is precisely the operation you least want performed by hand.
     *
     * Deleting is not free, and the console should not pretend otherwise. Every
     * foreign key into `staff` is ON DELETE SET NULL, so the rows a person
     * touched — audit entries, check-ins, check-outs, payment verifications,
     * discount reviews — survive the deletion but stop naming anybody. The
     * audit log is the system's account of who did what, so losing the "who"
     * quietly would defeat it.
     *
     * That is why the identity is written into the log BEFORE the row goes:
     * the deletion entry carries the id, name, email and role, so the trail can
     * still answer "who was staff #11" long after staff #11 stopped existing.
     */
    public function destroy(Staff $staff)
    {
        if ($denied = $this->denyUnlessMayManage($staff, 'remove staff accounts', 'deleted')) {
            return $denied;
        }

        $actor = Auth::guard('staff')->user();
        $removed = $staff->only(['id', 'name', 'email', 'role', 'is_suspended']);

        // Logged first: once the row is gone the FKs have already nulled every
        // reference to it, and there is nothing left to describe.
        AuditLogger::log(
            'staff_deleted',
            $staff,
            $removed,
            null,
            sprintf(
                'Master Admin %s permanently deleted staff account: #%d %s <%s>, role %s. '
                . 'Records this account touched remain but no longer reference a staff row.',
                $actor->name,
                $staff->id,
                $staff->name,
                $staff->email,
                $staff->role
            ),
            $actor
        );

        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff account deleted permanently',
        ]);
    }

    /**
     * Who may act on somebody else's staff account.
     *
     * The staff records page has always drawn the Suspend/Unsuspend buttons
     * behind `$isMaster && $staff->role !== 'master_admin'` — but that rule
     * lived only in the Blade template, and those endpoints checked nothing at
     * all. The route group admits `admin` as well as `master_admin`, so any
     * admin could POST the id of the master account and switch it off.
     *
     * That is not merely a missing check, it is an inversion: `master_admin`
     * is the role this system treats as above the others — it is the only one
     * that may create staff (createStaff), the only one that may edit them
     * (updateByMasterAdmin), and it is deliberately kept out of
     * ASSIGNABLE_ROLES so no form can ever hand it out. A subordinate role
     * being able to disable it undoes all of that in one request, and
     * EnsureStaffNotSuspended makes it immediate: the master's live session is
     * torn down on its very next request and the login refuses them after.
     *
     * So the server states the same rule the buttons do. Acting on yourself
     * falls out of it for free — the only actor who passes the first test is a
     * master_admin, and the second test refuses every master_admin target,
     * including themselves. That is what stops the last master account
     * suspending or deleting itself and leaving nobody able to manage staff.
     *
     * @param string $verb  What the actor was trying to do, for the 403.
     * @param string $past  Past participle used in the master-account refusal.
     */
    private function denyUnlessMayManage(Staff $target, string $verb, string $past)
    {
        $actor = Auth::guard('staff')->user();

        if ($actor->role !== 'master_admin') {
            return response()->json([
                'success' => false,
                'message' => "Only the master account may {$verb}.",
            ], 403);
        }

        if ($target->role === 'master_admin') {
            return response()->json([
                'success' => false,
                'message' => "The master account cannot be {$past}.",
            ], 403);
        }

        return null;
    }

    /**
     * Disabling a colleague's access is exactly the kind of act the audit log
     * exists to record, and it was the one staff action that wrote nothing —
     * the guest-side equivalents in UserRecordsController have logged from the
     * start, so the trail simply stopped at the staff table.
     */
    private function logSuspensionChange(Staff $staff, bool $suspended): void
    {
        $actor = Auth::guard('staff')->user();

        AuditLogger::log(
            $suspended ? 'staff_suspended' : 'staff_unsuspended',
            $staff,
            ['is_suspended' => ! $suspended],
            ['is_suspended' => $suspended],
            sprintf(
                'Master Admin %s %s staff account: %s (%s).',
                $actor->name,
                $suspended ? 'suspended' : 'unsuspended',
                $staff->name,
                $staff->role
            ),
            $actor
        );
    }

    public function updateByMasterAdmin(Request $request)
    {
        $admin = Auth::guard('staff')->user();

        if ($admin->role !== 'master_admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'name'     => 'required|string|min:3|max:50',
            'email'    => 'required|email|unique:staff,email,' . $request->staff_id,
            'role'     => ['required', Rule::in(Staff::ASSIGNABLE_ROLES)],
            'password' => ['nullable', ...self::PASSWORD_RULES],
        ]);

        $staff = Staff::findOrFail($request->staff_id);
        $oldValues = $staff->only(['name', 'email', 'role']);

        $staff->name = $request->name;
        $staff->email = $request->email;
        $staff->role = $request->role;

        if ($request->filled('password')) {
            $staff->password = Hash::make($request->password);
        }

        $staff->save();

        AuditLogger::log(
            'master_admin_edit_staff',
            $staff,
            $oldValues,
            $staff->only(['name', 'email', 'role']),
            "Master Admin {$admin->name} updated staff account: {$staff->name} ({$staff->role}).",
            $admin
        );

        return back()->with('success', "Staff account updated successfully.");
    }
}   

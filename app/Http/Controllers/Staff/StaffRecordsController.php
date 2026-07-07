<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\AuditLogger;

class StaffRecordsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'latest'); // newest first by default
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
            ->orderBy(
                'created_at',
                $sort === 'oldest' ? 'asc' : 'desc'
            )
            ->paginate($perPage)
            ->withQueryString();

        return view('staff.staffrecords.index', compact('staffs', 'search', 'sort', 'stats'));
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
            'email'    => 'required|email|unique:staff,email',
            'role'     => 'required|in:admin,frontdesk,housekeeping',
            'password' => 'required|string|min:6|confirmed', // add confirmation if you want
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
            'password'              => 'nullable|string|min:6|confirmed',
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

    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required']);

        $staff = Auth::guard('staff')->user();

        if (!Hash::check($request->password, $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid password']);
        }

        return response()->json(['success' => true]);
    }

    public function suspend(Staff $staff)
    {
        $staff->update(['is_suspended' => true]);
        return response()->json(['success' => true, 'message' => 'User suspended successfully']);
    }

    public function unsuspend(Staff $staff)
    {
        $staff->update(['is_suspended' => false]);
        return response()->json(['success' => true, 'message' => 'User unsuspended successfully']);
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
            'role'     => 'required|in:admin,frontdesk,housekeeping',
            'password' => 'nullable|string|min:6|confirmed',
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

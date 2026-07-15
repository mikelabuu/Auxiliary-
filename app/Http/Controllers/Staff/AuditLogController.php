<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    /**
     * Display audit logs wrapper page.
     */
    public function index()
    {
        return view('staff.audit.index');
    }

    /**
     * Show detail (modal) for a single audit log (ajax).
     */
    public function show($id)
    {   
        $admin = Auth::guard('staff')->user();
        $log = AuditLog::with('staff')->findOrFail($id);

        if ($admin->role != 'master_admin'){
            return response()->json(['success' => false, 'message' => 'Only Master Account is allowed to view details'], 403);
        }

        $old = $log->old_values;
        $new = $log->new_values;

        return response()->json([
            'success' => true,
            'log' => $log,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}

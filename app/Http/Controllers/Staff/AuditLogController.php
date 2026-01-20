<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Staff;

class AuditLogController extends Controller
{
    /**
     * Display audit logs with filters, search, sort, pagination.
     */
    public function index(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $table = $request->query('table'); // e.g. bookings, payments, etc.
        $search = $request->query('search');
        $role = $request->query('role');
        $action = $request->query('action');
        $sort = $request->query('sort', 'latest'); // latest | oldest | role | target
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');
        $perPage = (int) $request->query('per_page', 15);

        $query = AuditLog::with('staff');

        // Table filter: allow both short keys and fully-qualified class names
        if ($table) {
            $map = [
                'bookings'   => 'Booking',
                'discounts'  => 'Discount',
                'payments'   => 'Payment',
                'users'      => 'User',
                'staff'      => 'Staff',
                'rooms'      => 'Room',
                'unsorted'   => 'unsorted', // placeholder
            ];

            $key = strtolower($table);

            if (array_key_exists($key, $map)) {
                if ($key === 'unsorted') {
                    $knownTables = ['Booking','Discount','Payment','User','Staff','Room'];
                    $query->whereNotIn('target_type', $knownTables)
                        ->orWhereNull('target_type')
                        ->orWhere('target_type', '');
                } else {
                    $targetType = $map[$key];
                    $query->where(function ($q) use ($targetType) {
                        $q->where('target_type', $targetType)
                        ->orWhere('target_type', 'like', "%{$targetType}");
                    });
                }
            } else {
                // fallback: filter by raw value
                $query->where('target_type', $table);
            }
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->toDateString());
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->toDateString());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('target_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            })->orWhereHas('staff', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'role') {
            $query->orderBy('role', 'asc')->orderBy('created_at', 'desc');
        } elseif ($sort === 'target') {
            $query->orderBy('target_type', 'asc')->orderBy('created_at', 'desc');
        } else { // latest
            $query->orderBy('created_at', 'desc');
        }

        $logs = $query->paginate($perPage)->appends($request->query());

        // provide compact lists for quick UI building
        $availableRoles = AuditLog::select('role')->distinct()->pluck('role')->filter()->values();
        $availableActions = AuditLog::select('action')->distinct()->pluck('action')->filter()->values();

        return view('staff.audit.index', [
            'logs' => $logs,
            'availableRoles' => $availableRoles,
            'availableActions' => $availableActions,
            'filters' => [
                'table' => $table,
                'search' => $search,
                'role' => $role,
                'action' => $action,
                'sort' => $sort,
                'from' => $dateFrom,
                'to' => $dateTo,
                'per_page' => $perPage,
            ],
            'staff' => $staff,
        ]);
    }

    /**
     * Show detail (modal) for a single audit log (ajax).
     */
    public function show($id)
    {   
        $admin = Auth::guard('staff')->user();
        $log = AuditLog::with('staff')->findOrFail($id);

        if ($admin->role != 'master_admin'){
            return back()->withErrors(['Only Master Account is allowed to view details'])->withInput();
        }
        // decode JSON fields if they are json / arrays
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

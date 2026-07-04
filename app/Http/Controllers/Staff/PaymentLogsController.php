<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class PaymentLogsController extends Controller
{
    public function index(Request $request)
    {   
        $staff = Auth::guard('staff')->user();
        $search = $request->input('search');
        $sort = $request->input('sort', 'latest'); // default sort
        $perPage = 15;

        $query = Payment::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                  ->orWhere('booking_id', 'like', "%$search%")
                  ->orWhere('reference_no', 'like', "%$search%")
                  ->orWhere('landbank_transaction_id', 'like', "%$search%");
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'latest') {
            $query->orderBy('created_at', 'desc');
        }
        
        $payments = $query->paginate($perPage);

        AuditLogger::log(
            'view_payment_records',
            'Payments', // Just use a string label instead of array
            null,
            null,
            "Staff {$staff->name} viewed payment records (page {$payments->currentPage()} showing {$payments->count()} of {$payments->total()} total)."
        );

        return view('staff.paymentlogs.index', compact('payments', 'search', 'sort'));
    }
}

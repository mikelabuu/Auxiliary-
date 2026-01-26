<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;

class AdminBalanceController extends Controller
{
    public function index()
    {
        $balances = Balance::with(['booking.user'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('staff.balance', compact('balances'));
    }
}

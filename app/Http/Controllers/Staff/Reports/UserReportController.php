<?php

namespace App\Http\Controllers\Staff\Reports;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserReportController extends Controller
{
    public function exportAll(Request $request)
    {
        return Excel::download(new UsersExport('all', $request), 'users_all.xlsx');
    }

    public function exportActive(Request $request)
    {
        return Excel::download(new UsersExport('active', $request), 'users_active.xlsx');
    }

    public function exportSuspended(Request $request)
    {
        return Excel::download(new UsersExport('suspended', $request), 'users_suspended.xlsx');
    }
}

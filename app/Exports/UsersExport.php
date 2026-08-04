<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class UsersExport implements FromCollection, WithHeadings
{
    protected $type;
    protected $request;

    public function __construct($type = 'all', $request = null)
    {
        $this->type = $type;
        $this->request = $request;
    }

    public function collection()
    {
        $query = User::query();

        // Optional date range filter (registration)
        if ($this->request?->has('from_date') && $this->request?->has('to_date')) {
            $query->whereBetween('created_at', [$this->request->from_date, $this->request->to_date]);
        }

        // Filter by type
        switch ($this->type) {
            case 'active':
                $query->where('is_suspended', false);
                break;
            case 'suspended':
                $query->where('is_suspended', true);
                break;
        }

        $users = $query->get();

        return $users->map(function($user) {
            return [
                'User ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Email Verified At' => $user->email_verified_at ? Carbon::parse($user->email_verified_at)->setTimezone(config('hostel.timezone'))->format('Y-m-d H:i:s') : '',
                'Phone' => $user->phone,
                'Last Login At' => $user->last_login_at ? Carbon::parse($user->last_login_at)->setTimezone(config('hostel.timezone'))->format('Y-m-d H:i:s') : '',
                'Registration Date' => $user->created_at->setTimezone(config('hostel.timezone'))->format('Y-m-d'),
                'Status' => $user->is_suspended ? 'Suspended' : 'Active',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'User ID',
            'Name',
            'Email',
            'Email Verified At',
            'Phone',
            'Last Login At',
            'Registration Date',
            'Status',
        ];
    }
}

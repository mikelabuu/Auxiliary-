<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditLog;
use Carbon\Carbon;

class AuditLogs extends Component
{
    use WithPagination;

    public $table = '';
    public $search = '';
    public $role = '';
    public $action = '';
    public $sort = 'latest';
    public $from = '';
    public $to = '';
    public $perPage = 15;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'table'   => ['except' => ''],
        'search'  => ['except' => ''],
        'role'    => ['except' => ''],
        'action'  => ['except' => ''],
        'sort'    => ['except' => 'latest'],
        'from'    => ['except' => ''],
        'to'      => ['except' => ''],
        'perPage' => ['except' => 15, 'as' => 'per_page'],
    ];

    public function updatingTable() { $this->resetPage(); }
    public function updatingSearch() { $this->resetPage(); }
    public function updatingRole() { $this->resetPage(); }
    public function updatingAction() { $this->resetPage(); }
    public function updatingSort() { $this->resetPage(); }
    public function updatingFrom() { $this->resetPage(); }
    public function updatingTo() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['table', 'search', 'role', 'action', 'sort', 'from', 'to', 'perPage']);
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::with('staff');

        if ($this->table) {
            $map = [
                'bookings'   => 'Booking',
                'discounts'  => 'Discount',
                'payments'   => 'Payment',
                'users'      => 'User',
                'staff'      => 'Staff',
                'rooms'      => 'Room',
                'unsorted'   => 'unsorted',
            ];

            $key = strtolower($this->table);

            if (array_key_exists($key, $map)) {
                if ($key === 'unsorted') {
                    $knownTables = ['Booking','Discount','Payment','User','Staff','Room'];
                    $query->where(function($q) use ($knownTables) {
                        $q->whereNotIn('target_type', $knownTables)
                          ->orWhereNull('target_type')
                          ->orWhere('target_type', '');
                    });
                } else {
                    $targetType = $map[$key];
                    $query->where(function ($q) use ($targetType) {
                        $q->where('target_type', $targetType)
                          ->orWhere('target_type', 'like', "%{$targetType}");
                    });
                }
            } else {
                $query->where('target_type', $this->table);
            }
        }

        if ($this->role) {
            $query->where('role', $this->role);
        }

        if ($this->action) {
            $query->where('action', $this->action);
        }

        if ($this->from) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->from)->toDateString());
        }
        if ($this->to) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->to)->toDateString());
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('action', 'like', "%{$this->search}%")
                  ->orWhere('target_id', 'like', "%{$this->search}%")
                  ->orWhere('ip_address', 'like', "%{$this->search}%")
                  ->orWhereHas('staff', function ($sq) {
                      $sq->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($this->sort === 'role') {
            $query->orderBy('role', 'asc')->orderBy('created_at', 'desc');
        } elseif ($this->sort === 'target') {
            $query->orderBy('target_type', 'asc')->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $logs = $query->paginate($this->perPage);

        $availableRoles = AuditLog::select('role')->distinct()->pluck('role')->filter()->values();
        $availableActions = AuditLog::select('action')->distinct()->pluck('action')->filter()->values();

        return view('livewire.staff.audit-logs', [
            'logs' => $logs,
            'availableRoles' => $availableRoles,
            'availableActions' => $availableActions,
        ]);
    }
}

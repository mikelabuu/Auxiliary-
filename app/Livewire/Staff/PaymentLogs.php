<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithSorting;
use App\Models\Payment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class PaymentLogs extends Component
{
    use WithPagination;
    use WithSorting;

    public $search = '';
    public $sort = 'latest';
    public $statusFilter = 'all';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search'       => ['except' => ''],
        'sort'         => ['except' => 'latest'],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSort()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'sort', 'statusFilter']);
        $this->resetPage();
    }

    public function mount()
    {
        // Once per login session: this used to write a row on every visit and
        // page change, and became 14% of the entire audit log — noise that
        // buries the actions the log exists to record.
        $staff = Auth::guard('staff')->user();
        if ($staff && !session()->has('audited_payment_records_view')) {
            session(['audited_payment_records_view' => true]);
            AuditLogger::log(
                'view_payment_records',
                'Payments',
                null,
                null,
                "Staff {$staff->name} viewed payment records."
            );
        }
    }

    public function render()
    {
        $perPage = 15;
        // Eager-load the booking's guest name so the ledger can show WHO paid
        // without an N+1 per row. Only id + guest_name are needed.
        $query = Payment::query()->with('booking:id,guest_name');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('id', 'like', "%{$this->search}%")
                  ->orWhere('booking_id', 'like', "%{$this->search}%")
                  ->orWhere('reference_no', 'like', "%{$this->search}%")
                  ->orWhere('landbank_transaction_id', 'like', "%{$this->search}%")
                  ->orWhereHas('booking', function ($b) {
                      $b->where('guest_name', 'like', "%{$this->search}%");
                  });
            });
        }

        if (in_array($this->statusFilter, ['success', 'failed', 'pending'])) {
            $query->where('status', $this->statusFilter);
        }

        $query = $this->applySort(
            $query,
            ['id', 'booking_id', 'amount', 'status', 'gateway', 'reference_no', 'landbank_transaction_id', 'created_at'],
            fn ($q) => $q->orderBy('created_at', $this->sort === 'oldest' ? 'asc' : 'desc')
        );

        $payments = $query->paginate($perPage);

        // Ledger-wide stats (deliberately independent of search/filter)
        $stats = [
            'collected'       => (float) Payment::where('status', 'success')->sum('amount'),
            'collected_today' => (float) Payment::where('status', 'success')->whereDate('created_at', now('Asia/Manila')->toDateString())->sum('amount'),
            'success'         => Payment::where('status', 'success')->count(),
            'failed'          => Payment::where('status', 'failed')->count(),
            'pending'         => Payment::where('status', 'pending')->count(),
        ];

        return view('livewire.staff.payment-logs', [
            'payments' => $payments,
            'stats'    => $stats,
        ]);
    }
}

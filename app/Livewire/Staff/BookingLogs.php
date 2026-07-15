<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Checkin;
use App\Models\Checkout;
use App\Models\NoShowLog;
use App\Models\ExpiryLog;
use App\Models\CancellationLog;
use Carbon\Carbon;

class BookingLogs extends Component
{
    use WithPagination;

    public $tab = 'checkins';
    public $search = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'tab'    => ['except' => 'checkins'],
        'search' => ['except' => ''],
    ];

    public function updatingTab()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'tab']);
        $this->resetPage();
    }

    public function render()
    {
        $perPage = 10;

        switch ($this->tab) {
            case 'checkouts':
                $logs = Checkout::with(['booking', 'staff'])
                    ->when($this->search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%{$this->search}%")
                          ->orWhere('guest_name', 'like', "%{$this->search}%")
                    ))
                    ->orderByDesc('checked_out_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->checked_out_at = Carbon::parse($item->checked_out_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            case 'noshow':
                $logs = NoShowLog::with(['booking', 'staff'])
                    ->when($this->search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%{$this->search}%")
                          ->orWhere('guest_name', 'like', "%{$this->search}%")
                    ))
                    ->orderByDesc('marked_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->marked_at = Carbon::parse($item->marked_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            case 'cancellations':
                $logs = CancellationLog::with(['booking'])
                    ->when($this->search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%{$this->search}%")
                          ->orWhere('guest_name', 'like', "%{$this->search}%")
                    ))
                    ->orderByDesc('cancelled_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(fn($item) => tap($item, fn($i) =>
                    $i->cancelled_at = Carbon::parse($i->cancelled_at)->format('M d, Y h:i A')
                ));
                break;

            case 'expiry':
                $logs = ExpiryLog::with(['booking', 'staff'])
                    ->when($this->search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%{$this->search}%")
                          ->orWhere('guest_name', 'like', "%{$this->search}%")
                    ))
                    ->orderByDesc('expired_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->expired_at = Carbon::parse($item->expired_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            default: // checkins
                $logs = Checkin::with(['booking', 'staff'])
                    ->when($this->search, fn($q) => $q->whereHas('booking', fn($b) =>
                        $b->where('id', 'like', "%{$this->search}%")
                          ->orWhere('guest_name', 'like', "%{$this->search}%")
                    ))
                    ->orderByDesc('checked_in_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->checked_in_at = Carbon::parse($item->checked_in_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;
        }

        return view('livewire.staff.booking-logs', [
            'logs' => $logs,
        ]);
    }
}
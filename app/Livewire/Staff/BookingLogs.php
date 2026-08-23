<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\WithPagination;
use App\Support\RefCode;
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

    /**
     * The booking-side search, shared by all five log tabs.
     *
     * The reference these tables print is BK-0004, and only the bare id was
     * compared — so the code staff are given to identify a booking by found
     * nothing. That is the fix; the rest is that this stood five times over,
     * once per tab, identical each time.
     *
     * The explicit where(fn) around the pair is belt-and-braces rather than a
     * repair: whereHas() already wraps a callback's constraints in their own
     * group, so the correlation was never at risk of being ORed away. It is
     * written out here because the added id clause makes three conditions
     * where there were two, and a grouping worth relying on is worth seeing.
     */
    private function matchesBooking(): \Closure
    {
        $refId = RefCode::toId($this->search);

        return fn ($q) => $q->whereHas('booking', fn ($b) => $b->where(function ($w) use ($refId) {
            $w->where('id', 'like', "%{$this->search}%")
              ->orWhere('guest_name', 'like', "%{$this->search}%");

            if ($refId !== null) {
                $w->orWhere('id', $refId);
            }
        }));
    }

    public function render()
    {
        $perPage = 10;

        switch ($this->tab) {
            case 'checkouts':
                $logs = Checkout::with(['booking', 'staff'])
                    ->when($this->search, $this->matchesBooking())
                    ->orderByDesc('checked_out_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->checked_out_at = Carbon::parse($item->checked_out_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            case 'noshow':
                $logs = NoShowLog::with(['booking', 'staff'])
                    ->when($this->search, $this->matchesBooking())
                    ->orderByDesc('marked_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->marked_at = Carbon::parse($item->marked_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            case 'cancellations':
                $logs = CancellationLog::with(['booking'])
                    ->when($this->search, $this->matchesBooking())
                    ->orderByDesc('cancelled_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(fn($item) => tap($item, fn($i) =>
                    $i->cancelled_at = Carbon::parse($i->cancelled_at)->format('M d, Y h:i A')
                ));
                break;

            case 'expiry':
                $logs = ExpiryLog::with(['booking', 'staff'])
                    ->when($this->search, $this->matchesBooking())
                    ->orderByDesc('expired_at')
                    ->paginate($perPage);

                $logs->getCollection()->transform(function ($item) {
                    $item->expired_at = Carbon::parse($item->expired_at)->format('M d, Y h:i A');
                    return $item;
                });
                break;

            default: // checkins
                $logs = Checkin::with(['booking', 'staff'])
                    ->when($this->search, $this->matchesBooking())
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
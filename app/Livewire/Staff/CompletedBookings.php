<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithSorting;
use App\Models\Booking;
use App\Support\RefCode;

class CompletedBookings extends Component
{
    use WithPagination;
    use WithSorting;

    // ① Declare public properties for every filter/search input
    public $search = '';
    public $sort = 'latest';

    // ② Use tailwind pagination theme (same as your existing components)
    protected $paginationTheme = 'tailwind';

    // ③ Sync properties to URL query string (for deep-linking/bookmarking)
    protected $queryString = [
        'search' => ['except' => ''],
        'sort'   => ['except' => 'latest'],
    ];

    // ④ Reset pagination when filters change
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSort()
    {
        $this->resetPage();
    }

    // ⑤ Clear all filters at once
    public function resetFilters()
    {
        $this->reset(['search', 'sort']);
        $this->resetPage();
    }

    // ⑥ The render method — this is your old controller's index() logic
    public function render()
    {
        // The reference this table prints — and offers a copy button for — is
        // BK-0004, which matched nothing against the bare id column.
        $refId = RefCode::toId($this->search);

        $query = Booking::where('status', 'completed')
            // Grouped, which it was not. `->where(status)->when(fn => where(id)
            // ->orWhere(guest_name))` binds as
            // `(status = completed AND id LIKE ...) OR guest_name LIKE ...`,
            // so searching a guest name on the *completed* page returned that
            // guest's live bookings too.
            ->when($this->search, fn($q) => $q->where(function ($w) use ($refId) {
                $w->where('id', 'like', "%{$this->search}%")
                  ->orWhere('guest_name', 'like', "%{$this->search}%");

                if ($refId !== null) {
                    $w->orWhere('id', $refId);
                }
            }));

        $query = $this->applySort(
            $query,
            ['id', 'guest_name', 'check_in', 'check_out', 'status', 'updated_at'],
            fn ($q) => $q->orderBy('updated_at', $this->sort === 'oldest' ? 'asc' : 'desc')
        );

        $bookings = $query->paginate(10);

        return view('livewire.staff.completed-bookings', [
            'bookings' => $bookings,
        ]);
    }
}

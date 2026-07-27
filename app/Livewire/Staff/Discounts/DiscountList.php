<?php

namespace App\Livewire\Staff\Discounts;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Discount;

class DiscountList extends Component
{
    use WithPagination;

    public $status = '';
    public $sort = '';

    /**
     * Pushed by the Echo listener on the discounts index when a
     * DiscountChanged broadcast lands — a guest submitting or withdrawing a
     * request, or another staff member reviewing one. The wire:poll on the
     * view stays as the fallback for when Reverb isn't running.
     */
    protected $listeners = ['refreshDiscountList' => '$refresh'];

    protected $updatesQueryString = ['status', 'sort'];

    protected $paginationTheme = 'tailwind'; // or 'bootstrap' depending on your setup

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingSort()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Discount::with('booking', 'files');

        if ($this->status) {
            $query->where('status', $this->status);
        }

        switch ($this->sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'checkin':
                $query->join('bookings', 'discounts.booking_id', '=', 'bookings.id')
                      ->orderBy('bookings.check_in', 'asc');
                break;
            default:
                $query->latest();
                break;
        }

        $discounts = $query->paginate(10);

        $counts = [
            ''         => Discount::count(),
            'pending'  => Discount::where('status', 'pending')->count(),
            'approved' => Discount::where('status', 'approved')->count(),
            'rejected' => Discount::where('status', 'rejected')->count(),
        ];

        return view('livewire.staff.discounts.discount-list', [
            'discounts' => $discounts,
            'counts' => $counts,
        ]);
    }
}

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

        return view('livewire.staff.discounts.discount-list', [
            'discounts' => $discounts
        ]);
    }
}

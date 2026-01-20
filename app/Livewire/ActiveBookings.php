<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Booking;

class ActiveBookings extends Component
{
    public $activeBookings = [];

    protected $listeners = ['refreshActiveBookings' => 'loadActiveBookings'];

    public function mount()
    {
        $this->loadActiveBookings();
    }

    public function loadActiveBookings()
    {
        $this->activeBookings = Booking::with('reservations.room')
            ->where('status', 'active')
            ->latest('check_in')
            ->get();
    }

    public function render()
    {
        return view('livewire.active-bookings');
    }
}

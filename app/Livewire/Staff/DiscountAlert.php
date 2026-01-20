<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use App\Models\Discount;

class DiscountAlert extends Component
{
    public $pendingCount = 0;

    public function render()
    {   
        // Fetch the count of pending discount requests
        $this->pendingCount = Discount::where('status', 'pending')->count();

        return view('livewire.staff.discount-alert');
    }
}



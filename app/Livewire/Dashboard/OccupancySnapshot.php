<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Room;
use App\Models\Reservation;

class OccupancySnapshot extends Component
{
    public $total = 0;
    public $occupied = 0;
    public $available = 0;
    public $percent = 0.0;
    public $pollInterval = 60; // seconds
    public $date;
    protected $occupyingStatuses = ['active'];

    public function mount()
    {
        $this->recalculate();
    }

    public function recalculate()
    {
        $today = Carbon::today('Asia/Manila')->toDateString();
        $cacheKey = "dashboard:occupancy:{$today}";

        $data = Cache::remember($cacheKey, 30, function () use ($today) {
            $totalRooms = Room::count();

            $occupiedRooms = Reservation::whereHas('booking', function ($q) use ($today) {
                $q->whereIn('status', $this->occupyingStatuses)
                  ->whereDate('check_in', '<=', $today);
            })->distinct('room_number')->count('room_number');

            $available = max(0, $totalRooms - $occupiedRooms);
            $percent = $totalRooms ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0.0;

            return [
                'total' => $totalRooms,
                'occupied' => $occupiedRooms,
                'available' => $available,
                'percent' => $percent,
            ];
        });

        $this->total = $data['total'];
        $this->occupied = $data['occupied'];
        $this->available = $data['available'];
        $this->percent = $data['percent'];

        // dispatch an event for the frontend chart to update
        $this->dispatch('occupancy-updated', [
            'total' => $this->total,
            'occupied' => $this->occupied,
            'available' => $this->available,
            'percent' => $this->percent,
        ]);
    }

    public function render()
    {
        return view('livewire.dashboard.occupancy-snapshot');
    }
}

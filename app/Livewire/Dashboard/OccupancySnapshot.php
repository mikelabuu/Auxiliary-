<?php

namespace App\Livewire\Dashboard;

use App\Models\Booking;
use App\Support\RoomCatalog;
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
    public $outOfService = 0;
    public $percent = 0.0;
    
    // Breakdown values
    public $dormTotal = 0;
    public $dormOccupied = 0;
    public $dormPercent = 0.0;

    public $standardTotal = 0;
    public $standardOccupied = 0;
    public $standardPercent = 0.0;

    public $deluxeTotal = 0;
    public $deluxeOccupied = 0;
    public $deluxePercent = 0.0;

    public $pollInterval = 30; // seconds
    public $date;
    protected $occupyingStatuses = [Booking::STATUS_ACTIVE];

    protected $listeners = ['refreshOccupancy' => 'forceRecalculate'];

    public function mount()
    {
        $this->recalculate();
    }

    /**
     * Broadcast pushes and the manual refresh button bypass the cache — an
     * event means something JUST changed, so serving the cached 30s snapshot
     * would show the pre-change numbers.
     */
    public function forceRecalculate()
    {
        Cache::forget($this->cacheKey());
        $this->recalculate();
    }

    protected function cacheKey(): string
    {
        return 'dashboard:occupancy:' . Carbon::today(config('hostel.timezone'))->toDateString();
    }

    public function recalculate()
    {
        $today = Carbon::today(config('hostel.timezone'))->toDateString();
        $cacheKey = $this->cacheKey();

        $data = Cache::remember($cacheKey, 30, function () use ($today) {
            $totalRooms = Room::count();

            $occupiedRoomNumbers = Reservation::whereHas('booking', function ($q) use ($today) {
                $q->whereIn('status', $this->occupyingStatuses)
                  ->where('check_in', '<=', $today);
            })->pluck('room_number')->toArray();

            $occupiedRoomsCount = count(array_unique($occupiedRoomNumbers));

            // "Available" used to be total − occupied, which silently counted
            // maintenance/cleaning rooms as sellable — this card said 22 while
            // every other panel said 16. Out-of-service rooms are their own
            // bucket; a set-union guards against a room being both.
            $outOfServiceNumbers = Room::whereIn('status', ['maintenance', 'cleaning'])
                ->pluck('room_number')->map(fn ($n) => trim($n))->toArray();
            $unavailable = array_unique(array_merge(
                array_map('trim', $occupiedRoomNumbers),
                $outOfServiceNumbers
            ));

            $available = max(0, $totalRooms - count($unavailable));
            $percent = $totalRooms ? round(($occupiedRoomsCount / $totalRooms) * 100, 1) : 0.0;

            // Dorm rooms
            $dormTotal = Room::whereIn('room_type', RoomCatalog::dormTypes())->count();
            $dormOccupied = Room::whereIn('room_type', RoomCatalog::dormTypes())
                ->whereIn('room_number', $occupiedRoomNumbers)->count();
            $dormPercent = $dormTotal > 0 ? round(($dormOccupied / $dormTotal) * 100, 1) : 0.0;

            // Standard rooms
            $standardTotal = Room::whereIn('room_type', RoomCatalog::standardTypes())->count();
            $standardOccupied = Room::whereIn('room_type', RoomCatalog::standardTypes())
                ->whereIn('room_number', $occupiedRoomNumbers)->count();
            $standardPercent = $standardTotal > 0 ? round(($standardOccupied / $standardTotal) * 100, 1) : 0.0;

            // Deluxe rooms
            $deluxeTotal = Room::where('room_type', 'deluxe')->count();
            $deluxeOccupied = Room::where('room_type', 'deluxe')
                ->whereIn('room_number', $occupiedRoomNumbers)->count();
            $deluxePercent = $deluxeTotal > 0 ? round(($deluxeOccupied / $deluxeTotal) * 100, 1) : 0.0;

            return [
                'total' => $totalRooms,
                'occupied' => $occupiedRoomsCount,
                'available' => $available,
                'outOfService' => count($outOfServiceNumbers),
                'percent' => $percent,
                
                'dormTotal' => $dormTotal,
                'dormOccupied' => $dormOccupied,
                'dormPercent' => $dormPercent,
                
                'standardTotal' => $standardTotal,
                'standardOccupied' => $standardOccupied,
                'standardPercent' => $standardPercent,
                
                'deluxeTotal' => $deluxeTotal,
                'deluxeOccupied' => $deluxeOccupied,
                'deluxePercent' => $deluxePercent,
            ];
        });

        $this->total = $data['total'];
        $this->occupied = $data['occupied'];
        $this->available = $data['available'];
        $this->outOfService = $data['outOfService'];
        $this->percent = $data['percent'];

        $this->dormTotal = $data['dormTotal'];
        $this->dormOccupied = $data['dormOccupied'];
        $this->dormPercent = $data['dormPercent'];

        $this->standardTotal = $data['standardTotal'];
        $this->standardOccupied = $data['standardOccupied'];
        $this->standardPercent = $data['standardPercent'];

        $this->deluxeTotal = $data['deluxeTotal'];
        $this->deluxeOccupied = $data['deluxeOccupied'];
        $this->deluxePercent = $data['deluxePercent'];

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

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Staff;
use App\Models\Room;
use App\Models\RoomType;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(PSGCSeeder::class);

        // Create Master Admin Staff
        $masterAdmin = Staff::create([
            'name' => 'Master Admin',
            'email' => 'master@example.com',
            'password' => Hash::make('password'),
            'role' => 'master_admin',
            'email_verified_at' => now(),
        ]);

        // Create Admin Staff
        Staff::create([
            'name' => 'Admin Staff',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Regular User
        User::create([
            'username' => 'testuser',
            'email' => 'user@example.com',
            'phone' => '09123456789',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Seed Room Types (must exist before rooms reference them, so a fresh
        // migrate+seed doesn't leave room_types empty — the migration's own
        // backfill only pulls from rooms that already exist at migration time)
        $roomTypesData = [
            ['slug' => 'double',     'name' => 'Double',     'base_price' => 1600.00, 'capacity' => 2],
            ['slug' => 'triple',     'name' => 'Triple',     'base_price' => 2100.00, 'capacity' => 3],
            ['slug' => 'quadruple',  'name' => 'Quadruple',  'base_price' => 2400.00, 'capacity' => 4],
            ['slug' => 'deluxe',     'name' => 'Deluxe',     'base_price' => 2500.00, 'capacity' => 2],
            ['slug' => 'dormitory1', 'name' => 'Dormitory1', 'base_price' => 2500.00, 'capacity' => 5],
            ['slug' => 'dormitory2', 'name' => 'Dormitory2', 'base_price' => 3000.00, 'capacity' => 6],
        ];

        foreach ($roomTypesData as $type) {
            RoomType::firstOrCreate(['slug' => $type['slug']], $type);
        }

        // Seed Rooms
        $roomsData = [
            ['room_number' => '101', 'room_type' => 'double', 'wing' => 'Block A', 'price' => 1600.00],
            ['room_number' => '102', 'room_type' => 'double', 'wing' => 'Block A', 'price' => 1600.00],
            ['room_number' => '103', 'room_type' => 'triple', 'wing' => 'Block B', 'price' => 2100.00],
            ['room_number' => '104', 'room_type' => 'triple', 'wing' => 'Block B', 'price' => 2100.00],
            ['room_number' => '201', 'room_type' => 'quadruple', 'wing' => 'Block A', 'price' => 2400.00],
            ['room_number' => '202', 'room_type' => 'quadruple', 'wing' => 'Block A', 'price' => 2400.00],
            ['room_number' => '203', 'room_type' => 'deluxe', 'wing' => 'Block C', 'price' => 2500.00],
            ['room_number' => '204', 'room_type' => 'deluxe', 'wing' => 'Block C', 'price' => 2500.00],
            ['room_number' => '301', 'room_type' => 'dormitory1', 'wing' => 'Dormitory Wing', 'price' => 2500.00],
            ['room_number' => '302', 'room_type' => 'dormitory1', 'wing' => 'Dormitory Wing', 'price' => 2500.00],
            ['room_number' => '303', 'room_type' => 'dormitory2', 'wing' => 'Dormitory Wing', 'price' => 3000.00],
            ['room_number' => '304', 'room_type' => 'dormitory2', 'wing' => 'Dormitory Wing', 'price' => 3000.00],
        ];

        foreach ($roomsData as $room) {
            Room::create([
                'room_number'   => $room['room_number'],
                'room_type'     => $room['room_type'],
                'wing'          => $room['wing'],
                'price'         => $room['price'],
                'status'        => 'available',
                'last_edited_by'=> $masterAdmin->id,
            ]);
        }
    }
}

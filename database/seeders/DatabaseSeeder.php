<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Staff;
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
        Staff::create([
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

        // Create Front Desk Staff. The front-desk console is its own role with
        // its own four screens, and `admin` cannot reach them (those routes are
        // `staff.role:frontdesk,master_admin`). Without a seeded account here
        // the entire console is unreachable on a fresh database.
        Staff::create([
            'name' => 'Front Desk',
            'email' => 'frontdesk@example.com',
            'password' => Hash::make('password'),
            'role' => 'frontdesk',
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

        // Room types and rooms. Held in RoomSeeder rather than inline here so
        // there is one definition of the inventory: this seeder cannot be run
        // against a database that already has accounts (the Staff::create calls
        // above hit the unique email constraint), and production needed the
        // rooms without the rest of this. Staff are created first so the rooms
        // can record a master admin as their last editor.
        $this->call(RoomSeeder::class);
    }
}

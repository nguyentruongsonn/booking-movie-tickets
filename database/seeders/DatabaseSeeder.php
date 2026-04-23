<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles & Permissions
        $roles = [
            'admin'    => 'Administrator',
            'manager'  => 'Cinema Manager',
            'staff'    => 'Staff',
            'customer' => 'Customer',
        ];

        foreach ($roles as $name => $displayName) {
            \App\Models\Role::firstOrCreate(['name' => $name], ['display_name' => $displayName]);
        }

        // 2. Default Admin User
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'full_name' => 'System Admin',
                'password'  => \Illuminate\Support\Facades\Hash::make('password'),
                'status'    => \App\Models\User::STATUS_ACTIVE,
            ]
        );
        $admin->roles()->sync([\App\Models\Role::where('name', 'admin')->first()->id]);

        // 3. Infrastructure
        $this->call([
            TheaterSeeder::class,
            ScreenSeeder::class,
            FormatSeeder::class,
            SoundSeeder::class,
            SubtitleSeeder::class,
            SeatTypeSeeder::class,
            SeatSeeder::class,
            MovieSeeder::class,
            ShowtimeSeeder::class,
            ProductSeeder::class,
            PromotionSeeder::class,
            CustomerPromotionSeeder::class,
        ]);
    }
}

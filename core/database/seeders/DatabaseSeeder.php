<?php

namespace Database\Seeders;

use App\Models\User;

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
        User::factory()->create([
            'name' => 'Sergio Brighenti',
            'email' => 'sergio@brighenti.me',
            'password' => Hash::make('xxx'),
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'a@a.a',
            'password' => Hash::make('xxx'),
        ]);
    }
}

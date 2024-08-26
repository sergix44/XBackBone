<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'password' => bcrypt('xxx'),
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'a@a.a',
            'password' => bcrypt('xxx'),
        ]);
    }
}

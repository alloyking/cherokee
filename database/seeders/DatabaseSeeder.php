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
        $this->call(CherokeeLexiconSeeder::class);
        $this->call(InitialUsersSeeder::class);

        if (app()->environment('local')) {
            if (! User::query()->where('email', 'test@example.com')->exists()) {
                User::factory()->create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ]);
            }
        }
    }
}

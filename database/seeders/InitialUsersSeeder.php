<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InitialUsersSeeder extends Seeder
{
    /**
     * Create initial users from config (env: INITIAL_ADMIN_*).
     * Idempotent: skips users that already exist by email.
     */
    public function run(): void
    {
        $users = config('initial-users.users', []);

        foreach ($users as $spec) {
            if (empty($spec['email'])) {
                continue;
            }

            if (User::query()->where('email', $spec['email'])->exists()) {
                continue;
            }

            User::query()->create([
                'name' => $spec['name'] ?? 'User',
                'email' => $spec['email'],
                'password' => $spec['password'],
            ]);
        }
    }
}

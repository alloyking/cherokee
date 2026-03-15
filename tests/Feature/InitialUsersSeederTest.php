<?php

use App\Models\User;
use Database\Seeders\InitialUsersSeeder;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\assertDatabaseHas;

test('initial users seeder creates admin users from config', function () {
    Config::set('initial-users.users', [
        [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
        ],
    ]);

    $this->seed(InitialUsersSeeder::class);

    assertDatabaseHas(User::class, [
        'email' => 'admin@example.com',
        'is_admin' => true,
    ]);
});

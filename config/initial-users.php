<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial users (Laravel Cloud / first deploy)
    |--------------------------------------------------------------------------
    |
    | Set INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL, INITIAL_ADMIN_PASSWORD in
    | your environment (e.g. Laravel Cloud dashboard). Then run:
    |   php artisan db:seed --class=InitialUsersSeeder
    | Users are created only if they don't already exist (by email).
    |
    */

    'users' => (function (): array {
        $users = [];
        $name = env('INITIAL_ADMIN_NAME');
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');
        if ($name !== null && $name !== '' && $email !== null && $email !== '' && $password !== null && $password !== '') {
            $users[] = ['name' => $name, 'email' => $email, 'password' => $password];
        }
        return $users;
    })(),

];

<?php

use App\Models\User;

test('admin users can access the admin panel', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('non-admin users can not access the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

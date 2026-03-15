<?php

use App\Livewire\Auth\LoginGateway;
use App\Models\User;
use Livewire\Livewire;

test('login gateway renders successfully', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Sign In');
});

test('users can authenticate using the login gateway', function () {
    $user = User::factory()->create();

    Livewire::test(LoginGateway::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/words');

    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    Livewire::test(LoginGateway::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

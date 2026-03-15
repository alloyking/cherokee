<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginGateway extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $this->remember)) {
            session()->regenerate();

            return redirect()->intended(route('words.index'));
        }

        $this->addError('email', 'The provided credentials do not match our records.');
    }

    #[\Livewire\Attributes\Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.login-gateway');
    }
}

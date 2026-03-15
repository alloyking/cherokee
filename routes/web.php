<?php

use App\Livewire\Auth\LoginGateway;
use App\Livewire\CherokeeWordsPage;
use Illuminate\Support\Facades\Route;

Route::get('/', LoginGateway::class)->name('login')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/words', CherokeeWordsPage::class)->name('words.index');
    Route::post('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

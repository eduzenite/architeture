<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::get('test', function () {
    $to = 'eduzenite11@gmail.com';

    Mail::raw('Olá! Esse é um email de teste enviado via MailHog.', function ($message) use ($to) {
        $message->to($to)->subject('Teste de Email via MailHog');
    });

    return 'Email enviado!';
});

require __DIR__.'/auth.php';

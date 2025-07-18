<?php

use App\Http\Controllers\UserFileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', function () {
    $files = Auth::user()?->toFiles ?? collect();
    return view('dashboard', compact('files'));
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/files', [UserFileController::class, 'index'])->name('files');
        Route::post('/files/upload', [UserFileController::class, 'upload'])->name('files.upload');
    });
});

require __DIR__ . '/auth.php';

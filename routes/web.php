<?php

use App\Enums\Medicine\AppearanceKeyEnum;
use App\Http\Controllers\UserFileController;
use App\Jobs\MedicineJob;
use App\Models\Medicine\Medicine;
use Illuminate\Http\Request;
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

Route::get('medicine', function (Request $request) {
    $medicines = Medicine::when($request->filled('chinese_name'), fn($q) => $q->where('chinese_name', 'like', "%{$request->chinese_name}%"))
        ->when($request->filled('english_name'), fn($q) => $q->where('english_name', 'like', "%{$request->english_name}%"))
        ->when($request->filled('license_number'), fn($q) => $q->where('license_number', 'like', "%{$request->license_number}%"))
        ->whereHas('toAppearance', fn($q) => $q
            ->when($request->filled('attr_key'), fn($q) => $q->where('attr_key', $request->attr_key))
            ->when($request->filled('attr_value'), fn($q) => $q->where('attr_value', 'like', "%{$request->attr_value}%")))
        ->paginate();

    $appearance = array_filter(AppearanceKeyEnum::translationMap(), function ($v) {
        return $v != 'image_link'
            && $v != 'english_name'
            && $v != 'chinese_name'
            && $v != 'license_number';
    });

    $resource = ['appearance' => $appearance];

    $keyMap = array_flip($appearance);

    return view('medicine', compact('medicines', 'resource', 'keyMap'));
})
    ->middleware(['auth', 'verified'])
    ->name('medicine');

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

Route::get('test', function () {
    MedicineJob::dispatch();
});

require __DIR__ . '/auth.php';

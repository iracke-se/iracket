<?php

use App\Http\Controllers\Auth\AppleController;
use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Public\Terms\Show as TermsShow;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\User\Information\Index as Information;
use App\Livewire\User\Notifications\Index as Notifications;
use App\Livewire\User\Players\Index as Players;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::redirect('/', '/login')->name('home');

// Social Authentication Routes
Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'callback']);

Route::get('auth/apple', [AppleController::class, 'redirect'])->name('auth.apple');
Route::post('auth/apple/callback', [AppleController::class, 'callback']);

// Terms and Privacy Policy
Route::get('terms/{slug}', TermsShow::class)->name('terms.show');

// Email Verification
Route::get('verify-email', VerifyEmail::class)
    ->middleware(['auth'])
    ->name('verification.notice');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    // Information page
    Route::get('information', Information::class)->name('information');

    // Notifications page
    Route::get('notifications', Notifications::class)->name('notifications');

    // Players page
    Route::get('players', Players::class)->middleware('verified')->name('players');

    // GET route for logout (convenience)
    Route::get('logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout.get');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

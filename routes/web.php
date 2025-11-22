<?php

use App\Http\Controllers\Auth\AppleController;
use App\Http\Controllers\Auth\GoogleController;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Public\Terms\Show as TermsShow;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Club as SettingsClub;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\User\Bubbler\Index as Bubbler;
use App\Livewire\User\Clubs\Index as Clubs;
use App\Livewire\User\Clubs\Show as ClubShow;
use App\Livewire\User\Information\Index as Information;
use App\Livewire\User\Matches\Form as MatchForm;
use App\Livewire\User\Matches\Index as Matches;
use App\Livewire\User\Matches\Show as MatchShow;
use App\Livewire\User\Notifications\Index as Notifications;
use App\Livewire\User\Players\Index as Players;
use App\Livewire\User\Players\Show as PlayerShow;
use App\Livewire\Admin\Terms\Index as AdminTermsIndex;
use App\Livewire\Admin\Terms\Form as AdminTermsForm;
use App\Livewire\Admin\Users\Index as AdminUsersIndex;
use App\Livewire\Admin\Users\Form as AdminUsersForm;
use App\Livewire\Admin\Clubs\Index as AdminClubsIndex;
use App\Livewire\Admin\Clubs\Form as AdminClubsForm;
use App\Livewire\Admin\Matches\Index as AdminMatchesIndex;
use App\Livewire\Admin\Matches\Form as AdminMatchesForm;
use App\Livewire\Admin\Staff\Index as AdminStaffIndex;
use App\Livewire\Admin\Staff\Form as AdminStaffForm;
use App\Livewire\Admin\Dashboard\Index as AdminDashboard;
use App\Livewire\Admin\Localization\Index as AdminLocalization;
use App\Livewire\Admin\Scraper\Index as AdminScraperIndex;
use App\Livewire\Admin\Scraper\Show as AdminScraperShow;
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
    Route::get('settings/club', SettingsClub::class)->name('club.edit');

    // Information page
    Route::get('information', Information::class)->name('information');

    // Notifications page
    Route::get('notifications', Notifications::class)->name('notifications');

    // Players page
    Route::get('players', Players::class)->middleware('verified')->name('players.index');
    Route::get('players/{user}', PlayerShow::class)->middleware('verified')->name('players.show');

    // Clubs
    Route::get('clubs', Clubs::class)->middleware('verified')->name('clubs.index');
    Route::get('clubs/{club:slug}', ClubShow::class)->middleware('verified')->name('clubs.show');

    // Matches
    Route::get('matches', Matches::class)->middleware('verified')->name('matches.index');
    Route::get('matches/create', MatchForm::class)->middleware('verified')->name('matches.create');
    Route::get('matches/{match}', MatchShow::class)->middleware('verified')->name('matches.show');
    Route::get('matches/{match}/edit', MatchForm::class)->middleware('verified')->name('matches.edit');

    // Bubbler
    Route::get('bubbler', Bubbler::class)->middleware('verified')->name('bubbler.index');

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

// Admin Routes
Route::middleware(['auth', 'role:Admin|Manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', 'admin/dashboard');

    // Dashboard
    Route::get('dashboard', AdminDashboard::class)->name('dashboard');

    // Terms
    Route::get('terms', AdminTermsIndex::class)->name('terms.index');
    Route::get('terms/create', AdminTermsForm::class)->name('terms.create');
    Route::get('terms/{id}/edit', AdminTermsForm::class)->name('terms.edit');

    // Users
    Route::get('users', AdminUsersIndex::class)->name('users.index');
    Route::get('users/create', AdminUsersForm::class)->name('users.create');
    Route::get('users/{id}/edit', AdminUsersForm::class)->name('users.edit');

    // Clubs
    Route::get('clubs', AdminClubsIndex::class)->name('clubs.index');
    Route::get('clubs/create', AdminClubsForm::class)->name('clubs.create');
    Route::get('clubs/{id}/edit', AdminClubsForm::class)->name('clubs.edit');

    // Matches
    Route::get('matches', AdminMatchesIndex::class)->name('matches.index');
    Route::get('matches/create', AdminMatchesForm::class)->name('matches.create');
    Route::get('matches/{id}/edit', AdminMatchesForm::class)->name('matches.edit');

    // Staff
    Route::get('staff', AdminStaffIndex::class)->name('staff.index');
    Route::get('staff/create', AdminStaffForm::class)->name('staff.create');
    Route::get('staff/{id}/edit', AdminStaffForm::class)->name('staff.edit');

    // Localization
    Route::get('localization', AdminLocalization::class)->name('localization.index');

    // Scraper
    Route::get('scraper', AdminScraperIndex::class)->name('scraper.index');
    Route::get('scraper/{run}', AdminScraperShow::class)->name('scraper.show');
});

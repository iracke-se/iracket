<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->email)->first();
            $isNewUser = false;

            if ($user) {
                // Update existing user with Google ID and verify email
                $user->update([
                    'google_id' => $googleUser->id,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                // Split name into first and last name
                $nameParts = explode(' ', $googleUser->name, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                // Create new user
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]);
                $isNewUser = true;
            }

            Auth::login($user);

            // Redirect to verification if email not verified
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            // Redirect new users to connect account page
            if ($isNewUser) {
                return redirect('/connect-account');
            }

            return redirect()->intended('/players');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google authentication failed. Please try again.');
        }
    }
}

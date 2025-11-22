<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AppleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('apple')->redirect();
    }

    public function callback()
    {
        try {
            $appleUser = Socialite::driver('apple')->user();

            $user = User::where('email', $appleUser->email)->first();

            if ($user) {
                // Update existing user with Apple ID if not set
                if (!$user->apple_id) {
                    $user->update([
                        'apple_id' => $appleUser->id,
                    ]);
                }
            } else {
                // Split name into first and last name
                $name = $appleUser->name ?? 'Apple User';
                $nameParts = explode(' ', $name, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                // Create new user
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $appleUser->email,
                    'apple_id' => $appleUser->id,
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]);
            }

            Auth::login($user);

            // Redirect to verification if email not verified
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->intended('dashboard');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Apple authentication failed. Please try again.');
        }
    }
}

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
        return Socialite::driver('apple')
            ->redirectUrl(config('services.apple.redirect'))
            ->redirect();
    }

    public function callback()
    {
        try {
            $appleUser = Socialite::driver('apple')
                ->redirectUrl(config('services.apple.redirect'))
                ->user();

            $user = User::where('email', $appleUser->email)->first();
            $isNewUser = false;

            if ($user) {
                // Update existing user with Apple ID
                $user->update(['apple_id' => $appleUser->id]);

                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
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
                    'user_fullname' => $name,
                    'email' => $appleUser->email,
                    'apple_id' => $appleUser->id,
                    'password' => Hash::make(Str::random(24)),
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]);
                $user->markEmailAsVerified();
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

            return redirect()->intended('dashboard');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Apple authentication failed. Please try again.');
        }
    }
}

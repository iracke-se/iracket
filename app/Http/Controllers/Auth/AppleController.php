<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AppLoginToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AppleController extends Controller
{
    public function redirect(Request $request)
    {
        // Remember when the flow was launched from the Flutter app's secure
        // browser so the callback hands the session back via a custom scheme.
        if ($request->query('flow') === 'app') {
            $request->session()->put('oauth_app_flow', true);
        }

        return Socialite::driver('apple')
            ->redirectUrl(config('services.apple.redirect'))
            ->redirect();
    }

    public function callback(Request $request)
    {
        $isAppFlow = (bool) $request->session()->get('oauth_app_flow', false);

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

            // App flow: hand the identity back to the WebView via a one-time
            // token instead of logging into this (secure-browser) session.
            if ($isAppFlow) {
                $request->session()->forget('oauth_app_flow');
                $next = $user->is_connected ? route('players.show', $user, absolute: false) : '/connect-account';
                $token = AppLoginToken::issue($user->id, $next);

                return redirect()->away('iracket://auth-callback?token='.urlencode($token));
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

            return redirect()->intended(route('players.show', $user));
        } catch (\Exception $e) {
            // In the app flow we must return to the custom scheme so the secure
            // browser sheet closes; a normal redirect would leave it hanging.
            if ($isAppFlow) {
                $request->session()->forget('oauth_app_flow');

                return redirect()->away('iracket://auth-callback?error=auth_failed');
            }

            return redirect()->route('login')->with('error', 'Apple authentication failed. Please try again.');
        }
    }
}

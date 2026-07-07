<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AppLoginToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Consumes a one-time token issued by the OAuth callback and logs the WebView's
 * session in. This is the WebView-side landing point of the native social
 * sign-in flow (see App\Services\Auth\AppLoginToken).
 */
class AppLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = (string) $request->query('token');

        $payload = $token !== '' ? AppLoginToken::consume($token) : null;

        if ($payload === null) {
            return redirect()->route('login')
                ->with('error', __('auth.failed'));
        }

        $user = User::find($payload['user_id']);

        if ($user === null) {
            return redirect()->route('login')
                ->with('error', __('auth.failed'));
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Only ever redirect to an in-app path recorded when the token was
        // issued — never to a user-supplied URL.
        return redirect()->to($payload['next']);
    }
}

<?php

namespace App\Http\Responses;

use App\Mail\Auth\VerificationCodeResent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        // Check if user's email is not verified
        if (!$user->hasVerifiedEmail()) {
            // Generate new verification code and send email
            $code = $user->generateVerificationCode();
            Mail::to($user->email)->send(new VerificationCodeResent($user, $code));

            return $request->wantsJson()
                ? new JsonResponse(['two_factor' => false, 'redirect' => route('verification.notice')], 200)
                : redirect()->route('verification.notice');
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended(config('fortify.home'));
    }
}

<?php

namespace App\Actions\Fortify;

use App\Mail\Auth\AccountVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'terms_accepted' => ['required', 'accepted'],
        ])->validate();

        // Handle profile picture upload
        $profilePicturePath = null;
        if (isset($input['profile_picture']) && $input['profile_picture']) {
            $profilePicturePath = $input['profile_picture']->store('profile-pictures', 'public');
        }

        $user = User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'user_fullname' => trim($input['first_name'] . ' ' . $input['last_name']),
            'email' => $input['email'],
            'password' => $input['password'],
            'profile_picture' => $profilePicturePath,
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'locale' => session('locale'),
        ]);

        // Generate verification code and send email
        $code = $user->generateVerificationCode();
        Mail::to($user)->send(new AccountVerification($user, $code));

        return $user;
    }
}

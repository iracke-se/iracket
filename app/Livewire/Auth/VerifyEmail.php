<?php

namespace App\Livewire\Auth;

use App\Mail\Auth\VerificationCodeResent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class VerifyEmail extends Component
{
    public string $code = '';
    public string $error = '';
    public string $success = '';

    public function mount()
    {
        // Clear any old error/success messages on page load
        $this->error = '';
        $this->success = '';
    }

    public function verify()
    {
        $this->error = '';
        $this->success = '';

        if (strlen($this->code) !== 6) {
            $this->error = 'Please enter a valid 6-digit code.';
            return;
        }

        $user = Auth::user();

        if ($user->isVerificationCodeValid($this->code)) {
            $user->markEmailAsVerified();

            // Redirect to connect-account if not connected, otherwise to players
            if (!$user->is_connected) {
                return redirect()->route('connect-account');
            }

            return redirect()->intended(route('players.index'));
        }

        $this->error = 'Invalid or expired verification code. Please try again or request a new code.';
        $this->code = '';
    }

    public function resend()
    {
        $this->error = '';
        $this->success = '';

        $user = Auth::user();
        $code = $user->generateVerificationCode();

        Mail::to($user->email)->send(new VerificationCodeResent($user, $code));

        $this->success = 'A new verification code has been sent to your email.';
        $this->code = '';

        $this->dispatch('code-resent');
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}

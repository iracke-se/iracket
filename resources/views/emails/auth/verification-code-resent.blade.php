<x-emails.email subject="Your New Verification Code">
    <h2 class="email-title">New Verification Code</h2>

    <p class="email-text">
        Hi {{ $user->first_name }},
    </p>

    <p class="email-text">
        You requested a new verification code for your {{ config('app.name') }} account. Please use the code below to verify your email address:
    </p>

    <div class="verification-code">
        <p class="verification-code-label">Your Verification Code</p>
        <p class="verification-code-value">{{ $code }}</p>
    </div>

    <p class="email-text">
        This code will expire in <strong>15 minutes</strong>. If you didn't request this code, please ignore this email or contact support if you have concerns.
    </p>

    <div class="divider"></div>

    <p class="text-muted text-center">
        Need help? Contact us at <a href="mailto:support@iracket.com" class="email-footer-link">support@iracket.com</a>
    </p>
</x-emails.email>

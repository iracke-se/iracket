<x-emails.email subject="Welcome to {{ config('app.name') }}">
    <h2 class="email-title">Welcome to {{ config('app.name') }}!</h2>

    <p class="email-text">
        Hi {{ $user->first_name }},
    </p>

    <p class="email-text">
        Thank you for creating an account with us. To complete your registration and start using {{ config('app.name') }}, please verify your email address by entering the code below:
    </p>

    <div class="verification-code">
        <p class="verification-code-label">Your Verification Code</p>
        <p class="verification-code-value">{{ $code }}</p>
    </div>

    <p class="email-text">
        This code will expire in <strong>15 minutes</strong>. If you didn't create an account with {{ config('app.name') }}, you can safely ignore this email.
    </p>

    <div class="divider"></div>

    <p class="text-muted text-center">
        Need help? Contact us at <a href="mailto:support@iracket.com" class="email-footer-link">support@iracket.com</a>
    </p>
</x-emails.email>

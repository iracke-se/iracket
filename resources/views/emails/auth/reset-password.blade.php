<x-emails.email subject="Reset Your Password">
    <h2 class="email-title">Reset Your Password</h2>

    <p class="email-text">
        Hi {{ $user->first_name }},
    </p>

    <p class="email-text">
        We received a request to reset your password for your {{ config('app.name') }} account. Click the button below to create a new password:
    </p>

    <div class="text-center">
        <a href="{{ $url }}" class="email-button">Reset Password</a>
    </div>

    <p class="email-text">
        This password reset link will expire in <strong>60 minutes</strong>. If you didn't request a password reset, you can safely ignore this email.
    </p>

    <div class="divider"></div>

    <p class="text-muted text-center">
        If you're having trouble clicking the button, copy and paste the URL below into your browser:
    </p>
    <p class="text-muted text-center" style="word-break: break-all; font-size: 12px;">
        <a href="{{ $url }}" class="email-footer-link">{{ $url }}</a>
    </p>
</x-emails.email>

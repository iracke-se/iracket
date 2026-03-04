<x-emails.email subject="{{ __('emails.verification_code_resent.subject', ['app' => config('app.name')]) }}">
    <h2 class="email-title">{{ __('emails.verification_code_resent.title') }}</h2>

    <p class="email-text">
        Hi {{ $user->first_name }},
    </p>

    <p class="email-text">
        {{ __('emails.verification_code_resent.body', ['app' => config('app.name')]) }}
    </p>

    <div class="verification-code">
        <p class="verification-code-label">{{ __('emails.verification_code_resent.code_label') }}</p>
        <p class="verification-code-value">{{ $code }}</p>
    </div>

    <p class="email-text">
        {!! __('emails.verification_code_resent.expiry') !!}
    </p>

    <div class="divider"></div>

    <p class="text-muted text-center">
        {{ __('emails.verification_code_resent.help') }} <a href="mailto:support@iracket.com" class="email-footer-link">support@iracket.com</a>
    </p>
</x-emails.email>

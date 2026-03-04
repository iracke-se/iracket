<x-emails.email subject="{{ __('emails.account_verification.subject', ['app' => config('app.name')]) }}">
    <h2 class="email-title">{!! __('emails.account_verification.title', ['app' => config('app.name')]) !!}</h2>

    <p class="email-text">
        Hi {{ $user->first_name }},
    </p>

    <p class="email-text">
        {{ __('emails.account_verification.body', ['app' => config('app.name')]) }}
    </p>

    <div class="verification-code">
        <p class="verification-code-label">{{ __('emails.account_verification.code_label') }}</p>
        <p class="verification-code-value">{{ $code }}</p>
    </div>

    <p class="email-text">
        {!! __('emails.account_verification.expiry', ['app' => config('app.name')]) !!}
    </p>

    <div class="divider"></div>

    <p class="text-muted text-center">
        {{ __('emails.account_verification.help') }} <a href="mailto:support@iracket.com" class="email-footer-link">support@iracket.com</a>
    </p>
</x-emails.email>

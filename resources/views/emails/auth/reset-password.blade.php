<x-emails.email subject="{{ __('emails.reset_password.subject', ['app' => config('app.name')]) }}">
    <h2 class="email-title">{{ __('emails.reset_password.title') }}</h2>

    <p class="email-text">
        Hi {{ $user->first_name }},
    </p>

    <p class="email-text">
        {{ __('emails.reset_password.body', ['app' => config('app.name')]) }}
    </p>

    <div class="text-center">
        <a href="{{ $url }}" class="email-button">{{ __('emails.reset_password.button') }}</a>
    </div>

    <p class="email-text">
        {!! __('emails.reset_password.expiry') !!}
    </p>

    <div class="divider"></div>

    <p class="text-muted text-center">
        {{ __('emails.reset_password.url_help') }}
    </p>
    <p class="text-muted text-center" style="word-break: break-all; font-size: 12px;">
        <a href="{{ $url }}" class="email-footer-link">{{ $url }}</a>
    </p>
</x-emails.email>

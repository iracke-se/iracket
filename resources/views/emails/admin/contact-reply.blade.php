<x-emails.email subject="{{ __('emails.contact_reply.subject', ['app' => config('app.name')]) }}">
    <h2 class="email-title">{{ __('emails.contact_reply.title') }}</h2>

    <p class="email-text">
        Hi {{ $contact->name }},
    </p>

    <p class="email-text">
        {{ __('emails.contact_reply.body', ['app' => config('app.name')]) }}
    </p>

    <div class="divider"></div>

    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="font-weight: 600; color: #374151; margin: 0 0 10px 0;">{{ __('emails.contact_reply.original_message') }}</p>
        <p style="color: #4b5563; margin: 0; font-style: italic;">"{{ $contact->message }}"</p>
    </div>

    <div style="background-color: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #22c55e;">
        <p style="font-weight: 600; color: #166534; margin: 0 0 10px 0;">{{ __('emails.contact_reply.our_response') }}</p>
        <p style="color: #166534; margin: 0;">{{ $contact->reply_message }}</p>
    </div>

    <div class="divider"></div>

    <p class="email-text">
        {{ __('emails.contact_reply.closing') }}
    </p>

    <p class="text-muted text-center" style="margin-top: 20px;">
        {!! __('emails.contact_reply.regards', ['app' => config('app.name')]) !!}
    </p>
</x-emails.email>

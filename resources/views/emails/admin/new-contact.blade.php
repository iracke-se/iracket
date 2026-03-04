<x-emails.email subject="{{ __('emails.new_contact.subject', ['app' => config('app.name')]) }}">
    <h2 class="email-title">{{ __('emails.new_contact.title') }}</h2>

    <p class="email-text">
        {{ __('emails.new_contact.body', ['app' => config('app.name')]) }}
    </p>

    <div class="divider"></div>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; width: 120px;">{{ __('emails.new_contact.col_name') }}</td>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #4b5563;">{{ $contact->name }}</td>
        </tr>
        <tr>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151;">{{ __('emails.new_contact.col_email') }}</td>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #4b5563;">
                <a href="mailto:{{ $contact->email }}" style="color: #22c55e; text-decoration: none;">{{ $contact->email }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 0; font-weight: 600; color: #374151; vertical-align: top;">{{ __('emails.new_contact.col_msg') }}</td>
            <td style="padding: 10px 0; color: #4b5563;">{{ $contact->message }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <p class="email-text">
        <strong>{{ __('emails.new_contact.submitted') }}</strong> {{ $contact->created_at->format('d M Y, H:i') }}
    </p>

    <p class="text-muted text-center" style="margin-top: 20px;">
        {{ __('emails.new_contact.footer') }}
    </p>
</x-emails.email>

<x-emails.email subject="Re: Your message to {{ config('app.name') }}">
    <h2 class="email-title">Thank you for contacting us</h2>

    <p class="email-text">
        Hi {{ $contact->name }},
    </p>

    <p class="email-text">
        Thank you for reaching out to {{ config('app.name') }}. We have received your message and are happy to assist you.
    </p>

    <div class="divider"></div>

    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p style="font-weight: 600; color: #374151; margin: 0 0 10px 0;">Your original message:</p>
        <p style="color: #4b5563; margin: 0; font-style: italic;">"{{ $contact->message }}"</p>
    </div>

    <div style="background-color: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #22c55e;">
        <p style="font-weight: 600; color: #166534; margin: 0 0 10px 0;">Our response:</p>
        <p style="color: #166534; margin: 0;">{{ $contact->reply_message }}</p>
    </div>

    <div class="divider"></div>

    <p class="email-text">
        If you have any further questions, feel free to reply to this email or contact us again through our website.
    </p>

    <p class="text-muted text-center" style="margin-top: 20px;">
        Best regards,<br>
        The {{ config('app.name') }} Team
    </p>
</x-emails.email>

<x-emails.email subject="New Contact Form Submission">
    <h2 class="email-title">New Contact Form Submission</h2>

    <p class="email-text">
        You have received a new contact form submission from the {{ config('app.name') }} website.
    </p>

    <div class="divider"></div>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; width: 120px;">Name:</td>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #4b5563;">{{ $contact->name }}</td>
        </tr>
        <tr>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151;">Email:</td>
            <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #4b5563;">
                <a href="mailto:{{ $contact->email }}" style="color: #22c55e; text-decoration: none;">{{ $contact->email }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 0; font-weight: 600; color: #374151; vertical-align: top;">Message:</td>
            <td style="padding: 10px 0; color: #4b5563;">{{ $contact->message }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <p class="email-text">
        <strong>Submitted:</strong> {{ $contact->created_at->format('d M Y, H:i') }}
    </p>

    <p class="text-muted text-center" style="margin-top: 20px;">
        You can reply to this contact from the admin panel or by replying directly to this email.
    </p>
</x-emails.email>

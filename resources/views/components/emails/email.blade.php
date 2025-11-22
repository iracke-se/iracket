<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            background-color: #ffffff;
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #e5e5e5;
        }
        .email-logo {
            width: 48px;
            height: 48px;
            margin-bottom: 10px;
        }
        .email-brand {
            font-size: 24px;
            font-weight: 600;
            color: #333333;
            margin: 0;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-title {
            font-size: 20px;
            font-weight: 600;
            color: #333333;
            margin: 0 0 20px 0;
        }
        .email-text {
            font-size: 16px;
            color: #555555;
            margin: 0 0 20px 0;
        }
        .verification-code {
            background-color: #f8f8f8;
            border: 2px solid #34C759;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .verification-code-label {
            font-size: 14px;
            color: #666666;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .verification-code-value {
            font-size: 32px;
            font-weight: 700;
            color: #34C759;
            letter-spacing: 8px;
            margin: 0;
            font-family: 'Courier New', monospace;
        }
        .email-button {
            display: inline-block;
            background-color: #34C759;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
        }
        .email-button:hover {
            background-color: #2da44e;
        }
        .email-footer {
            background-color: #fafafa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e5e5e5;
        }
        .email-footer-text {
            font-size: 12px;
            color: #888888;
            margin: 0;
        }
        .email-footer-link {
            color: #34C759;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e5e5e5;
            margin: 20px 0;
        }
        .text-muted {
            color: #888888;
            font-size: 14px;
        }
        .text-center {
            text-align: center;
        }
        .accent {
            color: #34C759;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <img src="{{ asset('assets/images/icon.png') }}" alt="{{ config('app.name') }}" class="email-logo">
                <h1 class="email-brand">{{ config('app.name') }}</h1>
            </div>

            <div class="email-body">
                {{ $slot }}
            </div>

            <div class="email-footer">
                <p class="email-footer-text">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
                <p class="email-footer-text" style="margin-top: 10px;">
                    If you didn't request this email, please ignore it.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

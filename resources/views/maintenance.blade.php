<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Under maintenance') }} — iRacket</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #18181b;
            --panel: #27272a;
            --border: #3f3f46;
            --text: #ffffff;
            --muted: #a1a1aa;
            --accent: #34C759;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .wrap {
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(52, 199, 89, 0.12);
            color: var(--accent);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 6px 12px;
            border-radius: 999px;
            margin-bottom: 24px;
        }
        .badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 4px rgba(52, 199, 89, 0.18);
        }
        h1 {
            margin: 0 0 12px 0;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.01em;
        }
        p {
            margin: 0 0 24px 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 15px;
        }
        .home {
            display: inline-block;
            background: var(--accent);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: 10px;
            transition: opacity .15s ease;
        }
        .home:hover { opacity: 0.9; }
        .brand {
            margin-top: 28px;
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <span class="badge">{{ __('Maintenance') }}</span>
            <h1>{{ __('We\'ll be back shortly') }}</h1>
            <p>{{ __('iRacket is currently undergoing scheduled maintenance. Please check back in a little while — thank you for your patience.') }}</p>
            <a class="home" href="{{ url('/') }}">{{ __('Back to home') }}</a>
            <div class="brand">iRacket</div>
        </div>
    </div>
</body>
</html>

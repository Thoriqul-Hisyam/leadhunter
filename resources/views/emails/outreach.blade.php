<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $companyName ?: config('app.name') }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.7;
            color: #334155;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f4f7f9;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.3;
        }
        .content {
            padding: 40px 35px;
            font-size: 15px;
            color: #334155;
        }
        .content p {
            margin-bottom: 24px;
            line-height: 1.8;
        }
        .content p:last-child {
            margin-bottom: 0;
        }
        .contact-card {
            background-color: #f8fafc;
            border-left: 4px solid #6366f1;
            padding: 20px;
            border-radius: 0 12px 12px 0;
            margin: 30px 0;
        }
        .contact-card h3 {
            margin: 0 0 10px 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .contact-item {
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        .contact-item:last-child {
            margin-bottom: 0;
        }
        .contact-item strong {
            color: #475569;
            width: 80px;
            display: inline-block;
        }
        .contact-item a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 24px 30px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }
        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <table class="wrapper" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="container" cellpadding="0" cellspacing="0" role="presentation">
                    @if($companyName)
                    <tr>
                        <td class="header">
                            <div class="badge">{{ $companyName }}</div>
                            @if($companyTagline)
                                <h1>{{ $companyTagline }}</h1>
                            @endif
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="content">
                            {!! nl2br(e($messageText)) !!}

                            @if($companyPhone || $companyWebsite)
                            <div class="contact-card">
                                <h3>Hubungi Kami</h3>
                                @if($companyPhone)
                                <div class="contact-item">
                                    <strong>Telepon:</strong>
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $companyPhone) }}">{{ $companyPhone }}</a>
                                </div>
                                @endif
                                @if($companyWebsite)
                                <div class="contact-item">
                                    <strong>Website:</strong>
                                    <a href="{{ \App\Helpers\Url::normalize($companyWebsite) }}" target="_blank">{{ preg_replace('#^https?://#', '', $companyWebsite) }}</a>
                                </div>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            @if($companyName)
                                <p style="margin: 0;">&copy; {{ date('Y') }} <strong>{{ $companyName }}</strong>. All rights reserved.</p>
                            @endif
                            @if($unsubscribeUrl)
                                <p style="margin: 8px 0 0 0; font-size: 11px;">Tidak ingin menerima email seperti ini lagi? <a href="{{ $unsubscribeUrl }}">Berhenti berlangganan</a></p>
                            @else
                                <p style="margin: 8px 0 0 0; font-size: 11px;">Tidak ingin menerima email seperti ini lagi? Balas email ini dengan kata <strong>BERHENTI</strong>.</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

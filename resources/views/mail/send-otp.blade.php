<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - {{ __('messages.otp_mail_title') }}</title>
    <style>
        /* Minimal, light email-safe CSS */
        body { margin: 0; padding: 0; background-color: #ffffff; font-family: Arial, Helvetica, sans-serif; color: #1f2937; }
        .wrapper { width: 100%; padding: 24px 0; }
        .container { max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; }
        .header { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; text-align: center; }
        .brand { font-size: 16px; font-weight: 700; color: #111827; }
        .content { padding: 20px; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 6px; color: #111827; }
        .subtitle { font-size: 14px; color: #4b5563; margin: 0 0 16px; line-height: 1.6; }
        .otp-block { border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; text-align: center; margin: 12px 0 16px; background: #fafafa; }
        .otp-label { display: block; font-size: 12px; color: #6b7280; letter-spacing: 0.4px; text-transform: uppercase; margin-bottom: 8px; }
        .otp-digits { display: inline-block; font-size: 22px; font-weight: 700; letter-spacing: 6px; color: #111827; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; min-width: 160px; font-family: "Courier New", Courier, monospace; }
        .message { font-size: 14px; color: #374151; line-height: 1.6; margin: 0 0 12px; }
        .note { font-size: 12px; color: #6b7280; line-height: 1.6; }
        .footer { padding: 14px 20px; text-align: center; color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; }
        .muted { color: #6b7280; }
    </style>
    <!--[if mso]><style>.otp-digits { letter-spacing: 4px !important; }</style><![endif]-->
    @php
        $otp = '';
        if (isset($data) && is_string($data)) {
            $digits = preg_replace('/\D+/', '', $data);
            // Use last 6 or last 4 digits heuristically
            if (strlen($digits) >= 6) {
                $otp = substr($digits, -6);
            } elseif (strlen($digits) >= 4) {
                $otp = substr($digits, -4);
            }
        } elseif (is_array($data) && isset($data['otp'])) {
            $otp = $data['otp'];
        }
    @endphp
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header"><div class="brand">{{ config('app.name') }}</div></div>
            <div class="content">
                <h1 class="title">{{ __('messages.otp_mail_title') }}</h1>
                <p class="subtitle">{{ __('messages.otp_mail_subtitle') }}</p>

                <div class="otp-block">
                    <span class="otp-label">{{ __('messages.otp_mail_label') }}</span>
                    <div class="otp-digits">{{ $otp ?: '••••••' }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

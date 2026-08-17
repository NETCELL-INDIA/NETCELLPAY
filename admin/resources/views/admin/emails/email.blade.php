@php
    $brand = $brand ?? (function_exists('email_brand') ? email_brand() : ['name' => 'NETCELL PAY', 'logo' => '', 'support_email' => '', 'support_phone' => '', 'website' => 'https://netcellpay.in', 'year' => date('Y')]);
    $bodyHtml = $bodyHtml ?? (function_exists('email_body_html') ? email_body_html($msg ?? '') : e($msg ?? ''));
    $subject = $subject ?? ($brand['name'] ?? 'NETCELL PAY');
    $preheader = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($msg ?? $subject)))), 90);
@endphp
<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $subject }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#eef2ff;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader }}</div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef2ff;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px;max-width:600px;background:#ffffff;border-radius:22px;overflow:hidden;box-shadow:0 18px 50px rgba(23,27,61,.12);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#171b3d 0%,#34308f 58%,#00a892 140%);padding:28px 32px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        @if(!empty($brand['logo']))
                                            <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" width="46" height="46" style="display:block;border:0;border-radius:12px;background:#fff;object-fit:contain;">
                                        @endif
                                        <div style="margin-top:12px;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:800;letter-spacing:.4px;color:#ffffff;">{{ $brand['name'] }}</div>
                                        <div style="margin-top:4px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:rgba(255,255,255,.78);">Secure payments. Instant updates.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px;background:#00bfa6;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 8px;font-family:Arial,Helvetica,sans-serif;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#00a892;">{{ $brand['name'] }} Notification</div>
                            <h1 style="margin:8px 0 18px;font-size:24px;line-height:1.3;color:#171b3d;font-weight:800;">{{ $subject }}</h1>
                            <div style="font-size:16px;line-height:1.7;color:#334155;">
                                {!! $bodyHtml !!}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 32px 28px;font-family:Arial,Helvetica,sans-serif;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border:1px solid #e8eef7;border-radius:16px;">
                                <tr>
                                    <td style="padding:16px 18px;font-size:13px;line-height:1.6;color:#64748b;">
                                        Need help? Reply to this email or contact support
                                        @if(!empty($brand['support_email']))
                                            at <a href="mailto:{{ $brand['support_email'] }}" style="color:#34308f;font-weight:700;text-decoration:none;">{{ $brand['support_email'] }}</a>
                                        @endif
                                        @if(!empty($brand['support_phone']))
                                            / {{ $brand['support_phone'] }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;font-family:Arial,Helvetica,sans-serif;text-align:center;">
                            @if(!empty($brand['website']))
                                <a href="{{ $brand['website'] }}" style="display:inline-block;padding:12px 22px;border-radius:999px;background:#34308f;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">Open {{ $brand['name'] }}</a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f4f7fb;padding:18px 32px 22px;font-family:Arial,Helvetica,sans-serif;text-align:center;color:#94a3b8;font-size:12px;line-height:1.6;">
                            &copy; {{ $brand['year'] }} {{ $brand['name'] }}. All rights reserved.<br>
                            This is an automated message. Please do not share OTPs or passwords with anyone.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

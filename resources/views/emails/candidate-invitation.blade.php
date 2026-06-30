<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $testTitle }} Invitation</title>
</head>
<body style="margin:0;padding:0;background:#09090b;color:#e4e4e7;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#09090b;margin:0;padding:32px 12px;width:100%;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;border-collapse:separate;border-spacing:0;">
                    <tr>
                        <td style="padding:0 0 16px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="width:42px;height:42px;border-radius:14px;background:#10b981;text-align:center;vertical-align:middle;">
                                        <span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:9px;background:#ecfdf5;color:#064e3b;font-size:18px;font-weight:800;">?</span>
                                    </td>
                                    <td style="padding-left:12px;color:#ffffff;font-size:15px;font-weight:700;">
                                        Online Quiz Platform
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="border:1px solid #27272a;border-radius:22px;background:#18181b;box-shadow:0 24px 70px rgba(0,0,0,0.35);overflow:hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:30px 30px 24px 30px;border-bottom:1px solid #27272a;background:#111113;">
                                        <div style="margin-bottom:12px;color:#6ee7b7;font-size:11px;font-weight:800;letter-spacing:2.4px;text-transform:uppercase;">
                                            Assessment Invitation
                                        </div>
                                        <h1 style="margin:0;color:#ffffff;font-size:28px;line-height:1.2;font-weight:800;">
                                            You are invited to take a quiz.
                                        </h1>
                                        <p style="margin:14px 0 0 0;color:#a1a1aa;font-size:14px;line-height:1.7;">
                                            Hello{{ $candidateName ? ' '.$candidateName : '' }}, you have been invited to complete the assessment below.
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:24px 30px 8px 30px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:0;">
                                            <tr>
                                                <td style="padding:18px;border:1px solid #27272a;border-radius:16px;background:#09090b;">
                                                    <div style="color:#71717a;font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;">
                                                        Quiz
                                                    </div>
                                                    <div style="margin-top:6px;color:#ffffff;font-size:20px;font-weight:800;line-height:1.35;">
                                                        {{ $testTitle }}
                                                    </div>
                                                    <div style="margin-top:10px;color:#a1a1aa;font-size:13px;line-height:1.6;">
                                                        From: <span style="color:#e4e4e7;font-weight:700;">{{ $owner }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                @if ($startsAt || $expiresAt)
                                    <tr>
                                        <td style="padding:10px 30px 8px 30px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:10px;">
                                                <tr>
                                                    <td style="width:50%;padding:14px;border:1px solid #27272a;border-radius:14px;background:#111113;vertical-align:top;">
                                                        <div style="color:#71717a;font-size:10px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;">Starts</div>
                                                        <div style="margin-top:6px;color:#e4e4e7;font-size:13px;font-weight:700;">{{ $startsAt ?? 'Available now' }}</div>
                                                    </td>
                                                    <td style="width:50%;padding:14px;border:1px solid #27272a;border-radius:14px;background:#111113;vertical-align:top;">
                                                        <div style="color:#71717a;font-size:10px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;">Expires</div>
                                                        <div style="margin-top:6px;color:#e4e4e7;font-size:13px;font-weight:700;">{{ $expiresAt ?? 'No expiry set' }}</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td style="padding:18px 30px 28px 30px;">
                                        <p style="margin:0 0 20px 0;color:#a1a1aa;font-size:14px;line-height:1.7;">
                                            Please read and accept the quiz policy before entering your candidate details. Your access link is unique to this invitation.
                                        </p>

                                        <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                            <tr>
                                                <td style="border-radius:14px;background:#10b981;">
                                                    <a href="{{ $url }}" style="display:inline-block;padding:14px 22px;color:#020617;font-size:14px;font-weight:800;text-decoration:none;border-radius:14px;">
                                                        Open Quiz Link
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:22px 0 0 0;color:#71717a;font-size:12px;line-height:1.6;">
                                            If the button does not work, copy and paste this link into your browser:<br>
                                            <a href="{{ $url }}" style="color:#6ee7b7;word-break:break-all;text-decoration:none;">{{ $url }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 4px 0 4px;color:#52525b;font-size:11px;line-height:1.6;text-align:center;">
                            Sent by Online Quiz Platform. Do not share this invitation link with anyone else.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

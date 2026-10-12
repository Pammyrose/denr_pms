<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your PMS sign-in code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px;">
                    <tr>
                        <td align="center" style="padding: 32px 24px 24px; text-align: center;">
                            <img src="{{ $message->embed(public_path('denr_logo.png')) }}" alt="DENR logo" width="90" style="display: block; width: 90px; height: auto; margin: 0 auto 16px; border: 0;">
                            <p style="margin: 0 0 24px; font-size: 14px; line-height: 20px; color: #6b7280;">DENR-CAR Performance Monitoring System</p>
                            <h1 style="margin: 0 0 16px; font-size: 24px; line-height: 32px; color: #1e40af;">Confirm your PMS sign-in</h1>
                            <p style="margin: 0 0 20px; font-size: 16px; line-height: 24px;">Enter this code on the sign-in page:</p>
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin: 0 auto 24px;">
                                <tr>
                                    <td align="center" style="padding: 16px 24px; background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; color: #1e40af; font-size: 32px; font-weight: bold; letter-spacing: 6px;">{{ $code }}</td>
                                </tr>
                            </table>
                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 22px;">This code expires in <strong>10 minutes</strong> and can only be used once. Do not share it.</p>
                            <p style="margin: 0; font-size: 13px; line-height: 20px; color: #6b7280;">If you did not try to sign in, change your PMS password and contact your administrator.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

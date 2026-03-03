<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? env('APP_NAME') . ' OTP Verification' }}</title>
</head>

<body style="margin:0; padding:0; background-color:#fafafa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fafafa; padding:40px 0;">
        <tr>
            <td align="center">

                <!-- Card -->
                <table width="420" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #dbdbdb; border-radius:8px; padding:40px 30px;">

                    <!-- Logo / App Name -->
                    <tr>
                        <td align="center" style="padding-bottom:25px;">
                            <h2 style="margin:0; font-size:22px; font-weight:600; color:#262626;">
                                {{ env('APP_NAME') }}
                            </h2>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="font-size:15px; color:#262626; padding-bottom:15px;">
                            Hi {{ $name }},
                        </td>
                    </tr>

                    <!-- Message -->
                    <tr>
                        <td style="font-size:14px; color:#555; line-height:1.6; padding-bottom:25px;">
                            Use the verification code below to complete your login.  
                            This code will expire in <strong>5 minutes</strong>.
                        </td>
                    </tr>

                    <!-- OTP Box -->
                    <tr>
                        <td align="center" style="padding-bottom:30px;">
                            <div style="
                                font-size:32px;
                                letter-spacing:6px;
                                font-weight:600;
                                color:#262626;
                                background:#f2f2f2;
                                padding:15px 25px;
                                border-radius:6px;
                                display:inline-block;
                            ">
                                {{ $otp }}
                            </div>
                        </td>
                    </tr>

                    <!-- Security Note -->
                    <tr>
                        <td style="font-size:13px; color:#8e8e8e; line-height:1.5;">
                            If you didn’t request this code, you can safely ignore this email.
                        </td>
                    </tr>

                </table>

                <!-- Footer -->
                <table width="420" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                    <tr>
                        <td align="center" style="font-size:12px; color:#8e8e8e;">
                            © {{ date('Y') }} {{ env('APP_NAME') }}. All rights reserved.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>

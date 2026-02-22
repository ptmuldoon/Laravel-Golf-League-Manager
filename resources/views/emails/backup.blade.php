<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background: #f4f4f7; font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #333;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f4f4f7; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, {{ $themeSettings['primary'] }} 0%, {{ $themeSettings['secondary'] }} 100%); background-color: {{ $themeSettings['primary'] }}; color: white; padding: 25px 20px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; font-size: 24px;">Database Backup</h1>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background: white; padding: 30px 25px;">
                            <p style="line-height: 1.6; color: #333; font-size: 15px; margin: 0 0 20px 0;">
                                A database backup has been created and is attached to this email.
                            </p>
                            <table cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-radius: 8px; padding: 15px; width: 100%;">
                                <tr>
                                    <td style="padding: 8px 15px; font-weight: bold; color: #555; width: 100px;">File:</td>
                                    <td style="padding: 8px 15px; font-family: monospace; font-size: 13px;">{{ $fileName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; font-weight: bold; color: #555;">Size:</td>
                                    <td style="padding: 8px 15px;">{{ $fileSize }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 15px; font-weight: bold; color: #555;">Date:</td>
                                    <td style="padding: 8px 15px;">{{ $backupDate }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background: {{ $themeSettings['primary_light'] }}; padding: 15px 20px; text-align: center; color: #999; font-size: 12px; border-radius: 0 0 12px 12px; border-top: 1px solid #eee;">
                            Tuesday Golf League &bull; Automated Backup
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

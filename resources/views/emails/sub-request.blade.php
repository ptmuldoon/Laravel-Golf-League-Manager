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
                            <h1 style="margin: 0; font-size: 24px;">{{ $league->name }}</h1>
                            <p style="margin: 8px 0 0; font-size: 14px; opacity: 0.9;">Sub Player Request</p>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background: white; padding: 30px 25px;">
                            <h2 style="color: {{ $themeSettings['primary'] }}; font-size: 20px; margin: 0 0 20px 0;">Sub Needed - Week {{ $weekNumber }}@if($weekDateLabel) ({{ $weekDateLabel }})@endif</h2>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 10px 15px; background: #f8f9fa; border-left: 4px solid {{ $themeSettings['primary'] }}; border-radius: 0 4px 4px 0;">
                                        <strong style="color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Player</strong><br>
                                        <span style="font-size: 16px; color: #333;">{{ $playerName }}</span>
                                    </td>
                                </tr>
                                <tr><td style="height: 8px;"></td></tr>
                                <tr>
                                    <td style="padding: 10px 15px; background: #f8f9fa; border-left: 4px solid {{ $themeSettings['primary'] }}; border-radius: 0 4px 4px 0;">
                                        <strong style="color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Week</strong><br>
                                        <span style="font-size: 16px; color: #333;">{{ $weekNumber }}@if($weekDateLabel) &mdash; {{ $weekDateLabel }}@endif</span>
                                    </td>
                                </tr>
                            </table>

                            @if($requestMessage)
                                <div style="padding: 15px; background: #fff8e1; border-radius: 6px; border: 1px solid #ffecb3; margin-top: 15px;">
                                    <strong style="color: #f57f17; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Message</strong>
                                    <p style="margin: 8px 0 0; line-height: 1.6; color: #333;">{{ $requestMessage }}</p>
                                </div>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background: {{ $themeSettings['primary_light'] }}; padding: 15px 20px; text-align: center; color: #999; font-size: 12px; border-radius: 0 0 12px 12px; border-top: 1px solid #eee;">
                            {{ $league->name }} &bull; {{ $league->season }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

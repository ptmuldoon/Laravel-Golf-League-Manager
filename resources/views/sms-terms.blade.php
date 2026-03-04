<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Terms &amp; Conditions</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .back-link {
            display: inline-block; color: white; text-decoration: none;
            padding: 10px 20px; background: rgba(255,255,255,0.2);
            border-radius: 5px; margin-bottom: 20px; transition: background 0.3s ease;
        }
        .back-link:hover { background: rgba(255,255,255,0.3); }
        .content-section {
            background: white; padding: 30px; border-radius: 12px;
            margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.8em; color: var(--primary-color); margin-bottom: 10px;
        }
        .updated { color: #888; font-size: 0.9em; margin-bottom: 25px; }
        h3 {
            color: var(--primary-color); margin: 25px 0 10px 0; font-size: 1.15em;
        }
        p, li {
            line-height: 1.7; color: #444; margin-bottom: 10px;
        }
        ul { padding-left: 20px; margin-bottom: 15px; }
        a { color: var(--primary-color); }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-section { padding: 16px; }
            .section-title { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ url('/') }}" class="back-link">&larr; Back to Home</a>

        <div class="content-section">
            <h2 class="section-title">SMS Terms &amp; Conditions</h2>
            <p class="updated">Last updated: {{ date('F j, Y') }}</p>

            <h3>Program Description</h3>
            <p>This golf league management application provides SMS notifications to league players. Messages may include weekly match results, standings, schedule updates, weather-related announcements, and other league communications as initiated by your league administrator.</p>

            <h3>Consent &amp; Opt-In</h3>
            <p>By providing your mobile phone number to your league administrator and agreeing to receive SMS notifications, you consent to receive text messages from this service related to your golf league. Consent is not a condition of participation in any league. You may opt out at any time.</p>

            <h3>Message Frequency</h3>
            <p>Message frequency varies based on league activity. You may receive messages when your league administrator sends out results, schedule changes, or announcements. Typical frequency is 1&ndash;4 messages per week during an active league season.</p>

            <h3>Message &amp; Data Rates</h3>
            <p>Message and data rates may apply. Please contact your wireless carrier for details about your text messaging plan.</p>

            <h3>Opt-Out</h3>
            <p>You can opt out of receiving SMS messages at any time by replying <strong>STOP</strong> to any message you receive from us. After opting out, you will receive a one-time confirmation message and no further messages will be sent. You may also contact your league administrator to have your phone number removed from the notification list.</p>

            <h3>Help</h3>
            <p>For help, reply <strong>HELP</strong> to any message you receive from us, or contact your league administrator directly.</p>

            <h3>Supported Carriers</h3>
            <p>Major US carriers are supported, including but not limited to AT&amp;T, Verizon, T-Mobile, Sprint, U.S. Cellular, and their affiliates. Carriers are not liable for delayed or undelivered messages.</p>

            <h3>Privacy</h3>
            <p>We respect your privacy. Your phone number is used solely for sending league-related SMS notifications and is not shared with third parties for marketing purposes. For full details, please see our <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>

            <h3>Contact</h3>
            <p>For questions about these SMS terms and conditions, please contact your league administrator or the site administrator.</p>
        </div>
    </div>
</body>
</html>

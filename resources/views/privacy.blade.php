<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy</title>
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
            <h2 class="section-title">Privacy Policy</h2>
            <p class="updated">Last updated: {{ date('F j, Y') }}</p>

            <p>This golf league management application is designed to help organize and run golf leagues. Your privacy is important to us. This policy describes what information we collect, how we use it, and your choices.</p>

            <h3>Information We Collect</h3>
            <ul>
                <li><strong>Account Information:</strong> Name, email address, and password when you register.</li>
                <li><strong>Player Information:</strong> Names, handicaps, phone numbers, and email addresses entered by league administrators for league management.</li>
                <li><strong>Golf Data:</strong> Scores, match results, standings, and related league activity.</li>
            </ul>

            <h3>How We Use Your Information</h3>
            <ul>
                <li>Operating and managing golf leagues, including scheduling, scoring, and standings.</li>
                <li>Sending league communications via email or SMS when initiated by a league administrator.</li>
                <li>Creating database backups to prevent data loss.</li>
            </ul>

            <h3>SMS &amp; Email Communications</h3>
            <p>League administrators may send SMS messages or emails to players regarding results, schedules, or league announcements. SMS is sent via Vonage and email via your configured mail provider. Message and data rates may apply. Players can request removal of their phone number from their league administrator at any time.</p>

            <h3>Data Storage &amp; Security</h3>
            <p>All data is stored on the server where this application is hosted. Database backups may optionally be sent via email or uploaded to Google Drive if configured by the site administrator. Passwords are hashed and never stored in plain text.</p>

            <h3>Third-Party Services</h3>
            <p>This application may integrate with the following third-party services when configured:</p>
            <ul>
                <li><strong>Vonage</strong> for sending SMS messages.</li>
                <li><strong>Google Drive</strong> for backup storage.</li>
                <li><strong>SMTP email provider</strong> for sending emails.</li>
            </ul>

            <h3>Your Rights</h3>
            <p>You may request access to, correction of, or deletion of your personal information by contacting your league administrator or the site administrator.</p>

            <h3>Contact</h3>
            <p>For questions about this privacy policy, please contact the site administrator.</p>
        </div>
    </div>
</body>
</html>

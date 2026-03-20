<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Message - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 700px; margin: 0 auto; }
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
            font-size: 1.8em; color: var(--primary-color); margin-bottom: 20px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-weight: 600; margin-bottom: 6px; color: #333;
        }
        .form-group input[type="text"],
        .form-group textarea {
            padding: 10px 14px; font-size: 1em; border: 2px solid #e0e0e0;
            border-radius: 8px; background: white; width: 100%;
            font-family: inherit;
        }
        .form-group input[type="text"]:focus,
        .form-group textarea:focus { outline: none; border-color: var(--primary-color); }
        .form-group textarea { min-height: 150px; resize: vertical; }
        .form-group .hint { font-size: 0.85em; color: #888; margin-top: 4px; }
        .btn {
            padding: 10px 20px; border-radius: 8px; text-decoration: none;
            font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer;
            display: inline-block; font-size: 1em;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .test-email-section {
            margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;
        }
        .test-email-section h4 { color: var(--primary-color); margin-bottom: 10px; }
        .test-email-row {
            display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;
        }
        .test-email-row .form-group { margin-bottom: 0; flex: 1; min-width: 200px; }
        .form-group input[type="email"] {
            padding: 10px 14px; font-size: 1em; border: 2px solid #e0e0e0;
            border-radius: 8px; background: white; width: 100%;
        }
        .form-group input[type="email"]:focus { outline: none; border-color: var(--primary-color); }
        .info-box {
            background: var(--primary-light); padding: 12px 16px; border-radius: 8px;
            color: #666; font-size: 0.95em; margin-bottom: 20px;
        }
        .success-message {
            background: #28a745; color: white; padding: 15px 20px;
            border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;
        }
        .error-message {
            background: #dc3545; color: white; padding: 15px 20px;
            border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;
        }
        .presets { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
        .preset-btn {
            padding: 6px 14px; border: 2px solid #e0e0e0; border-radius: 6px;
            background: white; color: var(--primary-color); font-size: 0.85em; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .preset-btn:hover { border-color: var(--primary-color); background: var(--primary-light); }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-section { padding: 16px; }
            .section-title { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">&larr; Back to League</a>

        @if(session('success'))
            <div class="success-message">&check; {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="error-message">{{ $errors->first() }}</div>
        @endif

        <div class="content-section">
            <h2 class="section-title">Email League Message</h2>

            <div class="info-box">
                <strong>{{ $playersWithEmail }}</strong> of {{ $totalPlayers }} players have email enabled and will receive this email.
                @if($playersWithEmail === 0)
                    <br><span style="color: #dc3545; font-weight: 600;">No players have email enabled. Check player email addresses and notification settings.</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.leagues.sendEmailMessage', $league->id) }}">
                @csrf

                <div class="form-group">
                    <label>Quick Templates</label>
                    <div class="presets">
                        <button type="button" class="preset-btn" onclick="setPreset('weather')">Weather Cancellation</button>
                        <button type="button" class="preset-btn" onclick="setPreset('delay')">Weather Delay</button>
                        <button type="button" class="preset-btn" onclick="setPreset('reminder')">Weekly Reminder</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" required maxlength="255"
                        value="{{ old('subject') }}" placeholder="e.g., Weather Cancellation - Week 5">
                </div>

                <div class="form-group">
                    <label for="message_body">Message</label>
                    <textarea name="message_body" id="message_body" required maxlength="5000"
                        placeholder="Type your message here...">{{ old('message_body') }}</textarea>
                    <div class="hint">Line breaks will be preserved in the email.</div>
                </div>

                <button type="submit" class="btn btn-success" id="sendAllBtn" {{ $playersWithEmail === 0 ? 'disabled' : '' }}
                    onclick="return sendAll()">
                    Send to All Players
                </button>

                <div class="test-email-section">
                    <h4>Send Test Email</h4>
                    <div class="test-email-row">
                        <div class="form-group">
                            <label for="test_email">Email Address(es)</label>
                            <input type="text" name="test_email" id="test_email" placeholder="Enter email(s), comma-separated">
                        </div>
                        <button type="submit" class="btn btn-warning" onclick="return sendTest()">Send Test</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function sendAll() {
            document.getElementById('test_email').value = '';
            return confirm('Send this message to {{ $playersWithEmail }} players?');
        }

        function sendTest() {
            var email = document.getElementById('test_email').value.trim();
            if (!email) {
                alert('Please enter an email address for the test.');
                return false;
            }
            return confirm('Send test message to ' + email + '?');
        }

        function setPreset(type) {
            var subject = document.getElementById('subject');
            var body = document.getElementById('message_body');
            if (type === 'weather') {
                subject.value = 'League Cancelled This Week - Weather';
                body.value = 'Due to inclement weather, league play has been cancelled for this week.\n\nPlease check back for updates on the rescheduled date. Stay safe!';
            } else if (type === 'delay') {
                subject.value = 'Weather Delay Notice';
                body.value = 'Due to weather conditions, there will be a delay to tonight\'s league play.\n\nPlease monitor your email for an update on when we will start. Thank you for your patience.';
            } else if (type === 'reminder') {
                subject.value = 'Weekly Reminder';
                body.value = 'This is a reminder that league play is scheduled for this week.\n\nPlease check your tee times and arrive at least 15 minutes early. See you on the course!';
            }
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Message - {{ $league->name }}</title>
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
        .form-group textarea {
            padding: 10px 14px; font-size: 1em; border: 2px solid #e0e0e0;
            border-radius: 8px; background: white; width: 100%;
            font-family: inherit;
        }
        .form-group textarea:focus { outline: none; border-color: var(--primary-color); }
        .form-group textarea { min-height: 120px; resize: vertical; }
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
        .test-section {
            margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;
        }
        .test-section h4 { color: var(--primary-color); margin-bottom: 10px; }
        .test-row {
            display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;
        }
        .test-row .form-group { margin-bottom: 0; flex: 1; min-width: 200px; }
        .form-group input[type="tel"] {
            padding: 10px 14px; font-size: 1em; border: 2px solid #e0e0e0;
            border-radius: 8px; background: white; width: 100%;
        }
        .form-group input[type="tel"]:focus { outline: none; border-color: var(--primary-color); }
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
        .char-count {
            font-size: 0.85em; color: #888; margin-top: 4px;
        }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-section { padding: 16px; }
            .section-title { font-size: 1.3em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.dashboard') }}" class="back-link">&larr; Back to Dashboard</a>

        @if(session('success'))
            <div class="success-message">&check; {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="error-message">{{ $errors->first() }}</div>
        @endif

        <div class="content-section">
            <h2 class="section-title">SMS League Message</h2>

            <div class="info-box">
                <strong>{{ $playersWithPhone }}</strong> of {{ $totalPlayers }} players have SMS enabled and will receive this SMS.
                @if($playersWithPhone === 0)
                    <br><span style="color: #dc3545; font-weight: 600;">No players have SMS enabled. Check player phone numbers and notification settings.</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.leagues.sendSmsMessage', $league->id) }}">
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
                    <label for="message_body">Message</label>
                    <textarea name="message_body" id="message_body" required maxlength="1600"
                        placeholder="Type your message here..." oninput="updateCharCount()">{{ old('message_body') }}</textarea>
                    <div class="hint">The league name will be prepended automatically: "{{ $league->name }}: ..."</div>
                    <div id="char-count" class="char-count"></div>
                </div>

                <button type="submit" class="btn btn-success" id="sendAllBtn" {{ $playersWithPhone === 0 ? 'disabled' : '' }}
                    onclick="return sendAll()">
                    Send to All Players
                </button>

                <div class="test-section">
                    <h4>Send Test SMS</h4>
                    <div class="test-row">
                        <div class="form-group">
                            <label for="test_phone">Phone Number</label>
                            <input type="tel" name="test_phone" id="test_phone" placeholder="e.g., (555) 123-4567">
                        </div>
                        <button type="submit" class="btn btn-warning" onclick="return sendTest()">Send Test</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        var leaguePrefix = '{{ $league->name }}: ';
        var recipientCount = {{ $playersWithPhone }};

        function updateCharCount() {
            var body = document.getElementById('message_body');
            var totalLen = leaguePrefix.length + body.value.length;
            var segments = Math.ceil(totalLen / 160) || 1;
            var cost = (segments * recipientCount * 0.0077).toFixed(2);
            document.getElementById('char-count').innerHTML =
                totalLen + '/1600 chars &bull; ' + segments + ' SMS segment(s) &bull; Est. $' + cost + ' for ' + recipientCount + ' recipients';
        }

        function sendAll() {
            document.getElementById('test_phone').value = '';
            return confirm('Send this SMS to ' + recipientCount + ' players?');
        }

        function sendTest() {
            var phone = document.getElementById('test_phone').value.trim();
            if (!phone) {
                alert('Please enter a phone number for the test.');
                return false;
            }
            return confirm('Send test SMS to ' + phone + '?');
        }

        function setPreset(type) {
            var body = document.getElementById('message_body');
            if (type === 'weather') {
                body.value = 'League cancelled this week due to weather. Check back for reschedule info.';
            } else if (type === 'delay') {
                body.value = 'Weather delay tonight. Monitor your phone for start time update.';
            } else if (type === 'reminder') {
                body.value = 'Reminder: League play this week. Check tee times and arrive 15 min early.';
            }
            updateCharCount();
        }

        updateCharCount();
    </script>
</body>
</html>

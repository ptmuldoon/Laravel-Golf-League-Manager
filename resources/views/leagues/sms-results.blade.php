<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Results - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
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
        .form-group select {
            padding: 10px 14px; font-size: 1em; border: 2px solid #e0e0e0;
            border-radius: 8px; background: white; width: 100%; max-width: 300px;
        }
        .form-group select:focus { outline: none; border-color: var(--primary-color); }
        .btn {
            padding: 10px 20px; border-radius: 8px; text-decoration: none;
            font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer;
            display: inline-block; font-size: 1em;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--secondary-color); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
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
        .button-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
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
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .sms-preview {
            background: #f4f4f7; padding: 16px; border-radius: 8px;
            border: 2px solid #e0e0e0; white-space: pre-wrap;
            font-family: monospace; font-size: 0.95em; line-height: 1.5;
        }
        .sms-info {
            margin-top: 8px; font-size: 0.9em; color: #666;
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
            <h2 class="section-title">SMS Weekly Results</h2>

            <div class="info-box">
                <strong>{{ $playersWithPhone }}</strong> of {{ $totalPlayers }} players have SMS enabled and will receive this SMS.
                @if($playersWithPhone === 0)
                    <br><span style="color: #dc3545; font-weight: 600;">No players have SMS enabled. Check player phone numbers and notification settings.</span>
                @endif
            </div>

            @if($completedWeeks->isNotEmpty())
                <form method="POST" action="{{ route('admin.leagues.sendSmsResults', $league->id) }}" id="smsForm">
                    @csrf
                    <div class="form-group">
                        <label for="week_number">Select Week</label>
                        <select name="week_number" id="week_number">
                            @foreach($completedWeeks->reverse() as $week)
                                <option value="{{ $week }}">Week {{ $week }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="button-row">
                        <button type="button" class="btn btn-primary" onclick="loadPreview()">Preview SMS</button>
                        <button type="submit" class="btn btn-success" id="sendAllBtn" {{ $playersWithPhone === 0 ? 'disabled' : '' }}
                            onclick="return sendAll()">
                            Send to All Players
                        </button>
                    </div>

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

                <div id="preview-container" style="margin-top: 20px; display: none;">
                    <h3 style="color: var(--primary-color); margin-bottom: 10px;">SMS Preview</h3>
                    <div id="sms-preview" class="sms-preview"></div>
                    <div id="sms-info" class="sms-info"></div>
                </div>

                <script>
                    function loadPreview() {
                        var week = document.getElementById('week_number').value;
                        fetch('{{ route("admin.leagues.previewSmsResults", $league->id) }}?week=' + week)
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                document.getElementById('sms-preview').textContent = data.text;
                                var cost = (data.segments * {{ $playersWithPhone }} * 0.0079).toFixed(2);
                                document.getElementById('sms-info').innerHTML =
                                    data.length + ' chars &bull; ' + data.segments + ' SMS segment(s) per recipient'
                                    + ' &bull; Est. cost: $' + cost + ' for {{ $playersWithPhone }} recipients';
                                document.getElementById('preview-container').style.display = 'block';
                            });
                    }

                    function sendAll() {
                        document.getElementById('test_phone').value = '';
                        return confirm('Send Week ' + document.getElementById('week_number').value + ' results via SMS to {{ $playersWithPhone }} players?');
                    }

                    function sendTest() {
                        var phone = document.getElementById('test_phone').value.trim();
                        if (!phone) {
                            alert('Please enter a phone number for the test.');
                            return false;
                        }
                        return confirm('Send test SMS to ' + phone + '?');
                    }

                    document.getElementById('sendAllBtn').addEventListener('click', function() {
                        document.getElementById('test_phone').value = '';
                    });
                </script>
            @else
                <div style="text-align: center; padding: 30px; color: #888;">
                    <p style="font-size: 1.1em;">No completed weeks yet. Complete some matches first to send results.</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>

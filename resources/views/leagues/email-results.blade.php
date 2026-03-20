<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Results - {{ $league->name }}</title>
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
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
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
        .preview-frame {
            width: 100%; border: 2px solid #e0e0e0; border-radius: 8px;
            min-height: 400px; background: #f4f4f7;
        }
        .button-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
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
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
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
            <h2 class="section-title">Email Weekly Results</h2>

            <div class="info-box">
                <strong>{{ $playersWithEmail }}</strong> of {{ $totalPlayers }} players have email enabled and will receive this email.
                @if($playersWithEmail === 0)
                    <br><span style="color: #dc3545; font-weight: 600;">No players have email enabled. Check player email addresses and notification settings.</span>
                @endif
            </div>

            @if($completedWeeks->isNotEmpty())
                <form method="POST" action="{{ route('admin.leagues.sendEmailResults', $league->id) }}" id="emailForm">
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
                        <button type="button" class="btn btn-primary" onclick="loadPreview()">Preview Email</button>
                        <button type="submit" class="btn btn-success" {{ $playersWithEmail === 0 ? 'disabled' : '' }}
                            onclick="return confirm('Send Week ' + document.getElementById('week_number').value + ' results to {{ $playersWithEmail }} players?')">
                            Send to All Players
                        </button>
                    </div>

                    <div class="test-email-section">
                        <h4>Send Test Email</h4>
                        <div class="test-email-row">
                            <div class="form-group">
                                <label for="test_email">Email Address(es)</label>
                                <input type="text" name="test_email" id="test_email" placeholder="Enter email(s), comma-separated">
                            </div>
                            <button type="submit" class="btn btn-warning" onclick="return sendTest(this)">Send Test</button>
                        </div>
                    </div>
                </form>

                <div id="preview-container" style="margin-top: 20px; display: none;">
                    <h3 style="color: var(--primary-color); margin-bottom: 10px;">Email Preview</h3>
                    <iframe id="preview-frame" class="preview-frame" title="Email Preview"></iframe>
                </div>

                <script>
                    function loadPreview() {
                        var week = document.getElementById('week_number').value;
                        var frame = document.getElementById('preview-frame');
                        var container = document.getElementById('preview-container');
                        frame.src = '{{ route("admin.leagues.previewEmailResults", $league->id) }}?week=' + week;
                        container.style.display = 'block';
                    }

                    function sendTest(btn) {
                        var email = document.getElementById('test_email').value.trim();
                        if (!email) {
                            alert('Please enter an email address for the test.');
                            return false;
                        }
                        var week = document.getElementById('week_number').value;
                        return confirm('Send test email for Week ' + week + ' results to ' + email + '?');
                    }

                    // Clear test_email when using "Send to All Players" so it doesn't accidentally send as test
                    document.querySelector('.btn-success').addEventListener('click', function() {
                        document.getElementById('test_email').value = '';
                    });
                </script>
            @else
                <div style="text-align: center; padding: 30px; color: #888;">
                    <p style="font-size: 1.1em;">No completed weeks yet. Complete some matches first to email results.</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>

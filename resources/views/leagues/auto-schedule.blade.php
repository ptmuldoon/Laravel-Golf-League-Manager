<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Schedule Generator</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; padding: 20px;">
    <div style="max-width: 800px; margin: 0 auto;">
        <a href="{{ route('admin.leagues.show', $league->id) }}" style="display: inline-block; color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 5px; margin-bottom: 20px;">← Back to League</a>

        @if($errors->any())
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Error:</strong> {{ $errors->first() }}
            </div>
        @endif

        <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h1 style="color: var(--primary-color); margin-bottom: 10px; font-size: 2em;">🤖 Auto-Schedule Generator</h1>
            <p style="color: #666; margin-bottom: 30px;">{{ $league->name }} - {{ $league->season }}</p>

            <div style="background: #e8f4f8; padding: 20px; border-radius: 8px; margin-bottom: 30px; color: #0c5460;">
                <h3 style="margin-bottom: 10px;">How it works:</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 8px 0;">✓ Automatically creates foursomes for your specified number of weeks</li>
                    <li style="padding: 8px 0;">✓ Intelligently rotates players to maximize variety</li>
                    <li style="padding: 8px 0;">✓ Creates balanced 2v2 match play pairings</li>
                    <li style="padding: 8px 0;">✓ You can preview and edit before saving</li>
                </ul>
            </div>

            <form action="{{ route('admin.leagues.generateSchedule', $league->id) }}" method="POST">
                @csrf

                @if(isset($segments) && $segments->isNotEmpty())
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Segment</label>
                        <select name="segment_id" id="segment_select" onchange="updateSegmentDefaults()" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit;">
                            <option value="">All Teams (No Segment)</option>
                            @foreach($segments as $segment)
                                <option value="{{ $segment->id }}" data-weeks="{{ $segment->weekCount() }}" data-teams="{{ $segment->teams->count() }}">
                                    {{ $segment->name }} (Weeks {{ $segment->start_week }}-{{ $segment->end_week }}, {{ $segment->teams->count() }} teams)
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">Select a segment to use its teams for scheduling</div>
                    </div>
                @endif

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Number of Weeks</label>
                    <input type="number" name="weeks" id="weeks_input" value="16" min="1" max="52" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em;">
                    <div style="font-size: 0.85em; color: #666; margin-top: 5px;">How many weeks to schedule (typically 16)</div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Start Date</label>
                    <input type="date" name="start_date" value="{{ $league->start_date->format('Y-m-d') }}" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em;">
                    <div style="font-size: 0.85em; color: #666; margin-top: 5px;">First week of play</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Holes</label>
                        <select name="holes" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit;">
                            <option value="front_9" selected>Front 9 (Holes 1-9)</option>
                            <option value="back_9">Back 9 (Holes 10-18)</option>
                        </select>
                        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">Applied to all weeks in the schedule</div>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Scoring Method</label>
                        <select name="scoring_type" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit;">
                            @foreach($scoringTypes as $key => $label)
                                <option value="{{ $key }}" {{ $key === 'best_ball_match_play' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">Applied to all weeks in the schedule</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Score Mode</label>
                        <select name="score_mode" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit;">
                            <option value="net" selected>Net Score (with handicap strokes)</option>
                            <option value="gross">Gross Score (no handicap adjustment)</option>
                        </select>
                        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">Net uses handicap strokes to determine hole winners. Gross uses raw scores.</div>
                    </div>
                    <div></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">First Tee Time</label>
                        <input type="time" name="start_tee_time" value="16:40" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em;">
                        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">Starting tee time for the first group</div>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Tee Time Interval (minutes)</label>
                        <input type="number" name="tee_time_interval" value="10" min="5" max="30" required style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em;">
                        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">Minutes between each group's tee time</div>
                    </div>
                </div>

                <div style="background: #e8f4f8; padding: 20px; border-radius: 8px; margin-top: 30px; color: #0c5460;">
                    <strong>📊 Current Status:</strong><br>
                    Players assigned to league: <strong>{{ $playerCount }}</strong><br>
                    Course: <strong>{{ $league->golfCourse->name ?? 'Not set' }}</strong><br>
                    Teebox: <strong>{{ $league->default_teebox ?? 'Not set' }}</strong>
                </div>

                @if($playerCount < 4)
                    <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <strong>⚠️ Not Enough Players</strong><br>
                        You need at least 4 players assigned to this league to generate a schedule. You currently have {{ $playerCount }} player(s).<br>
                        <a href="{{ route('admin.leagues.players.manage', $league->id) }}" style="color: #721c24; text-decoration: underline; font-weight: 600;">Add more players to the league</a>
                    </div>
                @endif

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" style="padding: 12px 24px; border: none; border-radius: 8px; font-size: 1em; font-weight: 600; cursor: pointer; background: {{ $playerCount < 4 ? '#94d3a2' : 'var(--primary-color)' }}; color: white;" {{ $playerCount < 4 ? 'disabled' : '' }}>Generate Schedule Preview</button>
                    <a href="{{ route('admin.leagues.show', $league->id) }}" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; text-decoration: none; background: #6c757d; color: white; display: inline-block;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateSegmentDefaults() {
            const select = document.getElementById('segment_select');
            if (!select) return;
            const option = select.options[select.selectedIndex];
            const weeks = option.getAttribute('data-weeks');
            if (weeks) {
                document.getElementById('weeks_input').value = weeks;
            }
        }
    </script>
</body>
</html>

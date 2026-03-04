<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Match</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 2em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        .required {
            color: #dc3545;
        }
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .help-text {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        .error {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #0c5460;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">← Back to League</a>

        <div class="form-container">
            <h1>📅 Schedule Match</h1>
            <p class="subtitle">{{ $league->name }} - Week {{ $nextWeek }}</p>

            <form action="{{ route('admin.matches.store') }}" method="POST">
                @csrf
                <input type="hidden" name="league_id" value="{{ $league->id }}">

                <div class="form-row">
                    <div class="form-group">
                        <label for="week_number">Week Number <span class="required">*</span></label>
                        <input type="number" id="week_number" name="week_number" value="{{ old('week_number', $nextWeek) }}" min="1" required>
                        @error('week_number')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="match_date">Match Date <span class="required">*</span></label>
                        <input type="date" id="match_date" name="match_date" value="{{ old('match_date') }}" required>
                        @error('match_date')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="home_team_id">Home Team (Optional)</label>
                        <select id="home_team_id" name="home_team_id">
                            <option value="">No team (player match)</option>
                            @foreach($league->teams as $team)
                                <option value="{{ $team->id }}" {{ old('home_team_id') == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('home_team_id')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="away_team_id">Away Team (Optional)</label>
                        <select id="away_team_id" name="away_team_id">
                            <option value="">No team (player match)</option>
                            @foreach($league->teams as $team)
                                <option value="{{ $team->id }}" {{ old('away_team_id') == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('away_team_id')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="holes">Holes <span class="required">*</span></label>
                        <select id="holes" name="holes" required>
                            <option value="front_9" {{ old('holes', 'front_9') === 'front_9' ? 'selected' : '' }}>Front 9 (Holes 1-9)</option>
                            <option value="back_9" {{ old('holes') === 'back_9' ? 'selected' : '' }}>Back 9 (Holes 10-18)</option>
                        </select>
                        @error('holes')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="scoring_type">Scoring Method <span class="required">*</span></label>
                        <select id="scoring_type" name="scoring_type" required>
                            @foreach($scoringTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('scoring_type', 'best_ball_match_play') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('scoring_type')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="score_mode">Score Mode <span class="required">*</span></label>
                        <select id="score_mode" name="score_mode" required>
                            <option value="net" {{ old('score_mode', 'net') === 'net' ? 'selected' : '' }}>Net Score (with handicap strokes)</option>
                            <option value="gross" {{ old('score_mode') === 'gross' ? 'selected' : '' }}>Gross Score (no handicap adjustment)</option>
                        </select>
                        <div class="help-text">Net uses handicap strokes to determine hole winners. Gross uses raw scores.</div>
                        @error('score_mode')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="golf_course_id">Golf Course <span class="required">*</span></label>
                        <select id="golf_course_id" name="golf_course_id" required onchange="updateTeeboxes()">
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('golf_course_id', $league->golf_course_id) == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('golf_course_id')
                            <div class="error">{{ $message }}</div>
                        @enderror
                        <div class="help-text">Defaults to league's default course</div>
                    </div>
                    <div class="form-group">
                        <label for="teebox">Teebox <span class="required">*</span></label>
                        <select id="teebox" name="teebox" required>
                            <option value="">Select teebox...</option>
                        </select>
                        @error('teebox')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Player Selection Section -->
                <div class="form-group" style="margin-top: 30px;">
                    <h3 style="color: var(--primary-color); margin-bottom: 20px;">👥 Assign Players</h3>

                    @if($allPlayers->isEmpty())
                        <div class="info-box" style="background: #f8d7da; color: #721c24;">
                            <strong>⚠️ No Players Assigned to League</strong><br>
                            There are no players assigned to this league yet.
                            <a href="{{ route('admin.leagues.players.manage', $league->id) }}" style="color: #721c24; text-decoration: underline; font-weight: 600;">Add players to the league</a> before assigning them to matches.
                        </div>
                    @endif

                    <div class="form-row">
                        <div class="form-group">
                            <label>Home Side Players</label>
                            <div id="home-players">
                                <div style="margin-bottom: 10px;">
                                    <select name="home_players[]" class="player-select">
                                        <option value="">Select player...</option>
                                        @foreach($allPlayers as $player)
                                            <option value="{{ $player->id }}">
                                                {{ $player->name }} (HI: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="button" onclick="addHomePlayer()" class="btn btn-secondary" style="margin-top: 10px; padding: 8px 16px; font-size: 0.9em;">+ Add Player</button>
                            @error('home_players')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Away Side Players</label>
                            <div id="away-players">
                                <div style="margin-bottom: 10px;">
                                    <select name="away_players[]" class="player-select">
                                        <option value="">Select player...</option>
                                        @foreach($allPlayers as $player)
                                            <option value="{{ $player->id }}">
                                                {{ $player->name }} (HI: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="button" onclick="addAwayPlayer()" class="btn btn-secondary" style="margin-top: 10px; padding: 8px 16px; font-size: 0.9em;">+ Add Player</button>
                            @error('away_players')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="help-text">You can assign players now or later. Leave empty to assign players after scheduling.</div>
                </div>

                <div class="info-box">
                    💡 You can schedule a player-based match without teams, or use teams if preferred. Assign players now or later. Only players assigned to this league are shown above.
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Schedule Match</button>
                    <a href="{{ route('admin.leagues.show', $league->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const courseTeeboxes = @json($courses->mapWithKeys(function($course) {
            return [$course->id => $course->courseInfo->pluck('teebox')->unique()->values()];
        }));

        const allPlayers = @json($allPlayers->map(function($player) {
            return [
                'id' => $player->id,
                'name' => $player->name,
                'handicap' => $player->currentHandicap()?->handicap_index ?? 'N/A'
            ];
        }));

        function updateTeeboxes() {
            const courseId = document.getElementById('golf_course_id').value;
            const teeboxSelect = document.getElementById('teebox');
            const defaultTeebox = "{{ $league->default_teebox }}";

            teeboxSelect.innerHTML = '<option value="">Select teebox...</option>';

            if (courseId && courseTeeboxes[courseId]) {
                courseTeeboxes[courseId].forEach(teebox => {
                    const option = document.createElement('option');
                    option.value = teebox;
                    option.textContent = teebox;
                    if (teebox === defaultTeebox) {
                        option.selected = true;
                    }
                    teeboxSelect.appendChild(option);
                });
            }
        }

        function addHomePlayer() {
            const container = document.getElementById('home-players');
            const div = document.createElement('div');
            div.style.marginBottom = '10px';
            div.innerHTML = `
                <select name="home_players[]" class="player-select">
                    <option value="">Select player...</option>
                    ${allPlayers.map(p => `<option value="${p.id}">${p.name} (HI: ${p.handicap})</option>`).join('')}
                </select>
                <button type="button" onclick="this.parentElement.remove()" style="margin-left: 5px; padding: 12px 15px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer;">✕</button>
            `;
            container.appendChild(div);
        }

        function addAwayPlayer() {
            const container = document.getElementById('away-players');
            const div = document.createElement('div');
            div.style.marginBottom = '10px';
            div.innerHTML = `
                <select name="away_players[]" class="player-select">
                    <option value="">Select player...</option>
                    ${allPlayers.map(p => `<option value="${p.id}">${p.name} (HI: ${p.handicap})</option>`).join('')}
                </select>
                <button type="button" onclick="this.parentElement.remove()" style="margin-left: 5px; padding: 12px 15px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer;">✕</button>
            `;
            container.appendChild(div);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateTeeboxes();
            const oldTeebox = "{{ old('teebox') }}";
            if (oldTeebox) {
                document.getElementById('teebox').value = oldTeebox;
            }
        });
    </script>
</body>
</html>

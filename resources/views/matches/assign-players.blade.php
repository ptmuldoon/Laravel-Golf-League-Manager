<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Players</title>
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
            max-width: 1200px;
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
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: var(--primary-color);
            font-size: 2em;
            margin-bottom: 10px;
        }
        .match-info {
            color: #666;
            font-size: 1.1em;
        }
        .teams-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .team-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .team-title {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .player-select {
            margin-bottom: 15px;
        }
        .player-select label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }
        select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .player-info {
            background: var(--primary-light);
            padding: 10px;
            border-radius: 5px;
            margin-top: 8px;
            font-size: 0.9em;
            color: #666;
        }
        .handicap-display {
            margin-top: 5px;
            font-weight: 600;
            color: var(--primary-color);
        }
        .submit-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
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
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #0c5460;
            font-size: 0.9em;
        }
        .error {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.scheduleOverview', $match->league_id) }}" class="back-link">← Back to Schedule</a>

        <div class="header">
            <h1>👥 Assign Players to Match</h1>
            <div class="match-info">
                {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}<br>
                📅 {{ $match->match_date->format('M d, Y') }} | ⛳ {{ $match->golfCourse->name }}
            </div>
        </div>

        <div class="info-box">
            💡 Select at least one player from each team. Handicap indexes will be snapshotted and course handicaps will be calculated automatically.
            @if($courseInfo)
                <br><strong>Slope Rating:</strong> {{ $courseInfo->slope }} (used for course handicap calculation)
            @endif
        </div>

        <form action="{{ route('admin.matches.storePlayers', $match->id) }}" method="POST" id="assignForm">
            @csrf

            <div class="teams-container">
                <!-- Home Team -->
                <div class="team-section">
                    <div class="team-title">🏠 {{ $match->homeTeam->name }}</div>

                    @if($match->homeTeam->players->isEmpty())
                        <p style="color: #888; font-style: italic;">No players on this team. Add players to the team first.</p>
                    @else
                        <div id="homePlayers">
                            <div class="player-select">
                                <label for="home_player_1">Player 1</label>
                                <select name="home_players[]" id="home_player_1" required onchange="updateHandicapInfo(this, 'home', 0)">
                                    <option value="">Select player...</option>
                                    @foreach($match->homeTeam->players as $player)
                                        <option value="{{ $player->id }}"
                                                data-handicap="{{ $player->currentHandicap()?->handicap_index ?? 0 }}"
                                                {{ (isset($match->matchPlayers) && $match->matchPlayers->where('team_id', $match->home_team_id)->where('position_in_pairing', 1)->first()?->player_id == $player->id) ? 'selected' : '' }}>
                                            {{ $player->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="home_info_0" class="player-info" style="display: none;"></div>
                            </div>

                            <div class="player-select">
                                <label for="home_player_2">Player 2</label>
                                <select name="home_players[]" id="home_player_2" onchange="updateHandicapInfo(this, 'home', 1)">
                                    <option value="">Select player (optional)...</option>
                                    @foreach($match->homeTeam->players as $player)
                                        <option value="{{ $player->id }}"
                                                data-handicap="{{ $player->currentHandicap()?->handicap_index ?? 0 }}"
                                                {{ (isset($match->matchPlayers) && $match->matchPlayers->where('team_id', $match->home_team_id)->where('position_in_pairing', 2)->first()?->player_id == $player->id) ? 'selected' : '' }}>
                                            {{ $player->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="home_info_1" class="player-info" style="display: none;"></div>
                            </div>
                        </div>
                        @error('home_players')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    @endif
                </div>

                <!-- Away Team -->
                <div class="team-section">
                    <div class="team-title">✈️ {{ $match->awayTeam->name }}</div>

                    @if($match->awayTeam->players->isEmpty())
                        <p style="color: #888; font-style: italic;">No players on this team. Add players to the team first.</p>
                    @else
                        <div id="awayPlayers">
                            <div class="player-select">
                                <label for="away_player_1">Player 1</label>
                                <select name="away_players[]" id="away_player_1" required onchange="updateHandicapInfo(this, 'away', 0)">
                                    <option value="">Select player...</option>
                                    @foreach($match->awayTeam->players as $player)
                                        <option value="{{ $player->id }}"
                                                data-handicap="{{ $player->currentHandicap()?->handicap_index ?? 0 }}"
                                                {{ (isset($match->matchPlayers) && $match->matchPlayers->where('team_id', $match->away_team_id)->where('position_in_pairing', 1)->first()?->player_id == $player->id) ? 'selected' : '' }}>
                                            {{ $player->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="away_info_0" class="player-info" style="display: none;"></div>
                            </div>

                            <div class="player-select">
                                <label for="away_player_2">Player 2</label>
                                <select name="away_players[]" id="away_player_2" onchange="updateHandicapInfo(this, 'away', 1)">
                                    <option value="">Select player (optional)...</option>
                                    @foreach($match->awayTeam->players as $player)
                                        <option value="{{ $player->id }}"
                                                data-handicap="{{ $player->currentHandicap()?->handicap_index ?? 0 }}"
                                                {{ (isset($match->matchPlayers) && $match->matchPlayers->where('team_id', $match->away_team_id)->where('position_in_pairing', 2)->first()?->player_id == $player->id) ? 'selected' : '' }}>
                                            {{ $player->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="away_info_1" class="player-info" style="display: none;"></div>
                            </div>
                        </div>
                        @error('away_players')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
            </div>

            <div class="submit-section">
                <button type="submit" class="btn btn-primary">Assign Players & Continue</button>
                <a href="{{ route('admin.leagues.scheduleOverview', $match->league_id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        const slope = {{ $courseInfo->slope ?? 113 }};
        const rating = {{ $courseInfo->rating ?? 0 }};
        const totalPar = {{ $totalPar ?? 72 }};

        function calculateCourseHandicap(handicapIndex, slope) {
            return Math.round((handicapIndex * slope / 113) + (rating - totalPar));
        }

        function updateHandicapInfo(select, team, index) {
            const infoDiv = document.getElementById(`${team}_info_${index}`);
            const selectedOption = select.options[select.selectedIndex];

            if (!selectedOption.value) {
                infoDiv.style.display = 'none';
                return;
            }

            const handicapIndex = parseFloat(selectedOption.dataset.handicap) || 0;
            const courseHandicap = calculateCourseHandicap(handicapIndex, slope);

            infoDiv.innerHTML = `
                <strong>${selectedOption.text}</strong><br>
                Handicap Index: ${handicapIndex.toFixed(1)}
                <div class="handicap-display">Course Handicap: ${courseHandicap}</div>
            `;
            infoDiv.style.display = 'block';
        }

        // Initialize handicap displays on page load
        document.addEventListener('DOMContentLoaded', function() {
            ['home', 'away'].forEach(team => {
                [0, 1].forEach(index => {
                    const select = document.querySelector(`select[name="${team}_players[]"]:nth-of-type(${index + 1})`);
                    if (select && select.value) {
                        updateHandicapInfo(select, team, index);
                    }
                });
            });
        });
    </script>
</body>
</html>

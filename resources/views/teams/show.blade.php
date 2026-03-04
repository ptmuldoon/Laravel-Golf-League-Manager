<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $team->name }}</title>
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
        }
        .team-name {
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .league-name {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: var(--primary-light);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-label {
            font-size: 0.85em;
            color: #888;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-color);
        }
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.5em;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .player-card {
            background: var(--primary-light);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }
        .player-name {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .captain-badge {
            background: #ffd700;
            color: #333;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.75em;
            font-weight: 600;
            margin-left: 8px;
        }
        .player-handicap {
            color: #666;
            font-size: 0.9em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: var(--primary-light);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #e0e0e0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        tr:hover {
            background: var(--primary-light);
        }
        .match-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .match-link:hover {
            text-decoration: underline;
        }
        .result-win {
            color: #28a745;
            font-weight: 600;
        }
        .result-loss {
            color: #dc3545;
            font-weight: 600;
        }
        .result-tie {
            color: #ffc107;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.show', $team->league_id) }}" class="back-link">← Back to League</a>

        <div class="header">
            <div class="team-name">{{ $team->name }}</div>
            <div class="league-name">
                {{ $team->league->name }} - {{ $team->league->season }}
                @if($team->segment)
                    <span style="background: var(--primary-color); color: white; padding: 2px 10px; border-radius: 10px; font-size: 0.7em; font-weight: 500; margin-left: 8px;">{{ $team->segment->name }}</span>
                @endif
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Wins</div>
                    <div class="stat-value">{{ $team->wins }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Losses</div>
                    <div class="stat-value">{{ $team->losses }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Ties</div>
                    <div class="stat-value">{{ $team->ties }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Points</div>
                    <div class="stat-value">{{ $team->totalPoints() }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Win %</div>
                    <div class="stat-value">{{ $team->winPercentage() }}%</div>
                </div>
            </div>

            @if($team->captain)
                <div style="text-align: center; margin-top: 15px; color: #666;">
                    ⭐ Team Captain: <strong>{{ $team->captain->name }}</strong>
                </div>
            @endif
        </div>

        <!-- Roster -->
        <div class="content-section">
            <h2 class="section-title">👥 Team Roster ({{ $team->players->count() }} players)</h2>

            @if($team->players->isEmpty())
                <div class="empty-state">
                    <p>No players on this team yet.</p>
                </div>
            @else
                <div class="players-grid">
                    @foreach($team->players as $player)
                        <div class="player-card">
                            <div class="player-name">
                                {{ $player->name }}
                                @if($team->captain_id == $player->id)
                                    <span class="captain-badge">CAPTAIN</span>
                                @endif
                            </div>
                            <div class="player-handicap">
                                Handicap Index: {{ $player->currentHandicap()?->handicap_index ? number_format($player->currentHandicap()->handicap_index, 1) : 'N/A' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Match History -->
        <div class="content-section">
            <h2 class="section-title">📅 Match History</h2>

            @if($matches->isEmpty())
                <div class="empty-state">
                    <p>No matches played yet.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Week</th>
                            <th>Date</th>
                            <th>Opponent</th>
                            <th>Result</th>
                            <th>Score</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($matches as $match)
                            @php
                                $isHome = $match->home_team_id == $team->id;
                                $opponent = $isHome ? $match->awayTeam : $match->homeTeam;
                                $result = $match->result;
                            @endphp
                            <tr>
                                <td>{{ $match->week_number }}</td>
                                <td>{{ $match->match_date->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.matches.show', $match->id) }}" class="match-link">
                                        {{ $isHome ? 'vs' : '@' }} {{ $opponent->name }}
                                    </a>
                                </td>
                                <td>
                                    @if($match->status === 'completed' && $result)
                                        @if($result->winning_team_id == $team->id)
                                            <span class="result-win">W</span>
                                        @elseif($result->winning_team_id == null)
                                            <span class="result-tie">T</span>
                                        @else
                                            <span class="result-loss">L</span>
                                        @endif
                                    @else
                                        <span style="color: #888;">{{ ucfirst($match->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($match->status === 'completed' && $result)
                                        @php
                                            $myWon = $isHome ? $result->holes_won_home : $result->holes_won_away;
                                            $oppWon = $isHome ? $result->holes_won_away : $result->holes_won_home;
                                            $myScore = $myWon + ($result->holes_tied * 0.5);
                                            $oppScore = $oppWon + ($result->holes_tied * 0.5);
                                            $fMy = $myScore == (int)$myScore ? (int)$myScore : number_format($myScore, 1);
                                            $fOpp = $oppScore == (int)$oppScore ? (int)$oppScore : number_format($oppScore, 1);
                                        @endphp
                                        {{ $fMy }} - {{ $fOpp }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($match->status === 'completed' && $result)
                                        {{ $isHome ? $result->team_points_home : $result->team_points_away }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</body>
</html>

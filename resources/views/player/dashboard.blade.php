<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.5em;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        .navbar-links { display: flex; gap: 15px; align-items: center; }
        .navbar-links a {
            color: white; text-decoration: none; padding: 8px 16px;
            border-radius: 5px; transition: background 0.3s ease;
        }
        .navbar-links a:hover { background: rgba(255,255,255,0.2); }
        .navbar-links a.active { background: rgba(255,255,255,0.25); }
        .navbar-hamburger {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            padding: 4px 8px;
            line-height: 1;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        h1 { color: #333; font-size: 2em; margin-bottom: 5px; }
        .welcome-text { color: #666; margin-bottom: 25px; font-size: 1.1em; }
        .alert-success {
            background: #d4edda; color: #155724; padding: 12px 20px;
            border-radius: 8px; margin-bottom: 20px; font-weight: 500;
        }
        .alert-error {
            background: #f8d7da; color: #721c24; padding: 12px 20px;
            border-radius: 8px; margin-bottom: 20px; font-weight: 500;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary-color);
        }
        .stat-label {
            color: #666;
            font-size: 0.95em;
            margin-top: 5px;
        }
        .content-section {
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 25px;
        }
        .section-title {
            font-size: 1.3em; color: var(--primary-color); margin-bottom: 15px;
            padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: var(--primary-light); padding: 10px 12px; text-align: left;
            font-weight: 600; color: var(--primary-color); border-bottom: 2px solid #e0e0e0;
            font-size: 0.9em;
        }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 0.95em; }
        tr:hover { background: var(--primary-light); }
        .btn {
            display: inline-block; padding: 10px 24px; border: none;
            border-radius: 8px; font-weight: 600; font-size: 0.95em;
            cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; color: white;
        }
        .btn-primary { background: var(--primary-color); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-sm { padding: 6px 14px; font-size: 0.85em; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 0.8em; font-weight: 600;
        }
        .badge-scheduled { background: #fff3cd; color: #856404; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-active { background: #d4edda; color: #155724; }
        .empty-state {
            text-align: center; padding: 30px; color: #888;
        }
        a.player-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        a.player-link:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .navbar { padding: 12px 16px; flex-wrap: wrap; }
            .navbar-brand { flex: 1; }
            .navbar-hamburger { display: block; }
            .navbar-links {
                display: none; width: 100%; flex-direction: column;
                gap: 0; padding-top: 8px;
                border-top: 1px solid rgba(255,255,255,0.2); margin-top: 8px;
            }
            .navbar-links.open { display: flex; }
            .navbar-links a { padding: 10px 12px; }
            .navbar-links form { width: 100%; }
            .navbar-links form button { width: 100%; text-align: left; padding: 10px 12px; }
            .container { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            table { font-size: 0.85em; }
            th, td { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="{{ route('player.dashboard') }}" class="navbar-brand">{{ config('app.name') }}</a>
        <button class="navbar-hamburger" onclick="var nl=this.closest('.navbar').querySelector('.navbar-links');nl.classList.toggle('open');" aria-label="Menu">&#9776;</button>
        <div class="navbar-links">
            <a href="{{ route('player.dashboard') }}" class="active">My Dashboard</a>
            <a href="{{ route('players.show', $player->id) }}">My Stats</a>
            <a href="{{ route('home') }}">League Home</a>
            <a href="{{ route('profile.show') }}">Profile</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: white; cursor: pointer; padding: 8px 16px; border-radius: 5px; transition: background 0.3s ease;">
                    Logout
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        <h1>Welcome, {{ $player->first_name }}!</h1>
        <p class="welcome-text">Your golf dashboard</p>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $currentHandicap ? number_format($currentHandicap->handicap_index, 1) : 'N/A' }}</div>
                <div class="stat-label">Handicap Index</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $player->rounds()->count() }}</div>
                <div class="stat-label">Total Rounds</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $leagues->count() }}</div>
                <div class="stat-label">Active Leagues</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $upcomingMatches->count() }}</div>
                <div class="stat-label">Upcoming Matches</div>
            </div>
        </div>

        @if($scorePostingEnabled)
        <!-- Quick Actions -->
        <div class="content-section">
            <h2 class="section-title">Quick Actions</h2>
            <a href="{{ route('player.score-entry') }}" class="btn btn-primary">Enter a Scorecard</a>
        </div>
        @endif

        <!-- Upcoming Matches -->
        @if($upcomingMatches->isNotEmpty())
        <div class="content-section">
            <h2 class="section-title">Upcoming Matches</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>League</th>
                            <th>Matchup</th>
                            <th>Course</th>
                            <th>Tee Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingMatches as $match)
                        <tr>
                            <td>{{ $match->match_date->format('M d, Y') }}</td>
                            <td>{{ $match->league->name ?? '' }}</td>
                            <td>
                                {{ $match->homeTeam->name ?? 'TBD' }}
                                vs
                                {{ $match->awayTeam->name ?? 'TBD' }}
                            </td>
                            <td>{{ $match->golfCourse->name ?? '' }}</td>
                            <td>{{ $match->tee_time ? \Carbon\Carbon::parse($match->tee_time)->format('g:i A') : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Recent Matches -->
        <div class="content-section">
            <h2 class="section-title">Recent Match Results</h2>
            @if($recentMatches->isNotEmpty())
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>League</th>
                            <th>Matchup</th>
                            <th>Course</th>
                            <th>Your Score</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentMatches as $match)
                        @php
                            $mp = $match->matchPlayers->first(function($mp) use ($player) {
                                return $mp->player_id == $player->id || $mp->substitute_player_id == $player->id;
                            });
                            $totalStrokes = $mp ? $mp->scores->sum('strokes') : null;
                        @endphp
                        <tr>
                            <td>{{ $match->match_date->format('M d, Y') }}</td>
                            <td>{{ $match->league->name ?? '' }}</td>
                            <td>
                                {{ $match->homeTeam->name ?? 'TBD' }}
                                vs
                                {{ $match->awayTeam->name ?? 'TBD' }}
                            </td>
                            <td>{{ $match->golfCourse->name ?? '' }}</td>
                            <td style="font-weight: 600;">{{ $totalStrokes ?? '-' }}</td>
                            <td>
                                <a href="{{ route('matches.show', $match->id) }}" class="btn btn-primary btn-sm">Details</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <div class="empty-state">No match results yet.</div>
            @endif
        </div>

        <!-- Recent Rounds -->
        <div class="content-section">
            <h2 class="section-title">
                Recent Rounds
                <a href="{{ route('players.show', $player->id) }}" class="player-link" style="font-size: 0.7em;">View All &rarr;</a>
            </h2>
            @if($recentRounds->isNotEmpty())
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Tees</th>
                            <th>Holes</th>
                            <th>Score</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRounds as $round)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($round->played_at)->format('M d, Y') }}</td>
                            <td>{{ $round->golfCourse->name ?? '' }}</td>
                            <td>{{ $round->teebox }}</td>
                            <td>{{ $round->holes_played ?? 18 }}</td>
                            <td style="font-weight: 600;">{{ $round->total_score }}</td>
                            <td>
                                <a href="{{ route('players.round', [$player->id, $round->id]) }}" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <div class="empty-state">No rounds recorded yet. @if($scorePostingEnabled)<a href="{{ route('player.score-entry') }}" class="player-link">Enter your first scorecard!</a>@endif</div>
            @endif
        </div>

        <!-- Active Leagues -->
        @if($leagues->isNotEmpty())
        <div class="content-section">
            <h2 class="section-title">My Leagues</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>League</th>
                            <th>Season</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leagues as $league)
                        <tr>
                            <td style="font-weight: 600;">{{ $league->name }}</td>
                            <td>{{ $league->season }}</td>
                            <td><span class="badge badge-active">Active</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</body>
</html>

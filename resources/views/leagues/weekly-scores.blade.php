<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Scores - {{ $league->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: var(--primary-color);
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        .back-link:hover {
            background: var(--secondary-color);
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
        }
        .navbar-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .navbar-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px;
        }
        h1 {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 25px;
        }
        .week-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        .week-selector label {
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }
        .week-selector select {
            padding: 10px 16px;
            font-size: 1em;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            background: white;
            color: #333;
            cursor: pointer;
            min-width: 200px;
        }
        .week-selector select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.2);
        }
        .match-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .match-header {
            background: var(--secondary-color);
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .match-header-title {
            font-size: 1.2em;
            font-weight: 600;
        }
        .match-header-info {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .scorecard-wrap {
            padding: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        th {
            background: var(--primary-color);
            color: white;
            padding: 10px 6px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9em;
            border: 1px solid #5568d3;
        }
        td {
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #e0e0e0;
            font-size: 0.9em;
        }
        .player-name-cell {
            text-align: left;
            font-weight: 600;
            color: #333;
            background: var(--primary-light);
            white-space: nowrap;
            min-width: 160px;
        }
        .handicap-cell {
            font-size: 0.8em;
            color: #666;
            white-space: nowrap;
        }
        .team-row {
            background: #e8ecf4;
        }
        .team-row td {
            font-weight: 600;
            color: var(--primary-color);
            text-align: left;
            padding: 8px 12px;
        }
        .par-row td {
            background: #f0f0f0;
            font-weight: 600;
            font-size: 0.85em;
        }
        .yardage-row td {
            background: #e8f0fe;
            font-size: 0.8em;
            color: #555;
        }
        input[type="number"] {
            width: 46px;
            padding: 6px 2px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            text-align: center;
            font-size: 0.95em;
            font-weight: 600;
            -moz-appearance: textfield;
            appearance: textfield;
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"]:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--primary-light);
        }
        .total-cell {
            background: var(--primary-light);
            font-weight: 600;
            color: var(--primary-color);
        }
        .stroke-dots-row td {
            padding: 1px 6px;
            font-size: 0.7em;
            color: var(--secondary-color);
            border: none;
            line-height: 1;
        }
        .results-row td {
            padding: 6px;
            font-size: 0.85em;
            font-weight: 700;
            border: 1px solid #e0e0e0;
            text-align: center;
        }
        .result-home {
            background: #e3f2fd;
            color: #1565c0;
        }
        .result-away {
            background: #fce4ec;
            color: #c62828;
        }
        .result-tie {
            background: #f5f5f5;
            color: #999;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .submit-section {
            text-align: center;
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #888;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-scheduled {
            background: #fff3cd;
            color: #856404;
        }
        .status-in_progress {
            background: #cce5ff;
            color: #004085;
        }
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
            .navbar-links a { padding: 10px 12px; border-radius: 4px; }
            .navbar-links form { width: 100%; display: block !important; }
            .navbar-links form button { width: 100%; text-align: left; padding: 10px 12px; border-radius: 4px; }
            .container { padding: 16px; }
            .week-selector { flex-wrap: wrap; }
            .week-selector select { min-width: 0; width: 100%; }
            .match-header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .submit-section { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🏌️ Golf Admin</div>
        <button class="navbar-hamburger" onclick="var nl=this.closest('.navbar').querySelector('.navbar-links');nl.classList.toggle('open');" aria-label="Menu">☰</button>
        <div class="navbar-links">
            <a href="{{ route('admin.dashboard') }}">📊 Dashboard</a>
            <a href="{{ route('home') }}">🏠 Public Site</a>
            <a href="{{ route('admin.leagues') }}">🏆 Leagues</a>
            <a href="{{ route('admin.players') }}">👥 Players</a>
            <a href="{{ route('admin.users') }}">🔑 Users</a>
            <a href="{{ route('admin.courses.index') }}">⛳ Courses</a>
            <a href="{{ route('profile.show') }}">👤 Profile</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: white; cursor: pointer; padding: 8px 16px; border-radius: 5px; transition: background 0.3s ease;">
                    🚪 Logout
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        <a href="{{ route('admin.leagues.scheduleOverview', $league->id) }}" class="back-link">← Back to Schedule</a>

        <h1>📋 Weekly Score Entry</h1>
        <p class="subtitle">{{ $league->name }} — {{ $league->season }}</p>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($weeks->isEmpty())
            <div class="empty-state">
                <p style="font-size: 1.2em; margin-bottom: 10px;">No matches scheduled yet</p>
                <p>Create a schedule first to enter scores.</p>
            </div>
        @else
            <div class="week-selector">
                <label for="week-select">Select Week:</label>
                <select id="week-select" onchange="window.location.href='{{ route('admin.leagues.scores', $league->id) }}?week=' + this.value">
                    @foreach($weeks as $week)
                        <option value="{{ $week }}" {{ $selectedWeek == $week ? 'selected' : '' }}>
                            Week {{ $week }}
                            @php
                                $weekMatch = $matches->where('week_number', $week)->first();
                            @endphp
                            @if($selectedWeek == $week && $matches->isNotEmpty())
                                — {{ $matches->first()->match_date->format('M d, Y') }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            @if($matches->isNotEmpty())
                @php
                    $jsMatchData = [];
                    $weekHasScores = \App\Models\MatchScore::whereIn('match_player_id', function ($q) use ($matches) {
                        $q->select('id')->from('match_players')->whereIn('match_id', $matches->pluck('id'));
                    })->exists();
                @endphp

                @if($weekHasScores)
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                        <button type="button" id="weekLockBtn" data-locked="true" onclick="toggleScoreLock()" style="padding: 8px 18px; border: none; border-radius: 8px; font-size: 0.95em; font-weight: 600; cursor: pointer; background: #ffc107; color: #333; white-space: nowrap;" title="Scores have been posted. Click to unlock editing.">
                            🔒 Locked
                        </button>
                        <span id="lockHint" style="font-size: 0.85em; color: #888;">Scores have been posted for this week. Click to unlock editing.</span>
                    </div>
                @endif

                <form action="{{ route('admin.leagues.scores.store', $league->id) }}" method="POST" id="weeklyScoreForm">
                    @csrf
                    <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                    @foreach($matches as $match)
                        @php
                            $info = $courseInfoMap[$match->id];
                            $holeRange = $info['holeRange'];
                            $courseHoles = $info['holes'];
                            $allCourseHoles = $info['allHoles'];

                            if ($match->home_team_id) {
                                $homePlayers = $match->matchPlayers->where('team_id', $match->home_team_id);
                                $awayPlayers = $match->matchPlayers->where('team_id', $match->away_team_id);
                            } else {
                                $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                                $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                            }

                            $homeTeamName = $match->homeTeam->name ?? null;
                            $awayTeamName = $match->awayTeam->name ?? null;

                            // Resolve team names from players when home_team_id/away_team_id are null
                            if (!$homeTeamName) {
                                $firstHome = $homePlayers->first();
                                $homeTeamName = $firstHome ? ($playerTeamNames[$firstHome->player_id] ?? 'Home Side') : 'Home Side';
                            }
                            if (!$awayTeamName) {
                                $firstAway = $awayPlayers->first();
                                $awayTeamName = $firstAway ? ($playerTeamNames[$firstAway->player_id] ?? 'Away Side') : 'Away Side';
                            }

                            $jsMatchData[$match->id] = [
                                'scoringType' => $match->scoring_type,
                                'scoreMode' => ($match->scoring_type === 'scramble') ? 'gross' : ($match->score_mode ?? 'net'),
                                'homeTeam' => $homeTeamName,
                                'awayTeam' => $awayTeamName,
                                'holeStart' => $holeRange[0],
                                'holeEnd' => $holeRange[1],
                                'homePlayers' => [],
                                'awayPlayers' => [],
                                'playerStrokes' => [],
                            ];
                        @endphp

                        <div class="match-section">
                            <div class="match-header">
                                <div>
                                    <div class="match-header-title">{{ $awayTeamName }} vs {{ $homeTeamName }}</div>
                                    <div class="match-header-info">
                                        {{ $match->match_date->format('M d, Y') }}{{ $match->tee_time ? ' at ' . \Carbon\Carbon::parse($match->tee_time)->format('g:i A') : '' }}
                                        | {{ $match->golfCourse->name }} ({{ $match->teebox }})
                                        | Holes {{ $holeRange[0] }}-{{ $holeRange[1] }}
                                        | {{ $scoringTypes[$match->scoring_type] ?? ucfirst(str_replace('_', ' ', $match->scoring_type)) }} ({{ ucfirst($match->scoring_type === 'scramble' ? 'gross' : ($match->score_mode ?? 'net')) }})
                                    </div>
                                </div>
                                <span class="status-badge status-{{ $match->status }}">{{ ucfirst(str_replace('_', ' ', $match->status)) }}</span>
                            </div>

                            <div class="scorecard-wrap">
                                @if($match->matchPlayers->isEmpty())
                                    <div style="text-align: center; padding: 20px; color: #888;">
                                        No players assigned to this match.
                                        <a href="{{ route('admin.matches.assignPlayers', $match->id) }}" style="color: var(--primary-color); font-weight: 600;">Assign Players</a>
                                    </div>
                                @else
                                    <table>
                                        <thead>
                                            <tr>
                                                <th style="min-width: 160px;">Player</th>
                                                <th style="width: 70px;">HCP</th>
                                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                    <th>{{ $hole }}</th>
                                                @endfor
                                                <th>Tot</th>
                                            </tr>
                                            <tr class="par-row">
                                                <td style="text-align: left;"><strong>Par</strong></td>
                                                <td>-</td>
                                                @foreach($courseHoles as $holeInfo)
                                                    <td>{{ $holeInfo->par }}</td>
                                                @endforeach
                                                <td>{{ $courseHoles->sum('par') }}</td>
                                            </tr>
                                            <tr style="background: #fff8e1; font-size: 0.8em; color: #7b6b00;">
                                                <td style="text-align: left;"><strong>Hdcp</strong></td>
                                                <td>-</td>
                                                @foreach($courseHoles as $holeInfo)
                                                    <td>{{ $holeInfo->handicap ?? '-' }}</td>
                                                @endforeach
                                                <td>-</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                // Pre-compute JS data and stroke allocations for all players
                                                $allPlayerStrokes = [];
                                                foreach ($homePlayers as $mp) {
                                                    $ch = isset($playerHandicaps[$mp->id]) ? (int) $playerHandicaps[$mp->id]['ch18'] : 0;
                                                    $strokesOnHole = [];
                                                    foreach ($allCourseHoles as $h) { $strokesOnHole[$h->hole_number] = 0; }
                                                    $sorted = $allCourseHoles->sortBy('handicap')->pluck('hole_number')->values();
                                                    $remaining = max(0, $ch);
                                                    while ($remaining > 0) { foreach ($sorted as $hn) { if ($remaining <= 0) break; $strokesOnHole[$hn]++; $remaining--; } }
                                                    $allPlayerStrokes[$mp->id] = $strokesOnHole;
                                                    $jsMatchData[$match->id]['homePlayers'][] = $mp->id;
                                                    $jsMatchData[$match->id]['playerStrokes'][$mp->id] = $strokesOnHole;
                                                }
                                                foreach ($awayPlayers as $mp) {
                                                    $ch = isset($playerHandicaps[$mp->id]) ? (int) $playerHandicaps[$mp->id]['ch18'] : 0;
                                                    $strokesOnHole = [];
                                                    foreach ($allCourseHoles as $h) { $strokesOnHole[$h->hole_number] = 0; }
                                                    $sorted = $allCourseHoles->sortBy('handicap')->pluck('hole_number')->values();
                                                    $remaining = max(0, $ch);
                                                    while ($remaining > 0) { foreach ($sorted as $hn) { if ($remaining <= 0) break; $strokesOnHole[$hn]++; $remaining--; } }
                                                    $allPlayerStrokes[$mp->id] = $strokesOnHole;
                                                    $jsMatchData[$match->id]['awayPlayers'][] = $mp->id;
                                                    $jsMatchData[$match->id]['playerStrokes'][$mp->id] = $strokesOnHole;
                                                }

                                                // Build display order (away on top, home on bottom)
                                                $rideWithOpponent = $match->ride_with_opponent;
                                                $homePlayerIds = $homePlayers->pluck('id')->toArray();
                                                $awayPlayerIds = $awayPlayers->pluck('id')->toArray();
                                                if ($rideWithOpponent) {
                                                    $homeVals = $homePlayers->sortBy('position_in_pairing')->values();
                                                    $awayVals = $awayPlayers->sortBy('position_in_pairing')->values();
                                                    $displayPlayers = collect();
                                                    $pairingCount = max($homeVals->count(), $awayVals->count());
                                                    for ($i = 0; $i < $pairingCount; $i++) {
                                                        if (isset($awayVals[$i])) $displayPlayers->push($awayVals[$i]);
                                                        if (isset($homeVals[$i])) $displayPlayers->push($homeVals[$i]);
                                                    }
                                                } else {
                                                    $displayPlayers = $awayPlayers->values()->merge($homePlayers->values());
                                                }
                                            @endphp

                                            @foreach($displayPlayers as $dIdx => $mp)
                                                @php
                                                    $isHome = in_array($mp->id, $homePlayerIds);
                                                    $strokesOnHole = $allPlayerStrokes[$mp->id];
                                                @endphp

                                                {{-- Team header rows (only in standard mode) --}}
                                                @if(!$rideWithOpponent)
                                                    @if($dIdx === 0)
                                                        {{-- Away players come first --}}
                                                    @elseif($dIdx === count($awayPlayerIds))
                                                        {{-- Insert away team result row before home players --}}
                                                        @if($match->scoring_type !== 'individual_match_play')
                                                            <tr class="results-row">
                                                                <td style="text-align: left; font-weight: 700; background: #fce4ec; color: #c62828;">✈️ {{ $awayTeamName }}</td>
                                                                <td style="background: #f0f0f0;">-</td>
                                                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                                    <td class="result-cell" id="result-away-{{ $match->id }}-{{ $hole }}">-</td>
                                                                @endfor
                                                                <td id="result-away-total-{{ $match->id }}" style="font-weight: 700; background: #fce4ec; color: #c62828;">-</td>
                                                            </tr>
                                                        @endif
                                                    @endif
                                                @else
                                                    @if($dIdx > 0 && $dIdx % 2 === 0)
                                                        <tr><td colspan="{{ $holeRange[1] - $holeRange[0] + 4 }}" style="border: none; height: 3px; background: #e0e0e0;"></td></tr>
                                                    @endif
                                                @endif

                                                <tr class="stroke-dots-row">
                                                    <td></td><td></td>
                                                    @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                        <td>{{ str_repeat('●', $strokesOnHole[$hole] ?? 0) }}</td>
                                                    @endfor
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td class="player-name-cell" {!! $rideWithOpponent ? 'style="color: ' . ($isHome ? '#28a745' : '#dc3545') . ';"' : '' !!}>
                                                        {{ $mp->display_name }}
                                                        @if(isset($playerHandicaps[$mp->id]))
                                                            <span style="font-size: 0.75em; color: var(--secondary-color); font-weight: 500;">({{ $playerHandicaps[$mp->id]['ch18'] }} / {{ collect($strokesOnHole)->only(range($holeRange[0], $holeRange[1]))->sum() }})</span>
                                                        @endif
                                                    </td>
                                                    <td class="handicap-cell">HI: {{ number_format($mp->handicap_index, 1) }}<br>CH: {{ $mp->course_handicap }}</td>
                                                    @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                        @php
                                                            $existing = $mp->scores->where('hole_number', $hole)->first();
                                                        @endphp
                                                        <td>
                                                            <input type="number"
                                                                   name="scores[{{ $mp->id }}][{{ $hole }}]"
                                                                   min="1" max="15"
                                                                   value="{{ $existing?->strokes ?? '' }}"
                                                                   class="score-input"
                                                                   data-player="{{ $mp->id }}"
                                                                   required>
                                                        </td>
                                                    @endfor
                                                    <td class="total-cell"><span id="total-{{ $mp->id }}">0</span></td>
                                                </tr>
                                                @if($match->scoring_type === 'individual_match_play')
                                                    <tr class="results-row">
                                                        <td style="text-align: left; font-weight: 600; background: var(--primary-light); font-size: 0.8em; color: #555;">Match</td>
                                                        <td style="background: #f0f0f0;">-</td>
                                                        @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                            <td class="result-cell" id="result-ind-{{ $mp->id }}-{{ $hole }}">-</td>
                                                        @endfor
                                                        <td id="result-ind-total-{{ $mp->id }}" style="font-weight: 700; background: var(--primary-light);">-</td>
                                                    </tr>
                                                @endif
                                            @endforeach

                                            @if($match->scoring_type !== 'individual_match_play')
                                                @if(!$rideWithOpponent)
                                                    <tr class="results-row">
                                                        <td style="text-align: left; font-weight: 700; background: #e3f2fd; color: #1565c0;">🏠 {{ $homeTeamName }}</td>
                                                        <td style="background: #f0f0f0;">-</td>
                                                        @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                            <td class="result-cell" id="result-home-{{ $match->id }}-{{ $hole }}">-</td>
                                                        @endfor
                                                        <td id="result-home-total-{{ $match->id }}" style="font-weight: 700; background: #e3f2fd; color: #1565c0;">-</td>
                                                    </tr>
                                                @else
                                                    <tr class="results-row">
                                                        <td style="text-align: left; font-weight: 700; background: #fce4ec; color: #c62828;">✈️ {{ $awayTeamName }}</td>
                                                        <td style="background: #f0f0f0;">-</td>
                                                        @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                            <td class="result-cell" id="result-away-{{ $match->id }}-{{ $hole }}">-</td>
                                                        @endfor
                                                        <td id="result-away-total-{{ $match->id }}" style="font-weight: 700; background: #fce4ec; color: #c62828;">-</td>
                                                    </tr>
                                                    <tr class="results-row">
                                                        <td style="text-align: left; font-weight: 700; background: #e3f2fd; color: #1565c0;">🏠 {{ $homeTeamName }}</td>
                                                        <td style="background: #f0f0f0;">-</td>
                                                        @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                            <td class="result-cell" id="result-home-{{ $match->id }}-{{ $hole }}">-</td>
                                                        @endfor
                                                        <td id="result-home-total-{{ $match->id }}" style="font-weight: 700; background: #e3f2fd; color: #1565c0;">-</td>
                                                    </tr>
                                                @endif
                                            @endif
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($matches->flatMap->matchPlayers->isNotEmpty())
                        <div class="submit-section">
                            <button type="submit" class="btn btn-primary">💾 Save All Scores</button>
                            <a href="{{ route('admin.leagues.show', $league->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    @endif
                </form>

                {{-- Par 3 Winners Section --}}
                @if($par3Holes->isNotEmpty())
                    <div class="match-section" style="margin-top: 25px;">
                        <div class="match-header" style="background: #28a745;">
                            <div>
                                <div class="match-header-title">🎯 Par 3 Winners — Week {{ $selectedWeek }}</div>
                                <div class="match-header-info">Closest to the pin on par 3 holes</div>
                            </div>
                        </div>
                        <div style="padding: 20px;">
                            <form action="{{ route('admin.leagues.par3Winners.store', $league->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="week_number" value="{{ $selectedWeek }}">

                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                                    @foreach($par3Holes as $par3Hole)
                                        @php $existingWinner = $par3Winners[$par3Hole->hole_number] ?? null; @endphp
                                        <div style="background: var(--primary-light); padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                                            <div style="font-weight: 600; color: #333; margin-bottom: 10px;">
                                                Hole {{ $par3Hole->hole_number }}
                                                <span style="font-size: 0.85em; color: #666; font-weight: normal;">— {{ $par3Hole->yardage ?? '?' }} yds</span>
                                            </div>
                                            <div style="margin-bottom: 8px;">
                                                <select name="par3_winners[{{ $par3Hole->hole_number }}]" style="width: 100%; padding: 8px 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.9em; font-family: inherit;">
                                                    <option value="">— No winner —</option>
                                                    @foreach($weekPlayers as $player)
                                                        <option value="{{ $player->id }}" {{ $existingWinner && $existingWinner->player_id == $player->id ? 'selected' : '' }}>
                                                            {{ $player->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <input type="text" name="par3_distances[{{ $par3Hole->hole_number }}]" value="{{ $existingWinner->distance ?? '' }}" placeholder="Distance (e.g. 4'6&quot;)" style="width: 100%; padding: 8px 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.9em; font-family: inherit;">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="submit-section" style="margin-top: 15px;">
                                    <button type="submit" class="btn btn-primary" style="background: #28a745;">🎯 Save Par 3 Winners</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <p>No matches found for this week.</p>
                </div>
            @endif
        @endif
    </div>

    <script>
        // Match data for hole winner calculations
        const matchData = @json($jsMatchData ?? []);

        function updateTotal(playerId) {
            const inputs = document.querySelectorAll(`input[data-player="${playerId}"]`);
            let total = 0;
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            const el = document.getElementById(`total-${playerId}`);
            if (el) el.textContent = total;
        }

        function getPlayerScore(playerId, hole, scoreMode, playerStrokes) {
            const input = document.querySelector(`input[name="scores[${playerId}][${hole}]"]`);
            if (!input || input.value === '') return null;
            let score = parseInt(input.value);
            if (scoreMode === 'net' && playerStrokes && playerStrokes[hole]) {
                score -= playerStrokes[hole];
            }
            return score;
        }

        function calculateHoleWinners(matchId) {
            const config = matchData[matchId];
            if (!config) return;

            const { scoringType, scoreMode, holeStart, holeEnd, homePlayers, awayPlayers, playerStrokes } = config;
            let homePoints = 0, awayPoints = 0;

            for (let hole = holeStart; hole <= holeEnd; hole++) {
                const homeCell = document.getElementById(`result-home-${matchId}-${hole}`);
                const awayCell = document.getElementById(`result-away-${matchId}-${hole}`);
                if (!homeCell || !awayCell) continue;

                const homeScores = homePlayers.map(pid => getPlayerScore(pid, hole, scoreMode, playerStrokes[pid]));
                const awayScores = awayPlayers.map(pid => getPlayerScore(pid, hole, scoreMode, playerStrokes[pid]));

                const allHomeFilled = homeScores.every(s => s !== null);
                const allAwayFilled = awayScores.every(s => s !== null);

                if (!allHomeFilled || !allAwayFilled) {
                    homeCell.textContent = '-';
                    homeCell.className = 'result-cell';
                    awayCell.textContent = '-';
                    awayCell.className = 'result-cell';
                    continue;
                }

                let homeVal, awayVal;

                if (scoringType === 'best_ball_match_play') {
                    homeVal = Math.min(...homeScores);
                    awayVal = Math.min(...awayScores);
                } else if (scoringType === 'team_2ball_match_play') {
                    homeVal = homeScores.reduce((a, b) => a + b, 0);
                    awayVal = awayScores.reduce((a, b) => a + b, 0);
                } else if (scoringType === 'individual_match_play') {
                    let pairHomeWins = 0, pairAwayWins = 0;
                    const pairs = Math.min(homePlayers.length, awayPlayers.length);
                    for (let p = 0; p < pairs; p++) {
                        if (homeScores[p] < awayScores[p]) pairHomeWins++;
                        else if (awayScores[p] < homeScores[p]) pairAwayWins++;
                    }
                    homeVal = pairAwayWins;
                    awayVal = pairHomeWins;
                } else {
                    homeVal = Math.min(...homeScores);
                    awayVal = Math.min(...awayScores);
                }

                if (homeVal < awayVal) {
                    homeCell.textContent = '1';
                    homeCell.className = 'result-cell result-home';
                    awayCell.textContent = '0';
                    awayCell.className = 'result-cell result-away';
                    homePoints += 1;
                } else if (awayVal < homeVal) {
                    homeCell.textContent = '0';
                    homeCell.className = 'result-cell result-away';
                    awayCell.textContent = '1';
                    awayCell.className = 'result-cell result-home';
                    awayPoints += 1;
                } else {
                    homeCell.innerHTML = '½';
                    homeCell.className = 'result-cell result-tie';
                    awayCell.innerHTML = '½';
                    awayCell.className = 'result-cell result-tie';
                    homePoints += 0.5;
                    awayPoints += 0.5;
                }
            }

            const homeTotalCell = document.getElementById(`result-home-total-${matchId}`);
            const awayTotalCell = document.getElementById(`result-away-total-${matchId}`);
            if (homeTotalCell && awayTotalCell) {
                if (homePoints > 0 || awayPoints > 0) {
                    homeTotalCell.textContent = homePoints % 1 === 0 ? homePoints : homePoints.toFixed(1);
                    awayTotalCell.textContent = awayPoints % 1 === 0 ? awayPoints : awayPoints.toFixed(1);
                } else {
                    homeTotalCell.textContent = '-';
                    awayTotalCell.textContent = '-';
                }
            }
        }

        function calculateIndividualResults(matchId) {
            const config = matchData[matchId];
            if (!config || config.scoringType !== 'individual_match_play') return;

            const { scoreMode, holeStart, holeEnd, homePlayers, awayPlayers, playerStrokes } = config;
            const pairs = Math.min(homePlayers.length, awayPlayers.length);

            for (let p = 0; p < pairs; p++) {
                const hpId = homePlayers[p];
                const apId = awayPlayers[p];
                let hPoints = 0, aPoints = 0;

                for (let hole = holeStart; hole <= holeEnd; hole++) {
                    const hScore = getPlayerScore(hpId, hole, scoreMode, playerStrokes[hpId]);
                    const aScore = getPlayerScore(apId, hole, scoreMode, playerStrokes[apId]);

                    const hCell = document.getElementById(`result-ind-${hpId}-${hole}`);
                    const aCell = document.getElementById(`result-ind-${apId}-${hole}`);

                    if (hScore === null || aScore === null) {
                        if (hCell) { hCell.textContent = '-'; hCell.className = 'result-cell'; }
                        if (aCell) { aCell.textContent = '-'; aCell.className = 'result-cell'; }
                        continue;
                    }

                    if (hScore < aScore) {
                        if (hCell) { hCell.textContent = '1'; hCell.className = 'result-cell result-home'; }
                        if (aCell) { aCell.textContent = '0'; aCell.className = 'result-cell result-away'; }
                        hPoints += 1;
                    } else if (aScore < hScore) {
                        if (hCell) { hCell.textContent = '0'; hCell.className = 'result-cell result-away'; }
                        if (aCell) { aCell.textContent = '1'; aCell.className = 'result-cell result-home'; }
                        aPoints += 1;
                    } else {
                        if (hCell) { hCell.innerHTML = '½'; hCell.className = 'result-cell result-tie'; }
                        if (aCell) { aCell.innerHTML = '½'; aCell.className = 'result-cell result-tie'; }
                        hPoints += 0.5;
                        aPoints += 0.5;
                    }
                }

                const hTotal = document.getElementById(`result-ind-total-${hpId}`);
                const aTotal = document.getElementById(`result-ind-total-${apId}`);
                if (hTotal) hTotal.textContent = hPoints % 1 === 0 ? hPoints : hPoints.toFixed(1);
                if (aTotal) aTotal.textContent = aPoints % 1 === 0 ? aPoints : aPoints.toFixed(1);
            }
        }

        function updateAllHoleWinners() {
            Object.keys(matchData).forEach(matchId => {
                const id = parseInt(matchId);
                calculateHoleWinners(id);
                calculateIndividualResults(id);
            });
        }

        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', function() {
                updateTotal(this.dataset.player);
                updateAllHoleWinners();
            });
        });

        // Lock/unlock score editing
        function toggleScoreLock() {
            var btn = document.getElementById('weekLockBtn');
            if (!btn) return;
            var isLocked = btn.dataset.locked === 'true';
            if (isLocked) {
                btn.dataset.locked = 'false';
                btn.innerHTML = '🔓 Unlocked';
                btn.style.background = '#28a745';
                btn.style.color = 'white';
                btn.title = 'Week is unlocked for editing. Click to lock.';
                document.getElementById('lockHint').textContent = 'Editing is enabled. Click to lock.';
                setScoresEditable(true);
            } else {
                btn.dataset.locked = 'true';
                btn.innerHTML = '🔒 Locked';
                btn.style.background = '#ffc107';
                btn.style.color = '#333';
                btn.title = 'Scores have been posted. Click to unlock editing.';
                document.getElementById('lockHint').textContent = 'Scores have been posted for this week. Click to unlock editing.';
                setScoresEditable(false);
            }
        }

        function setScoresEditable(editable) {
            document.querySelectorAll('.score-input').forEach(function(inp) {
                inp.disabled = !editable;
                inp.style.opacity = editable ? '1' : '0.5';
            });

            // Par 3 winner selects and distance inputs
            document.querySelectorAll('select[name^="par3_winners"], input[name^="par3_distances"]').forEach(function(el) {
                el.disabled = !editable;
                el.style.opacity = editable ? '1' : '0.5';
            });

            // Submit buttons
            document.querySelectorAll('.submit-section button[type="submit"]').forEach(function(btn) {
                btn.disabled = !editable;
                btn.style.opacity = editable ? '1' : '0.6';
            });
        }

        // Initialize totals and hole winners on page load
        document.addEventListener('DOMContentLoaded', function() {
            const playerIds = [...new Set([...document.querySelectorAll('.score-input')].map(i => i.dataset.player))];
            playerIds.forEach(id => updateTotal(id));
            updateAllHoleWinners();

            // Lock if scores have been posted
            var lockBtn = document.getElementById('weekLockBtn');
            if (lockBtn && lockBtn.dataset.locked === 'true') {
                setScoresEditable(false);
            }
        });
    </script>
</body>
</html>

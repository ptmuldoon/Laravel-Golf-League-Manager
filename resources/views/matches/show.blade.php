<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Details</title>
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
            max-width: 1600px;
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
        h1 {
            color: var(--primary-color);
            font-size: 2.5em;
            margin-bottom: 15px;
            text-align: center;
        }
        .match-info {
            text-align: center;
            color: #666;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        .result-box {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .result-text {
            font-size: 1.5em;
            font-weight: bold;
            color: #155724;
        }
        .result-score {
            font-size: 2em;
            font-weight: bold;
            color: #155724;
            margin-top: 10px;
        }
        .scorecard-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .section-title {
            font-size: 1.5em;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }
        th {
            background: var(--primary-color);
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #5568d3;
        }
        td {
            padding: 10px 8px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        .player-name {
            text-align: left;
            font-weight: 600;
            color: #333;
            background: var(--primary-light);
        }
        .team-header {
            background: var(--secondary-color);
            color: white;
            font-weight: 600;
        }
        .par-row {
            background: #f0f0f0;
            font-weight: 600;
        }
        .total-cell {
            background: var(--primary-light);
            font-weight: 600;
            color: var(--primary-color);
        }
        .handicap-cell {
            font-size: 0.85em;
            color: #666;
        }
        .stroke-dots-row td {
            padding: 1px 8px;
            font-size: 0.7em;
            color: var(--secondary-color);
            border: none;
            line-height: 1;
        }
        .hole-won {
            background: #d4edda;
            font-weight: 600;
        }
        .hole-lost {
            background: #f8d7da;
        }
        .hole-tied {
            background: #fff3cd;
        }
        .results-row td {
            padding: 8px 6px;
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
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
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
        .actions {
            text-align: center;
            margin-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .status-scheduled {
            background: #cce5ff;
            color: #004085;
        }
        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .success-message {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        @auth
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.leagues.scheduleOverview', $match->league_id) }}" class="back-link">← Back to Schedule</a>
            @else
                <a href="{{ route('home') }}" class="back-link">← Back to Home</a>
            @endif
        @else
            <a href="{{ route('home') }}" class="back-link">← Back to Home</a>
        @endauth

        @if(session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif

        <div class="header">
            <h1>{{ $homeTeamName }} vs {{ $awayTeamName }}</h1>
            <div class="match-info">
                📅 {{ $match->match_date->format('l, F d, Y') }}{{ $match->tee_time ? ' at ' . \Carbon\Carbon::parse($match->tee_time)->format('g:i A') : '' }}<br>
                ⛳ {{ $match->golfCourse->name }} - {{ $match->teebox }} Tees<br>
                🏌️ {{ $match->holes === 'back_9' ? 'Back 9' : 'Front 9' }} |
                📋 {{ $scoringTypes[$match->scoring_type] ?? ucfirst(str_replace('_', ' ', $match->scoring_type)) }} |
                🎯 {{ ($match->scoring_type === 'scramble' || $match->score_mode === 'gross') ? 'Gross Scoring' : 'Net Scoring' }}<br>
                Week {{ $match->week_number }}
            </div>
            <div style="text-align: center;">
                <span class="status-badge status-{{ $match->status }}">
                    {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                </span>
            </div>

            @if($match->status === 'completed' && $match->result)
                @php
                    $hHome = $match->result->holes_won_home + ($match->result->holes_tied * 0.5);
                    $hAway = $match->result->holes_won_away + ($match->result->holes_tied * 0.5);
                    $fH = $hHome == (int)$hHome ? (int)$hHome : number_format($hHome, 1);
                    $fA = $hAway == (int)$hAway ? (int)$hAway : number_format($hAway, 1);
                @endphp
                <div class="result-box">
                    @if($match->result->winning_team_id)
                        <div class="result-text">🏆 {{ $match->result->winningTeam->name }} Wins!</div>
                        <div class="result-score">
                            {{ $fH }} - {{ $fA }}
                        </div>
                    @else
                        <div class="result-text">🤝 Match Tied</div>
                        <div class="result-score">
                            {{ $fH }} - {{ $fA }}
                        </div>
                    @endif
                    <div style="margin-top: 10px; font-size: 1.1em;">
                        Points: {{ $homeTeamName }} {{ $match->result->team_points_home }} - {{ $match->result->team_points_away }} {{ $awayTeamName }}
                    </div>
                </div>
            @endif

            @auth
                @if(auth()->user()->isAdmin())
                    <div class="actions">
                        @if($match->status === 'scheduled')
                            <a href="{{ route('admin.matches.assignPlayers', $match->id) }}" class="btn btn-primary">
                                Assign Players
                            </a>
                        @elseif($match->status === 'in_progress')
                            <a href="{{ route('admin.matches.scoreEntry', $match->id) }}" class="btn btn-primary">
                                Enter/Edit Scores
                            </a>
                        @endif
                    </div>
                @endif
            @endauth
        </div>

        @if($match->matchPlayers->isNotEmpty())
            <div class="scorecard-container">
                <h2 class="section-title">📊 Scorecard</h2>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 200px;">Player</th>
                            <th style="width: 80px;">Handicap</th>
                            @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                <th>{{ $hole }}</th>
                            @endfor
                            <th>Total</th>
                        </tr>
                        <tr style="background: #e8f0fe; font-size: 0.85em; color: #555;">
                            <td><strong>{{ $match->teebox }} Tees</strong></td>
                            <td>Yds</td>
                            @foreach($courseInfo as $holeInfo)
                                <td>{{ $holeInfo->yardage ?? '-' }}</td>
                            @endforeach
                            <td>{{ $courseInfo->sum('yardage') ?: '-' }}</td>
                        </tr>
                        <tr class="par-row">
                            <td><strong>Par</strong></td>
                            <td>-</td>
                            @foreach($courseInfo as $holeInfo)
                                <td>{{ $holeInfo->par }}</td>
                            @endforeach
                            <td>{{ $courseInfo->sum('par') }}</td>
                        </tr>
                        <tr style="background: #fff8e1; font-size: 0.85em; color: #7b6b00;">
                            <td><strong>Hdcp</strong></td>
                            <td>-</td>
                            @foreach($courseInfo as $holeInfo)
                                <td>{{ $holeInfo->handicap ?? '-' }}</td>
                            @endforeach
                            <td>-</td>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rideWithOpponent = $match->ride_with_opponent;
                            $homePlayerIds = $homePlayers->pluck('id')->toArray();
                            if ($rideWithOpponent) {
                                $homeVals = $homePlayers->sortBy('position_in_pairing')->values();
                                $awayVals = $awayPlayers->sortBy('position_in_pairing')->values();
                                $pairingCount = max($homeVals->count(), $awayVals->count());
                                $displayPlayers = collect();
                                for ($i = 0; $i < $pairingCount; $i++) {
                                    if (isset($awayVals[$i])) $displayPlayers->push($awayVals[$i]);
                                    if (isset($homeVals[$i])) $displayPlayers->push($homeVals[$i]);
                                }
                            } else {
                                $displayPlayers = $homePlayers->values()->merge($awayPlayers->values());
                            }
                        @endphp

                        @if($rideWithOpponent)
                            <tr class="team-header">
                                <td colspan="11">{{ $homeTeamName }} vs {{ $awayTeamName }}</td>
                            </tr>
                        @endif

                        @foreach($displayPlayers as $dIdx => $matchPlayer)
                            @php
                                $isHome = in_array($matchPlayer->id, $homePlayerIds);
                                $ch = isset($playerHandicaps[$matchPlayer->id]) ? (int) $playerHandicaps[$matchPlayer->id]['ch18'] : 0;
                                $strokesOnHole = [];
                                foreach ($allCourseInfo as $h) { $strokesOnHole[$h->hole_number] = 0; }
                                $sorted = $allCourseInfo->sortBy('handicap')->pluck('hole_number')->values();
                                $remaining = max(0, (int)$ch);
                                while ($remaining > 0) {
                                    foreach ($sorted as $hn) {
                                        if ($remaining <= 0) break;
                                        $strokesOnHole[$hn]++;
                                        $remaining--;
                                    }
                                }
                            @endphp

                            {{-- Team headers and result rows (standard mode only) --}}
                            @if(!$rideWithOpponent)
                                @if($dIdx === 0)
                                    <tr class="team-header">
                                        <td colspan="11">🏠 {{ $homeTeamName }}</td>
                                    </tr>
                                @elseif($dIdx === count($homePlayerIds))
                                    @if($match->scoring_type !== 'individual_match_play' && !empty($holeResults))
                                        <tr class="results-row">
                                            <td style="text-align: left; font-weight: 700; background: #e3f2fd; color: #1565c0;">🏠 {{ $homeTeamName }}</td>
                                            <td style="background: #f0f0f0;">-</td>
                                            @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                @php $r = $holeResults[$hole] ?? 'none'; @endphp
                                                <td class="{{ $r === 'home' ? 'result-home' : ($r === 'away' ? 'result-away' : 'result-tie') }}">
                                                    @if($r === 'home') 1 @elseif($r === 'away') 0 @elseif($r === 'tie') ½ @else - @endif
                                                </td>
                                            @endfor
                                            <td style="font-weight: 700; background: #e3f2fd; color: #1565c0;">{{ $homeWinsTotal + ($tiesTotal * 0.5) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="team-header">
                                        <td colspan="11">✈️ {{ $awayTeamName }}</td>
                                    </tr>
                                @endif
                            @else
                                @if($dIdx > 0 && $dIdx % 2 === 0)
                                    <tr><td colspan="11" style="border: none; height: 3px; background: #e0e0e0;"></td></tr>
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
                                <td class="player-name" {!! $rideWithOpponent ? 'style="color: ' . ($isHome ? '#28a745' : '#dc3545') . ';"' : '' !!}>
                                    {{ $matchPlayer->display_name }}
                                    @if(isset($playerHandicaps[$matchPlayer->id]))
                                        <span style="font-size: 0.75em; color: var(--secondary-color); font-weight: 500;">({{ $playerHandicaps[$matchPlayer->id]['ch18'] }} / {{ collect($strokesOnHole)->only(range($holeRange[0], $holeRange[1]))->sum() }})</span>
                                    @endif
                                </td>
                                <td class="handicap-cell">
                                    HI: {{ number_format($matchPlayer->handicap_index, 1) }}<br>
                                    CH: {{ $playerHandicaps[$matchPlayer->id]['ch18'] ?? $matchPlayer->course_handicap }}
                                </td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    @php
                                        $score = $matchPlayer->scores->where('hole_number', $hole)->first();
                                    @endphp
                                    <td>{{ $score?->strokes ?? '-' }}</td>
                                @endfor
                                <td class="total-cell">{{ $matchPlayer->totalStrokes() ?: '-' }}</td>
                            </tr>
                            @if($match->scoring_type === 'individual_match_play' && isset($individualResults[$matchPlayer->id]))
                                <tr class="results-row">
                                    <td style="text-align: left; font-weight: 600; background: var(--primary-light); font-size: 0.8em; color: #555;">Match</td>
                                    <td style="background: #f0f0f0;">-</td>
                                    @php $indWins = 0; $indLosses = 0; $indTies = 0; @endphp
                                    @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                        @php
                                            $r = $individualResults[$matchPlayer->id][$hole] ?? null;
                                            if ($r === 'won') $indWins++;
                                            elseif ($r === 'lost') $indLosses++;
                                            elseif ($r === 'tie') $indTies++;
                                        @endphp
                                        <td class="{{ $r === 'won' ? 'result-home' : ($r === 'lost' ? 'result-away' : 'result-tie') }}">
                                            {{ $r === 'won' ? '1' : ($r === 'lost' ? '0' : '½') }}
                                        </td>
                                    @endfor
                                    <td style="font-weight: 700; background: var(--primary-light);">{{ $indWins + ($indTies * 0.5) }}</td>
                                </tr>
                            @endif
                        @endforeach

                        {{-- Team result rows at the end --}}
                        @if($match->scoring_type !== 'individual_match_play' && !empty($holeResults))
                            @if($rideWithOpponent)
                                <tr class="results-row">
                                    <td style="text-align: left; font-weight: 700; background: #e3f2fd; color: #1565c0;">🏠 {{ $homeTeamName }}</td>
                                    <td style="background: #f0f0f0;">-</td>
                                    @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                        @php $r = $holeResults[$hole] ?? 'none'; @endphp
                                        <td class="{{ $r === 'home' ? 'result-home' : ($r === 'away' ? 'result-away' : 'result-tie') }}">
                                            @if($r === 'home') 1 @elseif($r === 'away') 0 @elseif($r === 'tie') ½ @else - @endif
                                        </td>
                                    @endfor
                                    <td style="font-weight: 700; background: #e3f2fd; color: #1565c0;">{{ $homeWinsTotal + ($tiesTotal * 0.5) }}</td>
                                </tr>
                            @endif
                            <tr class="results-row">
                                <td style="text-align: left; font-weight: 700; background: #fce4ec; color: #c62828;">✈️ {{ $awayTeamName }}</td>
                                <td style="background: #f0f0f0;">-</td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    @php $r = $holeResults[$hole] ?? 'none'; @endphp
                                    <td class="{{ $r === 'away' ? 'result-home' : ($r === 'home' ? 'result-away' : 'result-tie') }}">
                                        @if($r === 'away') 1 @elseif($r === 'home') 0 @elseif($r === 'tie') ½ @else - @endif
                                    </td>
                                @endfor
                                <td style="font-weight: 700; background: #fce4ec; color: #c62828;">{{ $awayWinsTotal + ($tiesTotal * 0.5) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</body>
</html>

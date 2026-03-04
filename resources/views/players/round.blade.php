<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scorecard - {{ $round->golfCourse->name }}</title>
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
            max-width: 1400px;
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
        .scorecard-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .round-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .info-item {
            padding: 15px;
            background: var(--primary-light);
            border-radius: 8px;
        }
        .info-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--primary-color);
        }
        .teebox {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 600;
        }
        .teebox-Black { background: #333; color: white; }
        .teebox-Blue { background: #4169E1; color: white; }
        .teebox-White { background: #f0f0f0; color: #333; }
        .teebox-Red { background: #DC143C; color: white; }
        .scorecard-table {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            min-width: 500px;
            border-collapse: collapse;
        }
        thead {
            background: var(--primary-color);
            color: white;
        }
        th {
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
        }
        td {
            padding: 15px 10px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }
        .hole-col {
            font-weight: bold;
            color: var(--primary-color);
        }
        .birdie {
            background: #d4edda;
            font-weight: bold;
            color: #155724;
        }
        .eagle {
            background: #bee5eb;
            font-weight: bold;
            color: #0c5460;
        }
        .bogey {
            background: #fff3cd;
            font-weight: bold;
            color: #856404;
        }
        .double-bogey {
            background: #f8d7da;
            font-weight: bold;
            color: #721c24;
        }
        .par-score {
            font-weight: bold;
            color: #333;
        }
        .totals-row {
            background: var(--primary-color);
            color: white;
            font-weight: bold;
        }
        .subtotal-row {
            background: #f0f0f0;
            font-weight: 600;
        }
        .summary {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background: var(--primary-light);
            border-radius: 8px;
        }
        .stat-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-color);
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .scorecard-header {
                padding: 15px;
            }
            h1 {
                font-size: 1.3em;
            }
            .round-info {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 10px;
            }
            .info-item {
                padding: 10px;
            }
            .info-value {
                font-size: 1em;
            }
            th, td {
                padding: 8px 4px;
                font-size: 0.85em;
            }
            .summary {
                padding: 15px;
            }
            .summary-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            .stat-box {
                padding: 10px;
            }
            .stat-value {
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('players.show', $player->id) }}" class="back-link">← Back to {{ $player->first_name }}'s Rounds</a>

        <div class="scorecard-header">
            <h1>⛳ {{ $round->golfCourse->name }}</h1>
            <div class="round-info">
                <div class="info-item">
                    <div class="info-label">Player</div>
                    <div class="info-value">
                        {{ $player->first_name }} {{ $player->last_name }}
                        @if($courseHandicap18 !== null)
                            <span style="font-size: 0.75em; color: var(--secondary-color); font-weight: 500;">({{ $courseHandicap18 }} / {{ $courseHandicap9 }})</span>
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Played</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($round->played_at)->format('M d, Y') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teebox</div>
                    <div class="info-value">
                        <span class="teebox teebox-{{ $round->teebox }}">{{ $round->teebox }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Holes Played</div>
                    <div class="info-value">{{ $round->holes_played }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Score</div>
                    <div class="info-value">{{ $scorecard->sum('strokes') }}</div>
                </div>
            </div>
        </div>

        <div class="scorecard-table">
            @php
                $frontNinePar = 0;
                $backNinePar = 0;
                $frontNineScore = 0;
                $backNineScore = 0;

                // Determine which holes were played for 9-hole rounds
                $holeNumbers = $scorecard->pluck('hole_number')->toArray();
                $isFrontNine = $round->holes_played == 9 ? max($holeNumbers) <= 9 : true;
                $isBackNine = $round->holes_played == 9 ? min($holeNumbers) >= 10 : true;
            @endphp

            @if($isFrontNine && ($round->holes_played == 18 || min($holeNumbers) <= 9))
            <table>
                <thead>
                    <tr>
                        <th>Hole</th>
                        @for($i = 1; $i <= 9; $i++)
                            <th>{{ $i }}</th>
                        @endfor
                        <th>OUT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="hole-col">Par</td>
                        @for($i = 1; $i <= 9; $i++)
                            @php
                                $hole = $scorecard->firstWhere('hole_number', $i);
                                $frontNinePar += $hole['par'] ?? 0;
                            @endphp
                            <td>{{ $hole['par'] ?? '-' }}</td>
                        @endfor
                        <td><strong>{{ $frontNinePar }}</strong></td>
                    </tr>
                    <tr>
                        <td class="hole-col">Score</td>
                        @for($i = 1; $i <= 9; $i++)
                            @php
                                $hole = $scorecard->firstWhere('hole_number', $i);
                                $frontNineScore += $hole['strokes'] ?? 0;
                                $diff = $hole['score'] ?? 0;
                                $class = '';
                                if ($diff <= -2) $class = 'eagle';
                                elseif ($diff == -1) $class = 'birdie';
                                elseif ($diff == 0) $class = 'par-score';
                                elseif ($diff == 1) $class = 'bogey';
                                elseif ($diff >= 2) $class = 'double-bogey';
                            @endphp
                            <td class="{{ $class }}">{{ $hole['strokes'] ?? '-' }}</td>
                        @endfor
                        <td class="subtotal-row">{{ $frontNineScore }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            @if($isBackNine && ($round->holes_played == 18 || min($holeNumbers) >= 10))
            <table style="margin-top: {{ $round->holes_played == 18 ? '20px' : '0' }};">
                <thead>
                    <tr>
                        <th>Hole</th>
                        @for($i = 10; $i <= 18; $i++)
                            <th>{{ $i }}</th>
                        @endfor
                        <th>IN</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="hole-col">Par</td>
                        @for($i = 10; $i <= 18; $i++)
                            @php
                                $hole = $scorecard->firstWhere('hole_number', $i);
                                $backNinePar += $hole['par'] ?? 0;
                            @endphp
                            <td>{{ $hole['par'] ?? '-' }}</td>
                        @endfor
                        <td><strong>{{ $backNinePar }}</strong></td>
                    </tr>
                    <tr>
                        <td class="hole-col">Score</td>
                        @for($i = 10; $i <= 18; $i++)
                            @php
                                $hole = $scorecard->firstWhere('hole_number', $i);
                                $backNineScore += $hole['strokes'] ?? 0;
                                $diff = $hole['score'] ?? 0;
                                $class = '';
                                if ($diff <= -2) $class = 'eagle';
                                elseif ($diff == -1) $class = 'birdie';
                                elseif ($diff == 0) $class = 'par-score';
                                elseif ($diff == 1) $class = 'bogey';
                                elseif ($diff >= 2) $class = 'double-bogey';
                            @endphp
                            <td class="{{ $class }}">{{ $hole['strokes'] ?? '-' }}</td>
                        @endfor
                        <td class="subtotal-row">{{ $backNineScore }}</td>
                    </tr>
                    @if($round->holes_played == 18)
                    <tr class="totals-row">
                        <td>TOTAL</td>
                        @for($i = 10; $i <= 18; $i++)
                            <td>-</td>
                        @endfor
                        <td>{{ $scorecard->sum('strokes') }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
            @endif
        </div>

        <div class="summary">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Round Summary</h2>
            <div class="summary-stats">
                <div class="stat-box">
                    <div class="stat-label">Total Score</div>
                    <div class="stat-value">{{ $scorecard->sum('strokes') }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Par</div>
                    <div class="stat-value">{{ $frontNinePar + $backNinePar }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Score vs Par</div>
                    <div class="stat-value">
                        @php
                            $totalPar = $frontNinePar + $backNinePar;
                            $totalScore = $scorecard->sum('strokes');
                            $diff = $totalScore - $totalPar;
                        @endphp
                        {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Birdies</div>
                    <div class="stat-value">{{ $scorecard->where('score', -1)->count() }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Pars</div>
                    <div class="stat-value">{{ $scorecard->where('score', 0)->count() }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Bogeys</div>
                    <div class="stat-value">{{ $scorecard->where('score', 1)->count() }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

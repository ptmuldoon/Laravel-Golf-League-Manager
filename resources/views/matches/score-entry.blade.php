<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Scores</title>
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
        .scorecard-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
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
        input[type="number"] {
            width: 50px;
            padding: 8px 4px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            text-align: center;
            font-size: 1em;
            font-weight: 600;
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
        .submit-section {
            margin-top: 20px;
            text-align: center;
        }
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #0c5460;
            font-size: 0.9em;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.scheduleOverview', $match->league_id) }}" class="back-link">← Back to Schedule</a>

        <div class="header">
            <h1>📋 Enter Match Scores</h1>
            <div class="match-info">
                {{ $homeTeamName }} vs {{ $awayTeamName }}<br>
                📅 {{ $match->match_date->format('M d, Y') }}{{ $match->tee_time ? ' at ' . \Carbon\Carbon::parse($match->tee_time)->format('g:i A') : '' }} | ⛳ {{ $match->golfCourse->name }} ({{ $match->teebox }})
            </div>
        </div>

        <div class="info-box">
            💡 Enter gross scores for each player on each hole ({{ $holeRange[0] }}-{{ $holeRange[1] }}).
            Adjusted gross, net scores, and match results will be calculated automatically.<br>
            @php $effectiveScoreMode = ($match->scoring_type === 'scramble') ? 'gross' : ($match->score_mode ?? 'net'); @endphp
            🎯 <strong>Score Mode: {{ $effectiveScoreMode === 'gross' ? 'Gross' : 'Net' }}</strong> — Hole winners determined by {{ $effectiveScoreMode === 'gross' ? 'gross (raw) scores' : 'net scores (after handicap strokes)' }}.
        </div>

        <form action="{{ route('admin.matches.storeScores', $match->id) }}" method="POST" id="scoreForm">
            @csrf

            <div class="scorecard-container">
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
                        <!-- Home Team -->
                        <tr class="team-header">
                            <td colspan="{{ 11 }}">🏠 {{ $homeTeamName }}</td>
                        </tr>
                        @foreach($homePlayers as $matchPlayer)
                            @php
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
                            <tr class="stroke-dots-row">
                                <td></td><td></td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td>{{ str_repeat('●', $strokesOnHole[$hole] ?? 0) }}</td>
                                @endfor
                                <td></td>
                            </tr>
                            <tr>
                                <td class="player-name">
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
                                        $existingScore = $matchPlayer->scores->where('hole_number', $hole)->first();
                                    @endphp
                                    <td>
                                        <input type="number"
                                               name="scores[{{ $matchPlayer->id }}][{{ $hole }}]"
                                               min="1"
                                               max="15"
                                               value="{{ $existingScore?->strokes ?? '' }}"
                                               class="score-input"
                                               data-player="{{ $matchPlayer->id }}"
                                               required>
                                    </td>
                                @endfor
                                <td class="total-cell">
                                    <span id="total-{{ $matchPlayer->id }}">0</span>
                                </td>
                            </tr>
                        @endforeach

                        <!-- Away Team -->
                        <tr class="team-header">
                            <td colspan="{{ 11 }}">✈️ {{ $awayTeamName }}</td>
                        </tr>
                        @foreach($awayPlayers as $matchPlayer)
                            @php
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
                            <tr class="stroke-dots-row">
                                <td></td><td></td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td>{{ str_repeat('●', $strokesOnHole[$hole] ?? 0) }}</td>
                                @endfor
                                <td></td>
                            </tr>
                            <tr>
                                <td class="player-name">
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
                                        $existingScore = $matchPlayer->scores->where('hole_number', $hole)->first();
                                    @endphp
                                    <td>
                                        <input type="number"
                                               name="scores[{{ $matchPlayer->id }}][{{ $hole }}]"
                                               min="1"
                                               max="15"
                                               value="{{ $existingScore?->strokes ?? '' }}"
                                               class="score-input"
                                               data-player="{{ $matchPlayer->id }}"
                                               required>
                                    </td>
                                @endfor
                                <td class="total-cell">
                                    <span id="total-{{ $matchPlayer->id }}">0</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="submit-section">
                <button type="submit" class="btn btn-primary">💾 Save Scores & Complete Match</button>
                <a href="{{ route('admin.leagues.scheduleOverview', $match->league_id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Calculate totals as scores are entered
        function updateTotal(playerId) {
            const inputs = document.querySelectorAll(`input[data-player="${playerId}"]`);
            let total = 0;
            inputs.forEach(input => {
                const value = parseInt(input.value) || 0;
                total += value;
            });
            document.getElementById(`total-${playerId}`).textContent = total;
        }

        // Collect all score inputs in DOM order for auto-advance
        const allScoreInputs = Array.from(document.querySelectorAll('.score-input'));

        // Add event listeners to all score inputs
        document.querySelectorAll('.score-input').forEach((input, index) => {
            input.addEventListener('input', function() {
                const playerId = this.dataset.player;
                updateTotal(playerId);

                // Auto-advance to next input when a valid score is entered
                const val = parseInt(this.value);
                if (val >= 1 && val <= 15) {
                    const nextInput = allScoreInputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                        nextInput.select();
                    }
                }
            });

            // Select all text on focus for easy overwriting
            input.addEventListener('focus', function() {
                this.select();
            });

            // Calculate initial totals on page load
            const playerId = input.dataset.player;
            updateTotal(playerId);
        });

        // Initialize totals on page load
        document.addEventListener('DOMContentLoaded', function() {
            const playerIds = [...new Set([...document.querySelectorAll('.score-input')].map(input => input.dataset.player))];
            playerIds.forEach(playerId => updateTotal(playerId));
        });
    </script>
</body>
</html>

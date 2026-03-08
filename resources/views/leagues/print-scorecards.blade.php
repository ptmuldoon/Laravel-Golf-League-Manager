<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scorecards - {{ $league->name }} Week {{ $weekNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .no-print a, .no-print button {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1em;
            text-decoration: none;
            border: none;
            cursor: pointer;
            margin: 0 5px;
        }
        .btn-back {
            background: #6c757d;
            color: white;
        }
        .btn-print {
            background: var(--primary-color);
            color: white;
        }
        .scorecard {
            background: white;
            border: 2px solid #333;
            border-radius: 4px;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .scorecard-header {
            padding: 12px 15px;
            border-bottom: 2px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .scorecard-title {
            font-size: 1.1em;
            font-weight: 700;
        }
        .scorecard-info {
            font-size: 0.85em;
            color: #444;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #999;
            padding: 5px 4px;
            text-align: center;
            font-size: 0.8em;
        }
        th {
            background: #e8ecf4;
            font-weight: 700;
            color: #333;
        }
        .hole-col {
            width: 10px;
        }
        .player-name-cell {
            text-align: left;
            font-weight: 600;
            padding-left: 8px;
            white-space: nowrap;
        }
        .par-row {
            background: #f0f0f0;
            font-weight: 700;
        }
        .hdcp-row {
            background: #fff8e1;
            font-size: 0.75em;
        }
        .yardage-row {
            background: #e8f0fe;
            font-size: 0.75em;
        }
        .team-header-row td {
            background: var(--primary-color);
            color: white;
            font-weight: 700;
            font-size: 0.8em;
            padding: 4px 8px;
        }
        .score-cell {
            height: 28px;
            min-width: 28px;
            position: relative;
        }
        .score-cell .stroke-dots {
            position: absolute;
            top: 1px;
            right: 1px;
            font-size: 10px;
            line-height: 1;
            color: var(--secondary-color);
        }
        .total-cell {
            font-weight: 700;
            background: var(--primary-light);
        }
        .net-row td {
            height: 22px;
            font-size: 0.7em;
            color: #888;
            border-top: none;
        }
        .net-label {
            text-align: left;
            padding-left: 8px;
            font-style: italic;
        }

        /* Force browsers to print background colors and images */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            .scorecard {
                margin-top: 10px;
                margin-bottom: 20px;
                border: 2px solid #000;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            /* Force page break after every 2nd scorecard (2n+1 accounts for the .no-print div) */
            .scorecard:nth-of-type(2n+1) {
                page-break-after: always;
                break-after: page;
            }
            /* No page break after the last scorecard */
            .scorecard:last-of-type {
                page-break-after: auto;
                break-after: auto;
            }
            .scorecard-header {
                padding: 6px 10px;
            }
            .scorecard-title {
                font-size: 0.95em;
            }
            .scorecard-info {
                font-size: 0.7em;
            }
            table th, table td {
                padding: 3px 2px;
                font-size: 0.7em;
            }
            .score-cell {
                height: 36px;
                min-width: 22px;
            }
            .net-row td {
                height: 24px;
                font-size: 0.6em;
            }
            .hdcp-row {
                font-size: 0.65em;
            }
            .yardage-row {
                font-size: 0.65em;
            }
            .team-header-row td {
                padding: 2px 6px;
                font-size: 0.7em;
            }
            .note-row td {
                height: 18px;
            }
            @page {
                size: portrait;
                margin: 0.3in;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('admin.leagues.scheduleOverview', $league->id) }}" class="btn-back">← Back to Schedule</a>
        <button onclick="window.print()" class="btn-print">🖨️ Print Scorecards</button>
    </div>

    @foreach($scorecards as $card)
        @php
            $match = $card['match'];
            $courseInfo = $card['courseInfo'];
            $allCourseInfo = $card['allCourseInfo'] ?? $courseInfo;
            $holeRange = $card['holeRange'];
            $homePlayers = $card['homePlayers'];
            $awayPlayers = $card['awayPlayers'];
            $homeTeamName = $card['homeTeamName'];
            $awayTeamName = $card['awayTeamName'];
            $cardHandicaps = $card['playerHandicaps'];
            $numHoles = $holeRange[1] - $holeRange[0] + 1;
            $colSpan = $numHoles + 2; // player + holes + total
        @endphp
        <div class="scorecard">
            <div class="scorecard-header">
                <div class="scorecard-title">
                    {{ $awayTeamName }} vs {{ $homeTeamName }}
                </div>
                <div class="scorecard-info">
                    {{ $league->name }} - Week {{ $weekNumber }}<br>
                    {{ $match->match_date->format('M d, Y') }}{{ $match->tee_time ? ' @ ' . \Carbon\Carbon::parse($match->tee_time)->format('g:i A') : '' }}
                    | {{ $match->golfCourse->name }} ({{ $match->teebox }}) | {{ $match->holes === 'back_9' ? 'Back 9' : 'Front 9' }}<br>
                    {{ \App\Models\ScoringSetting::scoringTypes()[$match->scoring_type] ?? ucfirst(str_replace('_', ' ', $match->scoring_type)) }}
                    | {{ $match->ride_with_opponent ? 'Riding with Opponent' : 'Riding with Teammate' }}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px; text-align: left; padding-left: 8px;">Player</th>
                        @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                            <th class="hole-col">{{ $hole }}</th>
                        @endfor
                        <th class="hole-col">TOT</th>
                    </tr>
                    <tr class="yardage-row">
                        <td style="text-align: left; padding-left: 8px;"><strong>{{ $match->teebox }}</strong> Yds</td>
                        @foreach($courseInfo as $holeInfo)
                            <td>{{ $holeInfo->yardage ?? '' }}</td>
                        @endforeach
                        <td>{{ $courseInfo->sum('yardage') ?: '' }}</td>
                    </tr>
                    <tr class="par-row">
                        <td style="text-align: left; padding-left: 8px;"><strong>Par</strong></td>
                        @foreach($courseInfo as $holeInfo)
                            <td>{{ $holeInfo->par }}</td>
                        @endforeach
                        <td>{{ $courseInfo->sum('par') }}</td>
                    </tr>
                    <tr class="hdcp-row">
                        <td style="text-align: left; padding-left: 8px;"><strong>Hdcp</strong></td>
                        @foreach($courseInfo as $holeInfo)
                            <td>{{ $holeInfo->handicap ?? '' }}</td>
                        @endforeach
                        <td></td>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rideWithOpponent = $match->ride_with_opponent;
                        if ($rideWithOpponent) {
                            // Interleave: Away P1, Home P1, Away P2, Home P2
                            $homeVals = $homePlayers->sortBy('position_in_pairing')->values();
                            $awayVals = $awayPlayers->sortBy('position_in_pairing')->values();
                            $pairingCount = max($homeVals->count(), $awayVals->count());
                            $orderedPlayers = collect();
                            for ($i = 0; $i < $pairingCount; $i++) {
                                if (isset($awayVals[$i])) $orderedPlayers->push($awayVals[$i]);
                                if (isset($homeVals[$i])) $orderedPlayers->push($homeVals[$i]);
                            }
                        }
                    @endphp

                    @if($rideWithOpponent)
                        {{-- Ride with Opponent: interleaved by pairing --}}
                        <tr class="team-header-row">
                            <td colspan="{{ $colSpan }}" style="text-align: left;">{{ $awayTeamName }} vs {{ $homeTeamName }}</td>
                        </tr>
                        @foreach($orderedPlayers as $idx => $mp)
                            @php
                                $isHome = $homePlayers->contains('id', $mp->id);
                                $teamColor = $isHome ? '#28a745' : '#dc3545';
                                $ph = $cardHandicaps[$mp->id] ?? null;
                                $hi = $ph ? $ph['hi'] : (float) $mp->handicap_index;
                                $ch18 = $ph ? $ph['ch18'] : $mp->course_handicap;
                                $ch9 = $ph ? $ph['ch9'] : round($mp->course_handicap / 2);
                                $ch = (int) $ch18;
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
                            @if($idx > 0 && $idx % 2 === 0)
                                <tr><td colspan="{{ $colSpan }}" style="border: none; height: 3px; background: #e0e0e0;"></td></tr>
                            @endif
                            <tr>
                                <td class="player-name-cell" style="color: {{ $teamColor }};">
                                    {{ $mp->display_name }}
                                    <span style="font-size: 0.75em; color: var(--secondary-color); font-weight: 500;">({{ $ch18 }} / {{ collect($strokesOnHole)->only(range($holeRange[0], $holeRange[1]))->sum() }})</span>
                                </td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td class="score-cell">@if(($strokesOnHole[$hole] ?? 0) > 0)<span class="stroke-dots">{{ str_repeat('●', $strokesOnHole[$hole]) }}</span>@endif</td>
                                @endfor
                                <td class="score-cell total-cell"></td>
                            </tr>
                            <tr class="net-row">
                                <td class="net-label">Net</td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td></td>
                                @endfor
                                <td></td>
                            </tr>
                        @endforeach
                        <tr class="note-row">
                            <td style="text-align: left; padding-left: 8px; font-size: 0.75em; font-weight: 600; color: #666;">Note:</td>
                            <td colspan="{{ $colSpan - 1 }}" style="height: 24px;"></td>
                        </tr>
                    @else
                        {{-- Standard: grouped by team --}}
                        {{-- Away Team (top) --}}
                        <tr class="team-header-row">
                            <td colspan="{{ $colSpan }}" style="text-align: left;">{{ $awayTeamName }}</td>
                        </tr>
                        @foreach($awayPlayers as $mp)
                            @php
                                $ph = $cardHandicaps[$mp->id] ?? null;
                                $hi = $ph ? $ph['hi'] : (float) $mp->handicap_index;
                                $ch18 = $ph ? $ph['ch18'] : $mp->course_handicap;
                                $ch9 = $ph ? $ph['ch9'] : round($mp->course_handicap / 2);
                                $ch = (int) $ch18;
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
                            <tr>
                                <td class="player-name-cell">
                                    {{ $mp->display_name }}
                                    <span style="font-size: 0.75em; color: var(--secondary-color); font-weight: 500;">({{ $ch18 }} / {{ collect($strokesOnHole)->only(range($holeRange[0], $holeRange[1]))->sum() }})</span>
                                </td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td class="score-cell">@if(($strokesOnHole[$hole] ?? 0) > 0)<span class="stroke-dots">{{ str_repeat('●', $strokesOnHole[$hole]) }}</span>@endif</td>
                                @endfor
                                <td class="score-cell total-cell"></td>
                            </tr>
                            <tr class="net-row">
                                <td class="net-label">Net</td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td></td>
                                @endfor
                                <td></td>
                            </tr>
                        @endforeach
                        <tr class="note-row">
                            <td style="text-align: left; padding-left: 8px; font-size: 0.75em; font-weight: 600; color: #666;">Note:</td>
                            <td colspan="{{ $colSpan - 1 }}" style="height: 24px;"></td>
                        </tr>

                        {{-- Home Team (bottom) --}}
                        <tr class="team-header-row">
                            <td colspan="{{ $colSpan }}" style="text-align: left;">{{ $homeTeamName }}</td>
                        </tr>
                        @foreach($homePlayers as $mp)
                            @php
                                $ph = $cardHandicaps[$mp->id] ?? null;
                                $hi = $ph ? $ph['hi'] : (float) $mp->handicap_index;
                                $ch18 = $ph ? $ph['ch18'] : $mp->course_handicap;
                                $ch9 = $ph ? $ph['ch9'] : round($mp->course_handicap / 2);
                                $ch = (int) $ch18;
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
                            <tr>
                                <td class="player-name-cell">
                                    {{ $mp->display_name }}
                                    <span style="font-size: 0.75em; color: var(--secondary-color); font-weight: 500;">({{ $ch18 }} / {{ collect($strokesOnHole)->only(range($holeRange[0], $holeRange[1]))->sum() }})</span>
                                </td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td class="score-cell">@if(($strokesOnHole[$hole] ?? 0) > 0)<span class="stroke-dots">{{ str_repeat('●', $strokesOnHole[$hole]) }}</span>@endif</td>
                                @endfor
                                <td class="score-cell total-cell"></td>
                            </tr>
                            <tr class="net-row">
                                <td class="net-label">Net</td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td></td>
                                @endfor
                                <td></td>
                            </tr>
                        @endforeach
                        <tr class="note-row">
                            <td style="text-align: left; padding-left: 8px; font-size: 0.75em; font-weight: 600; color: #666;">Note:</td>
                            <td colspan="{{ $colSpan - 1 }}" style="height: 24px;"></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            window.print();
        });
    </script>
</body>
</html>

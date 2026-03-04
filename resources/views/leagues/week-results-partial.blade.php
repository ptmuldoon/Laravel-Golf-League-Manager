@if($weekMatches->isNotEmpty())
    @php
        $firstMatch = $weekMatches->first();
    @endphp
    <div style="color: #666; font-size: 0.9em; margin-bottom: 15px; text-align: center;">
        {{ $firstMatch->match_date->format('l, M d, Y') }} | {{ $firstMatch->golfCourse->name ?? '' }}
        | {{ $firstMatch->holes === 'back_9' ? 'Back 9' : 'Front 9' }}
        | {{ \App\Models\ScoringSetting::scoringTypes()[$firstMatch->scoring_type] ?? ucfirst(str_replace('_', ' ', $firstMatch->scoring_type)) }}
    </div>

    @foreach($weekMatches as $match)
        @php
            if ($match->home_team_id) {
                $homePlayers = $match->matchPlayers->where('team_id', $match->home_team_id)->sortBy('position_in_pairing');
                $awayPlayers = $match->matchPlayers->where('team_id', $match->away_team_id)->sortBy('position_in_pairing');
            } else {
                $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2)->sortBy('position_in_pairing');
                $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2)->sortBy('position_in_pairing');
            }
            $homeTeam = $matchTeamNames[$match->id]['home'] ?? 'Home';
            $awayTeam = $matchTeamNames[$match->id]['away'] ?? 'Away';
            $scData = $scorecardData[$match->id] ?? null;
            $courseInfo = $scData ? $scData['courseInfo'] : collect();
            $allCourseInfo = $scData ? ($scData['allCourseInfo'] ?? $courseInfo) : collect();
            $holeRange = $scData ? $scData['holeRange'] : [1, 9];
            $playerHandicaps = $scData ? $scData['playerHandicaps'] : [];
        @endphp
        <div class="match-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div class="match-teams" style="margin-bottom: 0;">
                    {{ $homeTeam }} vs {{ $awayTeam }}
                </div>
                @if($match->tee_time)
                    <span class="tee-time-badge">{{ \Carbon\Carbon::parse($match->tee_time)->format('g:i A') }}</span>
                @endif
            </div>

            @if($match->result)
                @php
                    $homeScore = $match->result->holes_won_home + ($match->result->holes_tied * 0.5);
                    $awayScore = $match->result->holes_won_away + ($match->result->holes_tied * 0.5);
                    $fmtHome = $homeScore == (int)$homeScore ? (int)$homeScore : number_format($homeScore, 1);
                    $fmtAway = $awayScore == (int)$awayScore ? (int)$awayScore : number_format($awayScore, 1);
                @endphp
                <div class="match-result" style="margin-bottom: 10px;">
                    @if($match->result->winning_team_id && $match->result->winningTeam)
                        🏆 {{ $match->result->winningTeam->name }} wins
                        ({{ $fmtHome }} - {{ $fmtAway }})
                    @else
                        🤝 Match Tied
                        ({{ $fmtHome }} - {{ $fmtAway }})
                    @endif
                    | Pts: {{ number_format($match->result->team_points_home, 2) }} - {{ number_format($match->result->team_points_away, 2) }}
                </div>
            @endif

            @if($courseInfo->isNotEmpty())
            <div class="scrollable-table">
                <table class="scorecard-table">
                    <thead>
                        <tr>
                            <th class="sc-player-col">Player</th>
                            <th class="sc-hcp-col">Hcp</th>
                            @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                <th class="sc-hole-col">{{ $hole }}</th>
                            @endfor
                            <th class="sc-total-col">Tot</th>
                        </tr>
                        <tr class="sc-par-row">
                            <td>Par</td>
                            <td>-</td>
                            @foreach($courseInfo as $holeInfo)
                                <td>{{ $holeInfo->par }}</td>
                            @endforeach
                            <td>{{ $courseInfo->sum('par') }}</td>
                        </tr>
                        <tr class="sc-hdcp-row">
                            <td>Hdcp</td>
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
                                $homeVals = $homePlayers->values();
                                $awayVals = $awayPlayers->values();
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
                            <tr class="sc-team-header">
                                <td colspan="12">{{ $homeTeam }} vs {{ $awayTeam }}</td>
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
                                    <tr class="sc-team-header">
                                        <td colspan="12">{{ $homeTeam }}</td>
                                    </tr>
                                @elseif($dIdx === count($homePlayerIds))
                                    @if($scData && isset($scData['holeResults']) && $scData['holeResults']['type'] === 'team')
                                        @php $homeRes = $scData['holeResults']['homeResults']; @endphp
                                        <tr class="sc-results-row">
                                            <td style="text-align: left; font-weight: 700; font-size: 0.75em; background: #e3f2fd; color: #1565c0;">{{ $homeTeam }}</td>
                                            <td style="background: #f0f0f0;">-</td>
                                            @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                                <td class="{{ $homeRes['holes'][$hole]['class'] ?? '' }}">{{ $homeRes['holes'][$hole]['display'] ?? '-' }}</td>
                                            @endfor
                                            <td style="font-weight: 700; background: #e3f2fd; color: #1565c0;">{{ $homeRes['total'] == (int)$homeRes['total'] ? (int)$homeRes['total'] : number_format($homeRes['total'], 1) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="sc-team-header">
                                        <td colspan="12">{{ $awayTeam }}</td>
                                    </tr>
                                @endif
                            @else
                                @if($dIdx > 0 && $dIdx % 2 === 0)
                                    <tr><td colspan="12" style="border: none; height: 3px; background: #e0e0e0;"></td></tr>
                                @endif
                            @endif

                            <tr class="sc-dots-row">
                                <td></td><td></td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td>{{ str_repeat('●', $strokesOnHole[$hole] ?? 0) }}</td>
                                @endfor
                                <td></td>
                            </tr>
                            <tr>
                                <td class="sc-player-name" {!! $rideWithOpponent ? 'style="color: ' . ($isHome ? '#28a745' : '#dc3545') . ';"' : '' !!}>
                                    @if($matchPlayer->player)
                                        <a href="{{ route('players.show', $matchPlayer->player->id) }}" class="team-link" {!! $rideWithOpponent ? 'style="color: ' . ($isHome ? '#28a745' : '#dc3545') . ';"' : '' !!}>{{ $matchPlayer->display_name }}</a>
                                    @else
                                        {{ $matchPlayer->display_name }}
                                    @endif
                                </td>
                                <td class="sc-hcp-cell">
                                    {{ collect($strokesOnHole)->only(range($holeRange[0], $holeRange[1]))->sum() }}
                                </td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    @php $score = $matchPlayer->scores->where('hole_number', $hole)->first(); @endphp
                                    <td class="sc-score-cell">{{ $score?->strokes ?? '-' }}</td>
                                @endfor
                                <td class="sc-total-cell">{{ $matchPlayer->totalStrokes() ?: '-' }}</td>
                            </tr>
                            @if($scData && isset($scData['holeResults']) && $scData['holeResults']['type'] === 'individual' && isset($scData['holeResults']['playerResults'][$matchPlayer->id]))
                                @php $pResult = $scData['holeResults']['playerResults'][$matchPlayer->id]; @endphp
                                <tr class="sc-results-row">
                                    <td style="text-align: left; font-weight: 600; background: var(--primary-light); font-size: 0.75em; color: #555;">Match</td>
                                    <td style="background: #f0f0f0;">-</td>
                                    @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                        <td class="{{ $pResult['holes'][$hole]['class'] ?? '' }}">{{ $pResult['holes'][$hole]['display'] ?? '-' }}</td>
                                    @endfor
                                    <td style="font-weight: 700; background: var(--primary-light);">{{ $pResult['total'] == (int)$pResult['total'] ? (int)$pResult['total'] : number_format($pResult['total'], 1) }}</td>
                                </tr>
                            @endif
                        @endforeach

                        {{-- Team result rows at the end --}}
                        @if($scData && isset($scData['holeResults']) && $scData['holeResults']['type'] === 'team')
                            @if($rideWithOpponent)
                                @php $homeRes = $scData['holeResults']['homeResults']; @endphp
                                <tr class="sc-results-row">
                                    <td style="text-align: left; font-weight: 700; font-size: 0.75em; background: #e3f2fd; color: #1565c0;">{{ $homeTeam }}</td>
                                    <td style="background: #f0f0f0;">-</td>
                                    @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                        <td class="{{ $homeRes['holes'][$hole]['class'] ?? '' }}">{{ $homeRes['holes'][$hole]['display'] ?? '-' }}</td>
                                    @endfor
                                    <td style="font-weight: 700; background: #e3f2fd; color: #1565c0;">{{ $homeRes['total'] == (int)$homeRes['total'] ? (int)$homeRes['total'] : number_format($homeRes['total'], 1) }}</td>
                                </tr>
                            @endif
                            @php $awayRes = $scData['holeResults']['awayResults']; @endphp
                            <tr class="sc-results-row">
                                <td style="text-align: left; font-weight: 700; font-size: 0.75em; background: #fce4ec; color: #c62828;">{{ $awayTeam }}</td>
                                <td style="background: #f0f0f0;">-</td>
                                @for($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++)
                                    <td class="{{ $awayRes['holes'][$hole]['class'] ?? '' }}">{{ $awayRes['holes'][$hole]['display'] ?? '-' }}</td>
                                @endfor
                                <td style="font-weight: 700; background: #fce4ec; color: #c62828;">{{ $awayRes['total'] == (int)$awayRes['total'] ? (int)$awayRes['total'] : number_format($awayRes['total'], 1) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @endif

            <div style="margin-top: 8px; text-align: right;">
                <a href="{{ route('matches.show', $match->id) }}" style="color: var(--primary-color); text-decoration: none; font-size: 0.85em; font-weight: 600;">
                    View Full Scorecard →
                </a>
            </div>
        </div>
    @endforeach
@else
    <div style="text-align: center; padding: 30px; color: #888;">No results for this week.</div>
@endif

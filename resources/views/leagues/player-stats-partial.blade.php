<style>
    @media (max-width: 600px) {
        .nine-summary-grid { grid-template-columns: 1fr !important; }
    }
</style>
@php
    $scoreColor = function($score, $par) {
        if ($score === null || $par === null) return '';
        $diff = $score - $par;
        if ($diff <= -3) return 'background: #f5e6ff; color: #7b2d8e; font-weight: 700;'; // albatross
        if ($diff == -2) return 'background: #fff3e0; color: #e65100; font-weight: 700;'; // eagle
        if ($diff == -1) return 'background: #e8f5e9; color: #2e7d32; font-weight: 600;'; // birdie
        if ($diff == 0)  return '';                                                         // par
        if ($diff == 1)  return 'background: #e3f2fd; color: #1565c0;';                    // bogey
        if ($diff == 2)  return 'background: #fce4ec; color: #c62828; font-weight: 600;';  // double
        return 'background: #f8d7da; color: #721c24; font-weight: 700;';                   // triple+
    };
    $vsParText = function($diff) {
        if ($diff === null) return '-';
        if ($diff == 0) return 'E';
        return ($diff > 0 ? '+' : '') . (is_float($diff) ? number_format($diff, 1) : $diff);
    };
@endphp
<div class="content-section">
    <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span>Player Stats</span>
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="display: flex; gap: 6px; font-size: 0.5em;">
                <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: var(--primary-color); color: white; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" id="btn-ps-gross-{{ $league->id }}" onclick="togglePlayerStatsMode('gross', {{ $league->id }})">Gross</button>
                <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: #f0f0f5; color: #666; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" id="btn-ps-net-{{ $league->id }}" onclick="togglePlayerStatsMode('net', {{ $league->id }})">Net</button>
                <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: #f0f0f5; color: #666; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" id="btn-ps-vspar-{{ $league->id }}" onclick="togglePlayerStatsMode('vspar', {{ $league->id }})">vs. Par</button>
            </div>
            <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('player-stats-body-{{ $league->id }}')" id="toggle-player-stats-body-{{ $league->id }}">&#9650;</span>
        </div>
    </h2>

    <div id="player-stats-body-{{ $league->id }}">
        @if($players->isEmpty())
            <div style="text-align: center; padding: 40px; color: #888;">No players in this league.</div>
        @else
            {{-- Color Legend --}}
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; font-size: 0.8em; align-items: center;">
                <span style="color: #888;">Legend:</span>
                <span style="background: #f5e6ff; color: #7b2d8e; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Albatross</span>
                <span style="background: #fff3e0; color: #e65100; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Eagle</span>
                <span style="background: #e8f5e9; color: #2e7d32; font-weight: 600; padding: 2px 8px; border-radius: 4px;">Birdie</span>
                <span style="padding: 2px 8px; border-radius: 4px; border: 1px solid #e0e0e0;">Par</span>
                <span style="background: #e3f2fd; color: #1565c0; padding: 2px 8px; border-radius: 4px;">Bogey</span>
                <span style="background: #fce4ec; color: #c62828; font-weight: 600; padding: 2px 8px; border-radius: 4px;">Double</span>
                <span style="background: #f8d7da; color: #721c24; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Triple+</span>
            </div>

            <div style="margin-bottom: 15px;">
                <select id="ps-player-select-{{ $league->id }}" onchange="showPlayerStats({{ $league->id }})" style="padding: 8px 14px; font-size: 0.9em; font-weight: 600; border: 2px solid #e0e0e0; border-radius: 8px; background: white; color: var(--primary-color); cursor: pointer; min-width: 200px;">
                    <option value="">Select a Player</option>
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">{{ $player->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="ps-no-player-{{ $league->id }}" style="text-align: center; padding: 30px; color: #888;">
                Select a player to view their scores.
            </div>

            @foreach($players as $player)
                @php $weeks = $playerWeekData[$player->id] ?? []; @endphp
                <div id="ps-player-{{ $league->id }}-{{ $player->id }}" style="display: none;">
                    @if(empty($weeks))
                        <div style="text-align: center; padding: 30px; color: #888;">No scores recorded for this player.</div>
                    @else
                        {{-- Front 9 / Back 9 Summary --}}
                        @php $nineSummary = $playerNineSummary[$player->id] ?? ['front' => null, 'back' => null]; @endphp
                        @if($nineSummary['front'] || $nineSummary['back'])
                            <div style="margin-bottom: 20px; padding: 15px; background: var(--primary-light); border-radius: 10px; border: 1px solid #e8ecf4;">
                                <div style="font-weight: 700; color: var(--primary-color); margin-bottom: 12px; font-size: 1em;">Front 9 / Back 9 Summary</div>
                                <div class="nine-summary-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    @foreach(['front' => 'Front 9', 'back' => 'Back 9'] as $key => $label)
                                        @php $summary = $nineSummary[$key]; @endphp
                                        <div style="background: white; border-radius: 8px; padding: 12px; border-left: 4px solid {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }};">
                                            <div style="font-weight: 700; color: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; margin-bottom: 8px;">{{ $label }}</div>
                                            @if($summary)
                                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 10px;">
                                                    <div style="text-align: center; padding: 6px; background: var(--primary-light); border-radius: 6px;">
                                                        <div style="font-size: 0.7em; color: #888;">Rounds</div>
                                                        <div style="font-size: 1.3em; font-weight: 700; color: #333;">{{ $summary['count'] }}</div>
                                                    </div>
                                                    <div style="text-align: center; padding: 6px; background: var(--primary-light); border-radius: 6px;">
                                                        <div style="font-size: 0.7em; color: #888;">Avg Gross</div>
                                                        <div style="font-size: 1.3em; font-weight: 700; color: #333;">{{ $summary['avg_gross'] ?? '-' }}</div>
                                                    </div>
                                                    <div style="text-align: center; padding: 6px; background: var(--primary-light); border-radius: 6px;">
                                                        <div style="font-size: 0.7em; color: #888;">Avg Net</div>
                                                        <div style="font-size: 1.3em; font-weight: 700; color: #333;">{{ $summary['avg_net'] ?? '-' }}</div>
                                                    </div>
                                                    <div style="text-align: center; padding: 6px; background: var(--primary-light); border-radius: 6px;">
                                                        <div style="font-size: 0.7em; color: #888;">Low Gross</div>
                                                        <div style="font-size: 1.2em; font-weight: 600; color: #28a745;">{{ $summary['low_gross'] ?? '-' }}</div>
                                                    </div>
                                                    <div style="text-align: center; padding: 6px; background: var(--primary-light); border-radius: 6px;">
                                                        <div style="font-size: 0.7em; color: #888;">High Gross</div>
                                                        <div style="font-size: 1.2em; font-weight: 600; color: #dc3545;">{{ $summary['high_gross'] ?? '-' }}</div>
                                                    </div>
                                                    <div style="text-align: center; padding: 6px; background: var(--primary-light); border-radius: 6px;">
                                                        <div style="font-size: 0.7em; color: #888;">Low Net</div>
                                                        <div style="font-size: 1.2em; font-weight: 600; color: #28a745;">{{ $summary['low_net'] ?? '-' }}</div>
                                                    </div>
                                                </div>
                                                {{-- Per-hole averages --}}
                                                <div id="ps-nine-gross-{{ $league->id }}-{{ $player->id }}-{{ $key }}" class="scrollable-table">
                                                    <table style="font-size: 0.82em;">
                                                        <thead>
                                                            <tr>
                                                                <th style="text-align: center; padding: 4px; font-size: 0.85em; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">Hole</th>
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    <th style="text-align: center; padding: 4px; width: 36px; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">{{ $key === 'front' ? $h : $h + 9 }}</th>
                                                                @endfor
                                                                <th style="text-align: center; padding: 4px; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">Tot</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr style="background: #f0f0f0;">
                                                                <td style="text-align: center; padding: 3px; font-weight: 600; font-size: 0.85em;">Par</td>
                                                                @php $parTotal = 0; @endphp
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    @php $parTotal += $summary['hole_par'][$h] ?? 0; @endphp
                                                                    <td style="text-align: center; padding: 3px; font-weight: 600;">{{ $summary['hole_par'][$h] ?? '-' }}</td>
                                                                @endfor
                                                                <td style="text-align: center; padding: 3px; font-weight: 700;">{{ $parTotal ?: '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="text-align: center; padding: 3px; font-weight: 600; font-size: 0.85em; color: var(--primary-color);">Avg</td>
                                                                @php $avgTotal = 0; @endphp
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    @php
                                                                        $avg = $summary['hole_gross_avg'][$h];
                                                                        $par = $summary['hole_par'][$h];
                                                                        $avgTotal += $avg ?? 0;
                                                                    @endphp
                                                                    <td style="text-align: center; padding: 3px; {{ $avg !== null && $par !== null ? $scoreColor($avg, $par) : '' }}">{{ $avg ?? '-' }}</td>
                                                                @endfor
                                                                <td style="text-align: center; padding: 3px; font-weight: 700; color: var(--primary-color);">{{ $avgTotal ? round($avgTotal, 1) : '-' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div id="ps-nine-net-{{ $league->id }}-{{ $player->id }}-{{ $key }}" class="scrollable-table" style="display: none;">
                                                    <table style="font-size: 0.82em;">
                                                        <thead>
                                                            <tr>
                                                                <th style="text-align: center; padding: 4px; font-size: 0.85em; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">Hole</th>
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    <th style="text-align: center; padding: 4px; width: 36px; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">{{ $key === 'front' ? $h : $h + 9 }}</th>
                                                                @endfor
                                                                <th style="text-align: center; padding: 4px; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">Tot</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr style="background: #f0f0f0;">
                                                                <td style="text-align: center; padding: 3px; font-weight: 600; font-size: 0.85em;">Par</td>
                                                                @php $parTotal = 0; @endphp
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    @php $parTotal += $summary['hole_par'][$h] ?? 0; @endphp
                                                                    <td style="text-align: center; padding: 3px; font-weight: 600;">{{ $summary['hole_par'][$h] ?? '-' }}</td>
                                                                @endfor
                                                                <td style="text-align: center; padding: 3px; font-weight: 700;">{{ $parTotal ?: '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="text-align: center; padding: 3px; font-weight: 600; font-size: 0.85em; color: var(--primary-color);">Avg</td>
                                                                @php $avgTotal = 0; @endphp
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    @php
                                                                        $avg = $summary['hole_net_avg'][$h];
                                                                        $par = $summary['hole_par'][$h];
                                                                        $avgTotal += $avg ?? 0;
                                                                    @endphp
                                                                    <td style="text-align: center; padding: 3px; {{ $avg !== null && $par !== null ? $scoreColor($avg, $par) : '' }}">{{ $avg ?? '-' }}</td>
                                                                @endfor
                                                                <td style="text-align: center; padding: 3px; font-weight: 700; color: var(--primary-color);">{{ $avgTotal ? round($avgTotal, 1) : '-' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div id="ps-nine-vspar-{{ $league->id }}-{{ $player->id }}-{{ $key }}" class="scrollable-table" style="display: none;">
                                                    <table style="font-size: 0.82em;">
                                                        <thead>
                                                            <tr>
                                                                <th style="text-align: center; padding: 4px; font-size: 0.85em; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">Hole</th>
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    <th style="text-align: center; padding: 4px; width: 36px; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">{{ $key === 'front' ? $h : $h + 9 }}</th>
                                                                @endfor
                                                                <th style="text-align: center; padding: 4px; background: {{ $key === 'front' ? 'var(--primary-color)' : '#e67e22' }}; color: white;">Tot</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr style="background: #f0f0f0;">
                                                                <td style="text-align: center; padding: 3px; font-weight: 600; font-size: 0.85em;">Par</td>
                                                                @php $parTotal = 0; @endphp
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    @php $parTotal += $summary['hole_par'][$h] ?? 0; @endphp
                                                                    <td style="text-align: center; padding: 3px; font-weight: 600;">{{ $summary['hole_par'][$h] ?? '-' }}</td>
                                                                @endfor
                                                                <td style="text-align: center; padding: 3px; font-weight: 700;">{{ $parTotal ?: '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="text-align: center; padding: 3px; font-weight: 600; font-size: 0.85em; color: var(--primary-color);">Avg</td>
                                                                @php $avgTotal = 0; @endphp
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    @php
                                                                        $avg = $summary['hole_gross_avg'][$h];
                                                                        $par = $summary['hole_par'][$h];
                                                                        $avgTotal += $avg ?? 0;
                                                                    @endphp
                                                                    <td style="text-align: center; padding: 3px;">{{ $avg ?? '-' }}</td>
                                                                @endfor
                                                                <td style="text-align: center; padding: 3px; font-weight: 700; color: var(--primary-color);">{{ $avgTotal ? round($avgTotal, 1) : '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="text-align: center; padding: 3px; font-weight: 600; font-size: 0.85em; color: var(--primary-color);">vs Par</td>
                                                                @php $diffTotal = 0; $hasAnyDiff = false; @endphp
                                                                @for($h = 1; $h <= 9; $h++)
                                                                    @php
                                                                        $avg = $summary['hole_gross_avg'][$h];
                                                                        $par = $summary['hole_par'][$h];
                                                                        $diff = ($avg !== null && $par !== null) ? round($avg - $par, 1) : null;
                                                                        if ($diff !== null) { $diffTotal += $diff; $hasAnyDiff = true; }
                                                                    @endphp
                                                                    <td style="text-align: center; padding: 3px; font-weight: 600; {{ $diff !== null ? $scoreColor($avg, $par) : '' }}">{{ $vsParText($diff) }}</td>
                                                                @endfor
                                                                <td style="text-align: center; padding: 3px; font-weight: 700; color: var(--primary-color);">{{ $hasAnyDiff ? $vsParText(round($diffTotal, 1)) : '-' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div style="text-align: center; padding: 12px; color: #999; font-size: 0.85em;">No {{ strtolower($label) }} rounds played.</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Gross Table --}}
                        <div id="ps-gross-{{ $league->id }}-{{ $player->id }}" class="scrollable-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Week</th>
                                        <th style="text-align: center;">Date</th>
                                        <th style="text-align: center;">Side</th>
                                        @for($h = 1; $h <= 9; $h++)
                                            <th style="text-align: center; width: 36px;">{{ $h }}</th>
                                        @endfor
                                        <th style="text-align: center; font-weight: 700;">Tot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weeks as $week)
                                        <tr>
                                            <td style="text-align: center; font-weight: 600; color: var(--primary-color);">{{ $week['week'] }}</td>
                                            <td style="text-align: center; font-size: 0.85em; color: #666; white-space: nowrap;">{{ $week['date'] ? $week['date']->format('m-d-Y') : '-' }}</td>
                                            <td style="text-align: center; font-size: 0.85em; color: #666;">{{ $week['side'] }}</td>
                                            @for($h = $week['hole_start']; $h <= $week['hole_end']; $h++)
                                                @php
                                                    $gs = $week['gross'][$h] ?? null;
                                                    $hp = $week['par'][$h] ?? null;
                                                @endphp
                                                <td style="text-align: center; {{ $gs !== null && $hp !== null ? $scoreColor($gs, $hp) : '' }}">{{ $gs ?? '-' }}</td>
                                            @endfor
                                            <td style="text-align: center; font-weight: 700; color: var(--primary-color);">{{ $week['gross_total'] ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Net Table --}}
                        <div id="ps-net-{{ $league->id }}-{{ $player->id }}" class="scrollable-table" style="display: none;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Week</th>
                                        <th style="text-align: center;">Date</th>
                                        <th style="text-align: center;">Side</th>
                                        @for($h = 1; $h <= 9; $h++)
                                            <th style="text-align: center; width: 36px;">{{ $h }}</th>
                                        @endfor
                                        <th style="text-align: center; font-weight: 700;">Tot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weeks as $week)
                                        <tr>
                                            <td style="text-align: center; font-weight: 600; color: var(--primary-color);">{{ $week['week'] }}</td>
                                            <td style="text-align: center; font-size: 0.85em; color: #666; white-space: nowrap;">{{ $week['date'] ? $week['date']->format('m-d-Y') : '-' }}</td>
                                            <td style="text-align: center; font-size: 0.85em; color: #666;">{{ $week['side'] }}</td>
                                            @for($h = $week['hole_start']; $h <= $week['hole_end']; $h++)
                                                @php
                                                    $ns = $week['net'][$h] ?? null;
                                                    $hp = $week['par'][$h] ?? null;
                                                @endphp
                                                <td style="text-align: center; {{ $ns !== null && $hp !== null ? $scoreColor($ns, $hp) : '' }}">{{ $ns ?? '-' }}</td>
                                            @endfor
                                            <td style="text-align: center; font-weight: 700; color: var(--primary-color);">{{ $week['net_total'] ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- vs Par Table (gross diff per hole) --}}
                        <div id="ps-vspar-{{ $league->id }}-{{ $player->id }}" class="scrollable-table" style="display: none;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Week</th>
                                        <th style="text-align: center;">Date</th>
                                        <th style="text-align: center;">Side</th>
                                        @for($h = 1; $h <= 9; $h++)
                                            <th style="text-align: center; width: 36px;">{{ $h }}</th>
                                        @endfor
                                        <th style="text-align: center; font-weight: 700;">Tot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weeks as $week)
                                        @php
                                            $roundDiff = 0;
                                            $roundParTotal = 0;
                                            $roundHasScore = false;
                                            for ($h = $week['hole_start']; $h <= $week['hole_end']; $h++) {
                                                $hp = $week['par'][$h] ?? null;
                                                if ($hp !== null) $roundParTotal += $hp;
                                            }
                                        @endphp
                                        <tr>
                                            <td style="text-align: center; font-weight: 600; color: var(--primary-color);">{{ $week['week'] }}</td>
                                            <td style="text-align: center; font-size: 0.85em; color: #666; white-space: nowrap;">{{ $week['date'] ? $week['date']->format('m-d-Y') : '-' }}</td>
                                            <td style="text-align: center; font-size: 0.85em; color: #666;">{{ $week['side'] }}</td>
                                            @for($h = $week['hole_start']; $h <= $week['hole_end']; $h++)
                                                @php
                                                    $gs = $week['gross'][$h] ?? null;
                                                    $hp = $week['par'][$h] ?? null;
                                                    $d = ($gs !== null && $hp !== null) ? $gs - $hp : null;
                                                    if ($d !== null) { $roundDiff += $d; $roundHasScore = true; }
                                                @endphp
                                                <td style="text-align: center; font-weight: 600; {{ $gs !== null && $hp !== null ? $scoreColor($gs, $hp) : '' }}">{{ $vsParText($d) }}</td>
                                            @endfor
                                            <td style="text-align: center; font-weight: 700; color: var(--primary-color);">{{ $roundHasScore ? $vsParText($roundDiff) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>

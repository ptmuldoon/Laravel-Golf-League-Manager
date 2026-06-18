<div class="content-section">
    @if($matchesByWeek->isEmpty())
        <div style="text-align: center; padding: 40px; color: #888;">No matches scheduled yet.</div>
    @else
        {{-- View mode toggle --}}
        <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
            <button type="button" id="sched-mode-week-{{ $league->id }}" onclick="setScheduleMode('week', {{ $league->id }})"
                style="padding: 8px 18px; border: 2px solid var(--primary-color); border-radius: 20px; font-size: 0.9em; font-weight: 600; cursor: pointer; background: var(--primary-color); color: white;">By Week</button>
            <button type="button" id="sched-mode-player-{{ $league->id }}" onclick="setScheduleMode('player', {{ $league->id }})"
                style="padding: 8px 18px; border: 2px solid var(--primary-color); border-radius: 20px; font-size: 0.9em; font-weight: 600; cursor: pointer; background: white; color: var(--primary-color);">By Player</button>
        </div>

        {{-- ===================== BY WEEK ===================== --}}
        <div id="schedule-by-week-{{ $league->id }}">
        <div style="background: #e8f4f8; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; color: #0c5460; font-size: 0.9em;">
            <strong>Schedule Summary:</strong>
            Total Weeks: <strong>{{ $matchesByWeek->count() }}</strong> |
            Total Matches: <strong>{{ $totalMatches }}</strong> |
            Completed: <strong>{{ $completedMatches }}</strong>
        </div>

        @foreach($matchesByWeek as $weekNumber => $matches)
            @php
                $firstMatch = $matches->first();
                $isCompleted = $matches->every(fn($m) => $m->status === 'completed');
                $weekHomeTeamName = $firstMatch->homeTeam->name ?? null;
                $weekAwayTeamName = $firstMatch->awayTeam->name ?? null;
            @endphp
            <div style="border: 1px solid #e0e0e0; border-radius: 10px; margin-bottom: 15px; overflow: hidden;">
                <div onclick="toggleScheduleWeek({{ $weekNumber }}, {{ $league->id }})" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background: {{ $isCompleted ? '#f0fdf4' : 'var(--primary-light)' }}; cursor: pointer; user-select: none;" onmouseover="this.style.background='{{ $isCompleted ? '#dcfce7' : '#eef0ff' }}'" onmouseout="this.style.background='{{ $isCompleted ? '#f0fdf4' : 'var(--primary-light)' }}'">
                    <h3 style="color: var(--primary-color); font-size: 1.15em; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span id="sched-arrow-{{ $league->id }}-{{ $weekNumber }}" style="display: inline-block; transition: transform 0.2s ease; font-size: 0.7em;">&#9654;</span>
                        Week {{ $weekNumber }}
                    </h3>
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85em; color: #666;">
                        @if($firstMatch->match_date)
                            <span>{{ $firstMatch->match_date->format('M d, Y') }}</span>
                        @endif
                        @if($firstMatch->golfCourse)
                            <span style="color: #999;">&bull;</span>
                            <span>{{ $firstMatch->golfCourse->name }}</span>
                        @endif
                        <span style="padding: 2px 10px; border-radius: 10px; font-size: 0.85em; font-weight: 600;
                            background: {{ $isCompleted ? '#d4edda' : '#cce5ff' }};
                            color: {{ $isCompleted ? '#155724' : '#004085' }};">
                            {{ $isCompleted ? 'Completed' : ($matches->count() . ' ' . ($matches->count() == 1 ? 'match' : 'matches')) }}
                        </span>
                    </div>
                </div>

                <div id="sched-week-{{ $league->id }}-{{ $weekNumber }}" style="display: none; padding: 10px 18px 14px;">
                    <div style="font-size: 0.85em; color: #888; margin-bottom: 10px;">
                        {{ $firstMatch->holes === 'back_9' ? 'Back 9' : 'Front 9' }}
                        @if($firstMatch->scoring_type)
                            &bull; {{ \App\Models\ScoringSetting::scoringTypes()[$firstMatch->scoring_type] ?? ucfirst(str_replace('_', ' ', $firstMatch->scoring_type)) }}
                        @endif
                    </div>

                    {{-- Team name header --}}
                    @if($weekAwayTeamName || $weekHomeTeamName)
                        <div style="display: flex; align-items: center; gap: 8px; padding: 4px 0 8px; margin-left: 60px;">
                            <div style="flex: 1; text-align: center;">
                                <span style="font-size: 0.85em; color: #dc3545; font-weight: 700;">{{ $weekAwayTeamName ?? '' }}</span>
                            </div>
                            <span style="color: transparent; flex-shrink: 0;">vs</span>
                            <div style="flex: 1; text-align: center;">
                                <span style="font-size: 0.85em; color: #28a745; font-weight: 700;">{{ $weekHomeTeamName ?? '' }}</span>
                            </div>
                        </div>
                    @endif

                    @foreach($matches as $index => $match)
                        @php
                            $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2)->sortBy('position_in_pairing');
                            $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2)->sortBy('position_in_pairing');
                            $shortName = function($mp) {
                                $player = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
                                if ($player && $player->first_name && $player->last_name) {
                                    return $player->name;
                                }
                                return $player ? $player->name : ($mp->substitute_name ?? '');
                            };
                        @endphp
                        <div style="display: flex; align-items: center; gap: 8px; padding: 6px 0; {{ !$loop->last ? 'border-bottom: 1px solid #f0f0f0;' : '' }} font-size: 0.95em;">
                            <span style="color: var(--primary-color); font-weight: 700; min-width: 55px; font-size: 0.9em;">
                                @if($match->tee_time)
                                    {{ \Carbon\Carbon::parse($match->tee_time)->format('g:i A') }}
                                @else
                                    #{{ $index + 1 }}
                                @endif
                            </span>
                            <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
                                {{-- Away team (left side) --}}
                                <div style="flex: 1; text-align: right;">
                                    @foreach($awayPlayers as $mp)
                                        @if(!$loop->first) <span style="color: #dc3545;">&amp;</span> @endif
                                        <span style="color: #dc3545; font-weight: 600;">
                                            {{ $shortName($mp) }}
                                            @if($mp->substitute_player_id)
                                                <span style="font-size: 0.7em; color: #999;">(sub)</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                                <span style="color: #888; font-weight: 600; flex-shrink: 0;">vs</span>
                                {{-- Home team (right side) --}}
                                <div style="flex: 1; text-align: left;">
                                    @foreach($homePlayers as $mp)
                                        @if(!$loop->first) <span style="color: #28a745;">&amp;</span> @endif
                                        <span style="color: #28a745; font-weight: 600;">
                                            {{ $shortName($mp) }}
                                            @if($mp->substitute_player_id)
                                                <span style="font-size: 0.7em; color: #999;">(sub)</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        </div>{{-- end by-week --}}

        {{-- ===================== BY PLAYER ===================== --}}
        <div id="schedule-by-player-{{ $league->id }}" style="display: none;">
            <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <label for="sched-player-select-{{ $league->id }}" style="font-weight: 600; color: var(--primary-color);">Player:</label>
                <select id="sched-player-select-{{ $league->id }}" onchange="showPlayerSchedule({{ $league->id }})"
                    style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em;">
                    @foreach($schedulePlayers as $sp)
                        <option value="{{ $sp['id'] }}">{{ $sp['name'] }}</option>
                    @endforeach
                </select>
            </div>

            @foreach($schedulePlayers as $spi => $sp)
                <div class="sched-player-block-{{ $league->id }}" id="sched-player-{{ $league->id }}-{{ $sp['id'] }}" style="{{ $spi === 0 ? '' : 'display: none;' }}">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
                            <thead>
                                <tr style="background: var(--primary-light); color: var(--primary-color);">
                                    <th style="padding: 8px 10px; text-align: left;">Week</th>
                                    <th style="padding: 8px 10px; text-align: left;">Date</th>
                                    <th style="padding: 8px 10px; text-align: left;">Tee Time</th>
                                    <th style="padding: 8px 10px; text-align: left;">Format</th>
                                    <th style="padding: 8px 10px; text-align: left;">Partner</th>
                                    <th style="padding: 8px 10px; text-align: left;">Opponents</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($playerSchedules[$sp['id']] ?? [] as $row)
                                    <tr style="border-bottom: 1px solid #f0f0f0;{{ $row['status'] === 'completed' ? ' background: #f0fdf4;' : '' }}">
                                        <td style="padding: 8px 10px; font-weight: 700; color: var(--primary-color);">Week {{ $row['week'] }}</td>
                                        <td style="padding: 8px 10px; color: #666;">{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('M d, Y') : '—' }}</td>
                                        <td style="padding: 8px 10px; font-weight: 600;">{{ $row['tee_time'] ? \Carbon\Carbon::parse($row['tee_time'])->format('g:i A') : 'TBD' }}</td>
                                        <td style="padding: 8px 10px; color: #555; font-size: 0.9em;">
                                            {{ \App\Models\ScoringSetting::scoringTypes()[$row['scoring_type']] ?? ucfirst(str_replace('_', ' ', $row['scoring_type'])) }}
                                            <span style="color: #999; font-size: 0.85em;">&bull; {{ $row['holes'] === 'back_9' ? 'Back 9' : 'Front 9' }}</span>
                                        </td>
                                        <td style="padding: 8px 10px; color: #28a745; font-weight: 600;">{{ count($row['partners']) ? implode(' & ', $row['partners']) : '—' }}</td>
                                        <td style="padding: 8px 10px; color: #dc3545; font-weight: 600;">{{ count($row['opponents']) ? implode(', ', $row['opponents']) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>{{-- end by-player --}}
    @endif
</div>

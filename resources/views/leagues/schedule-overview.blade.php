<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Schedule Overview - {{ $league->name }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; padding: 20px;">
    <div style="max-width: 1400px; margin: 0 auto;">
        <a href="{{ route('admin.leagues.show', $league->id) }}" style="display: inline-block; color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 5px; margin-bottom: 20px;">← Back to League</a>

        @if(session('success'))
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 style="color: var(--primary-color); margin-bottom: 10px; font-size: 2em;">📅 Full Schedule</h1>
                    <p style="color: #666;">{{ $league->name }} - {{ $league->season }}</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <form action="{{ route('admin.leagues.addWeeks', $league->id) }}" method="POST" style="display: flex; gap: 8px; align-items: center;">
                        @csrf
                        <input type="number" name="additional_weeks" min="1" max="52" value="1" style="width: 60px; padding: 10px 8px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; text-align: center;">
                        <button type="submit" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; border: none; cursor: pointer; background: #28a745; color: white; white-space: nowrap;">
                            + Add Weeks
                        </button>
                    </form>
                    <form action="{{ route('admin.leagues.addEmptyWeeks', $league->id) }}" method="POST" style="display: flex; gap: 8px; align-items: center;">
                        @csrf
                        <input type="number" name="additional_weeks" min="1" max="52" value="1" style="width: 60px; padding: 10px 8px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; text-align: center;">
                        <button type="submit" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; border: none; cursor: pointer; background: #ffc107; color: #333; white-space: nowrap;">
                            + Add Empty Weeks
                        </button>
                    </form>
                    <a href="{{ route('admin.leagues.autoSchedule', $league->id) }}" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; text-decoration: none; background: var(--primary-color); color: white; display: inline-block;">
                        🤖 Regenerate Schedule
                    </a>
                </div>
            </div>

            @if($matchesByWeek->isEmpty())
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p style="font-size: 1.2em; margin-bottom: 15px;">No matches scheduled yet</p>
                    <a href="{{ route('admin.leagues.autoSchedule', $league->id) }}" style="padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; background: var(--primary-color); color: white; display: inline-block;">
                        Generate Schedule
                    </a>
                </div>
            @else
                <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #0c5460;">
                    <strong>📊 Schedule Summary:</strong><br>
                    Total Weeks: <strong>{{ $matchesByWeek->count() }}</strong> |
                    Total Matches: <strong>{{ $league->matches->count() }}</strong> |
                    Scheduled: <strong>{{ $league->matches->where('status', 'scheduled')->count() }}</strong> |
                    Completed: <strong>{{ $league->matches->where('status', 'completed')->count() }}</strong>
                </div>
            @endif
        </div>

        <!-- Schedule by Week -->
        <div id="weeks-container" data-league-id="{{ $league->id }}">
        @foreach($matchesByWeek as $weekNumber => $matches)
            @php
                $firstMatch = $matches->first();
                $weekMatchIds = $matches->pluck('id');
                $weekHasScores = \App\Models\MatchScore::whereIn('match_player_id', function ($q) use ($weekMatchIds) {
                    $q->select('id')->from('match_players')->whereIn('match_id', $weekMatchIds);
                })->exists();
            @endphp
            <div class="week-card" data-week="{{ $weekNumber }}" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; transition: opacity 0.15s;">
                <div onclick="toggleWeek(this.closest('.week-card').dataset.week)" style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px; padding: 25px 30px; border-bottom: 2px solid #f0f0f0; cursor: pointer; user-select: none;" onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='white'">
                    <h2 style="color: var(--primary-color); font-size: 1.5em; margin: 0; display: flex; align-items: center; gap: 10px;">
                        <span class="week-drag-handle" onmousedown="event.stopPropagation(); this.closest('.week-card').setAttribute('draggable','true');" onmouseup="this.closest('.week-card').setAttribute('draggable','false');" style="cursor: grab; color: #bbb; font-size: 0.7em; padding: 0 4px;" title="Drag to reorder week">⠿</span>
                        <span id="arrow-{{ $weekNumber }}" class="week-arrow" style="display: inline-block; transition: transform 0.2s ease; font-size: 0.7em;">▶</span>
                        <span class="week-label">Week {{ $weekNumber }}</span>
                        @if(isset($weekSegmentMap[$weekNumber]))
                            <span style="font-size: 0.55em; background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 10px; font-weight: 500;">{{ $weekSegmentMap[$weekNumber] }}</span>
                        @endif
                        <span style="font-size: 0.7em; color: #666; font-weight: normal;">
                            ({{ $matches->count() }} {{ $matches->count() == 1 ? 'match' : 'matches' }})
                        </span>
                    </h2>
                    <form action="{{ route('admin.leagues.updateWeekSettings', [$league->id, $weekNumber]) }}" method="POST" class="week-settings-form" data-week="{{ $weekNumber }}" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;" onclick="event.stopPropagation()">
                        @csrf
                        @method('PUT')
                        <input type="date" name="match_date" value="{{ $firstMatch->match_date->format('Y-m-d') }}" data-week="{{ $weekNumber }}" class="week-date-input" onchange="cascadeWeekDates(this.closest('.week-card').dataset.week)" style="padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.85em; font-family: inherit; cursor: pointer;">
                        <select name="holes" data-week="{{ $weekNumber }}" class="week-holes-input" onchange="cascadeWeekHoles(this.closest('.week-card').dataset.week)" style="padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.85em; font-family: inherit; cursor: pointer;">
                            <option value="front_9" {{ $firstMatch->holes === 'front_9' ? 'selected' : '' }}>Front 9</option>
                            <option value="back_9" {{ $firstMatch->holes === 'back_9' ? 'selected' : '' }}>Back 9</option>
                        </select>
                        <select name="scoring_type" onchange="saveWeekSettings(this.closest('.week-card').dataset.week)" style="padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.85em; font-family: inherit; cursor: pointer;">
                            @foreach($scoringTypes as $value => $label)
                                <option value="{{ $value }}" {{ $firstMatch->scoring_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label onclick="event.stopPropagation()" style="display: flex; align-items: center; gap: 4px; font-size: 0.8em; color: #555; cursor: pointer; white-space: nowrap;">
                            <input type="hidden" name="ride_with_opponent" value="0">
                            <input type="checkbox" name="ride_with_opponent" value="1" data-week="{{ $weekNumber }}" class="week-ride-input" {{ $firstMatch->ride_with_opponent ? 'checked' : '' }} style="cursor: pointer;" onchange="saveWeekSettings(this.closest('.week-card').dataset.week)">
                            Ride w/ Opponent
                        </label>
                        <button type="submit" style="padding: 6px 14px; border: none; border-radius: 6px; font-size: 0.85em; font-weight: 600; cursor: pointer; background: var(--primary-color); color: white; white-space: nowrap;">
                            Update Week
                        </button>
                    </form>
                    <a href="{{ route('admin.leagues.scores', $league->id) }}?week={{ $weekNumber }}" onclick="event.stopPropagation()" style="padding: 6px 14px; border-radius: 6px; font-size: 0.85em; font-weight: 600; text-decoration: none; background: #17a2b8; color: white; white-space: nowrap; display: inline-block;">
                        📝 Post
                    </a>
                    <a href="{{ route('admin.leagues.printScorecards', [$league->id, $weekNumber]) }}" target="_blank" onclick="event.stopPropagation()" style="padding: 6px 14px; border-radius: 6px; font-size: 0.85em; font-weight: 600; text-decoration: none; background: #6c757d; color: white; white-space: nowrap; display: inline-block;">
                        🖨️ Print
                    </a>
                    @if($weekHasScores)
                        <button type="button" class="week-lock-btn" data-week="{{ $weekNumber }}" data-locked="true" onclick="event.stopPropagation(); toggleWeekLock(this.closest('.week-card').dataset.week)" style="padding: 6px 14px; border: none; border-radius: 6px; font-size: 0.85em; font-weight: 600; cursor: pointer; background: #ffc107; color: #333; white-space: nowrap;" title="Week is locked because scores have been posted. Click to unlock editing.">
                            🔒 Locked
                        </button>
                    @endif
                    @if(!$weekHasScores)
                        <form action="{{ route('admin.leagues.deleteWeek', [$league->id, $weekNumber]) }}" method="POST" onclick="event.stopPropagation()" onsubmit="return confirm('Delete all matches in Week ' + this.closest('.week-card').dataset.week + '? This cannot be undone.');" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="padding: 6px 14px; border: none; border-radius: 6px; font-size: 0.85em; font-weight: 600; cursor: pointer; background: #dc3545; color: white; white-space: nowrap;">
                                🗑️ Delete
                            </button>
                        </form>
                    @endif
                </div>

                <div id="week-{{ $weekNumber }}" class="week-matches" data-league="{{ $league->id }}" data-week="{{ $weekNumber }}" style="display: none; padding: 10px 30px 15px;">
                    @php
                        // Get team names from first match for the header
                        $firstMatchInWeek = $matches->first();
                        $firstHome = $firstMatchInWeek->matchPlayers->where('position_in_pairing', '<=', 2);
                        $firstAway = $firstMatchInWeek->matchPlayers->where('position_in_pairing', '>', 2);
                        $weekHomeTeamName = $firstMatchInWeek->homeTeam->name
                            ?? ($firstHome->first() ? ($playerTeamNames[$firstHome->first()->player_id] ?? null) : null);
                        $weekAwayTeamName = $firstMatchInWeek->awayTeam->name
                            ?? ($firstAway->first() ? ($playerTeamNames[$firstAway->first()->player_id] ?? null) : null);
                    @endphp

                    {{-- Team name header - shown once above all matches --}}
                    @if($weekAwayTeamName || $weekHomeTeamName)
                        <div style="display: flex; align-items: center; gap: 12px; padding: 4px 0 8px; margin-left: 76px;">
                            <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; text-align: center;">
                                    <span style="font-size: 0.85em; color: #dc3545; font-weight: 700;">{{ $weekAwayTeamName ?? '' }}</span>
                                </div>
                                <span style="color: transparent; flex-shrink: 0;">vs</span>
                                <div style="flex: 1; text-align: center;">
                                    <span style="font-size: 0.85em; color: #28a745; font-weight: 700;">{{ $weekHomeTeamName ?? '' }}</span>
                                </div>
                            </div>
                            <span style="visibility: hidden; padding: 2px 10px; font-size: 0.8em;">Status</span>
                            <div style="visibility: hidden; display: flex; gap: 6px;">
                                <span style="padding: 4px 10px; font-size: 0.8em;">View</span>
                            </div>
                        </div>
                    @endif

                    @foreach($matches as $index => $match)
                        @php
                            // positions 1-2 = home (right/green), positions 3-4 = away (left/red)
                            $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                            $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                        @endphp
                        <div class="match-row" draggable="false" data-match-id="{{ $match->id }}" data-tee-time="{{ $match->tee_time }}" style="display: flex; align-items: center; gap: 12px; padding: 4px 0; {{ !$loop->last ? 'border-bottom: 1px solid #f0f0f0;' : '' }} flex-wrap: wrap; cursor: grab; transition: background 0.15s, opacity 0.15s;">
                            <span style="color: #bbb; cursor: grab; font-size: 1.1em; padding: 0 2px;" title="Drag to reorder">⠿</span>
                            <span class="tee-time-label" style="color: var(--primary-color); font-weight: 700; min-width: 55px; font-size: 0.9em;">
                                @if($match->tee_time)
                                    {{ \Carbon\Carbon::parse($match->tee_time)->format('g:i A') }}
                                @else
                                    #{{ $index + 1 }}
                                @endif
                            </span>
                            <div style="flex: 1; display: flex; align-items: center; gap: 8px; font-size: 0.95em;">
                                {{-- Away team (left side) --}}
                                <div style="flex: 1; text-align: right;">
                                    <div>
                                        @foreach($awayPlayers as $mp)
                                            @if(!$loop->first) <span style="color: #dc3545;">&</span> @endif
                                            @php $mpTeamId = $playerTeamIds[$mp->player_id] ?? null; @endphp
                                            <span class="player-slot" data-mp-id="{{ $mp->id }}" style="display: inline; position: relative;">
                                                <span class="normal-player-view" style="{{ $mp->substitute_player_id ? 'display:none;' : '' }}">
                                                    <select class="player-select" data-mp-id="{{ $mp->id }}" data-week="{{ $weekNumber }}" onchange="swapPlayer(this)" style="padding: 2px 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em; color: #dc3545; font-weight: 600; background: white; cursor: pointer; max-width: 140px;">
                                                        @foreach(($mpTeamId && isset($teamPlayersMap[$mpTeamId]) ? $teamPlayersMap[$mpTeamId] : collect()) as $p)
                                                            <option value="{{ $p->id }}" {{ $p->id === $mp->player_id ? 'selected' : '' }}>{{ $p->name }}</option>
                                                        @endforeach
                                                    </select><button type="button" onclick="showSubUI({{ $mp->id }})" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ff9800; border-radius: 3px; background: #fff3e0; color: #e65100; cursor: pointer; margin-left: 1px; vertical-align: middle;" title="Assign substitute">Sub</button>
                                                </span>
                                                <span class="sub-player-view" style="{{ $mp->substitute_player_id ? '' : 'display:none;' }}">
                                                    <span style="font-size: 0.85em; color: #e65100; font-weight: 600;">
                                                        <span class="sub-display-name">{{ $mp->substitutePlayer ? $mp->substitutePlayer->name : $mp->substitute_name }}</span>
                                                    </span>
                                                    <span style="font-size: 0.6em; color: #999;">(sub)</span>
                                                    <button type="button" onclick="removeSubstitute({{ $mp->id }})" style="padding: 0px 3px; font-size: 0.6em; border: 1px solid #dc3545; border-radius: 3px; background: #f8d7da; color: #721c24; cursor: pointer; margin-left: 1px; vertical-align: middle;" title="Remove substitute">✕</button>
                                                </span>
                                                <span class="sub-search-ui" style="display:none; position: relative;" data-mp-id="{{ $mp->id }}">
                                                    <input type="text" class="sub-search-input" placeholder="Search player..." data-mp-id="{{ $mp->id }}" oninput="searchSubstitute(this)" style="padding: 2px 4px; border: 1px solid #ff9800; border-radius: 4px; font-size: 0.8em; width: 130px;">
                                                    <div class="sub-search-results" style="display:none; position: absolute; background: white; border: 1px solid #ccc; border-radius: 4px; z-index: 1000; max-height: 200px; overflow-y: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.15); min-width: 180px; right: 0;"></div>
                                                    <button type="button" onclick="hideSubUI({{ $mp->id }})" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ccc; border-radius: 3px; background: #f0f0f0; cursor: pointer; margin-left: 1px;">Cancel</button>
                                                </span>
                                            </span><input type="number" step="0.1" class="handicap-input" data-mp-id="{{ $mp->id }}" value="{{ number_format($matchPlayerHandicaps[$mp->id] ?? $mp->handicap_index, 1) }}" onchange="updateHandicap(this)" style="width: 48px; padding: 2px 3px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.8em; color: var(--secondary-color); font-weight: 600; text-align: center;" title="Handicap Index (as of match date)">
                                        @endforeach
                                        @php
                                            $awayPositionsFilled = $awayPlayers->pluck('position_in_pairing')->toArray();
                                            $awayPositionsMissing = array_diff([3, 4], $awayPositionsFilled);
                                            $awayTeamIdForEmpty = $match->away_team_id;
                                            $awayDropdownPlayers = ($awayTeamIdForEmpty && isset($teamPlayersMap[$awayTeamIdForEmpty]))
                                                ? $teamPlayersMap[$awayTeamIdForEmpty]
                                                : $league->players->sortBy(['first_name', 'last_name']);
                                        @endphp
                                        @foreach($awayPositionsMissing as $pos)
                                            @if($awayPlayers->isNotEmpty() || $pos > 3) <span style="color: #dc3545;">&</span> @endif
                                            <select class="assign-player-select" data-match-id="{{ $match->id }}" data-position="{{ $pos }}" data-team-id="{{ $awayTeamIdForEmpty }}" onchange="assignPlayer(this)" style="padding: 2px 4px; border: 2px dashed #dc3545; border-radius: 4px; font-size: 0.9em; color: #999; background: #fff5f5; cursor: pointer; max-width: 140px;">
                                                <option value="">-- Select --</option>
                                                @foreach($awayDropdownPlayers as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        @endforeach
                                    </div>
                                </div>
                                <span style="color: #888; font-weight: 600; flex-shrink: 0;">vs</span>
                                {{-- Home team (right side) --}}
                                <div style="flex: 1; text-align: left;">
                                    <div>
                                        @foreach($homePlayers as $mp)
                                            @if(!$loop->first) <span style="color: #28a745;">&</span> @endif
                                            @php $mpTeamId = $playerTeamIds[$mp->player_id] ?? null; @endphp
                                            <span class="player-slot" data-mp-id="{{ $mp->id }}" style="display: inline; position: relative;">
                                                <span class="normal-player-view" style="{{ $mp->substitute_player_id ? 'display:none;' : '' }}">
                                                    <select class="player-select" data-mp-id="{{ $mp->id }}" data-week="{{ $weekNumber }}" onchange="swapPlayer(this)" style="padding: 2px 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em; color: #28a745; font-weight: 600; background: white; cursor: pointer; max-width: 140px;">
                                                        @foreach(($mpTeamId && isset($teamPlayersMap[$mpTeamId]) ? $teamPlayersMap[$mpTeamId] : collect()) as $p)
                                                            <option value="{{ $p->id }}" {{ $p->id === $mp->player_id ? 'selected' : '' }}>{{ $p->name }}</option>
                                                        @endforeach
                                                    </select><button type="button" onclick="showSubUI({{ $mp->id }})" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ff9800; border-radius: 3px; background: #fff3e0; color: #e65100; cursor: pointer; margin-left: 1px; vertical-align: middle;" title="Assign substitute">Sub</button>
                                                </span>
                                                <span class="sub-player-view" style="{{ $mp->substitute_player_id ? '' : 'display:none;' }}">
                                                    <span style="font-size: 0.85em; color: #e65100; font-weight: 600;">
                                                        <span class="sub-display-name">{{ $mp->substitutePlayer ? $mp->substitutePlayer->name : $mp->substitute_name }}</span>
                                                    </span>
                                                    <span style="font-size: 0.6em; color: #999;">(sub)</span>
                                                    <button type="button" onclick="removeSubstitute({{ $mp->id }})" style="padding: 0px 3px; font-size: 0.6em; border: 1px solid #dc3545; border-radius: 3px; background: #f8d7da; color: #721c24; cursor: pointer; margin-left: 1px; vertical-align: middle;" title="Remove substitute">✕</button>
                                                </span>
                                                <span class="sub-search-ui" style="display:none; position: relative;" data-mp-id="{{ $mp->id }}">
                                                    <input type="text" class="sub-search-input" placeholder="Search player..." data-mp-id="{{ $mp->id }}" oninput="searchSubstitute(this)" style="padding: 2px 4px; border: 1px solid #ff9800; border-radius: 4px; font-size: 0.8em; width: 130px;">
                                                    <div class="sub-search-results" style="display:none; position: absolute; background: white; border: 1px solid #ccc; border-radius: 4px; z-index: 1000; max-height: 200px; overflow-y: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.15); min-width: 180px; left: 0;"></div>
                                                    <button type="button" onclick="hideSubUI({{ $mp->id }})" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ccc; border-radius: 3px; background: #f0f0f0; cursor: pointer; margin-left: 1px;">Cancel</button>
                                                </span>
                                            </span><input type="number" step="0.1" class="handicap-input" data-mp-id="{{ $mp->id }}" value="{{ number_format($matchPlayerHandicaps[$mp->id] ?? $mp->handicap_index, 1) }}" onchange="updateHandicap(this)" style="width: 48px; padding: 2px 3px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.8em; color: var(--secondary-color); font-weight: 600; text-align: center;" title="Handicap Index (as of match date)">
                                        @endforeach
                                        @php
                                            $homePositionsFilled = $homePlayers->pluck('position_in_pairing')->toArray();
                                            $homePositionsMissing = array_diff([1, 2], $homePositionsFilled);
                                            $homeTeamIdForEmpty = $match->home_team_id;
                                            $homeDropdownPlayers = ($homeTeamIdForEmpty && isset($teamPlayersMap[$homeTeamIdForEmpty]))
                                                ? $teamPlayersMap[$homeTeamIdForEmpty]
                                                : $league->players->sortBy(['first_name', 'last_name']);
                                        @endphp
                                        @foreach($homePositionsMissing as $pos)
                                            @if($homePlayers->isNotEmpty() || $pos > 1) <span style="color: #28a745;">&</span> @endif
                                            <select class="assign-player-select" data-match-id="{{ $match->id }}" data-position="{{ $pos }}" data-team-id="{{ $homeTeamIdForEmpty }}" onchange="assignPlayer(this)" style="padding: 2px 4px; border: 2px dashed #28a745; border-radius: 4px; font-size: 0.9em; color: #999; background: #f5fff5; cursor: pointer; max-width: 140px;">
                                                <option value="">-- Select --</option>
                                                @foreach($homeDropdownPlayers as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <span style="padding: 2px 10px; border-radius: 10px; font-size: 0.8em; font-weight: 600;
                                background: {{ $match->status === 'completed' ? '#d4edda' : ($match->status === 'in_progress' ? '#fff3cd' : '#cce5ff') }};
                                color: {{ $match->status === 'completed' ? '#155724' : ($match->status === 'in_progress' ? '#856404' : '#004085') }};">
                                {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                            </span>
                            <div style="display: flex; gap: 6px;">
                                <a href="{{ route('admin.matches.show', $match->id) }}" style="padding: 4px 10px; border-radius: 4px; font-size: 0.8em; font-weight: 600; text-decoration: none; background: var(--primary-color); color: white;">View</a>
                                @if($match->status === 'in_progress')
                                    <a href="{{ route('admin.matches.scoreEntry', $match->id) }}" style="padding: 4px 10px; border-radius: 4px; font-size: 0.8em; font-weight: 600; text-decoration: none; background: #28a745; color: white;">Scores</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        </div>{{-- end weeks-container --}}
    </div>

    <script>
        var leaguePlayers = @json($league->players->sortBy(['first_name', 'last_name'])->values()->map(fn($p) => ['id' => $p->id, 'name' => $p->name]));
        var teamPlayersMap = @json(collect($teamPlayersMap)->map(fn($players) => $players->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values()));

        function toggleWeek(week) {
            var content = document.getElementById('week-' + week);
            var arrow = document.getElementById('arrow-' + week);
            if (content.style.display === 'none') {
                content.style.display = 'block';
                arrow.style.transform = 'rotate(90deg)';
            } else {
                content.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            }
        }

        function cascadeWeekDates(changedWeek) {
            var changedInput = document.querySelector('.week-date-input[data-week="' + changedWeek + '"]');
            if (!changedInput || !changedInput.value) return;

            var baseDate = new Date(changedInput.value + 'T00:00:00');
            var allInputs = document.querySelectorAll('.week-date-input');
            var weekInputs = {};
            allInputs.forEach(function(input) {
                weekInputs[parseInt(input.dataset.week)] = input;
            });

            var weeks = Object.keys(weekInputs).map(Number).sort(function(a, b) { return a - b; });
            var daysOffset = 7;
            for (var i = 0; i < weeks.length; i++) {
                if (weeks[i] <= changedWeek) continue;
                var newDate = new Date(baseDate);
                newDate.setDate(newDate.getDate() + daysOffset);
                weekInputs[weeks[i]].value = newDate.toISOString().split('T')[0];
                daysOffset += 7;
            }

            saveWeekSettings(changedWeek);
        }

        function cascadeWeekHoles(changedWeek) {
            var changedSelect = document.querySelector('.week-holes-input[data-week="' + changedWeek + '"]');
            if (!changedSelect) return;

            var allSelects = document.querySelectorAll('.week-holes-input');
            var weekSelects = {};
            allSelects.forEach(function(sel) {
                weekSelects[parseInt(sel.dataset.week)] = sel;
            });

            var weeks = Object.keys(weekSelects).map(Number).sort(function(a, b) { return a - b; });
            var current = changedSelect.value;
            for (var i = 0; i < weeks.length; i++) {
                if (weeks[i] <= changedWeek) continue;
                current = (current === 'front_9') ? 'back_9' : 'front_9';
                weekSelects[weeks[i]].value = current;
            }

            saveWeekSettings(changedWeek);
        }

        function saveWeekSettings(weekNumber) {
            var dateInput = document.querySelector('.week-date-input[data-week="' + weekNumber + '"]');
            var holesSelect = document.querySelector('.week-holes-input[data-week="' + weekNumber + '"]');
            var rideCheckbox = document.querySelector('.week-ride-input[data-week="' + weekNumber + '"]');
            var form = dateInput.closest('form');
            var scoringSelect = form.querySelector('select[name="scoring_type"]');
            var baseUrl = @json(url('/'));
            var leagueId = document.querySelector('.week-matches').dataset.league;

            fetch(baseUrl + '/admin/leagues/' + leagueId + '/week/' + weekNumber + '/settings', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    match_date: dateInput.value,
                    holes: holesSelect.value,
                    scoring_type: scoringSelect.value,
                    ride_with_opponent: rideCheckbox && rideCheckbox.checked ? 1 : 0
                })
            });
        }

        // Player swap via dropdown
        function swapPlayer(select) {
            var mpId = select.dataset.mpId;
            var newPlayerId = select.value;
            var weekNumber = select.dataset.week;
            var baseUrl = @json(url('/'));

            select.disabled = true;
            fetch(baseUrl + '/admin/match-players/' + mpId + '/swap', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ new_player_id: parseInt(newPlayerId) })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                select.disabled = false;
                if (!data.success) {
                    alert('Failed to swap player');
                    location.reload();
                } else if (data.handicap_index !== undefined) {
                    var hInput = select.parentElement.querySelector('.handicap-input[data-mp-id="' + mpId + '"]');
                    if (!hInput) {
                        hInput = select.nextElementSibling;
                    }
                    if (hInput && hInput.classList.contains('handicap-input')) {
                        hInput.value = parseFloat(data.handicap_index).toFixed(1);
                    }
                }
                highlightDuplicates(weekNumber);
            })
            .catch(function() {
                select.disabled = false;
                alert('Error swapping player');
                location.reload();
            });

            // Immediately highlight duplicates on client side
            highlightDuplicates(weekNumber);
        }

        // Update match player handicap
        function updateHandicap(input) {
            var mpId = input.dataset.mpId;
            var newValue = parseFloat(input.value);
            if (isNaN(newValue)) return;
            var baseUrl = @json(url('/'));

            input.disabled = true;
            input.style.borderColor = '#ffc107';
            fetch(baseUrl + '/admin/match-players/' + mpId + '/handicap', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ handicap_index: newValue })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                input.disabled = false;
                if (data.success) {
                    input.style.borderColor = '#28a745';
                    setTimeout(function() { input.style.borderColor = '#ccc'; }, 1500);
                } else {
                    input.style.borderColor = '#dc3545';
                    alert('Failed to update handicap');
                }
            })
            .catch(function() {
                input.disabled = false;
                input.style.borderColor = '#dc3545';
                alert('Error updating handicap');
            });
        }

        // Highlight duplicate players within a week
        function highlightDuplicates(weekNumber) {
            var container = document.getElementById('week-' + weekNumber);
            if (!container) return;

            var playerSelects = container.querySelectorAll('.player-select');
            var assignSelects = container.querySelectorAll('.assign-player-select');
            var valueCounts = {};

            // Count occurrences of each player across both select types
            playerSelects.forEach(function(sel) {
                var val = sel.value;
                if (val) valueCounts[val] = (valueCounts[val] || 0) + 1;
            });
            assignSelects.forEach(function(sel) {
                var val = sel.value;
                if (val) valueCounts[val] = (valueCounts[val] || 0) + 1;
            });

            // Apply or remove highlight on player-selects
            playerSelects.forEach(function(sel) {
                if (sel.value && valueCounts[sel.value] > 1) {
                    sel.style.background = '#fff3cd';
                    sel.style.borderColor = '#ffc107';
                    sel.style.boxShadow = '0 0 4px rgba(255, 193, 7, 0.5)';
                } else {
                    sel.style.background = 'white';
                    sel.style.borderColor = '#ccc';
                    sel.style.boxShadow = 'none';
                }
            });

            // Apply or remove highlight on assign-player-selects
            assignSelects.forEach(function(sel) {
                if (sel.value && valueCounts[sel.value] > 1) {
                    sel.style.background = '#fff3cd';
                    sel.style.borderColor = '#ffc107';
                    sel.style.boxShadow = '0 0 4px rgba(255, 193, 7, 0.5)';
                } else {
                    // Restore dashed default style
                    var pos = parseInt(sel.dataset.position);
                    var isHome = pos <= 2;
                    sel.style.background = isHome ? '#f5fff5' : '#fff5f5';
                    sel.style.borderColor = isHome ? '#28a745' : '#dc3545';
                    sel.style.boxShadow = 'none';
                }
            });
        }

        // --- Substitute player functions ---
        function showSubUI(mpId) {
            var slot = document.querySelector('.player-slot[data-mp-id="' + mpId + '"]');
            if (!slot) return;
            slot.querySelector('.normal-player-view').style.display = 'none';
            slot.querySelector('.sub-search-ui').style.display = 'inline';
            var input = slot.querySelector('.sub-search-input');
            input.value = '';
            input.focus();
        }

        function hideSubUI(mpId) {
            var slot = document.querySelector('.player-slot[data-mp-id="' + mpId + '"]');
            if (!slot) return;
            slot.querySelector('.sub-search-ui').style.display = 'none';
            slot.querySelector('.sub-search-results').style.display = 'none';
            slot.querySelector('.normal-player-view').style.display = '';
        }

        var subSearchTimeout = null;
        function searchSubstitute(input) {
            clearTimeout(subSearchTimeout);
            var mpId = input.dataset.mpId;
            var query = input.value.trim();
            var resultsDiv = input.parentElement.querySelector('.sub-search-results');

            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            subSearchTimeout = setTimeout(function() {
                var baseUrl = @json(url('/'));
                fetch(baseUrl + '/admin/players/search?q=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                })
                .then(function(res) { return res.json(); })
                .then(function(players) {
                    resultsDiv.innerHTML = '';

                    players.forEach(function(p) {
                        var item = document.createElement('div');
                        item.style.cssText = 'padding: 6px 10px; cursor: pointer; font-size: 0.85em; border-bottom: 1px solid #eee;';
                        item.textContent = p.name;
                        item.onmouseover = function() { this.style.background = '#e8f4f8'; };
                        item.onmouseout = function() { this.style.background = 'white'; };
                        item.onclick = function() { assignSubstitute(mpId, p.id, null, null); };
                        resultsDiv.appendChild(item);
                    });

                    // "Create new player" option
                    var parts = query.split(/\s+/);
                    var createItem = document.createElement('div');
                    createItem.style.cssText = 'padding: 6px 10px; cursor: pointer; font-size: 0.85em; color: #28a745; font-weight: 600; border-top: 2px solid #eee;';
                    var firstName = parts[0] || '';
                    var lastName = parts.slice(1).join(' ') || '';
                    createItem.textContent = '+ Create "' + query + '" as new player';
                    createItem.onmouseover = function() { this.style.background = '#d4edda'; };
                    createItem.onmouseout = function() { this.style.background = 'white'; };
                    createItem.onclick = function() { assignSubstitute(mpId, null, firstName, lastName); };
                    resultsDiv.appendChild(createItem);

                    resultsDiv.style.display = 'block';
                });
            }, 300);
        }

        function assignSubstitute(mpId, substitutePlayerId, firstName, lastName) {
            var baseUrl = @json(url('/'));
            var body = {};
            if (substitutePlayerId) {
                body.substitute_player_id = substitutePlayerId;
            } else {
                body.first_name = firstName;
                body.last_name = lastName;
            }

            fetch(baseUrl + '/admin/match-players/' + mpId + '/substitute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    var slot = document.querySelector('.player-slot[data-mp-id="' + mpId + '"]');
                    slot.querySelector('.sub-search-ui').style.display = 'none';
                    slot.querySelector('.normal-player-view').style.display = 'none';
                    slot.querySelector('.sub-player-view').style.display = '';
                    slot.querySelector('.sub-display-name').textContent = data.substitute_name;

                    var hInput = slot.parentElement.querySelector('.handicap-input[data-mp-id="' + mpId + '"]');
                    if (hInput) hInput.value = parseFloat(data.handicap_index).toFixed(1);
                } else {
                    alert('Failed to assign substitute: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function() { alert('Error assigning substitute'); });
        }

        function removeSubstitute(mpId) {
            if (!confirm('Remove substitute and restore original player?')) return;

            var baseUrl = @json(url('/'));
            fetch(baseUrl + '/admin/match-players/' + mpId + '/substitute', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    var slot = document.querySelector('.player-slot[data-mp-id="' + mpId + '"]');
                    slot.querySelector('.sub-player-view').style.display = 'none';
                    slot.querySelector('.normal-player-view').style.display = '';

                    var hInput = slot.parentElement.querySelector('.handicap-input[data-mp-id="' + mpId + '"]');
                    if (hInput) hInput.value = parseFloat(data.handicap_index).toFixed(1);
                } else {
                    alert('Failed to remove substitute');
                }
            })
            .catch(function() { alert('Error removing substitute'); });
        }

        // Assign player to an empty match position (AJAX, no reload)
        function assignPlayer(select) {
            var matchId = select.dataset.matchId;
            var position = parseInt(select.dataset.position);
            var playerId = select.value;
            var weekContainer = select.closest('.week-matches');
            var weekNumber = weekContainer ? weekContainer.dataset.week : '';

            if (!playerId) return;

            // Immediately highlight duplicates before AJAX
            if (weekNumber) highlightDuplicates(weekNumber);

            select.disabled = true;
            select.style.opacity = '0.5';
            var baseUrl = @json(url('/'));

            fetch(baseUrl + '/admin/matches/' + matchId + '/assign-player', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    player_id: parseInt(playerId),
                    position_in_pairing: position
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    var isHome = position <= 2;
                    var color = isHome ? '#28a745' : '#dc3545';
                    var mpId = data.match_player_id;
                    var resultsAlign = isHome ? 'left' : 'right';

                    // Build player options for swap select
                    var players = (data.team_id && teamPlayersMap[data.team_id]) ? teamPlayersMap[data.team_id] : leaguePlayers;
                    var optionsHtml = '';
                    players.forEach(function(p) {
                        var sel = p.id == data.player_id ? ' selected' : '';
                        optionsHtml += '<option value="' + p.id + '"' + sel + '>' + p.name + '</option>';
                    });

                    // Build full player slot + handicap input
                    var html =
                        '<span class="player-slot" data-mp-id="' + mpId + '" style="display: inline; position: relative;">' +
                            '<span class="normal-player-view">' +
                                '<select class="player-select" data-mp-id="' + mpId + '" data-week="' + weekNumber + '" onchange="swapPlayer(this)" style="padding: 2px 4px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em; color: ' + color + '; font-weight: 600; background: white; cursor: pointer; max-width: 140px;">' + optionsHtml + '</select>' +
                                '<button type="button" onclick="showSubUI(' + mpId + ')" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ff9800; border-radius: 3px; background: #fff3e0; color: #e65100; cursor: pointer; margin-left: 1px; vertical-align: middle;" title="Assign substitute">Sub</button>' +
                            '</span>' +
                            '<span class="sub-player-view" style="display:none;">' +
                                '<span style="font-size: 0.85em; color: #e65100; font-weight: 600;"><span class="sub-display-name"></span></span>' +
                                '<span style="font-size: 0.6em; color: #999;">(sub)</span>' +
                                '<button type="button" onclick="removeSubstitute(' + mpId + ')" style="padding: 0px 3px; font-size: 0.6em; border: 1px solid #dc3545; border-radius: 3px; background: #f8d7da; color: #721c24; cursor: pointer; margin-left: 1px; vertical-align: middle;" title="Remove substitute">✕</button>' +
                            '</span>' +
                            '<span class="sub-search-ui" style="display:none; position: relative;" data-mp-id="' + mpId + '">' +
                                '<input type="text" class="sub-search-input" placeholder="Search player..." data-mp-id="' + mpId + '" oninput="searchSubstitute(this)" style="padding: 2px 4px; border: 1px solid #ff9800; border-radius: 4px; font-size: 0.8em; width: 130px;">' +
                                '<div class="sub-search-results" style="display:none; position: absolute; background: white; border: 1px solid #ccc; border-radius: 4px; z-index: 1000; max-height: 200px; overflow-y: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.15); min-width: 180px; ' + resultsAlign + ': 0;"></div>' +
                                '<button type="button" onclick="hideSubUI(' + mpId + ')" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ccc; border-radius: 3px; background: #f0f0f0; cursor: pointer; margin-left: 1px;">Cancel</button>' +
                            '</span>' +
                        '</span>' +
                        '<input type="number" step="0.1" class="handicap-input" data-mp-id="' + mpId + '" value="' + parseFloat(data.handicap_index).toFixed(1) + '" onchange="updateHandicap(this)" style="width: 48px; padding: 2px 3px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.8em; color: var(--secondary-color); font-weight: 600; text-align: center;" title="Handicap Index (as of match date)">';

                    // Replace the assign-select with the new player slot
                    var temp = document.createElement('span');
                    temp.innerHTML = html;
                    var parent = select.parentNode;
                    while (temp.firstChild) {
                        parent.insertBefore(temp.firstChild, select);
                    }
                    parent.removeChild(select);

                    // Run duplicate highlight
                    if (weekNumber) highlightDuplicates(weekNumber);
                } else {
                    select.disabled = false;
                    select.style.opacity = '1';
                    alert('Failed to assign player: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function() {
                select.disabled = false;
                select.style.opacity = '1';
                alert('Error assigning player');
            });
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sub-search-ui')) {
                document.querySelectorAll('.sub-search-results').forEach(function(d) {
                    d.style.display = 'none';
                });
            }
        });

        // Lock/unlock week editing
        function toggleWeekLock(weekNumber) {
            var btn = document.querySelector('.week-lock-btn[data-week="' + weekNumber + '"]');
            if (!btn) return;
            var isLocked = btn.dataset.locked === 'true';
            if (isLocked) {
                btn.dataset.locked = 'false';
                btn.innerHTML = '🔓 Unlocked';
                btn.style.background = '#28a745';
                btn.style.color = 'white';
                btn.title = 'Week is unlocked for editing. Click to lock.';
                setWeekEditable(weekNumber, true);
            } else {
                btn.dataset.locked = 'true';
                btn.innerHTML = '🔒 Locked';
                btn.style.background = '#ffc107';
                btn.style.color = '#333';
                btn.title = 'Week is locked because scores have been posted. Click to unlock editing.';
                setWeekEditable(weekNumber, false);
            }
        }

        function setWeekEditable(weekNumber, editable) {
            // Week settings form (date, holes, scoring type, update button)
            var settingsForm = document.querySelector('.week-settings-form[data-week="' + weekNumber + '"]');
            if (settingsForm) {
                settingsForm.querySelectorAll('input, select, button').forEach(function(el) {
                    el.disabled = !editable;
                    el.style.opacity = editable ? '1' : '0.5';
                    el.style.pointerEvents = editable ? '' : 'none';
                });
            }

            // Week matches content (player selects, sub buttons, handicap inputs, assign selects)
            var container = document.getElementById('week-' + weekNumber);
            if (!container) return;

            container.querySelectorAll('.player-select').forEach(function(sel) {
                sel.disabled = !editable;
                sel.style.opacity = editable ? '1' : '0.5';
                sel.style.pointerEvents = editable ? '' : 'none';
            });

            container.querySelectorAll('.assign-player-select').forEach(function(sel) {
                sel.disabled = !editable;
                sel.style.opacity = editable ? '1' : '0.5';
                sel.style.pointerEvents = editable ? '' : 'none';
            });

            container.querySelectorAll('.handicap-input').forEach(function(inp) {
                inp.disabled = !editable;
                inp.style.opacity = editable ? '1' : '0.5';
                inp.style.pointerEvents = editable ? '' : 'none';
            });

            // Sub buttons and remove-sub buttons
            container.querySelectorAll('button').forEach(function(btn) {
                btn.disabled = !editable;
                btn.style.opacity = editable ? '1' : '0.5';
                btn.style.pointerEvents = editable ? '' : 'none';
            });

            // Drag handles
            container.querySelectorAll('[title="Drag to reorder"]').forEach(function(handle) {
                handle.style.opacity = editable ? '1' : '0.3';
                handle.style.pointerEvents = editable ? '' : 'none';
            });
        }

        // Run duplicate check on page load for all weeks, and lock scored weeks
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.week-matches').forEach(function(container) {
                highlightDuplicates(container.dataset.week);
            });

            // Lock weeks that have scores
            document.querySelectorAll('.week-lock-btn[data-locked="true"]').forEach(function(btn) {
                setWeekEditable(btn.dataset.week, false);
            });
        });

        // Drag and drop reordering
        (function() {
            var dragRow = null;

            // Disable draggable on all rows by default; only enable from handle
            document.querySelectorAll('.match-row').forEach(function(row) {
                row.setAttribute('draggable', 'false');
            });
            document.querySelectorAll('[title="Drag to reorder"]').forEach(function(handle) {
                handle.addEventListener('mousedown', function() {
                    var row = handle.closest('.match-row');
                    if (row) row.setAttribute('draggable', 'true');
                });
                handle.addEventListener('mouseup', function() {
                    var row = handle.closest('.match-row');
                    if (row) row.setAttribute('draggable', 'false');
                });
            });

            document.addEventListener('dragstart', function(e) {
                var row = e.target.closest('.match-row');
                if (!row || row.getAttribute('draggable') !== 'true') return;
                dragRow = row;
                row.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', row.dataset.matchId);
            });

            document.addEventListener('dragend', function(e) {
                var row = e.target.closest('.match-row');
                if (row) {
                    row.style.opacity = '1';
                    row.setAttribute('draggable', 'false');
                }
                document.querySelectorAll('.match-row').forEach(function(r) {
                    r.style.borderTop = '';
                    r.style.borderBottom = '';
                });
                dragRow = null;
            });

            document.addEventListener('dragover', function(e) {
                var row = e.target.closest('.match-row');
                if (!row || !dragRow || row === dragRow) return;
                // Only allow reorder within same week
                if (row.parentElement !== dragRow.parentElement) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                document.querySelectorAll('.match-row').forEach(function(r) {
                    r.style.borderTop = '';
                    r.style.borderBottom = '';
                });

                var rect = row.getBoundingClientRect();
                var mid = rect.top + rect.height / 2;
                if (e.clientY < mid) {
                    row.style.borderTop = '2px solid var(--primary-color)';
                } else {
                    row.style.borderBottom = '2px solid var(--primary-color)';
                }
            });

            document.addEventListener('drop', function(e) {
                var targetRow = e.target.closest('.match-row');
                if (!targetRow || !dragRow || targetRow === dragRow) return;
                if (targetRow.parentElement !== dragRow.parentElement) return;
                e.preventDefault();

                var container = targetRow.parentElement;
                var rect = targetRow.getBoundingClientRect();
                var mid = rect.top + rect.height / 2;

                if (e.clientY < mid) {
                    container.insertBefore(dragRow, targetRow);
                } else {
                    container.insertBefore(dragRow, targetRow.nextSibling);
                }

                // Reassign tee time labels and save
                var rows = container.querySelectorAll('.match-row');
                var teeTimes = [];
                rows.forEach(function(r) { teeTimes.push(r.dataset.teeTime); });

                // Sort tee times chronologically so earliest stays on top
                teeTimes.sort();

                var matchIds = [];
                rows.forEach(function(r, i) {
                    r.dataset.teeTime = teeTimes[i];
                    var label = r.querySelector('.tee-time-label');
                    if (label && teeTimes[i]) {
                        var parts = teeTimes[i].split(':');
                        var h = parseInt(parts[0]);
                        var m = parts[1];
                        var ampm = h >= 12 ? 'PM' : 'AM';
                        if (h > 12) h -= 12;
                        if (h === 0) h = 12;
                        label.textContent = h + ':' + m + ' ' + ampm;
                    }
                    matchIds.push(parseInt(r.dataset.matchId));

                    // Reset borders
                    r.style.borderTop = '';
                    r.style.borderBottom = '';
                });

                // Save to server
                var leagueId = container.dataset.league;
                var weekNum = container.dataset.week;
                var baseUrl = @json(url('/'));
                fetch(baseUrl + '/admin/leagues/' + leagueId + '/week/' + weekNum + '/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ match_ids: matchIds })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data.success) {
                        alert('Failed to save tee time order');
                        location.reload();
                    }
                })
                .catch(function() {
                    alert('Error saving tee time order');
                    location.reload();
                });
            });
        })();

        // Week-level drag and drop reordering
        (function() {
            var dragWeek = null;

            document.addEventListener('dragstart', function(e) {
                var card = e.target.closest('.week-card');
                if (!card || card.getAttribute('draggable') !== 'true') return;
                dragWeek = card;
                card.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', 'week-' + card.dataset.week);
            });

            document.addEventListener('dragend', function(e) {
                var card = e.target.closest('.week-card');
                if (card) {
                    card.style.opacity = '1';
                    card.setAttribute('draggable', 'false');
                }
                document.querySelectorAll('.week-card').forEach(function(c) {
                    c.style.borderTop = '';
                    c.style.borderBottom = '';
                });
                dragWeek = null;
            });

            document.addEventListener('dragover', function(e) {
                if (!dragWeek) return;
                var card = e.target.closest('.week-card');
                if (!card || card === dragWeek) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                document.querySelectorAll('.week-card').forEach(function(c) {
                    c.style.borderTop = '';
                    c.style.borderBottom = '';
                });

                var rect = card.getBoundingClientRect();
                var mid = rect.top + rect.height / 2;
                if (e.clientY < mid) {
                    card.style.borderTop = '3px solid var(--primary-color)';
                } else {
                    card.style.borderBottom = '3px solid var(--primary-color)';
                }
            });

            document.addEventListener('drop', function(e) {
                var targetCard = e.target.closest('.week-card');
                if (!targetCard || !dragWeek || targetCard === dragWeek) return;
                e.preventDefault();

                var container = document.getElementById('weeks-container');
                var rect = targetCard.getBoundingClientRect();
                var mid = rect.top + rect.height / 2;

                if (e.clientY < mid) {
                    container.insertBefore(dragWeek, targetCard);
                } else {
                    container.insertBefore(dragWeek, targetCard.nextSibling);
                }

                // Clear drop indicators
                document.querySelectorAll('.week-card').forEach(function(c) {
                    c.style.borderTop = '';
                    c.style.borderBottom = '';
                });

                // Collect the old week numbers in their new DOM order
                var cards = container.querySelectorAll('.week-card');
                var weekOrder = [];
                cards.forEach(function(c) {
                    weekOrder.push(parseInt(c.dataset.week));
                });

                // Update labels and data attributes to reflect new numbering
                cards.forEach(function(c, i) {
                    var newWeek = i + 1;
                    var oldWeek = parseInt(c.dataset.week);
                    c.dataset.week = newWeek;

                    // Update week label text
                    var label = c.querySelector('.week-label');
                    if (label) label.textContent = 'Week ' + newWeek;

                    // Update arrow ID
                    var arrow = c.querySelector('.week-arrow');
                    if (arrow) arrow.id = 'arrow-' + newWeek;

                    // Update week-matches container
                    var weekMatches = c.querySelector('.week-matches');
                    if (weekMatches) {
                        weekMatches.id = 'week-' + newWeek;
                        weekMatches.dataset.week = newWeek;
                    }

                    // Update week settings form
                    var settingsForm = c.querySelector('.week-settings-form');
                    if (settingsForm) settingsForm.dataset.week = newWeek;

                    // Update date and holes inputs
                    var dateInput = c.querySelector('.week-date-input');
                    if (dateInput) dateInput.dataset.week = newWeek;
                    var holesInput = c.querySelector('.week-holes-input');
                    if (holesInput) holesInput.dataset.week = newWeek;

                    // Update lock button
                    var lockBtn = c.querySelector('.week-lock-btn');
                    if (lockBtn) lockBtn.dataset.week = newWeek;

                    // Update form action URLs with new week number
                    var settingsFormEl = c.querySelector('.week-settings-form');
                    if (settingsFormEl) {
                        settingsFormEl.action = settingsFormEl.action.replace(/\/week\/\d+\/settings/, '/week/' + newWeek + '/settings');
                    }

                    // Update delete form action (URL: /week/{week_number})
                    var deleteForms = c.querySelectorAll('form[method="POST"]');
                    deleteForms.forEach(function(f) {
                        if (f.action.match(/\/week\/\d+$/)) {
                            f.action = f.action.replace(/\/week\/\d+$/, '/week/' + newWeek);
                        }
                    });

                    // Update print link (URL: /week/{week_number}/scorecards)
                    var printLink = c.querySelector('a[href*="scorecards"]');
                    if (printLink) {
                        printLink.href = printLink.href.replace(/\/week\/\d+\/scorecards/, '/week/' + newWeek + '/scorecards');
                    }

                    // Update player-select data-week attributes
                    c.querySelectorAll('.player-select').forEach(function(sel) {
                        sel.dataset.week = newWeek;
                    });
                });

                // Save to server
                var leagueId = container.dataset.leagueId;
                var baseUrl = @json(url('/'));
                fetch(baseUrl + '/admin/leagues/' + leagueId + '/reorder-weeks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ week_order: weekOrder })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data.success) {
                        alert('Failed to reorder weeks');
                        location.reload();
                    }
                })
                .catch(function() {
                    alert('Error reordering weeks');
                    location.reload();
                });
            });
        })();
    </script>
</body>
</html>

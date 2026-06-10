@php
    $weekLocked = $weekMatches->contains(fn($m) => $m->status === 'completed');
@endphp

<div style="display: flex; gap: 16px; margin-bottom: 16px; font-size: 0.9em; color: #666;">
    <span><strong>Date:</strong> {{ $firstMatch->match_date->format('M d, Y') }}</span>
    <span><strong>Holes:</strong> {{ $firstMatch->holes === 'front_9' ? 'Front 9' : 'Back 9' }}</span>
    <span><strong>Matches:</strong> {{ $weekMatches->count() }}</span>
    @if($firstMatch->ride_with_opponent)
        <span style="color: #0c5460; background: #d1ecf1; padding: 2px 10px; border-radius: 10px; font-weight: 600; font-size: 0.85em;">Ride w/ Opponent</span>
    @endif
    @if($weekLocked)
        <span style="margin-left: auto; color: #856404; background: #fff3cd; padding: 2px 10px; border-radius: 10px; font-weight: 600; font-size: 0.85em;">Scores Locked</span>
    @endif
</div>

{{-- Team name header --}}
@php
    $modalAwayTeamName = $firstMatch->awayTeam->name ?? '';
    $modalHomeTeamName = $firstMatch->homeTeam->name ?? '';
@endphp
@if($modalAwayTeamName || $modalHomeTeamName)
    <div style="display: flex; align-items: center; gap: 12px; padding: 0 0 8px; margin-left: 67px;">
        <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
            <div style="flex: 1; text-align: right;">
                <span style="font-size: 0.85em; color: #dc3545; font-weight: 700;">{{ $modalAwayTeamName }}</span>
            </div>
            <span style="color: transparent; flex-shrink: 0;">vs</span>
            <div style="flex: 1; text-align: left;">
                <span style="font-size: 0.85em; color: #28a745; font-weight: 700;">{{ $modalHomeTeamName }}</span>
            </div>
        </div>
    </div>
@endif

@foreach($weekMatches as $idx => $match)
    @php
        $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2)->sortBy('position_in_pairing');
        $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2)->sortBy('position_in_pairing');
    @endphp
    <div class="modal-match-row">
        <div class="tee-time">
            @if($match->tee_time)
                {{ \Carbon\Carbon::parse($match->tee_time)->format('g:i A') }}
            @else
                #{{ $idx + 1 }}
            @endif
        </div>
        <div class="players-area">
            {{-- Away side --}}
            <div class="side away">
                @foreach($awayPlayers as $mp)
                    @php $mpTeamId = $match->away_team_id; @endphp
                    <div class="modal-player-row">
                        @if($weekLocked)
                            <span style="font-weight: 600; color: #dc3545; font-size: 0.9em;">
                                {{ $mp->substitute_player_id ? ($mp->substitutePlayer ? $mp->substitutePlayer->name : $mp->substitute_name) : ($mp->player->name ?? 'TBD') }}
                                @if($mp->substitute_player_id)
                                    <span class="modal-sub-label">(sub)</span>
                                @endif
                            </span>
                            <span style="color: var(--secondary-color); font-weight: 600; font-size: 0.8em; min-width: 48px; text-align: center;">{{ number_format($mp->handicap_index, 1) }}</span>
                        @else
                            <span class="player-slot" data-mp-id="{{ $mp->id }}">
                                <span class="normal-player-view" style="{{ $mp->substitute_player_id ? 'display:none;' : '' }}">
                                    <select class="modal-player-select player-select" data-mp-id="{{ $mp->id }}" data-week="{{ $weekNumber }}" onchange="modalSwapPlayer(this)" style="color: #dc3545;">
                                        @foreach(($mpTeamId && isset($modalTeamPlayers[$mpTeamId]) ? $modalTeamPlayers[$mpTeamId] : collect()) as $p)
                                            <option value="{{ $p->id }}" {{ $p->id === $mp->player_id ? 'selected' : '' }}>{{ $p->name }}</option>
                                        @endforeach
                                    </select><button type="button" onclick="modalShowSubUI({{ $mp->id }})" class="modal-sub-btn" title="Assign substitute">Sub</button>
                                </span>
                                <span class="sub-player-view" style="{{ $mp->substitute_player_id ? '' : 'display:none;' }}">
                                    <span class="modal-sub-name sub-display-name">{{ $mp->substitutePlayer ? $mp->substitutePlayer->name : $mp->substitute_name }}</span>
                                    <span class="modal-sub-label">(sub)</span>
                                    <button type="button" onclick="modalRemoveSubstitute({{ $mp->id }})" class="modal-sub-remove" title="Remove substitute">&times;</button>
                                </span>
                                <span class="modal-sub-search sub-search-ui" data-mp-id="{{ $mp->id }}">
                                    <input type="text" class="sub-search-input" placeholder="Search player..." data-mp-id="{{ $mp->id }}" oninput="modalSearchSubstitute(this)">
                                    <div class="results sub-search-results"></div>
                                    <button type="button" onclick="modalHideSubUI({{ $mp->id }})" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ccc; border-radius: 3px; background: #f0f0f0; cursor: pointer; margin-left: 1px;">Cancel</button>
                                </span>
                            </span>
                            <input type="number" step="0.1" class="modal-handicap-input handicap-input" data-mp-id="{{ $mp->id }}" value="{{ number_format($mp->handicap_index, 1) }}" onchange="modalUpdateHandicap(this)">
                        @endif
                    </div>
                @endforeach
                @if($awayPlayers->isEmpty())
                    <span style="color: #dc3545; font-weight: 600;">TBD</span>
                @endif
            </div>
            <span class="vs">vs</span>
            {{-- Home side --}}
            <div class="side home">
                @foreach($homePlayers as $mp)
                    @php $mpTeamId = $match->home_team_id; @endphp
                    <div class="modal-player-row">
                        @if($weekLocked)
                            <span style="font-weight: 600; color: #28a745; font-size: 0.9em;">
                                {{ $mp->substitute_player_id ? ($mp->substitutePlayer ? $mp->substitutePlayer->name : $mp->substitute_name) : ($mp->player->name ?? 'TBD') }}
                                @if($mp->substitute_player_id)
                                    <span class="modal-sub-label">(sub)</span>
                                @endif
                            </span>
                            <span style="color: var(--secondary-color); font-weight: 600; font-size: 0.8em; min-width: 48px; text-align: center;">{{ number_format($mp->handicap_index, 1) }}</span>
                        @else
                            <span class="player-slot" data-mp-id="{{ $mp->id }}">
                                <span class="normal-player-view" style="{{ $mp->substitute_player_id ? 'display:none;' : '' }}">
                                    <select class="modal-player-select player-select" data-mp-id="{{ $mp->id }}" data-week="{{ $weekNumber }}" onchange="modalSwapPlayer(this)" style="color: #28a745;">
                                        @foreach(($mpTeamId && isset($modalTeamPlayers[$mpTeamId]) ? $modalTeamPlayers[$mpTeamId] : collect()) as $p)
                                            <option value="{{ $p->id }}" {{ $p->id === $mp->player_id ? 'selected' : '' }}>{{ $p->name }}</option>
                                        @endforeach
                                    </select><button type="button" onclick="modalShowSubUI({{ $mp->id }})" class="modal-sub-btn" title="Assign substitute">Sub</button>
                                </span>
                                <span class="sub-player-view" style="{{ $mp->substitute_player_id ? '' : 'display:none;' }}">
                                    <span class="modal-sub-name sub-display-name">{{ $mp->substitutePlayer ? $mp->substitutePlayer->name : $mp->substitute_name }}</span>
                                    <span class="modal-sub-label">(sub)</span>
                                    <button type="button" onclick="modalRemoveSubstitute({{ $mp->id }})" class="modal-sub-remove" title="Remove substitute">&times;</button>
                                </span>
                                <span class="modal-sub-search sub-search-ui" data-mp-id="{{ $mp->id }}">
                                    <input type="text" class="sub-search-input" placeholder="Search player..." data-mp-id="{{ $mp->id }}" oninput="modalSearchSubstitute(this)">
                                    <div class="results sub-search-results"></div>
                                    <button type="button" onclick="modalHideSubUI({{ $mp->id }})" style="padding: 1px 4px; font-size: 0.65em; border: 1px solid #ccc; border-radius: 3px; background: #f0f0f0; cursor: pointer; margin-left: 1px;">Cancel</button>
                                </span>
                            </span>
                            <input type="number" step="0.1" class="modal-handicap-input handicap-input" data-mp-id="{{ $mp->id }}" value="{{ number_format($mp->handicap_index, 1) }}" onchange="modalUpdateHandicap(this)">
                        @endif
                    </div>
                @endforeach
                @if($homePlayers->isEmpty())
                    <span style="color: #28a745; font-weight: 600;">TBD</span>
                @endif
            </div>
        </div>
    </div>
@endforeach

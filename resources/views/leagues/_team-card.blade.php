<div class="team-card" data-team-id="{{ $team->id }}">
    <div class="team-card-header">
        <div class="team-name-area">
            <div class="team-name-display" id="name-display-{{ $team->id }}" style="display: flex; align-items: center; gap: 6px;">
                <span class="team-name">{{ $team->name }}</span>
                <button type="button" class="edit-name-btn" onclick="showEditName({{ $team->id }})">✏️</button>
            </div>
            <form action="{{ route('admin.teams.update', $team->id) }}" method="POST" class="edit-name-form" id="name-form-{{ $team->id }}">
                @csrf
                @method('PUT')
                <input type="text" name="name" value="{{ $team->name }}" class="edit-name-input" required maxlength="255">
                <button type="submit" class="btn btn-success btn-small">Save</button>
                <button type="button" class="btn btn-secondary btn-small" onclick="cancelEditName({{ $team->id }})">Cancel</button>
            </form>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
            <span class="team-player-count" style="font-size: 0.8em; color: #666;">{{ $team->players->count() }} players</span>
            <form action="{{ route('admin.teams.destroy', $team->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete team {{ $team->name }}? This cannot be undone!');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-small">Delete</button>
            </form>
        </div>
    </div>

    <div class="team-meta">
        <span>{{ $team->wins }}-{{ $team->losses }}-{{ $team->ties }} ({{ $team->totalPoints() }} pts)</span>
        @if($team->captain)
            <span>Capt: {{ $team->captain->name }}</span>
        @endif
    </div>

    <div class="team-drop-zone" id="team-zone-{{ $team->id }}" data-team-id="{{ $team->id }}">
        @if($team->players->isEmpty())
            <div class="team-empty" id="team-empty-{{ $team->id }}">Drop players here</div>
        @else
            @foreach($team->players->sortBy('first_name') as $player)
                <div class="team-player-item" draggable="true" data-player-id="{{ $player->id }}" data-player-name="{{ $player->name }}" data-player-handicap="{{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}" data-team-id="{{ $team->id }}">
                    <div class="player-info">
                        <div class="player-name">
                            {{ $player->name }}
                            @if($team->captain_id == $player->id)
                                <span class="captain-badge">CAPTAIN</span>
                            @endif
                        </div>
                        <div class="player-handicap">HI: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}</div>
                    </div>
                    <button class="remove-btn" title="Remove from team" data-player-id="{{ $player->id }}" data-team-id="{{ $team->id }}">✕</button>
                </div>
            @endforeach
        @endif
    </div>

    <div class="team-footer">
        <form action="{{ route('admin.teams.update', $team->id) }}" method="POST" style="display: flex; align-items: center; gap: 8px; width: 100%;">
            @csrf
            @method('PUT')
            <span style="font-size: 0.8em; color: #666; white-space: nowrap;">Capt:</span>
            <select name="captain_id" id="captain-select-{{ $team->id }}">
                <option value="">None</option>
                @foreach($team->players as $player)
                    <option value="{{ $player->id }}" {{ $team->captain_id == $player->id ? 'selected' : '' }}>
                        {{ $player->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-small">Set</button>
        </form>
    </div>
</div>

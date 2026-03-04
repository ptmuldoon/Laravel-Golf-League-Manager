<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Teams - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1800px; margin: 0 auto; }
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
        .back-link:hover { background: rgba(255,255,255,0.3); }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 { color: var(--primary-color); font-size: 2em; margin-bottom: 10px; }
        .subtitle { color: #666; }
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
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--secondary-color); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 6px 12px; font-size: 0.85em; }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .toast.show { transform: translateX(0); }
        .toast-success { background: #28a745; }
        .toast-error { background: #dc3545; }
        .success-message {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        /* Three-column layout: teams | players | teams */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 340px 1fr;
            gap: 20px;
            align-items: start;
        }

        .teams-column { display: flex; flex-direction: column; gap: 16px; }

        /* Center panel: available players */
        .players-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
        }
        .panel-header {
            padding: 20px;
            border-bottom: 2px solid #e8e9ff;
        }
        .panel-title {
            font-size: 1.2em;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .panel-count {
            font-size: 0.7em;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .search-box {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e8e9ff;
            border-radius: 8px;
            font-size: 0.9em;
            outline: none;
            transition: border-color 0.3s;
        }
        .search-box:focus { border-color: var(--primary-color); }
        .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            min-height: 200px;
        }
        .panel-body.drag-over {
            background: rgba(var(--primary-rgb), 0.08);
        }

        .player-card {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            background: var(--primary-light);
            border-radius: 8px;
            border: 2px solid #e8e9ff;
            margin-bottom: 6px;
            cursor: grab;
            transition: all 0.2s ease;
            user-select: none;
        }
        .player-card:active { cursor: grabbing; }
        .player-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.15);
        }
        .player-card.selected {
            border-color: var(--primary-color);
            background: rgba(var(--primary-rgb), 0.15);
            box-shadow: 0 0 0 1px var(--primary-color);
        }
        .player-card.dragging { opacity: 0.4; transform: scale(0.95); }
        .player-card.moving { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .player-info { flex: 1; min-width: 0; }
        .player-name {
            font-weight: 600;
            font-size: 0.95em;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .player-handicap { color: #666; font-size: 0.8em; margin-top: 1px; }

        .selection-toolbar {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: rgba(var(--primary-rgb), 0.1);
            border-top: 1px solid #e8e9ff;
            gap: 6px;
        }
        .selection-toolbar.visible { display: flex; }
        .selection-toolbar .sel-info {
            font-size: 0.82em;
            font-weight: 600;
            color: var(--primary-color);
            white-space: nowrap;
        }
        .selection-toolbar .sel-actions { display: flex; gap: 4px; }
        .sel-btn {
            padding: 5px 10px;
            border-radius: 6px;
            border: none;
            font-size: 0.78em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .sel-btn-primary { background: var(--primary-color); color: white; }
        .sel-btn-primary:hover { opacity: 0.9; }
        .sel-btn-secondary { background: #e8e9ff; color: #555; }
        .sel-btn-secondary:hover { background: #d8d9ef; }

        .drag-hint {
            text-align: center;
            padding: 8px;
            color: #999;
            font-size: 0.8em;
            border-top: 1px solid #e8e9ff;
        }
        .drag-badge {
            position: fixed;
            pointer-events: none;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: 700;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        /* Team cards */
        .create-team-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.15em;
            color: var(--primary-color);
            margin-bottom: 12px;
        }
        .create-form-row {
            display: flex;
            gap: 8px;
            align-items: end;
        }
        .form-group { margin-bottom: 0; flex: 1; }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            font-size: 0.85em;
        }
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9em;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .team-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .team-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        .team-name-area { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 0; }
        .team-name {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .edit-name-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.55em;
            color: #999;
            padding: 2px 5px;
            border-radius: 4px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .edit-name-btn:hover { color: var(--primary-color); background: #f0f2ff; }
        .edit-name-form { display: none; align-items: center; gap: 6px; }
        .edit-name-form.active { display: flex; }
        .edit-name-input {
            font-size: 0.9em;
            padding: 5px 8px;
            border: 2px solid var(--primary-color);
            border-radius: 6px;
            font-weight: 600;
            color: var(--primary-color);
            width: 140px;
            font-family: inherit;
        }
        .edit-name-input:focus { outline: none; box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2); }
        .team-meta {
            padding: 8px 16px;
            display: flex;
            gap: 12px;
            font-size: 0.8em;
            color: #666;
            border-bottom: 1px solid #f0f0f0;
            flex-wrap: wrap;
        }
        .team-drop-zone {
            padding: 10px 12px;
            min-height: 60px;
            transition: background 0.2s;
        }
        .team-drop-zone.drag-over {
            background: rgba(var(--primary-rgb), 0.08);
        }
        .team-player-item {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            background: var(--primary-light);
            border-radius: 6px;
            margin-bottom: 5px;
            cursor: grab;
            user-select: none;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .team-player-item:active { cursor: grabbing; }
        .team-player-item:hover { border-color: #ddd; }
        .team-player-item.dragging { opacity: 0.4; }
        .team-player-item.moving { animation: fadeIn 0.3s ease; }
        .team-player-item .player-info { flex: 1; }
        .captain-badge {
            background: #ffd700;
            color: #333;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.65em;
            font-weight: 600;
            margin-left: 6px;
        }
        .remove-btn {
            background: none;
            border: none;
            color: #ccc;
            cursor: pointer;
            font-size: 1em;
            padding: 3px 6px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .remove-btn:hover { color: #dc3545; background: #fff5f5; }
        .team-empty {
            text-align: center;
            padding: 15px;
            color: #bbb;
            font-size: 0.85em;
        }
        .team-footer {
            padding: 10px 16px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .team-footer select {
            flex: 1;
            padding: 6px 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.8em;
            font-family: inherit;
        }
        .team-footer select:focus { outline: none; border-color: var(--primary-color); }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .column-label {
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 0.85em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        @media (max-width: 1100px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
            .main-layout > .teams-column:first-child { order: 2; }
            .main-layout > .players-panel { order: 1; }
            .main-layout > .teams-column:last-child { order: 3; }
            .players-panel {
                position: static;
                max-height: 400px;
            }
            h1 { font-size: 1.5em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">← Back to League</a>

        @if(session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif

        <div class="header">
            <h1>Manage Teams</h1>
            <p class="subtitle">{{ $league->name }} - {{ $league->season }}</p>
        </div>

        @if(isset($segments) && $segments->isNotEmpty())
            <div style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="font-weight: 600; color: #333; margin-bottom: 10px;">Select Segment</div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    @foreach($segments as $segment)
                        <a href="{{ route('admin.leagues.teams.manage', $league->id) }}?segment={{ $segment->id }}"
                           class="btn {{ isset($selectedSegment) && $selectedSegment->id == $segment->id ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.9em;">
                            {{ $segment->name }} (Wk {{ $segment->start_week }}-{{ $segment->end_week }})
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($allPlayers->isEmpty())
            <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <strong>No Players Assigned to League</strong><br>
                You need to assign players to this league before you can add them to teams.
                <a href="{{ route('admin.leagues.players.manage', $league->id) }}" style="color: #721c24; text-decoration: underline; font-weight: 600; margin-left: 5px;">Manage League Players</a>
            </div>
        @endif

        <!-- Create Team (full width above the 3-column layout) -->
        <div class="create-team-section" style="margin-bottom: 20px;">
            <h2 class="section-title">Create New Team</h2>
            <form action="{{ route('admin.teams.store') }}" method="POST">
                @csrf
                <input type="hidden" name="league_id" value="{{ $league->id }}">
                @if(isset($selectedSegment) && $selectedSegment)
                    <input type="hidden" name="league_segment_id" value="{{ $selectedSegment->id }}">
                @endif
                <div class="create-form-row">
                    <div class="form-group">
                        <label for="name">Team Name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g., Eagles">
                    </div>
                    <div class="form-group">
                        <label for="captain_id">Captain</label>
                        <select id="captain_id" name="captain_id">
                            <option value="">None</option>
                            @foreach($allPlayers as $player)
                                <option value="{{ $player->id }}">{{ $player->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="white-space: nowrap;">Create Team</button>
                </div>
            </form>
        </div>

        @php
            $playersOnTeams = $league->teams->flatMap(fn($t) => $t->players->pluck('id'))->unique();
            $unassignedPlayers = $allPlayers->filter(fn($p) => !$playersOnTeams->contains($p->id))->sortBy('first_name');
            $teams = $league->teams->values();
            $leftTeams = $teams->filter(fn($t, $i) => $i % 2 === 0);
            $rightTeams = $teams->filter(fn($t, $i) => $i % 2 === 1);
        @endphp

        <div class="main-layout">
            <!-- Left: Teams (even-indexed) -->
            <div class="teams-column">
                @if($teams->isEmpty())
                    <div class="empty-state">
                        <p style="font-size: 1.1em; margin-bottom: 8px;">No teams yet</p>
                        <p>Create your first team above</p>
                    </div>
                @else
                    @foreach($leftTeams as $team)
                        @include('leagues._team-card', ['team' => $team])
                    @endforeach
                @endif
            </div>

            <!-- Center: Available Players -->
            <div class="players-panel">
                <div class="panel-header">
                    <div class="panel-title">
                        Available Players
                        <span class="panel-count" id="availableCount">{{ $unassignedPlayers->count() }}</span>
                    </div>
                    <input type="text" class="search-box" id="searchAvailable" placeholder="Search players...">
                </div>
                <div class="panel-body" id="availablePanel">
                    @forelse($unassignedPlayers as $player)
                        <div class="player-card" draggable="true" data-player-id="{{ $player->id }}" data-player-name="{{ $player->name }}" data-player-handicap="{{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}">
                            <div class="player-info">
                                <div class="player-name">{{ $player->name }}</div>
                                <div class="player-handicap">HI: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="team-empty" id="availableEmpty">All players are assigned to teams</div>
                    @endforelse
                </div>
                <div class="selection-toolbar" id="availableToolbar">
                    <span class="sel-info"><span id="availableSelCount">0</span> selected</span>
                    <div class="sel-actions">
                        <button class="sel-btn sel-btn-secondary" onclick="clearSelection()">Clear</button>
                        <button class="sel-btn sel-btn-secondary" onclick="selectAllVisible()">All</button>
                    </div>
                </div>
                <div class="drag-hint" id="availableHint">Drag players to a team</div>
            </div>

            <!-- Right: Teams (odd-indexed) -->
            <div class="teams-column">
                @foreach($rightTeams as $team)
                    @include('leagues._team-card', ['team' => $team])
                @endforeach
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        const leagueId = {{ $league->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const availablePanel = document.getElementById('availablePanel');
        let isProcessing = false;

        // ── Toast ──
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast toast-' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // ── Team name editing ──
        function showEditName(teamId) {
            document.getElementById(`name-display-${teamId}`).style.display = 'none';
            const form = document.getElementById(`name-form-${teamId}`);
            form.classList.add('active');
            form.querySelector('input[name="name"]').focus();
        }
        function cancelEditName(teamId) {
            document.getElementById(`name-display-${teamId}`).style.display = 'flex';
            document.getElementById(`name-form-${teamId}`).classList.remove('active');
        }

        // ── Selection (available panel only) ──

        function getSelectedCards() {
            return Array.from(availablePanel.querySelectorAll('.player-card.selected'));
        }

        function toggleSelect(card, e) {
            if (isProcessing) return;
            if (e && e.shiftKey && availablePanel._lastSelected) {
                const cards = Array.from(availablePanel.querySelectorAll('.player-card'));
                const visible = cards.filter(c => c.style.display !== 'none');
                const lastIdx = visible.indexOf(availablePanel._lastSelected);
                const curIdx = visible.indexOf(card);
                if (lastIdx !== -1 && curIdx !== -1) {
                    const start = Math.min(lastIdx, curIdx);
                    const end = Math.max(lastIdx, curIdx);
                    for (let i = start; i <= end; i++) visible[i].classList.add('selected');
                }
            } else {
                card.classList.toggle('selected');
            }
            availablePanel._lastSelected = card;
            updateSelectionUI();
        }

        function updateSelectionUI() {
            const count = getSelectedCards().length;
            const toolbar = document.getElementById('availableToolbar');
            const hint = document.getElementById('availableHint');
            document.getElementById('availableSelCount').textContent = count;
            if (count > 0) {
                toolbar.classList.add('visible');
                if (hint) hint.style.display = 'none';
            } else {
                toolbar.classList.remove('visible');
                if (hint) hint.style.display = '';
            }
        }

        function clearSelection() {
            availablePanel.querySelectorAll('.player-card.selected').forEach(c => c.classList.remove('selected'));
            updateSelectionUI();
        }

        function selectAllVisible() {
            availablePanel.querySelectorAll('.player-card').forEach(c => {
                if (c.style.display !== 'none') c.classList.add('selected');
            });
            updateSelectionUI();
        }

        availablePanel.addEventListener('click', (e) => {
            const card = e.target.closest('.player-card');
            if (!card || !availablePanel.contains(card)) return;
            toggleSelect(card, e);
        });

        // ── Update available count ──
        function updateAvailableCount() {
            const count = availablePanel.querySelectorAll('.player-card').length;
            document.getElementById('availableCount').textContent = count;
            let empty = document.getElementById('availableEmpty');
            if (count === 0 && !empty) {
                availablePanel.insertAdjacentHTML('beforeend', '<div class="team-empty" id="availableEmpty">All players are assigned to teams</div>');
            } else if (count > 0 && empty) {
                empty.remove();
            }
        }

        // ── API calls ──

        async function apiAddToTeam(teamId, playerIds) {
            const response = await fetch(`/admin/teams/${teamId}/players`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ player_ids: playerIds }),
                redirect: 'manual'
            });
            return response.ok || response.type === 'opaqueredirect' || response.status === 302 || response.status === 0;
        }

        async function apiRemoveFromTeam(teamId, playerId) {
            const response = await fetch(`/admin/teams/${teamId}/players/${playerId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ _method: 'DELETE' }),
                redirect: 'manual'
            });
            return response.ok || response.type === 'opaqueredirect' || response.status === 302 || response.status === 0;
        }

        // ── Element creation ──

        function createTeamPlayerEl(playerId, playerName, playerHandicap, teamId) {
            const div = document.createElement('div');
            div.className = 'team-player-item moving';
            div.draggable = true;
            div.dataset.playerId = playerId;
            div.dataset.playerName = playerName;
            div.dataset.playerHandicap = playerHandicap;
            div.dataset.teamId = teamId;
            div.innerHTML = `
                <div class="player-info">
                    <div class="player-name">${playerName}</div>
                    <div class="player-handicap">HI: ${playerHandicap}</div>
                </div>
                <button class="remove-btn" title="Remove from team" data-player-id="${playerId}" data-team-id="${teamId}">✕</button>
            `;
            setTimeout(() => div.classList.remove('moving'), 300);
            return div;
        }

        function createAvailablePlayerEl(playerId, playerName, playerHandicap) {
            const div = document.createElement('div');
            div.className = 'player-card moving';
            div.draggable = true;
            div.dataset.playerId = playerId;
            div.dataset.playerName = playerName;
            div.dataset.playerHandicap = playerHandicap;
            div.innerHTML = `
                <div class="player-info">
                    <div class="player-name">${playerName}</div>
                    <div class="player-handicap">HI: ${playerHandicap}</div>
                </div>
            `;
            setTimeout(() => div.classList.remove('moving'), 300);
            return div;
        }

        // ── Move operations ──

        async function addPlayersToTeam(teamId, cards) {
            if (isProcessing || cards.length === 0) return;
            isProcessing = true;
            const playerIds = cards.map(c => c.dataset.playerId);
            const zone = document.getElementById(`team-zone-${teamId}`);
            try {
                const success = await apiAddToTeam(teamId, playerIds);
                if (success) {
                    const emptyEl = document.getElementById(`team-empty-${teamId}`);
                    if (emptyEl) emptyEl.remove();
                    cards.forEach(card => {
                        const el = createTeamPlayerEl(card.dataset.playerId, card.dataset.playerName, card.dataset.playerHandicap, teamId);
                        zone.appendChild(el);
                        card.remove();
                    });
                    sortZone(zone);
                    updateAvailableCount();
                    updateSelectionUI();
                    updateTeamPlayerCount(teamId);
                    showToast(`${cards.length} player${cards.length > 1 ? 's' : ''} added to team`);
                } else {
                    showToast('Failed to add players to team', 'error');
                }
            } catch { showToast('Network error. Please try again.', 'error'); }
            finally { isProcessing = false; }
        }

        async function removePlayerFromTeam(teamId, playerId, playerName, playerHandicap, element) {
            if (isProcessing) return;
            isProcessing = true;
            try {
                const success = await apiRemoveFromTeam(teamId, playerId);
                if (success) {
                    element.remove();
                    const emptyEl = document.getElementById('availableEmpty');
                    if (emptyEl) emptyEl.remove();
                    const el = createAvailablePlayerEl(playerId, playerName, playerHandicap);
                    availablePanel.appendChild(el);
                    sortAvailablePanel();
                    updateAvailableCount();
                    updateTeamPlayerCount(teamId);
                    const zone = document.getElementById(`team-zone-${teamId}`);
                    if (zone.querySelectorAll('.team-player-item').length === 0) {
                        zone.innerHTML = `<div class="team-empty" id="team-empty-${teamId}">Drop players here</div>`;
                    }
                    showToast(`${playerName} removed from team`);
                } else { showToast('Failed to remove player', 'error'); }
            } catch { showToast('Network error. Please try again.', 'error'); }
            finally { isProcessing = false; }
        }

        async function movePlayerBetweenTeams(fromTeamId, toTeamId, item) {
            if (isProcessing) return;
            isProcessing = true;
            try {
                const removeOk = await apiRemoveFromTeam(fromTeamId, item.dataset.playerId);
                if (!removeOk) { showToast('Failed to move player', 'error'); isProcessing = false; return; }
                const addOk = await apiAddToTeam(toTeamId, [item.dataset.playerId]);
                if (addOk) {
                    item.remove();
                    const zone = document.getElementById(`team-zone-${toTeamId}`);
                    const emptyEl = document.getElementById(`team-empty-${toTeamId}`);
                    if (emptyEl) emptyEl.remove();
                    const el = createTeamPlayerEl(item.dataset.playerId, item.dataset.playerName, item.dataset.playerHandicap, toTeamId);
                    zone.appendChild(el);
                    sortZone(zone);
                    const oldZone = document.getElementById(`team-zone-${fromTeamId}`);
                    if (oldZone.querySelectorAll('.team-player-item').length === 0) {
                        oldZone.innerHTML = `<div class="team-empty" id="team-empty-${fromTeamId}">Drop players here</div>`;
                    }
                    updateTeamPlayerCount(fromTeamId);
                    updateTeamPlayerCount(toTeamId);
                    showToast(`${item.dataset.playerName} moved to team`);
                } else { showToast('Failed to move player', 'error'); }
            } catch { showToast('Network error', 'error'); }
            finally { isProcessing = false; }
        }

        function updateTeamPlayerCount(teamId) {
            const zone = document.getElementById(`team-zone-${teamId}`);
            const count = zone.querySelectorAll('.team-player-item').length;
            const card = zone.closest('.team-card');
            const countSpan = card.querySelector('.team-player-count');
            if (countSpan) countSpan.textContent = count + ' players';
        }

        function sortZone(zone) {
            const items = Array.from(zone.querySelectorAll('.team-player-item'));
            items.sort((a, b) => a.dataset.playerName.localeCompare(b.dataset.playerName));
            items.forEach(item => zone.appendChild(item));
        }

        function sortAvailablePanel() {
            const cards = Array.from(availablePanel.querySelectorAll('.player-card'));
            cards.sort((a, b) => a.dataset.playerName.localeCompare(b.dataset.playerName));
            cards.forEach(card => availablePanel.appendChild(card));
        }

        // ── Remove button click ──
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-btn');
            if (!btn) return;
            e.stopPropagation();
            const item = btn.closest('.team-player-item');
            if (!item) return;
            if (!confirm(`Remove ${item.dataset.playerName} from team?`)) return;
            removePlayerFromTeam(item.dataset.teamId, item.dataset.playerId, item.dataset.playerName, item.dataset.playerHandicap, item);
        });

        // ── Drag and Drop ──

        let draggedCards = [];
        let dragSource = null;

        document.addEventListener('dragstart', (e) => {
            const availCard = e.target.closest('#availablePanel .player-card');
            if (availCard) {
                const selected = getSelectedCards();
                if (availCard.classList.contains('selected') && selected.length > 1) {
                    draggedCards = selected;
                } else {
                    availablePanel.querySelectorAll('.player-card.selected').forEach(c => c.classList.remove('selected'));
                    availCard.classList.add('selected');
                    draggedCards = [availCard];
                    updateSelectionUI();
                }
                dragSource = 'available';
                draggedCards.forEach(c => c.classList.add('dragging'));
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', 'players');
                if (draggedCards.length > 1) {
                    const badge = document.createElement('div');
                    badge.className = 'drag-badge';
                    badge.textContent = draggedCards.length;
                    badge.id = 'dragBadge';
                    document.body.appendChild(badge);
                    const img = new Image();
                    img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
                    e.dataTransfer.setDragImage(img, 0, 0);
                }
                return;
            }
            const teamItem = e.target.closest('.team-player-item');
            if (teamItem) {
                draggedCards = [teamItem];
                dragSource = teamItem.dataset.teamId;
                teamItem.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', 'player');
                return;
            }
        });

        document.addEventListener('drag', (e) => {
            const badge = document.getElementById('dragBadge');
            if (badge && e.clientX && e.clientY) {
                badge.style.left = (e.clientX + 15) + 'px';
                badge.style.top = (e.clientY - 13) + 'px';
            }
        });

        document.addEventListener('dragend', () => {
            draggedCards.forEach(c => c.classList.remove('dragging'));
            draggedCards = [];
            dragSource = null;
            const badge = document.getElementById('dragBadge');
            if (badge) badge.remove();
            document.querySelectorAll('.team-drop-zone').forEach(z => z.classList.remove('drag-over'));
            availablePanel.classList.remove('drag-over');
        });

        // Team drop zones
        document.querySelectorAll('.team-drop-zone').forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                zone.classList.add('drag-over');
            });
            zone.addEventListener('dragleave', (e) => {
                if (!zone.contains(e.relatedTarget)) zone.classList.remove('drag-over');
            });
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                if (draggedCards.length === 0) return;
                const teamId = zone.dataset.teamId;
                if (dragSource === 'available') {
                    addPlayersToTeam(teamId, draggedCards);
                } else if (dragSource !== teamId) {
                    movePlayerBetweenTeams(dragSource, teamId, draggedCards[0]);
                }
            });
        });

        // Available panel as drop zone
        availablePanel.addEventListener('dragover', (e) => {
            if (dragSource && dragSource !== 'available') {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                availablePanel.classList.add('drag-over');
            }
        });
        availablePanel.addEventListener('dragleave', (e) => {
            if (!availablePanel.contains(e.relatedTarget)) availablePanel.classList.remove('drag-over');
        });
        availablePanel.addEventListener('drop', (e) => {
            e.preventDefault();
            availablePanel.classList.remove('drag-over');
            if (draggedCards.length === 0 || dragSource === 'available') return;
            const card = draggedCards[0];
            removePlayerFromTeam(card.dataset.teamId, card.dataset.playerId, card.dataset.playerName, card.dataset.playerHandicap, card);
        });

        // ── Search ──
        document.getElementById('searchAvailable').addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            availablePanel.querySelectorAll('.player-card').forEach(card => {
                card.style.display = (!q || card.dataset.playerName.toLowerCase().includes(q)) ? '' : 'none';
            });
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Players - {{ $league->name }}</title>
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
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: var(--primary-color);
            font-size: 2em;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
        }
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
        .toast.show {
            transform: translateX(0);
        }
        .toast-success { background: #28a745; }
        .toast-error { background: #dc3545; }

        .panels-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            min-height: 500px;
        }
        .panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .panel-header {
            padding: 20px 25px;
            border-bottom: 2px solid #e8e9ff;
        }
        .panel-title {
            font-size: 1.3em;
            color: var(--primary-color);
            margin-bottom: 12px;
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
            padding: 10px 15px;
            border: 2px solid #e8e9ff;
            border-radius: 8px;
            font-size: 0.95em;
            outline: none;
            transition: border-color 0.3s;
        }
        .search-box:focus {
            border-color: var(--primary-color);
        }
        .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            min-height: 300px;
            transition: background 0.2s;
        }
        .panel-body.drag-over {
            background: rgba(var(--primary-rgb), 0.08);
        }
        .player-card {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: var(--primary-light);
            border-radius: 8px;
            border: 2px solid #e8e9ff;
            margin-bottom: 8px;
            cursor: grab;
            transition: all 0.2s ease;
            user-select: none;
        }
        .player-card:active {
            cursor: grabbing;
        }
        .player-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.15);
        }
        .player-card.selected {
            border-color: var(--primary-color);
            background: rgba(var(--primary-rgb), 0.15);
            box-shadow: 0 0 0 1px var(--primary-color);
        }
        .player-card.dragging {
            opacity: 0.4;
            transform: scale(0.95);
        }
        .player-card.moving {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .player-info {
            flex: 1;
            min-width: 0;
        }
        .player-name {
            font-weight: 600;
            font-size: 1em;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .player-handicap {
            color: #666;
            font-size: 0.85em;
            margin-top: 2px;
        }
        .move-btn {
            background: none;
            border: 2px solid #ccc;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #666;
            font-size: 1.1em;
            transition: all 0.2s;
            flex-shrink: 0;
            margin-left: 10px;
        }
        .move-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--primary-light);
        }
        .move-btn.loading {
            pointer-events: none;
            opacity: 0.5;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .empty-state .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .selection-toolbar {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: rgba(var(--primary-rgb), 0.1);
            border-top: 1px solid #e8e9ff;
            gap: 8px;
        }
        .selection-toolbar.visible {
            display: flex;
        }
        .selection-toolbar .sel-info {
            font-size: 0.85em;
            font-weight: 600;
            color: var(--primary-color);
            white-space: nowrap;
        }
        .selection-toolbar .sel-actions {
            display: flex;
            gap: 6px;
        }
        .sel-btn {
            padding: 6px 14px;
            border-radius: 6px;
            border: none;
            font-size: 0.82em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .sel-btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .sel-btn-primary:hover {
            opacity: 0.9;
        }
        .sel-btn-secondary {
            background: #e8e9ff;
            color: #555;
        }
        .sel-btn-secondary:hover {
            background: #d8d9ef;
        }
        .drag-hint {
            text-align: center;
            padding: 10px;
            color: #999;
            font-size: 0.85em;
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
        .success-message {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        .error-message {
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .panels-container {
                grid-template-columns: 1fr;
            }
            .panel-body {
                min-height: 200px;
                max-height: 400px;
            }
            h1 { font-size: 1.5em; }
            .header { padding: 20px; }
            .drag-hint { display: none; }
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

        @if($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="header">
            <h1>Manage League Players</h1>
            <p class="subtitle">{{ $league->name }} - {{ $league->season }}</p>
        </div>

        <div class="panels-container">
            <!-- Left Panel: Available Players -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        Available Players
                        <span class="panel-count" id="availableCount">{{ $availablePlayers->count() }}</span>
                    </div>
                    <input type="text" class="search-box" id="searchAvailable" placeholder="Search available players...">
                </div>
                <div class="panel-body" id="availablePanel" data-panel="available">
                    @forelse($availablePlayers->sortBy('first_name') as $player)
                        <div class="player-card" draggable="true" data-player-id="{{ $player->id }}" data-player-name="{{ $player->name }}" data-player-handicap="{{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}">
                            <div class="player-info">
                                <div class="player-name">{{ $player->name }}</div>
                                <div class="player-handicap">Handicap: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}</div>
                            </div>
                            <button class="move-btn" title="Add to league">→</button>
                        </div>
                    @empty
                        <div class="empty-state" id="availableEmpty">
                            <div class="icon">✓</div>
                            <p>All players have been added to this league</p>
                        </div>
                    @endforelse
                </div>
                <div class="selection-toolbar" id="availableToolbar">
                    <span class="sel-info"><span id="availableSelCount">0</span> selected</span>
                    <div class="sel-actions">
                        <button class="sel-btn sel-btn-secondary" onclick="clearSelection('available')">Clear</button>
                        <button class="sel-btn sel-btn-secondary" onclick="selectAllVisible('available')">Select All</button>
                        <button class="sel-btn sel-btn-primary" onclick="moveSelected('available')">Add to League →</button>
                    </div>
                </div>
                <div class="drag-hint" id="availableHint">Click to select, then drag or use "Add to League" button →</div>
            </div>

            <!-- Right Panel: League Players -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        League Players
                        <span class="panel-count" id="leagueCount">{{ $league->players->count() }}</span>
                    </div>
                    <input type="text" class="search-box" id="searchLeague" placeholder="Search league players...">
                </div>
                <div class="panel-body" id="leaguePanel" data-panel="league">
                    @forelse($league->players->sortBy('first_name') as $player)
                        <div class="player-card" draggable="true" data-player-id="{{ $player->id }}" data-player-name="{{ $player->name }}" data-player-handicap="{{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}">
                            <div class="player-info">
                                <div class="player-name">{{ $player->name }}</div>
                                <div class="player-handicap">Handicap: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }}</div>
                            </div>
                            <button class="move-btn" title="Remove from league">←</button>
                        </div>
                    @empty
                        <div class="empty-state" id="leagueEmpty">
                            <div class="icon">👥</div>
                            <p>No players in this league yet</p>
                            <p style="font-size: 0.9em; margin-top: 5px;">Drag players from the left to add them</p>
                        </div>
                    @endforelse
                </div>
                <div class="selection-toolbar" id="leagueToolbar">
                    <span class="sel-info"><span id="leagueSelCount">0</span> selected</span>
                    <div class="sel-actions">
                        <button class="sel-btn sel-btn-secondary" onclick="clearSelection('league')">Clear</button>
                        <button class="sel-btn sel-btn-secondary" onclick="selectAllVisible('league')">Select All</button>
                        <button class="sel-btn sel-btn-primary" onclick="moveSelected('league')">← Remove from League</button>
                    </div>
                </div>
                <div class="drag-hint" id="leagueHint">← Click to select, then drag or use "Remove from League" button</div>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        const leagueId = {{ $league->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const availablePanel = document.getElementById('availablePanel');
        const leaguePanel = document.getElementById('leaguePanel');
        let isProcessing = false;

        // Toast notifications
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast toast-' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // ── Selection ──

        function getPanel(panelName) {
            return panelName === 'available' ? availablePanel : leaguePanel;
        }

        function getSelectedCards(panel) {
            return Array.from(panel.querySelectorAll('.player-card.selected'));
        }

        function toggleSelect(card, e) {
            if (isProcessing) return;
            const panel = card.closest('.panel-body');

            if (e && e.shiftKey && panel._lastSelected) {
                // Shift-click: range select
                const cards = Array.from(panel.querySelectorAll('.player-card'));
                const visibleCards = cards.filter(c => c.style.display !== 'none');
                const lastIdx = visibleCards.indexOf(panel._lastSelected);
                const curIdx = visibleCards.indexOf(card);
                if (lastIdx !== -1 && curIdx !== -1) {
                    const start = Math.min(lastIdx, curIdx);
                    const end = Math.max(lastIdx, curIdx);
                    for (let i = start; i <= end; i++) {
                        visibleCards[i].classList.add('selected');
                    }
                }
            } else {
                card.classList.toggle('selected');
            }

            panel._lastSelected = card;
            updateSelectionUI(panel);
        }

        function updateSelectionUI(panel) {
            const panelName = panel === availablePanel ? 'available' : 'league';
            const count = getSelectedCards(panel).length;
            const toolbar = document.getElementById(panelName + 'Toolbar');
            const hint = document.getElementById(panelName + 'Hint');
            const countEl = document.getElementById(panelName + 'SelCount');

            countEl.textContent = count;
            if (count > 0) {
                toolbar.classList.add('visible');
                if (hint) hint.style.display = 'none';
            } else {
                toolbar.classList.remove('visible');
                if (hint) hint.style.display = '';
            }
        }

        function clearSelection(panelName) {
            const panel = getPanel(panelName);
            panel.querySelectorAll('.player-card.selected').forEach(c => c.classList.remove('selected'));
            updateSelectionUI(panel);
        }

        function selectAllVisible(panelName) {
            const panel = getPanel(panelName);
            panel.querySelectorAll('.player-card').forEach(c => {
                if (c.style.display !== 'none') c.classList.add('selected');
            });
            updateSelectionUI(panel);
        }

        // Click handler on cards (not on button)
        document.addEventListener('click', (e) => {
            const card = e.target.closest('.player-card');
            if (!card) return;
            if (e.target.closest('.move-btn')) return; // ignore button clicks
            toggleSelect(card, e);
        });

        // ── Counts & empty states ──

        function updateCounts() {
            const availableCards = availablePanel.querySelectorAll('.player-card');
            const leagueCards = leaguePanel.querySelectorAll('.player-card');
            document.getElementById('availableCount').textContent = availableCards.length;
            document.getElementById('leagueCount').textContent = leagueCards.length;

            let availableEmpty = document.getElementById('availableEmpty');
            let leagueEmpty = document.getElementById('leagueEmpty');

            if (availableCards.length === 0 && !availableEmpty) {
                availablePanel.insertAdjacentHTML('beforeend', '<div class="empty-state" id="availableEmpty"><div class="icon">✓</div><p>All players have been added to this league</p></div>');
            } else if (availableCards.length > 0 && availableEmpty) {
                availableEmpty.remove();
            }

            if (leagueCards.length === 0 && !leagueEmpty) {
                leaguePanel.insertAdjacentHTML('beforeend', '<div class="empty-state" id="leagueEmpty"><div class="icon">👥</div><p>No players in this league yet</p><p style="font-size:0.9em;margin-top:5px;">Drag players from the left to add them</p></div>');
            } else if (leagueCards.length > 0 && leagueEmpty) {
                leagueEmpty.remove();
            }
        }

        // ── Move cards (single or batch) ──

        function transferCard(card, action) {
            const targetPanel = action === 'add' ? leaguePanel : availablePanel;
            const btn = card.querySelector('.move-btn');

            if (btn) {
                if (action === 'add') {
                    btn.innerHTML = '←';
                    btn.title = 'Remove from league';
                    btn.onclick = () => moveSinglePlayer(card, 'remove');
                } else {
                    btn.innerHTML = '→';
                    btn.title = 'Add to league';
                    btn.onclick = () => moveSinglePlayer(card, 'add');
                }
            }

            const emptyState = targetPanel.querySelector('.empty-state');
            if (emptyState) emptyState.remove();

            card.classList.remove('selected');
            card.classList.add('moving');
            targetPanel.appendChild(card);
            setTimeout(() => card.classList.remove('moving'), 300);
        }

        // Move a single player (arrow button click)
        async function moveSinglePlayer(card, action) {
            if (isProcessing) return;
            const panel = card.closest('.panel-body');
            const selected = getSelectedCards(panel);

            // If the clicked card is part of a selection, move all selected
            if (selected.length > 1 && card.classList.contains('selected')) {
                await moveBatch(selected, action);
                return;
            }

            isProcessing = true;
            const btn = card.querySelector('.move-btn');
            if (btn) btn.classList.add('loading');

            try {
                const success = await apiMove(card.dataset.playerId, action);
                if (success) {
                    transferCard(card, action);
                    sortPanel(action === 'add' ? leaguePanel : availablePanel);
                    updateCounts();
                    updateSelectionUI(panel);
                    showToast(`${card.dataset.playerName} ${action === 'add' ? 'added to' : 'removed from'} league`);
                } else {
                    showToast('Failed to update player', 'error');
                }
            } catch {
                showToast('Network error. Please try again.', 'error');
            } finally {
                if (btn) btn.classList.remove('loading');
                isProcessing = false;
            }
        }

        // Move selected players (toolbar button)
        async function moveSelected(panelName) {
            const panel = getPanel(panelName);
            const action = panelName === 'available' ? 'add' : 'remove';
            const selected = getSelectedCards(panel);
            if (selected.length === 0) return;
            await moveBatch(selected, action);
        }

        async function moveBatch(cards, action) {
            if (isProcessing) return;
            isProcessing = true;
            const sourcePanel = cards[0].closest('.panel-body');

            // Disable all buttons in batch
            cards.forEach(c => { const b = c.querySelector('.move-btn'); if (b) b.classList.add('loading'); });

            let successCount = 0;
            let failCount = 0;

            if (action === 'add') {
                // Batch add - use the existing endpoint which accepts an array
                const playerIds = cards.map(c => c.dataset.playerId);
                try {
                    const success = await apiAddBatch(playerIds);
                    if (success) {
                        cards.forEach(c => transferCard(c, 'add'));
                        successCount = cards.length;
                    } else {
                        failCount = cards.length;
                    }
                } catch {
                    failCount = cards.length;
                }
            } else {
                // Remove - must do one at a time since endpoint handles single player
                for (const card of cards) {
                    try {
                        const success = await apiMove(card.dataset.playerId, 'remove');
                        if (success) {
                            transferCard(card, 'remove');
                            successCount++;
                        } else {
                            failCount++;
                        }
                    } catch {
                        failCount++;
                    }
                }
            }

            const targetPanel = action === 'add' ? leaguePanel : availablePanel;
            sortPanel(targetPanel);
            updateCounts();
            updateSelectionUI(sourcePanel);
            updateSelectionUI(targetPanel);

            if (successCount > 0) {
                showToast(`${successCount} player${successCount > 1 ? 's' : ''} ${action === 'add' ? 'added to' : 'removed from'} league`);
            }
            if (failCount > 0) {
                showToast(`Failed to move ${failCount} player${failCount > 1 ? 's' : ''}`, 'error');
            }

            isProcessing = false;
        }

        // ── API calls ──

        async function apiMove(playerId, action) {
            let url, body;
            if (action === 'add') {
                url = `/admin/leagues/${leagueId}/players`;
                body = JSON.stringify({ player_ids: [playerId] });
            } else {
                url = `/admin/leagues/${leagueId}/players/${playerId}`;
                body = JSON.stringify({ _method: 'DELETE' });
            }
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body,
                redirect: 'manual'
            });
            return response.ok || response.type === 'opaqueredirect' || response.status === 302 || response.status === 0;
        }

        async function apiAddBatch(playerIds) {
            const response = await fetch(`/admin/leagues/${leagueId}/players`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ player_ids: playerIds }),
                redirect: 'manual'
            });
            return response.ok || response.type === 'opaqueredirect' || response.status === 302 || response.status === 0;
        }

        // ── Sort ──

        function sortPanel(panel) {
            const cards = Array.from(panel.querySelectorAll('.player-card'));
            cards.sort((a, b) => a.dataset.playerName.localeCompare(b.dataset.playerName));
            cards.forEach(card => panel.appendChild(card));
        }

        // ── Drag and Drop (multi-select aware) ──

        let draggedCards = [];
        let dragSourcePanel = null;

        document.addEventListener('dragstart', (e) => {
            const card = e.target.closest('.player-card');
            if (!card) return;

            const panel = card.closest('.panel-body');
            const selected = getSelectedCards(panel);

            // If dragging a selected card, drag all selected; otherwise just this card
            if (card.classList.contains('selected') && selected.length > 1) {
                draggedCards = selected;
            } else {
                // Clear other selections, select just this one
                panel.querySelectorAll('.player-card.selected').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                draggedCards = [card];
                updateSelectionUI(panel);
            }

            dragSourcePanel = panel;
            draggedCards.forEach(c => c.classList.add('dragging'));
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', draggedCards.length + ' players');

            // Show count badge if multiple
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
            dragSourcePanel = null;
            const badge = document.getElementById('dragBadge');
            if (badge) badge.remove();
            document.querySelectorAll('.panel-body').forEach(p => p.classList.remove('drag-over'));
        });

        [availablePanel, leaguePanel].forEach(panel => {
            panel.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                panel.classList.add('drag-over');
            });

            panel.addEventListener('dragleave', (e) => {
                if (!panel.contains(e.relatedTarget)) {
                    panel.classList.remove('drag-over');
                }
            });

            panel.addEventListener('drop', (e) => {
                e.preventDefault();
                panel.classList.remove('drag-over');

                if (draggedCards.length === 0 || dragSourcePanel === panel) return;

                const action = panel === leaguePanel ? 'add' : 'remove';
                moveBatch(draggedCards, action);
            });
        });

        // ── Search filtering ──

        document.getElementById('searchAvailable').addEventListener('input', function() {
            filterCards(availablePanel, this.value);
        });

        document.getElementById('searchLeague').addEventListener('input', function() {
            filterCards(leaguePanel, this.value);
        });

        function filterCards(panel, query) {
            const cards = panel.querySelectorAll('.player-card');
            const q = query.toLowerCase().trim();
            cards.forEach(card => {
                const name = card.dataset.playerName.toLowerCase();
                card.style.display = (!q || name.includes(q)) ? '' : 'none';
            });
        }

        // ── Init: wire up single-move buttons ──
        document.querySelectorAll('#availablePanel .move-btn').forEach(btn => {
            btn.onclick = () => moveSinglePlayer(btn.closest('.player-card'), 'add');
        });
        document.querySelectorAll('#leaguePanel .move-btn').forEach(btn => {
            btn.onclick = () => moveSinglePlayer(btn.closest('.player-card'), 'remove');
        });
    </script>
</body>
</html>

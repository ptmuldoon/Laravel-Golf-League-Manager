<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Players</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.5em;
            font-weight: bold;
        }
        .navbar-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .navbar-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        h1 {
            color: #333;
            font-size: 2em;
            margin-bottom: 30px;
        }
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: var(--primary-light);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #e0e0e0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        tr:hover {
            background: var(--primary-light);
        }
        .player-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .player-link:hover {
            text-decoration: underline;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            text-decoration: none;
            color: var(--primary-color);
        }
        .pagination .active span {
            background: var(--primary-color);
            color: white;
        }
        .submenu {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }
        .submenu-link {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .submenu-link:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 50px;
            gap: 15px;
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95em;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.15);
        }
        .form-error {
            color: #dc3545;
            font-size: 0.8em;
            margin-top: 4px;
            display: block;
        }
        .btn-save {
            padding: 10px 24px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95em;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-save:hover {
            background: #218838;
        }
        .btn-add-row {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85em;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-add-row:hover {
            background: var(--primary-hover);
        }
        .btn-remove {
            padding: 8px 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        .btn-remove:hover {
            background: #c82333;
        }
        .player-row {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .player-row:last-child {
            border-bottom: none;
            margin-bottom: 15px;
        }
        .search-box {
            width: 300px;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95em;
            transition: border-color 0.3s;
        }
        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.15);
        }
        .table-input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9em;
            transition: border-color 0.3s;
        }
        .table-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb),0.15);
        }
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .navbar-hamburger {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            padding: 4px 8px;
            line-height: 1;
        }
        @media (max-width: 768px) {
            .navbar { padding: 12px 16px; flex-wrap: wrap; }
            .navbar-brand { flex: 1; }
            .navbar-hamburger { display: block; }
            .navbar-links {
                display: none; width: 100%; flex-direction: column;
                gap: 0; padding-top: 8px;
                border-top: 1px solid rgba(255,255,255,0.2); margin-top: 8px;
            }
            .navbar-links.open { display: flex; }
            .navbar-links a { padding: 10px 12px; border-radius: 4px; }
            .navbar-links form { width: 100%; display: block !important; }
            .navbar-links form button { width: 100%; text-align: left; padding: 10px 12px; border-radius: 4px; }
            .container { padding: 16px; }
            .content-section { padding: 16px; }
            .form-row { grid-template-columns: 1fr; }
            .submenu { flex-wrap: wrap; }
            .search-box { width: 100%; }
            .table-toolbar { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🏌️ Golf Admin</div>
        <button class="navbar-hamburger" onclick="var nl=this.closest('.navbar').querySelector('.navbar-links');nl.classList.toggle('open');" aria-label="Menu">☰</button>
        <div class="navbar-links">
            <a href="{{ route('admin.dashboard') }}">📊 Dashboard</a>
            <a href="{{ route('home') }}">🏠 Public Site</a>
            <a href="{{ route('admin.leagues') }}">🏆 Leagues</a>
            <a href="{{ route('admin.users') }}">🔑 Users</a>
            <a href="{{ route('admin.courses.index') }}">⛳ Courses</a>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.super.index') }}">🛡️ Super</a>
            @endif
            <a href="{{ route('profile.show') }}">👤 Profile</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: white; cursor: pointer; padding: 8px 16px; border-radius: 5px; transition: background 0.3s ease;">
                    🚪 Logout
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        <h1>👥 Manage Players</h1>

        <div class="submenu">
            <a href="{{ route('admin.import.scores.form') }}" class="submenu-link">📥 Import Scores</a>
            <a href="{{ route('admin.scorecard.create') }}" class="submenu-link">📝 Enter Scorecard</a>
            <button type="button" onclick="document.getElementById('add-player-form').style.display = document.getElementById('add-player-form').style.display === 'none' ? 'block' : 'none'" class="submenu-link">➕ Add Player</button>
            <form action="{{ route('admin.players.recomputeHandicaps') }}" method="POST" style="display: inline;" onsubmit="return confirmRecompute(this)">
                @csrf
                <button type="submit" class="submenu-link" style="background: #e67e22;">🔄 Recompute Handicaps</button>
            </form>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <div id="add-player-form" style="display: none;">
            <div class="content-section" style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="color: #333; font-size: 1.3em;">Add Players</h2>
                    <button type="button" onclick="addPlayerRow()" class="btn-add-row">+ Add Another Row</button>
                </div>
                <form action="{{ route('admin.players.store') }}" method="POST">
                    @csrf
                    <div id="player-rows">
                        @if(old('players'))
                            @foreach(old('players') as $i => $oldPlayer)
                                <div class="player-row" data-row="{{ $i }}">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>First Name *</label>
                                            <input type="text" name="players[{{ $i }}][first_name]" value="{{ $oldPlayer['first_name'] ?? '' }}" required>
                                            @error("players.{$i}.first_name") <span class="form-error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Last Name *</label>
                                            <input type="text" name="players[{{ $i }}][last_name]" value="{{ $oldPlayer['last_name'] ?? '' }}" required>
                                            @error("players.{$i}.last_name") <span class="form-error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Email *</label>
                                            <input type="email" name="players[{{ $i }}][email]" value="{{ $oldPlayer['email'] ?? '' }}" required>
                                            @error("players.{$i}.email") <span class="form-error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="text" name="players[{{ $i }}][phone_number]" value="{{ $oldPlayer['phone_number'] ?? '' }}">
                                        </div>
                                        <div class="form-group" style="display: flex; align-items: flex-end;">
                                            @if($i > 0)
                                                <button type="button" onclick="removePlayerRow(this)" class="btn-remove">✕</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="player-row" data-row="0">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>First Name *</label>
                                        <input type="text" name="players[0][first_name]" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Last Name *</label>
                                        <input type="text" name="players[0][last_name]" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email *</label>
                                        <input type="email" name="players[0][email]" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="players[0][phone_number]">
                                    </div>
                                    <div class="form-group" style="display: flex; align-items: flex-end;">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <button type="submit" class="btn-save">Save All Players</button>
                </form>
            </div>
        </div>

        <div class="content-section">
            <div class="table-toolbar">
                <input type="text" id="player-search" class="search-box" placeholder="Search players..." oninput="filterPlayers()">
            </div>
                <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Notifications</th>
                            <th>Handicap Index</th>
                            <th>Rounds</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="players-table-body">
                        @foreach($players as $player)
                            <tr data-player-name="{{ strtolower($player->name) }}" data-player-email="{{ strtolower($player->email ?? '') }}" data-player-id="{{ $player->id }}">
                                <td>
                                    <a href="{{ route('players.show', $player->id) }}" class="player-link">
                                        {{ $player->name }}
                                    </a>
                                </td>
                                <td class="cell-email">
                                    <span class="display-value">{{ $player->email ?: 'N/A' }}</span>
                                    <input type="email" class="table-input edit-input" value="{{ $player->email }}" style="display:none;" data-field="email">
                                </td>
                                <td class="cell-phone">
                                    <span class="display-value">{{ $player->phone_number ?: 'N/A' }}</span>
                                    <input type="text" class="table-input edit-input" value="{{ $player->phone_number }}" style="display:none;" data-field="phone_number">
                                </td>
                                <td class="cell-notifications">
                                    <span class="display-value">
                                        <span title="Email {{ $player->email_enabled ? 'enabled' : 'disabled' }}" style="cursor: help; {{ $player->email_enabled ? 'opacity:1' : 'opacity:0.3' }}">📧</span>
                                        <span title="SMS {{ $player->sms_enabled ? 'enabled' : 'disabled' }}" style="cursor: help; {{ $player->sms_enabled ? 'opacity:1' : 'opacity:0.3' }}">💬</span>
                                    </span>
                                    <span class="edit-input" style="display:none;">
                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; font-size: 0.85em;">
                                            <input type="checkbox" class="edit-checkbox" data-field="email_enabled" {{ $player->email_enabled ? 'checked' : '' }}
                                                style="width: 15px; height: 15px; accent-color: var(--primary-color); cursor: pointer;">
                                            📧
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; font-size: 0.85em; margin-left: 8px;">
                                            <input type="checkbox" class="edit-checkbox" data-field="sms_enabled" {{ $player->sms_enabled ? 'checked' : '' }}
                                                style="width: 15px; height: 15px; accent-color: var(--primary-color); cursor: pointer;">
                                            💬
                                        </label>
                                    </span>
                                </td>
                                <td>
                                    {{ $player->currentHandicap()?->handicap_index ? number_format($player->currentHandicap()->handicap_index, 1) : 'N/A' }}
                                </td>
                                <td>{{ $player->rounds_count }}</td>
                                <td>
                                    <div style="display: flex; gap: 12px; align-items: center;">
                                        <a href="{{ route('players.show', $player->id) }}" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                            View
                                        </a>
                                        <button type="button" class="btn-inline-edit" onclick="toggleEditRow(this)" style="background: none; border: none; color: var(--primary-color); font-weight: 600; cursor: pointer; padding: 0;">
                                            Edit
                                        </button>
                                        <button type="button" class="btn-inline-save" onclick="saveRow(this)" style="display:none; background: none; border: none; color: #28a745; font-weight: 600; cursor: pointer; padding: 0;">
                                            Save
                                        </button>
                                        <button type="button" class="btn-inline-cancel" onclick="cancelEditRow(this)" style="display:none; background: none; border: none; color: #6c757d; font-weight: 600; cursor: pointer; padding: 0;">
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

            <div class="pagination">
                {{ $players->links() }}
            </div>
        </div>
    </div>
    <script>
        let rowIndex = document.querySelectorAll('.player-row').length;

        function addPlayerRow() {
            const container = document.getElementById('player-rows');
            const row = document.createElement('div');
            row.className = 'player-row';
            row.dataset.row = rowIndex;
            row.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="players[${rowIndex}][first_name]" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="players[${rowIndex}][last_name]" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="players[${rowIndex}][email]" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="players[${rowIndex}][phone_number]">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="button" onclick="removePlayerRow(this)" class="btn-remove">✕</button>
                    </div>
                </div>
            `;
            container.appendChild(row);
            rowIndex++;
        }

        function removePlayerRow(btn) {
            btn.closest('.player-row').remove();
        }

        function filterPlayers() {
            const query = document.getElementById('player-search').value.toLowerCase();
            const rows = document.querySelectorAll('#players-table-body tr');
            rows.forEach(function(row) {
                const name = row.getAttribute('data-player-name') || '';
                const email = row.getAttribute('data-player-email') || '';
                row.style.display = (name.includes(query) || email.includes(query)) ? '' : 'none';
            });
        }

        function confirmRecompute(form) {
            if (!confirm('This will clear all existing handicap data and recompute from scratch. Continue?')) {
                return false;
            }
            var btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = '⏳ Recomputing...';
            return true;
        }

        function toggleEditRow(btn) {
            var row = btn.closest('tr');
            row.querySelectorAll('.display-value').forEach(function(el) { el.style.display = 'none'; });
            row.querySelectorAll('.edit-input').forEach(function(el) { el.style.display = ''; });
            btn.style.display = 'none';
            row.querySelector('.btn-inline-save').style.display = '';
            row.querySelector('.btn-inline-cancel').style.display = '';
        }

        function cancelEditRow(btn) {
            var row = btn.closest('tr');
            row.querySelectorAll('.display-value').forEach(function(el) { el.style.display = ''; });
            row.querySelectorAll('.edit-input').forEach(function(el) {
                el.style.display = 'none';
                if (el.tagName === 'INPUT') {
                    el.value = el.defaultValue;
                }
            });
            row.querySelectorAll('.edit-checkbox').forEach(function(el) {
                el.checked = el.defaultChecked;
            });
            row.querySelector('.btn-inline-edit').style.display = '';
            row.querySelector('.btn-inline-save').style.display = 'none';
            row.querySelector('.btn-inline-cancel').style.display = 'none';
        }

        function saveRow(btn) {
            var row = btn.closest('tr');
            var playerId = row.dataset.playerId;
            var email = row.querySelector('[data-field="email"]').value;
            var phone = row.querySelector('[data-field="phone_number"]').value;
            var emailEnabled = row.querySelector('[data-field="email_enabled"]').checked;
            var smsEnabled = row.querySelector('[data-field="sms_enabled"]').checked;

            btn.textContent = 'Saving...';
            btn.disabled = true;

            fetch('/admin/players/' + playerId, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email, phone_number: phone, email_enabled: emailEnabled, sms_enabled: smsEnabled })
            })
            .then(function(response) {
                if (!response.ok) {
                    return response.json().then(function(data) { throw data; });
                }
                return response.json();
            })
            .then(function(data) {
                var emailDisplay = row.querySelector('.cell-email .display-value');
                var phoneDisplay = row.querySelector('.cell-phone .display-value');
                emailDisplay.textContent = email || 'N/A';
                phoneDisplay.textContent = phone || 'N/A';

                var notifIcons = row.querySelector('.cell-notifications .display-value');
                var icons = notifIcons.querySelectorAll('span');
                icons[0].style.opacity = emailEnabled ? '1' : '0.3';
                icons[0].title = 'Email ' + (emailEnabled ? 'enabled' : 'disabled');
                icons[1].style.opacity = smsEnabled ? '1' : '0.3';
                icons[1].title = 'SMS ' + (smsEnabled ? 'enabled' : 'disabled');

                row.querySelector('[data-field="email"]').defaultValue = email;
                row.querySelector('[data-field="phone_number"]').defaultValue = phone;
                row.querySelector('[data-field="email_enabled"]').defaultChecked = emailEnabled;
                row.querySelector('[data-field="sms_enabled"]').defaultChecked = smsEnabled;

                row.dataset.playerEmail = (email || '').toLowerCase();

                cancelEditRow(btn);
            })
            .catch(function(err) {
                var msg = 'Save failed.';
                if (err && err.errors) {
                    msg = Object.values(err.errors).flat().join('\n');
                }
                alert(msg);
            })
            .finally(function() {
                btn.textContent = 'Save';
                btn.disabled = false;
            });
        }
    </script>
    @if($errors->any())
    <script>document.getElementById('add-player-form').style.display = 'block';</script>
    @endif
</body>
</html>

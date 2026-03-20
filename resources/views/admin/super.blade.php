<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-links { display: flex; gap: 20px; align-items: center; }
        .navbar-links a {
            color: white; text-decoration: none; padding: 8px 16px;
            border-radius: 5px; transition: background 0.3s ease;
        }
        .navbar-links a:hover { background: rgba(255,255,255,0.2); }
        .navbar-links a.active { background: rgba(255,255,255,0.25); }
        .container { max-width: 1000px; margin: 0 auto; padding: 30px; }
        h1 { color: #333; font-size: 2em; margin-bottom: 30px; }
        .alert-success {
            background: #d4edda; color: #155724; padding: 12px 20px;
            border-radius: 8px; margin-bottom: 20px; font-weight: 500;
        }
        .alert-error {
            background: #f8d7da; color: #721c24; padding: 12px 20px;
            border-radius: 8px; margin-bottom: 20px; font-weight: 500;
        }
        .content-section {
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 25px;
        }
        .section-title {
            font-size: 1.4em; color: var(--primary-color); margin-bottom: 20px;
            padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: var(--primary-light); padding: 12px; text-align: left;
            font-weight: 600; color: var(--primary-color); border-bottom: 2px solid #e0e0e0;
        }
        td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        tr:hover { background: var(--primary-light); }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 0.8em; font-weight: 600;
        }
        .badge-super { background: var(--secondary-color); color: white; }
        .badge-admin { background: var(--primary-color); color: white; }
        .badge-user { background: #e0e0e0; color: #666; }
        .btn {
            display: inline-block; padding: 10px 24px; border: none;
            border-radius: 8px; font-weight: 600; font-size: 0.95em;
            cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; color: white;
        }
        .btn-primary { background: var(--primary-color); }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #e67e22; }
        .btn-warning:hover { background: #d35400; }
        .self-badge { font-size: 0.75em; color: #888; font-weight: normal; }
        .db-actions {
            display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-start;
        }
        .db-card {
            flex: 1; min-width: 280px; background: var(--primary-light); padding: 20px;
            border-radius: 10px; border: 1px solid #e0e0e0;
        }
        .db-card h3 { color: #333; margin-bottom: 10px; font-size: 1.1em; }
        .db-card p { color: #666; font-size: 0.9em; margin-bottom: 15px; }
        select.role-select {
            padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 6px;
            font-size: 0.85em; font-weight: 600; cursor: pointer; background: white;
        }
        .restore-warning {
            background: #fff3cd; color: #856404; padding: 10px 15px;
            border-radius: 6px; font-size: 0.85em; margin-bottom: 12px;
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
            .db-card { min-width: 100%; }
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
            <a href="{{ route('admin.players') }}">👥 Players</a>
            <a href="{{ route('admin.users') }}">🔑 Users</a>
            <a href="{{ route('admin.courses.index') }}">⛳ Courses</a>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.super.index') }}" class="active">🛡️ Super</a>
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
        <h1>🛡️ Super Admin</h1>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        {{-- Site Settings --}}
        <div class="content-section">
            <h2 class="section-title">Site Settings</h2>
            <form action="{{ route('admin.super.site-settings') }}" method="POST">
                @csrf
                <div class="db-actions">
                    <div class="db-card">
                        <h3>User Registration</h3>
                        <p>Allow new users/players to register accounts on the website.</p>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-top: 10px;">
                            <input type="hidden" name="registration_enabled" value="0">
                            <input type="checkbox" name="registration_enabled" value="1"
                                {{ ($siteSettings['registration_enabled'] ?? '1') === '1' ? 'checked' : '' }}
                                style="width: 20px; height: 20px; cursor: pointer;">
                            <span style="font-weight: 600; color: #333;">Enable Registration</span>
                        </label>
                    </div>
                    <div class="db-card">
                        <h3>Player Score Posting</h3>
                        <p>Allow registered players to post their own scores/scorecards.</p>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-top: 10px;">
                            <input type="hidden" name="player_score_posting_enabled" value="0">
                            <input type="checkbox" name="player_score_posting_enabled" value="1"
                                {{ ($siteSettings['player_score_posting_enabled'] ?? '1') === '1' ? 'checked' : '' }}
                                style="width: 20px; height: 20px; cursor: pointer;">
                            <span style="font-weight: 600; color: #333;">Enable Score Posting</span>
                        </label>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>

        {{-- Database Management --}}
        <div class="content-section">
            <h2 class="section-title">Database Management</h2>
            <div class="db-actions">
                <div class="db-card">
                    <h3>Backup Database</h3>
                    <p>Download a full backup of the database as a .sql file.</p>
                    <form action="{{ route('admin.super.backup') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Download Backup</button>
                    </form>
                </div>
                <div class="db-card">
                    <h3>Restore Database</h3>
                    <p>Upload a .sql backup file to restore the database.</p>
                    <div class="restore-warning">
                        <strong>Warning:</strong> This will overwrite all current data. Make sure to download a backup first.
                    </div>
                    <form action="{{ route('admin.super.restore') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to restore the database? This will overwrite ALL current data.')">
                        @csrf
                        <input type="file" name="sql_file" accept=".sql" required style="margin-bottom: 10px; display: block; font-size: 0.9em;">
                        @error('sql_file')
                            <div style="color: #dc3545; font-size: 0.85em; margin-bottom: 8px;">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-warning">Restore Database</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Automated Backups --}}
        <div class="content-section">
            <h2 class="section-title">Automated Backups</h2>
            <div class="db-actions" style="margin-bottom: 20px;">
                <div class="db-card" style="flex: 2;">
                    <h3>Backup Schedule</h3>
                    <p>Configure automatic database backups. Backups are stored on the server.</p>
                    <form action="{{ route('admin.super.backup.schedule') }}" method="POST">
                        @csrf
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end; margin-bottom: 15px;">
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Status
                                <select name="backup_enabled" style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; cursor: pointer; background: white;">
                                    <option value="1" {{ $backupSettings['enabled'] === '1' ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ $backupSettings['enabled'] !== '1' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </label>
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Frequency
                                <select name="backup_frequency" style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; cursor: pointer; background: white;">
                                    <option value="daily" {{ $backupSettings['frequency'] === 'daily' ? 'selected' : '' }}>Daily</option>
                                    <option value="weekly" {{ $backupSettings['frequency'] === 'weekly' ? 'selected' : '' }}>Weekly (Sunday)</option>
                                    <option value="monthly" {{ $backupSettings['frequency'] === 'monthly' ? 'selected' : '' }}>Monthly (1st)</option>
                                </select>
                            </label>
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Time
                                <input type="time" name="backup_time" value="{{ $backupSettings['time'] }}" style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; background: white;">
                            </label>
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Keep Last
                                <select name="backup_retention" style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; cursor: pointer; background: white;">
                                    @foreach([5, 10, 15, 20, 30] as $count)
                                        <option value="{{ $count }}" {{ (int)$backupSettings['retention'] === $count ? 'selected' : '' }}>{{ $count }} backups</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </form>
                </div>
                <div class="db-card" style="flex: 1;">
                    <h3>Run Now</h3>
                    <p>Create an immediate backup and save it to the server.</p>
                    <form action="{{ route('admin.super.backup.now') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Run Backup Now</button>
                    </form>
                </div>
            </div>

            @if(count($backups) > 0)
                <h3 style="color: #333; margin-bottom: 12px; font-size: 1.1em;">Saved Backups</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $bk)
                                <tr>
                                    <td style="font-family: monospace; font-size: 0.85em;">{{ $bk['name'] }}</td>
                                    <td>{{ $bk['size'] }}</td>
                                    <td>{{ $bk['date'] }}</td>
                                    <td style="display: flex; gap: 8px; align-items: center;">
                                        <a href="{{ route('admin.super.backup.download', $bk['name']) }}" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.8em;">Download</a>
                                        <form action="{{ route('admin.super.backup.delete', $bk['name']) }}" method="POST" onsubmit="return confirm('Delete this backup file?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 12px; font-size: 0.8em;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color: #888; font-size: 0.9em; font-style: italic;">No saved backups yet. Use "Run Backup Now" or enable the schedule to create backups automatically.</p>
            @endif
        </div>

        {{-- Backup Delivery --}}
        <div class="content-section">
            <h2 class="section-title">Backup Delivery</h2>
            <p style="color: #666; font-size: 0.9em; margin-bottom: 20px;">
                Configure where backups are sent after creation. Both options are independent and can be used together.
            </p>

            <form action="{{ route('admin.super.backup.delivery') }}" method="POST">
                @csrf
                <div class="db-actions" style="margin-bottom: 20px;">
                    {{-- Email Delivery --}}
                    <div class="db-card">
                        <h3>📧 Email Delivery</h3>
                        <p>Attach the backup .sql file and email it to one or more recipients.</p>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Status
                                <select name="backup_email_enabled" style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; cursor: pointer; background: white;">
                                    <option value="1" {{ $backupSettings['email_enabled'] === '1' ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ $backupSettings['email_enabled'] !== '1' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </label>
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Recipients (comma-separated)
                                <input type="text" name="backup_email_recipients"
                                       value="{{ $backupSettings['email_recipients'] }}"
                                       placeholder="admin@example.com, user@example.com"
                                       style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; background: white;">
                            </label>
                        </div>
                    </div>

                    {{-- Google Drive Delivery --}}
                    <div class="db-card">
                        <h3>☁️ Google Drive Delivery</h3>
                        <p>Upload the backup .sql file to a Google Drive folder via Service Account.</p>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Status
                                <select name="backup_gdrive_enabled" style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; cursor: pointer; background: white;">
                                    <option value="1" {{ $backupSettings['gdrive_enabled'] === '1' ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ $backupSettings['gdrive_enabled'] !== '1' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </label>
                            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 0.85em; color: #555;">
                                Folder ID
                                <input type="text" name="backup_gdrive_folder_id"
                                       value="{{ $backupSettings['gdrive_folder_id'] }}"
                                       placeholder="e.g. 1AbCdEfGhIjKlMnOpQrStUvWxYz"
                                       style="padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.95em; background: white;">
                            </label>
                            <div style="font-size: 0.85em; color: {{ $backupSettings['gdrive_configured'] ? '#155724' : '#856404' }}; background: {{ $backupSettings['gdrive_configured'] ? '#d4edda' : '#fff3cd' }}; padding: 8px 12px; border-radius: 6px;">
                                @if($backupSettings['gdrive_configured'])
                                    ✅ Google Drive OAuth configured.
                                @else
                                    ⚠️ Google Drive not configured. Run <code>php artisan google:auth</code> on the server and set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_DRIVE_REFRESH_TOKEN in .env.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Delivery Settings</button>
            </form>

            {{-- Test & Credentials --}}
            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                <h3 style="color: #333; margin-bottom: 12px; font-size: 1.1em;">Test & Credentials</h3>
                <div class="db-actions">
                    {{-- Test Email --}}
                    <div class="db-card">
                        <h3>Test Email</h3>
                        <p>Send the most recent backup to your configured email recipients.</p>
                        <form action="{{ route('admin.super.backup.testEmail') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Send Test Email</button>
                        </form>
                    </div>

                    {{-- Google Drive Connection --}}
                    <div class="db-card">
                        <h3>Google Drive Connection</h3>
                        <p>Configure via .env and <code>php artisan google:auth</code> on the server.</p>
                        @if($backupSettings['gdrive_configured'])
                            <form action="{{ route('admin.super.backup.testGdrive') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.85em;">Test Connection</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Site Theme --}}
        <div class="content-section">
            <h2 class="section-title">Site Theme</h2>
            <form action="{{ route('admin.super.theme.update') }}" method="POST" id="themeForm">
                @csrf
                <input type="hidden" name="theme_name" id="themeName" value="{{ $currentTheme['name'] }}">
                <input type="hidden" name="primary_color" id="primaryColorInput" value="{{ $currentTheme['primary'] }}">
                <input type="hidden" name="secondary_color" id="secondaryColorInput" value="{{ $currentTheme['secondary'] }}">

                <h3 style="margin-bottom: 12px; color: #333;">Preset Themes</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 25px;">
                    @php
                        $presets = [
                            ['name' => 'classic', 'label' => 'Classic Blue/Purple', 'primary' => '#667eea', 'secondary' => '#764ba2'],
                            ['name' => 'masters', 'label' => 'Masters Green', 'primary' => '#2d6a4f', 'secondary' => '#1b4332'],
                            ['name' => 'navy', 'label' => 'Country Club Navy', 'primary' => '#1e3a5f', 'secondary' => '#0d253f'],
                            ['name' => 'sunset', 'label' => 'Sunset Orange', 'primary' => '#e07c24', 'secondary' => '#c0392b'],
                            ['name' => 'teal', 'label' => 'Ocean Teal', 'primary' => '#0d9488', 'secondary' => '#115e59'],
                            ['name' => 'crimson', 'label' => 'Crimson Red', 'primary' => '#dc2626', 'secondary' => '#991b1b'],
                        ];
                    @endphp
                    @foreach($presets as $preset)
                        <div class="theme-card" data-name="{{ $preset['name'] }}" data-primary="{{ $preset['primary'] }}" data-secondary="{{ $preset['secondary'] }}"
                             onclick="selectPreset(this)"
                             style="border: 3px solid {{ $currentTheme['name'] === $preset['name'] ? 'var(--primary-color)' : '#e0e0e0' }}; border-radius: 10px; padding: 12px; cursor: pointer; text-align: center; transition: all 0.2s ease; background: white;">
                            <div style="display: flex; gap: 4px; justify-content: center; margin-bottom: 8px;">
                                <div style="width: 30px; height: 30px; border-radius: 6px; background: {{ $preset['primary'] }};"></div>
                                <div style="width: 30px; height: 30px; border-radius: 6px; background: {{ $preset['secondary'] }};"></div>
                            </div>
                            <div style="font-size: 0.85em; font-weight: 600; color: #333;">{{ $preset['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                <h3 style="margin-bottom: 12px; color: #333;">Custom Colors</h3>
                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 25px; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.9em; color: #555;">
                        Primary:
                        <input type="color" id="customPrimary" value="{{ $currentTheme['primary'] }}" onchange="selectCustom()" style="width: 50px; height: 35px; border: 2px solid #e0e0e0; border-radius: 6px; cursor: pointer; padding: 2px;">
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.9em; color: #555;">
                        Secondary:
                        <input type="color" id="customSecondary" value="{{ $currentTheme['secondary'] }}" onchange="selectCustom()" style="width: 50px; height: 35px; border: 2px solid #e0e0e0; border-radius: 6px; cursor: pointer; padding: 2px;">
                    </label>
                </div>

                <h3 style="margin-bottom: 12px; color: #333;">Preview</h3>
                <div style="border: 2px solid #e0e0e0; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
                    <div id="previewNavbar" style="background: linear-gradient(135deg, {{ $currentTheme['primary'] }} 0%, {{ $currentTheme['secondary'] }} 100%); color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: bold; font-size: 1.1em;">Golf League</span>
                        <div style="display: flex; gap: 10px;">
                            <span style="padding: 4px 10px; background: rgba(255,255,255,0.2); border-radius: 4px; font-size: 0.85em;">Dashboard</span>
                            <span style="padding: 4px 10px; background: rgba(255,255,255,0.25); border-radius: 4px; font-size: 0.85em;">Leagues</span>
                        </div>
                    </div>
                    <div style="padding: 15px 20px; background: #f5f7fa;">
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <button type="button" id="previewBtn" style="padding: 8px 18px; border: none; border-radius: 6px; color: white; font-weight: 600; font-size: 0.85em; background: {{ $currentTheme['primary'] }};">Save Scores</button>
                            <span id="previewBadge" style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.8em; font-weight: 600; color: white; background: {{ $currentTheme['secondary'] }};">Super Admin</span>
                            <span id="previewLink" style="font-weight: 600; font-size: 0.9em; color: {{ $currentTheme['primary'] }};">View Standings &rarr;</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Theme</button>
            </form>
        </div>

        {{-- User Role Management --}}
        <div class="content-section">
            <h2 class="section-title">User Role Management</h2>
            <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Change Role</th>
                        <th>Reset Password</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                {{ $user->name }}
                                @if($user->id === auth()->id())
                                    <span class="self-badge">(you)</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->is_super_admin)
                                    <span class="badge badge-super">Super Admin</span>
                                @elseif($user->is_admin)
                                    <span class="badge badge-admin">Admin</span>
                                @else
                                    <span class="badge badge-user">User</span>
                                @endif
                            </td>
                            <td>
                                @if($user->id === auth()->id())
                                    <span style="color: #888; font-size: 0.85em;">Cannot change own role</span>
                                @else
                                    <form action="{{ route('admin.super.users.role', $user->id) }}" method="POST" style="display: flex; align-items: center; gap: 8px;">
                                        @csrf
                                        <select name="role" class="role-select" onchange="this.form.submit()">
                                            <option value="user" {{ !$user->is_admin && !$user->is_super_admin ? 'selected' : '' }}>User</option>
                                            <option value="admin" {{ $user->is_admin && !$user->is_super_admin ? 'selected' : '' }}>Admin</option>
                                            <option value="super_admin" {{ $user->is_super_admin ? 'selected' : '' }}>Super Admin</option>
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if($user->id === auth()->id())
                                    <span style="color: #888; font-size: 0.85em;">Use profile page</span>
                                @else
                                    <form action="{{ route('admin.super.users.password', $user->id) }}" method="POST"
                                          style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"
                                          onsubmit="return confirm('Are you sure you want to reset the password for {{ $user->name }}?')">
                                        @csrf
                                        <input type="password" name="password" placeholder="New password" required minlength="8"
                                               style="padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.85em; width: 130px;">
                                        <input type="password" name="password_confirmation" placeholder="Confirm" required minlength="8"
                                               style="padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 0.85em; width: 130px;">
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8em;">Reset</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <script>
        function selectPreset(card) {
            var primary = card.dataset.primary;
            var secondary = card.dataset.secondary;
            var name = card.dataset.name;
            document.getElementById('themeName').value = name;
            document.getElementById('primaryColorInput').value = primary;
            document.getElementById('secondaryColorInput').value = secondary;
            document.getElementById('customPrimary').value = primary;
            document.getElementById('customSecondary').value = secondary;
            document.querySelectorAll('.theme-card').forEach(function(c) {
                c.style.borderColor = '#e0e0e0';
            });
            card.style.borderColor = primary;
            updatePreview(primary, secondary);
        }

        function selectCustom() {
            var primary = document.getElementById('customPrimary').value;
            var secondary = document.getElementById('customSecondary').value;
            document.getElementById('themeName').value = 'custom';
            document.getElementById('primaryColorInput').value = primary;
            document.getElementById('secondaryColorInput').value = secondary;
            document.querySelectorAll('.theme-card').forEach(function(c) {
                c.style.borderColor = '#e0e0e0';
            });
            updatePreview(primary, secondary);
        }

        function updatePreview(primary, secondary) {
            document.getElementById('previewNavbar').style.background = 'linear-gradient(135deg, ' + primary + ' 0%, ' + secondary + ' 100%)';
            document.getElementById('previewBtn').style.background = primary;
            document.getElementById('previewBadge').style.background = secondary;
            document.getElementById('previewLink').style.color = primary;
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoring Settings - {{ $league->name }}</title>
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
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: var(--primary-color);
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        .back-link:hover {
            background: var(--primary-hover);
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
        .navbar-links a.active {
            background: rgba(255,255,255,0.25);
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        h1 {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .scoring-type-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .scoring-type-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.65em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .type-badge-match {
            background: #cce5ff;
            color: #004085;
        }
        .type-badge-team {
            background: #d4edda;
            color: #155724;
        }
        .type-badge-stableford {
            background: #fff3cd;
            color: #856404;
        }
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .setting-row:last-child {
            border-bottom: none;
        }
        .setting-info {
            flex: 1;
        }
        .setting-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }
        .setting-description {
            color: #888;
            font-size: 0.85em;
        }
        .setting-input {
            width: 100px;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .setting-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2);
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .form-actions {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        .section-divider {
            border: none;
            border-top: 1px dashed #e0e0e0;
            margin: 8px 0;
        }
        .section-subtitle {
            font-size: 0.85em;
            font-weight: 600;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 0 4px 0;
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
            .scoring-type-card { padding: 16px; }
            .setting-row { flex-direction: column; align-items: flex-start; gap: 8px; }
            .setting-input { width: 100%; }
            .form-actions { text-align: left; }
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
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">← Back to League</a>
        <h1>Scoring Settings</h1>
        <p class="subtitle">Configure point values for <strong>{{ $league->name }}</strong> ({{ $league->season }})</p>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                Please correct the errors below. All point values must be numbers between 0 and 999.99.
            </div>
        @endif

        <form action="{{ route('admin.leagues.scoring.update', $league->id) }}" method="POST">
            @csrf
            @method('PUT')

            @foreach($scoringTypes as $typeKey => $typeLabel)
                <div class="scoring-type-card">
                    <h2 class="scoring-type-title">
                        {{ $typeLabel }}
                        @if($typeKey === 'stableford')
                            <span class="type-badge type-badge-stableford">Per-Hole + Match</span>
                        @elseif($typeKey === 'best_ball_match_play' || $typeKey === 'team_2ball_match_play' || $typeKey === 'scramble')
                            <span class="type-badge type-badge-team">Team</span>
                        @else
                            <span class="type-badge type-badge-match">Individual</span>
                        @endif
                    </h2>

                    @if(isset($settingsByType[$typeKey]))
                        @php
                            $settings = $settingsByType[$typeKey];
                            $matchOutcomes = $settings->whereIn('outcome', ['win', 'loss', 'tie']);
                            $holeOutcomes = $settings->whereNotIn('outcome', ['win', 'loss', 'tie']);
                        @endphp

                        @if($typeKey === 'stableford' && $holeOutcomes->count() > 0)
                            <div class="section-subtitle">Per-Hole Points</div>
                            @foreach($holeOutcomes as $setting)
                                <div class="setting-row">
                                    <div class="setting-info">
                                        <div class="setting-label">{{ ucfirst(str_replace('_', ' ', $setting->outcome)) }}</div>
                                        <div class="setting-description">{{ $setting->description }}</div>
                                    </div>
                                    <input
                                        type="number"
                                        name="settings[{{ $setting->id }}]"
                                        value="{{ old('settings.' . $setting->id, number_format((float)$setting->points, 2, '.', '')) }}"
                                        class="setting-input"
                                        step="0.01"
                                        min="0"
                                        max="999.99"
                                    >
                                </div>
                            @endforeach

                            @if($matchOutcomes->count() > 0)
                                <hr class="section-divider">
                                <div class="section-subtitle">Match Outcome Points</div>
                            @endif
                        @endif

                        @foreach($matchOutcomes as $setting)
                            <div class="setting-row">
                                <div class="setting-info">
                                    <div class="setting-label">{{ ucfirst(str_replace('_', ' ', $setting->outcome)) }}</div>
                                    <div class="setting-description">{{ $setting->description }}</div>
                                </div>
                                <input
                                    type="number"
                                    name="settings[{{ $setting->id }}]"
                                    value="{{ old('settings.' . $setting->id, number_format((float)$setting->points, 2, '.', '')) }}"
                                    class="setting-input"
                                    step="0.01"
                                    min="0"
                                    max="999.99"
                                >
                            </div>
                        @endforeach

                        @if($typeKey !== 'stableford')
                            @foreach($holeOutcomes as $setting)
                                <div class="setting-row">
                                    <div class="setting-info">
                                        <div class="setting-label">{{ ucfirst(str_replace('_', ' ', $setting->outcome)) }}</div>
                                        <div class="setting-description">{{ $setting->description }}</div>
                                    </div>
                                    <input
                                        type="number"
                                        name="settings[{{ $setting->id }}]"
                                        value="{{ old('settings.' . $setting->id, number_format((float)$setting->points, 2, '.', '')) }}"
                                        class="setting-input"
                                        step="0.01"
                                        min="0"
                                        max="999.99"
                                    >
                                </div>
                            @endforeach
                        @endif
                    @else
                        <div class="empty-state">
                            <p>No settings configured for this type. Run the scoring settings seeder.</p>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save All Settings</button>
            </div>
        </form>
    </div>
</body>
</html>

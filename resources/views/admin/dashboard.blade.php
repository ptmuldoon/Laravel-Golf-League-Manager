<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard</title>
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
            margin-bottom: 10px;
        }
        .welcome-text {
            color: #666;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        .stat-label {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 8px;
        }
        .stat-value {
            color: #333;
            font-size: 2.5em;
            font-weight: bold;
        }
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
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
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-info {
            background: #cce5ff;
            color: #004085;
        }
        .league-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .league-link:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
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
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .quick-links {
            position: relative;
            display: inline-block;
        }
        .quick-links-btn {
            padding: 6px 12px;
            font-size: 0.85em;
            font-weight: 600;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .quick-links-btn:hover {
            background: var(--secondary-color);
        }
        .quick-links-menu {
            display: none;
            position: fixed;
            z-index: 9999;
            min-width: 170px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .quick-links-menu.open {
            display: block;
        }
        .quick-links-menu-inner {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .quick-links-menu-inner a {
            display: block;
            padding: 10px 16px;
            color: #333;
            text-decoration: none;
            font-size: 0.85em;
            font-weight: 500;
            white-space: nowrap;
            transition: background 0.15s ease;
        }
        .quick-links-menu-inner a:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }
        .quick-links-menu-inner a + a,
        .quick-links-menu-inner .quick-link-disabled + a,
        .quick-links-menu-inner a + .quick-link-disabled,
        .quick-links-menu-inner .quick-link-disabled + .quick-link-disabled {
            border-top: 1px solid #f0f0f0;
        }
        .quick-links-menu-inner .quick-link-disabled {
            display: block;
            padding: 10px 16px;
            color: #aaa;
            font-size: 0.85em;
            font-weight: 500;
            white-space: nowrap;
            cursor: not-allowed;
            position: relative;
        }
        .quick-link-disabled .quick-link-tooltip {
            display: none;
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            white-space: nowrap;
            z-index: 10000;
            margin-right: 6px;
        }
        .quick-link-disabled:hover .quick-link-tooltip {
            display: block;
        }
        .schedule-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .schedule-modal-overlay.active {
            display: flex;
        }
        .schedule-modal {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            width: 95%;
            max-width: 900px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        /* schedule-modal-header styles moved to end of style block */
        .schedule-modal-close {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #999;
            padding: 0 4px;
            line-height: 1;
        }
        .schedule-modal-close:hover {
            color: #333;
        }
        .schedule-modal-body {
            padding: 20px 24px;
            overflow-y: auto;
        }
        .schedule-modal-footer {
            padding: 16px 24px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .modal-match-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
        }
        .modal-match-row + .modal-match-row {
            border-top: 1px solid #f0f0f0;
        }
        .modal-match-row .tee-time {
            color: var(--primary-color);
            font-weight: 700;
            min-width: 55px;
            font-size: 0.9em;
            text-align: center;
        }
        .modal-match-row .players-area {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }
        .modal-match-row .players-area .side {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .modal-match-row .players-area .side.away { text-align: right; }
        .modal-match-row .players-area .side.home { text-align: left; }
        .modal-match-row .players-area .vs {
            color: #888;
            font-weight: 600;
            flex-shrink: 0;
        }
        .modal-player-row {
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .side.away .modal-player-row { justify-content: flex-end; }
        .side.home .modal-player-row { justify-content: flex-start; }
        .modal-player-select {
            padding: 2px 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: 600;
            background: white;
            cursor: pointer;
            max-width: 140px;
        }
        .modal-handicap-input {
            width: 48px;
            padding: 2px 3px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.8em;
            color: var(--secondary-color);
            font-weight: 600;
            text-align: center;
        }
        .modal-sub-btn {
            padding: 1px 4px;
            font-size: 0.65em;
            border: 1px solid #ff9800;
            border-radius: 3px;
            background: #fff3e0;
            color: #e65100;
            cursor: pointer;
            vertical-align: middle;
        }
        .modal-sub-remove {
            padding: 0 3px;
            font-size: 0.6em;
            border: 1px solid #dc3545;
            border-radius: 3px;
            background: #f8d7da;
            color: #721c24;
            cursor: pointer;
            vertical-align: middle;
        }
        .modal-sub-name {
            font-size: 0.85em;
            color: #e65100;
            font-weight: 600;
        }
        .modal-sub-label {
            font-size: 0.6em;
            color: #999;
        }
        .modal-sub-search {
            display: none;
            position: relative;
        }
        .modal-sub-search input {
            padding: 2px 4px;
            border: 1px solid #ff9800;
            border-radius: 4px;
            font-size: 0.8em;
            width: 130px;
        }
        .modal-sub-search .results {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            z-index: 1100;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            min-width: 180px;
        }
        .side.away .modal-sub-search .results { right: 0; }
        .side.home .modal-sub-search .results { left: 0; }
        .active-leagues-wrapper {
            overflow: visible;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        .schedule-week-arrow {
            background: none;
            border: 1px solid #d0d5e0;
            border-radius: 6px;
            font-size: 1.2em;
            cursor: pointer;
            color: var(--primary-color);
            padding: 4px 10px;
            line-height: 1;
            transition: all 0.2s ease;
        }
        .schedule-week-arrow:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .schedule-week-arrow:disabled {
            color: #ccc;
            border-color: #e0e0e0;
            cursor: default;
        }
        .schedule-modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 2px solid #f0f0f0;
            gap: 12px;
        }
        .schedule-week-title {
            margin: 0;
            color: #333;
            font-size: 1.2em;
            flex: 1;
            text-align: center;
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
            .navbar {
                padding: 12px 16px;
                flex-wrap: wrap;
            }
            .navbar-brand { flex: 1; }
            .navbar-hamburger { display: block; }
            .navbar-links {
                display: none;
                width: 100%;
                flex-direction: column;
                gap: 0;
                padding-top: 8px;
                border-top: 1px solid rgba(255,255,255,0.2);
                margin-top: 8px;
            }
            .navbar-links.open { display: flex; }
            .navbar-links a { padding: 10px 12px; border-radius: 4px; }
            .navbar-links form { width: 100%; display: block !important; }
            .navbar-links form button { width: 100%; text-align: left; padding: 10px 12px; border-radius: 4px; }
            .container { padding: 16px; }
            .content-section { padding: 16px; }
            .active-leagues-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .active-leagues-wrapper table {
                min-width: 600px;
            }
            .active-leagues-wrapper td:last-child {
                white-space: nowrap;
            }
            .schedule-modal-body { padding: 12px 14px; }
            .modal-match-row { flex-direction: column; align-items: flex-start; gap: 6px; }
            .modal-match-row .players-area { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🏌️ Golf Admin</div>
        <button class="navbar-hamburger" onclick="var nl=this.closest('.navbar').querySelector('.navbar-links');nl.classList.toggle('open');" aria-label="Menu">☰</button>
        <div class="navbar-links">
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
        <h1>Welcome, {{ auth()->user()->name }}!</h1>
        <p class="welcome-text">Here's an overview of your golf league system</p>

        @if(session('success'))
            <div style="background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Leagues</div>
                <div class="stat-value">{{ $stats['total_leagues'] }}</div>
            </div>
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-label">Active Leagues</div>
                <div class="stat-value">{{ $stats['active_leagues'] }}</div>
            </div>
            <div class="stat-card" style="border-left-color: #ffc107;">
                <div class="stat-label">Total Players</div>
                <div class="stat-value">{{ $stats['total_players'] }}</div>
            </div>
            <div class="stat-card" style="border-left-color: #17a2b8;">
                <div class="stat-label">Golf Courses</div>
                <div class="stat-value">{{ $stats['total_courses'] }}</div>
            </div>
            <div class="stat-card" style="border-left-color: #6c757d;">
                <div class="stat-label">Scheduled Matches</div>
                <div class="stat-value">{{ $stats['scheduled_matches'] }}</div>
            </div>
            <div class="stat-card" style="border-left-color: #fd7e14;">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ $stats['in_progress_matches'] }}</div>
            </div>
        </div>

        <!-- Active Leagues -->
        <div class="content-section" style="position: relative; z-index: 10;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="section-title" style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">🏆 Active Leagues</h2>
                <a href="{{ route('admin.leagues.create') }}" class="btn btn-primary" style="white-space: nowrap;">+ New League</a>
            </div>
            <div style="border-bottom: 2px solid #f0f0f0; margin-bottom: 20px; margin-top: 10px;"></div>
            @if($activeLeagues->isEmpty())
                <div class="empty-state">
                    <p>No active leagues</p>
                    <a href="{{ route('admin.leagues.create') }}" class="btn btn-primary" style="margin-top: 15px;">Create League</a>
                </div>
            @else
                <div class="active-leagues-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>League</th>
                            <th>Season</th>
                            <th>Course</th>
                            <th>Teams</th>
                            <th>Dates</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeLeagues as $league)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.leagues.show', $league->id) }}" class="league-link">
                                        {{ $league->name }}
                                    </a>
                                </td>
                                <td>{{ $league->season }}</td>
                                <td>{{ $league->golfCourse->name ?? 'N/A' }}</td>
                                <td>{{ $league->teams->count() }}</td>
                                <td>{{ $league->start_date->format('M d') }} - {{ $league->end_date->format('M d, Y') }}</td>
                                <td style="display: flex; gap: 8px; align-items: center;">
                                    @php
                                        $qlAllWeeks = $league->matches->pluck('week_number')->unique()->sort()->values();
                                        $qlCompletedWeeks = $league->matches->where('status', 'completed')
                                            ->pluck('week_number')->unique();
                                        $scorecardWeek = $qlAllWeeks->first(fn($w) => !$qlCompletedWeeks->contains($w))
                                            ?? $league->matches->sortByDesc('week_number')->first()?->week_number;
                                    @endphp
                                    <a href="{{ route('admin.leagues.show', $league->id) }}" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85em;">
                                        Manage
                                    </a>
                                    <div class="quick-links">
                                        <button class="quick-links-btn" onclick="toggleQuickLinks(event, this)">Quick Links ▾</button>
                                        <div class="quick-links-menu">
                                            <div class="quick-links-menu-inner">
                                                @if($scorecardWeek)
                                                    <a href="#" onclick="event.preventDefault(); openScheduleModal({{ $league->id }})">Edit Schedule</a>
                                                    <a href="{{ route('admin.leagues.printScorecards', [$league->id, $scorecardWeek]) }}" target="_blank">Print Scorecards</a>
                                                @endif
                                                <a href="{{ route('admin.leagues.scores', $league->id) }}?week={{ $scorecardWeek }}">Post Scores</a>
                                                @if($emailConfigured)
                                                    <a href="{{ route('admin.leagues.emailResults', $league->id) }}">Email Results</a>
                                                    <a href="{{ route('admin.leagues.emailMessage', $league->id) }}">Email Message</a>
                                                @endif
                                                @if($smsConfigured)
                                                    <a href="{{ route('admin.leagues.smsResults', $league->id) }}">SMS Results</a>
                                                    <a href="{{ route('admin.leagues.smsMessage', $league->id) }}">SMS Message</a>
                                                @endif
                                                <a href="{{ route('admin.leagues.finances', $league->id) }}">Finances</a>
                                            </div>
                                        </div>
                                    </div>
                                    @if(in_array($league->id, $leaguesWithScores))
                                        <span class="btn" style="background: #ccc; color: #666; padding: 6px 12px; font-size: 0.85em; cursor: not-allowed;" title="Cannot delete — active league with scores recorded">
                                            Delete
                                        </span>
                                    @else
                                        <form action="{{ route('admin.leagues.destroy', $league->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete the league \'{{ addslashes($league->name) }}\'? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85em;">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>

        <!-- Recent Matches -->
        <div class="content-section" style="position: relative; z-index: 1;">
            <h2 class="section-title" style="margin-bottom: 0; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                <span>📅 Recent Matches</span>
                <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('recentMatchesBody')" id="toggle-recentMatchesBody">&#9650;</span>
            </h2>
            <div id="recentMatchesBody">
            @if($recentMatches->isEmpty())
                <div class="empty-state">No matches scheduled</div>
            @else
                <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>League</th>
                            <th>Match</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentMatches as $match)
                            <tr>
                                <td>{{ $match->league->name ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.matches.show', $match->id) }}" class="league-link">
                                        @if($match->homeTeam && $match->awayTeam)
                                            {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}
                                        @else
                                            Week {{ $match->week_number }} Match
                                        @endif
                                    </a>
                                </td>
                                <td>{{ $match->match_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-{{ $match->status === 'completed' ? 'success' : ($match->status === 'in_progress' ? 'warning' : 'info') }}">
                                        {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($match->status === 'completed' && $match->result)
                                        @php
                                            $hHome = $match->result->holes_won_home + ($match->result->holes_tied * 0.5);
                                            $hAway = $match->result->holes_won_away + ($match->result->holes_tied * 0.5);
                                            $fH = $hHome == (int)$hHome ? (int)$hHome : number_format($hHome, 1);
                                            $fA = $hAway == (int)$hAway ? (int)$hAway : number_format($hAway, 1);
                                        @endphp
                                        @if($match->result->winning_team_id && $match->result->winningTeam)
                                            {{ $match->result->winningTeam->name }} wins
                                            ({{ $fH }}-{{ $fA }})
                                        @else
                                            Tie ({{ $fH }}-{{ $fA }})
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
            </div>
        </div>
    </div>

    {{-- Schedule Modals (one per active league) --}}
    @foreach($activeLeagues as $league)
        @php
            // Default to first non-completed week
            $allWeeksSorted = $league->matches->pluck('week_number')->unique()->sort()->values();
            $completedWeekNums = $league->matches->where('status', 'completed')
                ->pluck('week_number')->unique();
            $firstOpenWeek = $allWeeksSorted->first(fn($w) => !$completedWeekNums->contains($w));
            $currentWeek = $firstOpenWeek
                ?? $league->matches->sortByDesc('week_number')->first()?->week_number;
            $weekMatches = $currentWeek
                ? $league->matches->where('week_number', $currentWeek)->sortBy('tee_time')->values()
                : collect();
            $firstWeekMatch = $weekMatches->first();
            $allWeeks = $league->matches->pluck('week_number')->unique()->sort()->values();

            // Build team player map for this league
            $modalTeamPlayers = [];
            $modalPlayerTeamId = [];
            foreach ($league->teams as $team) {
                $modalTeamPlayers[$team->id] = $team->players->sortBy(['first_name', 'last_name'])->values();
                foreach ($team->players as $p) {
                    $modalPlayerTeamId[$p->id] = $team->id;
                }
            }
            $weekNumber = $currentWeek;
            $firstMatch = $firstWeekMatch;
        @endphp
        @if($currentWeek && $weekMatches->isNotEmpty())
            <div class="schedule-modal-overlay" id="scheduleModal-{{ $league->id }}" data-league-id="{{ $league->id }}" data-weeks="{{ $allWeeks->toJson() }}" data-current-week="{{ $currentWeek }}">
                <div class="schedule-modal">
                    <div class="schedule-modal-header">
                        <button class="schedule-week-arrow schedule-week-prev" onclick="scheduleNavWeek({{ $league->id }}, -1)" title="Previous week">&larr;</button>
                        <h3 class="schedule-week-title">{{ $league->name }} — Week {{ $currentWeek }}</h3>
                        <button class="schedule-week-arrow schedule-week-next" onclick="scheduleNavWeek({{ $league->id }}, 1)" title="Next week">&rarr;</button>
                        <button class="schedule-modal-close" onclick="closeScheduleModal({{ $league->id }})">&times;</button>
                    </div>
                    <div class="schedule-modal-body" id="scheduleModalBody-{{ $league->id }}">
                        @include('admin.schedule-modal-body', [
                            'league' => $league,
                            'weekMatches' => $weekMatches,
                            'firstMatch' => $firstMatch,
                            'weekNumber' => $weekNumber,
                            'modalTeamPlayers' => $modalTeamPlayers,
                            'modalPlayerTeamId' => $modalPlayerTeamId,
                        ])
                    </div>
                    <div class="schedule-modal-footer">
                        <a href="{{ route('admin.leagues.scheduleOverview', $league->id) }}" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85em;">Full Schedule Overview</a>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <script>
        var baseUrl = @json(url('/'));
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function toggleSection(sectionId) {
            var body = document.getElementById(sectionId);
            var toggle = document.getElementById('toggle-' + sectionId);
            if (body.style.display === 'none') {
                body.style.display = '';
                toggle.innerHTML = '&#9650;';
            } else {
                body.style.display = 'none';
                toggle.innerHTML = '&#9660;';
            }
        }

        function toggleQuickLinks(e, btn) {
            e.stopPropagation();
            var menu = btn.nextElementSibling;
            var wasOpen = menu.classList.contains('open');

            // Close all open menus first
            document.querySelectorAll('.quick-links-menu.open').forEach(function(m) {
                m.classList.remove('open');
            });

            if (!wasOpen) {
                var rect = btn.getBoundingClientRect();
                menu.style.top = rect.bottom + 4 + 'px';
                menu.style.right = (window.innerWidth - rect.right) + 'px';
                menu.style.left = 'auto';
                menu.classList.add('open');
            }
        }

        // Close quick links when clicking anywhere else or scrolling
        document.addEventListener('click', function() {
            document.querySelectorAll('.quick-links-menu.open').forEach(function(m) {
                m.classList.remove('open');
            });
        });
        window.addEventListener('scroll', function() {
            document.querySelectorAll('.quick-links-menu.open').forEach(function(m) {
                m.classList.remove('open');
            });
        }, true);

        function openScheduleModal(leagueId) {
            var modal = document.getElementById('scheduleModal-' + leagueId);
            if (modal) {
                modal.classList.add('active');
                updateWeekArrows(leagueId);
            }
        }

        function scheduleNavWeek(leagueId, direction) {
            var modal = document.getElementById('scheduleModal-' + leagueId);
            if (!modal) return;
            var weeks = JSON.parse(modal.dataset.weeks);
            var currentWeek = parseInt(modal.dataset.currentWeek);
            var idx = weeks.indexOf(currentWeek);
            var newIdx = idx + direction;
            if (newIdx < 0 || newIdx >= weeks.length) return;
            var newWeek = weeks[newIdx];

            // Disable arrows during load
            var prevBtn = modal.querySelector('.schedule-week-prev');
            var nextBtn = modal.querySelector('.schedule-week-next');
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;

            fetch(baseUrl + '/admin/leagues/' + leagueId + '/schedule-modal-week/' + newWeek, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                modal.dataset.currentWeek = data.week;
                var title = modal.querySelector('.schedule-week-title');
                // Extract league name from title (everything before " — Week")
                var titleText = title.textContent;
                var leagueName = titleText.split(' — Week')[0].trim();
                title.textContent = leagueName + ' — Week ' + data.week;

                var body = document.getElementById('scheduleModalBody-' + leagueId);
                body.innerHTML = data.html;
                updateWeekArrows(leagueId);
            })
            .catch(function() {
                if (prevBtn) prevBtn.disabled = false;
                if (nextBtn) nextBtn.disabled = false;
            });
        }

        function updateWeekArrows(leagueId) {
            var modal = document.getElementById('scheduleModal-' + leagueId);
            if (!modal) return;
            var weeks = JSON.parse(modal.dataset.weeks);
            var currentWeek = parseInt(modal.dataset.currentWeek);
            var idx = weeks.indexOf(currentWeek);
            var prevBtn = modal.querySelector('.schedule-week-prev');
            var nextBtn = modal.querySelector('.schedule-week-next');
            if (prevBtn) prevBtn.disabled = (idx <= 0);
            if (nextBtn) nextBtn.disabled = (idx >= weeks.length - 1);
        }

        function closeScheduleModal(leagueId) {
            var modal = document.getElementById('scheduleModal-' + leagueId);
            if (modal) modal.classList.remove('active');
        }

        // Close on backdrop click
        document.querySelectorAll('.schedule-modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.schedule-modal-overlay.active').forEach(function(m) {
                    m.classList.remove('active');
                });
            }
        });

        // Player swap
        function modalSwapPlayer(select) {
            var mpId = select.dataset.mpId;
            var newPlayerId = select.value;
            select.disabled = true;

            fetch(baseUrl + '/admin/match-players/' + mpId + '/swap', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ new_player_id: parseInt(newPlayerId) })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                select.disabled = false;
                if (!data.success) {
                    alert('Failed to swap player');
                    location.reload();
                } else if (data.handicap_index !== undefined) {
                    var slot = select.closest('.player-slot');
                    var row = slot.closest('.modal-player-row');
                    var hInput = row.querySelector('.handicap-input');
                    if (hInput) hInput.value = parseFloat(data.handicap_index).toFixed(1);
                }
            })
            .catch(function() {
                select.disabled = false;
                alert('Error swapping player');
                location.reload();
            });
        }

        // Handicap update
        function modalUpdateHandicap(input) {
            var mpId = input.dataset.mpId;
            var newValue = parseFloat(input.value);
            if (isNaN(newValue)) return;

            input.disabled = true;
            input.style.borderColor = '#ffc107';
            fetch(baseUrl + '/admin/match-players/' + mpId + '/handicap', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
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

        // Substitute functions
        function modalShowSubUI(mpId) {
            var slot = document.querySelector('.schedule-modal-overlay .player-slot[data-mp-id="' + mpId + '"]');
            if (!slot) return;
            slot.querySelector('.normal-player-view').style.display = 'none';
            var searchUI = slot.querySelector('.sub-search-ui');
            searchUI.style.display = 'inline';
            var input = searchUI.querySelector('.sub-search-input');
            input.value = '';
            input.focus();
        }

        function modalHideSubUI(mpId) {
            var slot = document.querySelector('.schedule-modal-overlay .player-slot[data-mp-id="' + mpId + '"]');
            if (!slot) return;
            slot.querySelector('.sub-search-ui').style.display = 'none';
            slot.querySelector('.sub-search-results').style.display = 'none';
            slot.querySelector('.normal-player-view').style.display = '';
        }

        var modalSubSearchTimeout = null;
        function modalSearchSubstitute(input) {
            clearTimeout(modalSubSearchTimeout);
            var mpId = input.dataset.mpId;
            var query = input.value.trim();
            var resultsDiv = input.parentElement.querySelector('.sub-search-results');

            if (query.length < 2) { resultsDiv.style.display = 'none'; return; }

            modalSubSearchTimeout = setTimeout(function() {
                fetch(baseUrl + '/admin/players/search?q=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
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
                        item.onclick = function() { modalAssignSubstitute(mpId, p.id, null, null); };
                        resultsDiv.appendChild(item);
                    });

                    var parts = query.split(/\s+/);
                    var createItem = document.createElement('div');
                    createItem.style.cssText = 'padding: 6px 10px; cursor: pointer; font-size: 0.85em; color: #28a745; font-weight: 600; border-top: 2px solid #eee;';
                    createItem.textContent = '+ Create "' + query + '" as new player';
                    createItem.onmouseover = function() { this.style.background = '#d4edda'; };
                    createItem.onmouseout = function() { this.style.background = 'white'; };
                    createItem.onclick = function() { modalAssignSubstitute(mpId, null, parts[0] || '', parts.slice(1).join(' ') || ''); };
                    resultsDiv.appendChild(createItem);

                    resultsDiv.style.display = 'block';
                });
            }, 300);
        }

        function modalAssignSubstitute(mpId, substitutePlayerId, firstName, lastName) {
            var body = {};
            if (substitutePlayerId) {
                body.substitute_player_id = substitutePlayerId;
            } else {
                body.first_name = firstName;
                body.last_name = lastName;
            }

            fetch(baseUrl + '/admin/match-players/' + mpId + '/substitute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    var slot = document.querySelector('.schedule-modal-overlay .player-slot[data-mp-id="' + mpId + '"]');
                    slot.querySelector('.sub-search-ui').style.display = 'none';
                    slot.querySelector('.normal-player-view').style.display = 'none';
                    slot.querySelector('.sub-player-view').style.display = '';
                    slot.querySelector('.sub-display-name').textContent = data.substitute_name;

                    var row = slot.closest('.modal-player-row');
                    var hInput = row.querySelector('.handicap-input');
                    if (hInput) hInput.value = parseFloat(data.handicap_index).toFixed(1);
                } else {
                    alert('Failed to assign substitute: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function() { alert('Error assigning substitute'); });
        }

        function modalRemoveSubstitute(mpId) {
            if (!confirm('Remove substitute and restore original player?')) return;

            fetch(baseUrl + '/admin/match-players/' + mpId + '/substitute', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    var slot = document.querySelector('.schedule-modal-overlay .player-slot[data-mp-id="' + mpId + '"]');
                    slot.querySelector('.sub-player-view').style.display = 'none';
                    slot.querySelector('.normal-player-view').style.display = '';

                    var row = slot.closest('.modal-player-row');
                    var hInput = row.querySelector('.handicap-input');
                    if (hInput) hInput.value = parseFloat(data.handicap_index).toFixed(1);
                } else {
                    alert('Failed to remove substitute');
                }
            })
            .catch(function() { alert('Error removing substitute'); });
        }
    </script>
</body>
</html>

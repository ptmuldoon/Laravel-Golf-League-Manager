<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
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
        .header {
            color: white;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            padding-right: 50px; /* balance for the fixed gear icon */
        }
        .header-logo {
            height: 90px;
            flex-shrink: 0;
        }
        h1 {
            font-family: 'Impact', 'Arial Black', sans-serif;
            font-size: 3em;
            margin-bottom: 0;
            color: #f0c040;
            letter-spacing: 4px;
            white-space: nowrap;
            -webkit-text-stroke: 1.5px #8b6914;
            text-shadow: 0 0 10px rgba(240, 192, 64, 0.4), 2px 2px 4px rgba(0,0,0,0.3);
        }
        .tagline {
            font-size: 1.2em;
            opacity: 0.9;
            margin-top: 4px;
        }
        .admin-link {
            position: fixed;
            top: 15px;
            right: 15px;
            color: white;
            text-decoration: none;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            transition: all 0.3s ease;
            z-index: 100;
        }
        .admin-link:hover {
            background: rgba(255,255,255,0.4);
            transform: scale(1.1);
        }
        .league-selector select {
            padding: 8px 14px;
            font-size: 0.9em;
            font-weight: 600;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            color: var(--primary-color);
            cursor: pointer;
        }
        .league-selector select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 0;
        }
        .section-title {
            font-size: 1.8em;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th {
            background: var(--primary-light);
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #e0e0e0;
            font-size: 0.9em;
        }
        td {
            padding: 5px 8px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95em;
        }
        tr:hover {
            background: var(--primary-light);
        }
        .team-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .team-link:hover {
            text-decoration: underline;
        }
        .match-card {
            background: var(--primary-light);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
        }
        .match-teams {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .match-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 8px;
        }
        .match-result {
            background: #d4edda;
            padding: 8px 12px;
            border-radius: 5px;
            display: inline-block;
            color: #155724;
            font-weight: 600;
            margin-top: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        .rank-1 {
            background: #fff9e6 !important;
        }
        .top-sections-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (min-width: 900px) {
            .top-sections-grid {
                grid-template-columns: 1fr auto;
            }
        }
        .top-sections-grid .content-section {
            margin-bottom: 0;
        }
        .tee-time-grid {
            display: grid;
            grid-template-columns: auto 1fr auto 1fr;
            gap: 4px 8px;
            align-items: center;
            font-size: 0.85em;
        }
        .tee-time-grid .tee-row {
            display: contents;
        }
        .tee-time-grid .tee-row-header > *:not(:first-child) {
            padding-bottom: 6px;
            border-bottom: 2px solid #e8ecf4;
        }
        .tee-time-grid .tee-row + .tee-row:not(.tee-row-header) > *:not(:first-child) {
            border-top: 1px solid #f0f0f0;
            padding-top: 5px;
        }
        .tee-time-grid .tee-row-header + .tee-row > *:not(:first-child) {
            border-top: none;
            padding-top: 6px;
        }
        .tee-time-grid .tee-row:not(:last-child) > * {
            padding-bottom: 5px;
        }
        .tee-time-badge {
            background: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9em;
            white-space: nowrap;
            text-align: center;
        }
        .tee-time-side {
            color: #333;
            line-height: 1.3;
        }
        .tee-time-side.away {
            text-align: right;
        }
        .tee-time-vs {
            color: #999;
            font-weight: 400;
            text-align: center;
        }
        .quick-links {
            position: relative;
            display: inline-block;
        }
        .quick-links-btn {
            padding: 5px 12px;
            font-size: 0.85em;
            font-weight: 600;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .quick-links-btn:hover {
            background: var(--secondary-color);
        }
        .quick-links-menu {
            display: none;
            position: absolute;
            left: 0;
            top: 100%;
            padding-top: 4px;
            z-index: 100;
            min-width: 170px;
        }
        .quick-links-menu-inner {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .quick-links:hover .quick-links-menu {
            display: block;
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
        .quick-links-menu-inner a + a {
            border-top: 1px solid #f0f0f0;
        }
        .player-rank {
            font-weight: 600;
            color: var(--primary-color);
            text-align: center;
        }
        .stat-highlight {
            font-weight: 600;
            color: #28a745;
        }
        .scrollable-table {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }
        .scrollable-table::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 20px;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.8));
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .scrollable-table.has-overflow::after {
            opacity: 1;
        }
        /* Scorecard styles for Week Results */
        .scorecard-table {
            min-width: 600px;
            font-size: 0.82em;
        }
        .scorecard-table th {
            background: var(--primary-color);
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #5568d3;
            font-size: 0.9em;
        }
        .scorecard-table td {
            padding: 4px 4px;
            text-align: center;
            border: 1px solid #e0e0e0;
            font-size: 0.9em;
        }
        .scorecard-table tr:hover {
            background: transparent;
        }
        .sc-player-col {
            width: 130px;
            min-width: 100px;
        }
        .sc-hcp-col {
            width: 40px;
        }
        .sc-hole-col {
            width: 32px;
        }
        .sc-total-col {
            width: 36px;
        }
        .sc-par-row {
            background: #f0f0f0;
        }
        .sc-par-row td {
            font-weight: 600;
            background: #f0f0f0;
        }
        .sc-hdcp-row {
            background: #fff8e1;
        }
        .sc-hdcp-row td {
            font-size: 0.8em;
            color: #7b6b00;
            background: #fff8e1;
        }
        .sc-team-header td {
            background: var(--secondary-color);
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 5px 8px;
        }
        .sc-dots-row td {
            padding: 0 4px;
            font-size: 0.6em;
            color: var(--secondary-color);
            border: none;
            line-height: 1;
        }
        .sc-player-name {
            text-align: left !important;
            font-weight: 600;
            color: #333;
            background: var(--primary-light);
            white-space: nowrap;
        }
        .sc-hcp-cell {
            font-size: 0.8em;
            color: #666;
        }
        .sc-score-cell {
            font-weight: 500;
        }
        .sc-total-cell {
            background: var(--primary-light);
            font-weight: 600;
            color: var(--primary-color);
        }
        /* Match results row styles */
        .sc-results-row td {
            padding: 4px 4px;
            font-size: 0.85em;
            font-weight: 700;
            border: 1px solid #e0e0e0;
            text-align: center;
        }
        .sc-result-home {
            background: #e3f2fd;
            color: #1565c0;
        }
        .sc-result-away {
            background: #fce4ec;
            color: #c62828;
        }
        .sc-result-tie {
            background: #f5f5f5;
            color: #999;
        }
        /* Mobile styles */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }
            .header {
                margin-bottom: 20px;
                gap: 10px;
                padding-right: 40px;
            }
            .header-logo {
                height: 70px;
            }
            h1 {
                font-size: 1.8em;
                white-space: normal;
                letter-spacing: 2px;
            }
            .tagline {
                font-size: 1em;
            }
            .content-section {
                padding: 16px;
                border-radius: 10px;
                margin-bottom: 14px;
            }
            .section-title {
                font-size: 1.3em;
                margin-bottom: 14px;
            }
            th {
                padding: 8px 5px;
                font-size: 0.8em;
            }
            td {
                padding: 4px 5px;
                font-size: 0.82em;
            }
            .match-card {
                padding: 12px 14px;
            }
            .match-teams {
                font-size: 1em;
            }
            .match-info {
                font-size: 0.82em;
            }
            .match-result {
                font-size: 0.88em;
                padding: 6px 10px;
            }
            .empty-state {
                padding: 24px;
                font-size: 0.9em;
            }
            .tee-time-grid {
                font-size: 0.78em;
                gap: 3px 5px;
            }
            .tee-time-badge {
                font-size: 0.8em;
                padding: 2px 6px;
            }
            .scorecard-table {
                font-size: 0.75em;
                min-width: 500px;
            }
            .scorecard-table th, .scorecard-table td {
                padding: 3px 2px;
            }
            .sc-player-col { min-width: 80px; }
        }
        /* Small phone styles */
        @media (max-width: 480px) {
            body {
                padding: 8px;
            }
            .header-logo {
                height: 60px;
            }
            h1 {
                font-size: 1.3em;
                letter-spacing: 1px;
            }
            .tagline {
                font-size: 0.85em;
            }
            .content-section {
                padding: 12px;
                border-radius: 8px;
            }
            .section-title {
                font-size: 1.15em;
            }
            th {
                padding: 6px 4px;
                font-size: 0.75em;
            }
            td {
                padding: 3px 4px;
                font-size: 0.78em;
            }
            .tee-time-grid {
                grid-template-columns: auto 1fr auto 1fr;
                font-size: 0.72em;
                gap: 2px 4px;
            }
            .tee-time-badge {
                font-size: 0.75em;
                padding: 2px 4px;
            }
            .scorecard-table {
                font-size: 0.7em;
                min-width: 450px;
            }
            .scorecard-table th, .scorecard-table td {
                padding: 2px 1px;
            }
            .sc-player-col { min-width: 70px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="/images/logo3.svg" alt="" class="header-logo">
            <div style="flex: 1; text-align: center;">
                <h1>{{ config('app.name') }}</h1>
                @if(config('app.slogan'))
                    <p class="tagline">{{ config('app.slogan') }}</p>
                @endif
            </div>
        </div>

        @auth
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="admin-link" title="Admin Dashboard">⚙️</a>
            @endif
        @else
            <a href="{{ route('login') }}" class="admin-link" title="Admin Login">🔐</a>
        @endauth

        <!-- Standings per League -->
        @if($activeLeagues->isNotEmpty())
            @if($allActiveLeagues->count() > 1)
                <div class="league-selector" style="margin-bottom: 15px;">
                    <select onchange="window.location.href='/?league=' + this.value">
                        @foreach($allActiveLeagues as $leagueOption)
                            <option value="{{ $leagueOption->id }}" {{ $selectedLeagueId == $leagueOption->id ? 'selected' : '' }}>
                                {{ $leagueOption->name }} ({{ $leagueOption->season }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            @foreach($activeLeagues as $league)
                <!-- League Info -->
                <div class="content-section">
                    <h2 class="section-title" style="margin-bottom: 10px;">
                        <a href="{{ route('admin.leagues.show', $league->id) }}" style="color: var(--primary-color); text-decoration: none;">
                            {{ $league->name }}
                        </a>
                    </h2>
                    <div style="color: #666; font-size: 0.95em; display: flex; align-items: center; gap: 12px;">
                        <a href="#" onclick="event.preventDefault(); showQuickLink('home', {{ $league->id }})" class="quick-links-btn" style="text-decoration: none; display: inline-block;">Home</a>
                        <div class="quick-links">
                            <button class="quick-links-btn">Quick Links &#9662;</button>
                            <div class="quick-links-menu">
                                <div class="quick-links-menu-inner">
                                    <a href="#" onclick="event.preventDefault(); showQuickLink('schedule', {{ $league->id }})">Full Schedule</a>
                                    <a href="#" onclick="event.preventDefault(); showQuickLink('hole-stats', {{ $league->id }})">Hole Stats</a>
                                    <a href="#" onclick="event.preventDefault(); showQuickLink('player-stats', {{ $league->id }})">Player Stats</a>
                                </div>
                            </div>
                        </div>
                        Results Thru Week {{ isset($completedWeeks[$league->id]) && $completedWeeks[$league->id]->isNotEmpty() ? $completedWeeks[$league->id]->last() : '—' }}
                    </div>
                </div>

                <div id="dynamic-content-{{ $league->id }}" style="display: none;"></div>

                <div id="home-content-{{ $league->id }}">
                <div class="top-sections-grid">
                    <!-- Team Standings -->
                    <div class="content-section">
                        <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>🏆 Team Standings</span>
                            <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('team-standings-body-{{ $league->id }}')" id="toggle-team-standings-body-{{ $league->id }}">&#9650;</span>
                        </h2>
                        <div id="team-standings-body-{{ $league->id }}">
                            @if($league->segments->isNotEmpty() && isset($segmentStandings[$league->id]))
                                {{-- Segment tabs --}}
                                <div style="display: flex; gap: 5px; margin-bottom: 15px; flex-wrap: wrap;">
                                    @foreach($league->segments as $si => $segment)
                                        <button type="button" onclick="showHomeSegmentTab({{ $league->id }}, {{ $segment->id }})" id="home-seg-tab-{{ $league->id }}-{{ $segment->id }}" style="padding: 6px 14px; border: 2px solid {{ $si === 0 ? 'var(--primary-color)' : '#e0e0e0' }}; border-radius: 6px; font-size: 0.85em; font-weight: 600; cursor: pointer; background: {{ $si === 0 ? 'var(--primary-color)' : 'white' }}; color: {{ $si === 0 ? 'white' : 'var(--primary-color)' }};">
                                            {{ $segment->name }}
                                        </button>
                                    @endforeach
                                </div>

                                @foreach($league->segments as $si => $segment)
                                    <div id="home-seg-content-{{ $league->id }}-{{ $segment->id }}" style="{{ $si !== 0 ? 'display: none;' : '' }}">
                                        @php $segTeams = $segmentStandings[$league->id][$segment->id] ?? collect(); @endphp
                                        @if($segTeams->isNotEmpty())
                                            <div class="scrollable-table">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 45px;">#</th>
                                                            <th style="width: 1%; white-space: nowrap; border-right: 3px solid #d0d5e0; padding-right: 12px;">Team</th>
                                                            @if(isset($segmentCompletedWeeks[$league->id][$segment->id]))
                                                                @foreach($segmentCompletedWeeks[$league->id][$segment->id] as $week)
                                                                    <th style="width: 50px; text-align: center; line-height: 1.2;"><span style="font-size: 0.75em; display: block; opacity: 0.7;">Week</span>{{ $week }}</th>
                                                                @endforeach
                                                            @endif
                                                            <th></th>
                                                            <th style="width: 45px; border-left: 3px solid #d0d5e0; padding-left: 12px;">W</th>
                                                            <th style="width: 45px;">L</th>
                                                            <th style="width: 45px;">T</th>
                                                            <th style="width: 60px;">Pts</th>
                                                            <th style="width: 65px;">Win%</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($segTeams as $index => $team)
                                                            <tr class="{{ $index === 0 ? 'rank-1' : '' }}">
                                                                <td class="player-rank">
                                                                    {{ $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : $index + 1)) }}
                                                                </td>
                                                                <td style="white-space: nowrap; border-right: 3px solid #d0d5e0; padding-right: 12px;">
                                                                    <a href="{{ route('admin.teams.show', $team->id) }}" class="team-link">
                                                                        {{ $team->name }}
                                                                    </a>
                                                                </td>
                                                                @if(isset($segmentCompletedWeeks[$league->id][$segment->id]))
                                                                    @foreach($segmentCompletedWeeks[$league->id][$segment->id] as $week)
                                                                        <td style="text-align: center;">
                                                                            {{ isset($segmentWeeklyScores[$league->id][$segment->id][$team->id][$week]) ? number_format($segmentWeeklyScores[$league->id][$segment->id][$team->id][$week], 2) : '-' }}
                                                                        </td>
                                                                    @endforeach
                                                                @endif
                                                                <td></td>
                                                                <td style="color: #28a745; font-weight: 600; border-left: 3px solid #d0d5e0; padding-left: 12px;">{{ $team->wins }}</td>
                                                                <td style="color: #dc3545; font-weight: 600;">{{ $team->losses }}</td>
                                                                <td style="color: #856404; font-weight: 600;">{{ $team->ties }}</td>
                                                                <td style="font-weight: 600; color: var(--primary-color);">{{ number_format($team->totalPoints(), 2) }}</td>
                                                                <td>{{ $team->winPercentage() }}%</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="empty-state" style="padding: 20px;">No teams in this segment</div>
                                        @endif
                                    </div>
                                @endforeach
                            @elseif($league->teams->isNotEmpty())
                                <div class="scrollable-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th style="width: 45px;">#</th>
                                                <th style="width: 1%; white-space: nowrap; border-right: 3px solid #d0d5e0; padding-right: 12px;">Team</th>
                                                @if(isset($completedWeeks[$league->id]))
                                                    @foreach($completedWeeks[$league->id] as $week)
                                                        <th style="width: 50px; text-align: center; line-height: 1.2;"><span style="font-size: 0.75em; display: block; opacity: 0.7;">Week</span>{{ $week }}</th>
                                                    @endforeach
                                                @endif
                                                <th></th>
                                                <th style="width: 45px; border-left: 3px solid #d0d5e0; padding-left: 12px;">W</th>
                                                <th style="width: 45px;">L</th>
                                                <th style="width: 45px;">T</th>
                                                <th style="width: 60px;">Pts</th>
                                                <th style="width: 65px;">Win%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($league->teams as $index => $team)
                                                <tr class="{{ $index === 0 ? 'rank-1' : '' }}">
                                                    <td class="player-rank">
                                                        {{ $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : $index + 1)) }}
                                                    </td>
                                                    <td style="white-space: nowrap; border-right: 3px solid #d0d5e0; padding-right: 12px;">
                                                        <a href="{{ route('admin.teams.show', $team->id) }}" class="team-link">
                                                            {{ $team->name }}
                                                        </a>
                                                    </td>
                                                    @if(isset($completedWeeks[$league->id]))
                                                        @foreach($completedWeeks[$league->id] as $week)
                                                            <td style="text-align: center;">
                                                                {{ isset($weeklyTeamScores[$league->id][$team->id][$week]) ? number_format($weeklyTeamScores[$league->id][$team->id][$week], 2) : '-' }}
                                                            </td>
                                                        @endforeach
                                                    @endif
                                                    <td></td>
                                                    <td style="color: #28a745; font-weight: 600; border-left: 3px solid #d0d5e0; padding-left: 12px;">{{ $team->wins }}</td>
                                                    <td style="color: #dc3545; font-weight: 600;">{{ $team->losses }}</td>
                                                    <td style="color: #856404; font-weight: 600;">{{ $team->ties }}</td>
                                                    <td style="font-weight: 600; color: var(--primary-color);">{{ number_format($team->totalPoints(), 2) }}</td>
                                                    <td>{{ $team->winPercentage() }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="empty-state" style="padding: 20px;">No teams yet</div>
                            @endif
                        </div>
                    </div>

                    <!-- Next Week Tee Times -->
                    @php
                        $leagueUpcoming = $upcomingMatches->where('league_id', $league->id);
                        $nextWeekMatches = $leagueUpcoming->count() > 0
                            ? $leagueUpcoming->groupBy('week_number')->first()->sortBy('tee_time')
                            : collect();
                        $nextWeekNum = $nextWeekMatches->isNotEmpty() ? $nextWeekMatches->first()->week_number : null;
                        $nextWeekDate = $nextWeekMatches->isNotEmpty() ? $nextWeekMatches->first()->match_date : null;
                    @endphp
                    <div class="content-section">
                        <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center; text-align: center;">
                            <span style="flex: 1;">🕐 Week {{ $nextWeekNum ?? '?' }} Tee Times</span>
                            <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('tee-times-body-{{ $league->id }}')" id="toggle-tee-times-body-{{ $league->id }}">&#9650;</span>
                        </h2>
                        <div id="tee-times-body-{{ $league->id }}">
                        @if($nextWeekMatches->isNotEmpty())
                            <div style="font-size: 0.85em; color: #666; margin-bottom: 8px; text-align: center;">
                                @if($nextWeekDate)
                                    {{ $nextWeekDate->format('l, M d') }} &bull; {{ $nextWeekMatches->first()->golfCourse->name ?? '' }}
                                @endif
                                <br>{{ $nextWeekMatches->first()->holes === 'back_9' ? 'Back 9' : 'Front 9' }} &bull; {{ \App\Models\ScoringSetting::scoringTypes()[$nextWeekMatches->first()->scoring_type] ?? ucfirst(str_replace('_', ' ', $nextWeekMatches->first()->scoring_type)) }}
                            </div>
                            @php
                                $firstMatch = $nextWeekMatches->first();
                                $homeTeamName = $matchTeamNames[$firstMatch->id]['home'] ?? 'TBD';
                                $awayTeamName = $matchTeamNames[$firstMatch->id]['away'] ?? 'TBD';
                            @endphp
                            <div class="tee-time-grid">
                                <div class="tee-row tee-row-header">
                                    <div></div>
                                    <div class="tee-time-side away" style="color: var(--primary-color); font-weight: 700;">{{ $homeTeamName }}</div>
                                    <div class="tee-time-vs" style="color: var(--primary-color); font-weight: 600;">vs.</div>
                                    <div class="tee-time-side" style="color: var(--primary-color); font-weight: 700;">{{ $awayTeamName }}</div>
                                </div>
                                @foreach($nextWeekMatches as $upcoming)
                                    @php
                                        $shortName = function($mp) {
                                            if ($mp->player && $mp->player->first_name && $mp->player->last_name) {
                                                return strtoupper(substr($mp->player->first_name, 0, 1)) . '. ' . $mp->player->last_name;
                                            }
                                            return $mp->player ? $mp->player->name : ($mp->substitute_name ?? '');
                                        };
                                        $homePlayers = $upcoming->matchPlayers->where('position_in_pairing', '<=', 2)
                                            ->map($shortName)->filter()->map(fn($n) => e($n))->implode(' &bull; ');
                                        $awayPlayers = $upcoming->matchPlayers->where('position_in_pairing', '>', 2)
                                            ->map($shortName)->filter()->map(fn($n) => e($n))->implode(' &bull; ');
                                    @endphp
                                    <div class="tee-row">
                                        <div class="tee-time-badge">
                                            @if($upcoming->tee_time)
                                                {{ \Carbon\Carbon::parse($upcoming->tee_time)->format('g:i A') }}
                                            @else
                                                TBD
                                            @endif
                                        </div>
                                        <div class="tee-time-side away">{!! $homePlayers ?: ($matchTeamNames[$upcoming->id]['home'] ?? 'TBD') !!}</div>
                                        <div class="tee-time-vs">vs.</div>
                                        <div class="tee-time-side">{!! $awayPlayers ?: ($matchTeamNames[$upcoming->id]['away'] ?? 'TBD') !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state" style="padding: 15px;">No upcoming tee times</div>
                        @endif
                        </div>
                    </div>
                </div>

                <!-- Player Standings -->
                <div class="content-section">
                    <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>👤 Player Standings</span>
                        <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('player-standings-body-{{ $league->id }}')" id="toggle-player-standings-body-{{ $league->id }}">&#9650;</span>
                    </h2>
                    @if(isset($playerStandings[$league->id]) && $playerStandings[$league->id]->isNotEmpty())
                        @php
                            $pWeeks = $playerWeeks[$league->id] ?? collect();
                            $defaultWeek = $pWeeks->isNotEmpty() ? $pWeeks->last() : null;
                        @endphp
                        <div id="player-standings-body-{{ $league->id }}">
                        <div class="scrollable-table">
                            <table id="player-standings-table-{{ $league->id }}">
                                <thead>
                                    <tr>
                                        <th colspan="4" style="border-bottom: none;"></th>
                                        <th colspan="3" style="text-align: center; font-size: 0.85em; color: var(--primary-color); border-bottom: 1px solid #d0d5e0; border-right: 3px solid #d0d5e0; padding-bottom: 4px;">
                                            <span style="cursor: pointer; user-select: none;" onclick="changePlayerWeek({{ $league->id }}, -1)">&#9664;</span>
                                            <span id="player-week-label-{{ $league->id }}" style="margin: 0 8px;">Week {{ $defaultWeek }}</span>
                                            <span style="cursor: pointer; user-select: none;" onclick="changePlayerWeek({{ $league->id }}, 1)">&#9654;</span>
                                        </th>
                                        <th colspan="{{ 6 + ($league->segments->isNotEmpty() ? $league->segments->count() : 0) + 3 }}" style="text-align: center; font-size: 0.85em; color: var(--primary-color); border-bottom: 1px solid #d0d5e0; padding-bottom: 4px;">Season to Date</th>
                                    </tr>
                                    <tr>
                                        <th style="width: 45px;">#</th>
                                        <th style="width: 1%; white-space: nowrap;">Player</th>
                                        <th style="width: 50px;">HI</th>
                                        <th style="width: 45px;">CH</th>
                                        <th style="width: 1%; text-align: center; line-height: 1.2; white-space: nowrap;" title="Par 3 Wins"><span style="display: block;">Par 3's</span>Won</th>
                                        <th style="width: 50px; text-align: center;">Score</th>
                                        <th style="width: 1%; text-align: center; border-right: 3px solid #d0d5e0; padding-right: 12px; white-space: nowrap; line-height: 1.2;" title="Match Points"><span style="display: block;">Match</span>Pts</th>
                                        <th style="width: 45px; border-left: 3px solid #d0d5e0; padding-left: 12px;">MP</th>
                                        <th style="width: 55px;">Avg</th>
                                        <th style="width: 50px;">Low</th>
                                        <th style="width: 1%; text-align: center; line-height: 1.2; white-space: nowrap;" title="Total Par 3 Wins"><span style="display: block;">Par 3's</span>Won</th>
                                        <th style="width: 80px; text-align: center; line-height: 1.2;"><span style="display: block;">Match</span>W-L-T</th>
                                        <th style="width: 50px; text-align: center; white-space: nowrap;">Match %</th>
                                        @if($league->segments->isNotEmpty())
                                            @foreach($league->segments->sortBy('display_order') as $seg)
                                                <th style="width: 50px; text-align: center; font-size: 0.8em;" title="{{ $seg->name }} Points">{{ $seg->name }}</th>
                                            @endforeach
                                        @endif
                                        <th style="width: 55px; text-align: center; border-left: 3px solid #d0d5e0; padding-left: 12px; line-height: 1.2;"><span style="display: block;">Total</span>Points</th>
                                        <th style="width: 45px; text-align: center; border-left: 3px solid #d0d5e0; padding-left: 12px;">Rank</th>
                                        <th style="width: 1%; text-align: center; white-space: nowrap; line-height: 1.2;"><span style="display: block;">Pts.</span>Back</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($playerStandings[$league->id] as $index => $stat)
                                        @php $wd = $stat['weekly_data'][$defaultWeek] ?? null; @endphp
                                        <tr class="{{ $index === 0 ? 'rank-1' : '' }}" data-player-id="{{ $stat['player']->id }}">
                                            <td class="player-rank">
                                                {{ $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : $index + 1)) }}
                                            </td>
                                            <td style="white-space: nowrap;">
                                                <a href="{{ route('players.show', $stat['player']->id) }}" class="team-link">{{ $stat['player']->name }}</a>
                                            </td>
                                            <td>{{ $stat['current_hi'] ?? '-' }}</td>
                                            <td style="color: #888; font-size: 0.85em;">{{ $stat['current_ch'] ?? '-' }}</td>
                                            <td class="pw-par3" style="text-align: center;">{{ $wd && $wd['par3'] > 0 ? $wd['par3'] : '-' }}</td>
                                            <td class="pw-gross" style="text-align: center;">{{ $wd && $wd['gross'] !== null ? $wd['gross'] : '-' }}</td>
                                            <td class="pw-pts" style="text-align: center; font-weight: 600; color: var(--primary-color); border-right: 3px solid #d0d5e0; padding-right: 12px;">{{ $wd && $wd['points'] !== null ? $wd['points'] : '-' }}</td>
                                            <td style="border-left: 3px solid #d0d5e0; padding-left: 12px;">{{ $stat['matches_played'] }}</td>
                                            <td class="stat-highlight">{{ $stat['avg_score'] ?? '-' }}</td>
                                            <td style="font-weight: 600; color: var(--primary-color);">{{ $stat['low_round'] ?? '-' }}</td>
                                            <td style="text-align: center;">{{ $stat['total_par3'] > 0 ? $stat['total_par3'] : '-' }}</td>
                                            <td style="text-align: center; white-space: nowrap;">{{ $stat['season_wins'] }}-{{ $stat['season_losses'] }}-{{ $stat['season_ties'] }}</td>
                                            <td style="text-align: center;">{{ $stat['win_pct'] !== null ? number_format($stat['win_pct'], 1) . '%' : '-' }}</td>
                                            @if($league->segments->isNotEmpty())
                                                @foreach($league->segments->sortBy('display_order') as $seg)
                                                    <td style="text-align: center;">{{ number_format($stat['segment_points'][$seg->id] ?? 0, 2) }}</td>
                                                @endforeach
                                            @endif
                                            <td style="text-align: center; font-weight: 700; color: var(--primary-color); border-left: 3px solid #d0d5e0; padding-left: 12px;">{{ number_format($stat['total_season_points'], 2) }}</td>
                                            <td style="text-align: center; font-weight: 600; border-left: 3px solid #d0d5e0; padding-left: 12px;">{{ $stat['points_rank'] }}</td>
                                            <td style="text-align: center; color: #888;">{{ $stat['points_back'] > 0 ? number_format($stat['points_back'], 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <script>
                            (function() {
                                var weekData{{ $league->id }} = @json($playerStandings[$league->id]->mapWithKeys(fn($s) => [$s['player']->id => $s['weekly_data']]));
                                var weeks{{ $league->id }} = @json($pWeeks->values());
                                window['playerWeekData_{{ $league->id }}'] = weekData{{ $league->id }};
                                window['playerWeeks_{{ $league->id }}'] = weeks{{ $league->id }};
                                window['playerWeekIdx_{{ $league->id }}'] = weeks{{ $league->id }}.length - 1;
                            })();
                        </script>
                        </div>
                    @else
                        <div class="empty-state" style="padding: 20px;">No completed matches yet</div>
                    @endif
                </div>
                </div>{{-- end home-content --}}
            @endforeach
        @endif

        <div id="home-global-content">
        <!-- Par 3 Winners -->
        @if($par3Winners->isNotEmpty())
            <div class="content-section">
                <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>🎯 Par 3 Winners</span>
                    <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('par3-winners-body')" id="toggle-par3-winners-body">&#9650;</span>
                </h2>
                <div id="par3-winners-body">
                @php
                    $par3ByWeek = $par3Winners->groupBy('week_number');
                    $maxPar3 = $par3ByWeek->max(fn($w) => $w->count());
                @endphp
                <div class="scrollable-table">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: left;">Week</th>
                                @for($i = 0; $i < $maxPar3; $i++)
                                    <th style="text-align: left;">Hole</th>
                                    <th style="text-align: left;">Winner</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($par3ByWeek as $week => $winners)
                                <tr>
                                    <td style="font-weight: 600; color: var(--primary-color);">Week {{ $week }}</td>
                                    @foreach($winners->values() as $winner)
                                        <td>Hole {{ $winner->hole_number }}</td>
                                        <td style="font-weight: 600;">
                                            @if($winner->player)
                                                <a href="{{ route('players.show', $winner->player->id) }}" class="team-link">{{ $winner->player->name }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endforeach
                                    @for($i = $winners->count(); $i < $maxPar3; $i++)
                                        <td>-</td>
                                        <td>-</td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        @endif

        <!-- Week Match Results -->
        @if(!empty($allCompletedWeeks))
            <div class="content-section">
                <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <span>🏌️</span>
                        <span style="cursor: pointer; user-select: none; font-size: 0.65em; color: var(--primary-color); padding: 2px 6px;" onclick="changeWeekResults(-1)" id="week-results-prev">&#9664;</span>
                        <span id="week-results-label">Week {{ $currentWeekNumber }} Results</span>
                        <span style="cursor: pointer; user-select: none; font-size: 0.65em; color: var(--primary-color); padding: 2px 6px;" onclick="changeWeekResults(1)" id="week-results-next">&#9654;</span>
                    </span>
                    <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('current-week-body')" id="toggle-current-week-body">&#9650;</span>
                </h2>
                <div id="current-week-body">
                    <div id="week-results-content">
                        @include('leagues.week-results-partial', [
                            'weekMatches' => $currentWeekMatches,
                            'matchTeamNames' => $matchTeamNames,
                            'scorecardData' => $currentWeekScorecardData,
                        ])
                    </div>
                </div>
            </div>
        @endif
        </div>{{-- end home-global-content --}}

    </div>

    <script>
        var quickLinkCache = {};

        // Week results navigation
        var weekResultsWeeks = @json($allCompletedWeeks ?? []);
        var weekResultsLeagueId = {{ $selectedLeagueId ?? 'null' }};
        var weekResultsIdx = weekResultsWeeks.length - 1;
        var weekResultsCache = {};

        function changeWeekResults(direction) {
            if (!weekResultsWeeks.length) return;
            var newIdx = weekResultsIdx + direction;
            if (newIdx < 0 || newIdx >= weekResultsWeeks.length) return;
            weekResultsIdx = newIdx;
            var weekNum = weekResultsWeeks[weekResultsIdx];
            document.getElementById('week-results-label').textContent = 'Week ' + weekNum + ' Results';
            updateWeekArrowVisibility();
            loadWeekResults(weekNum);
        }

        function updateWeekArrowVisibility() {
            var prev = document.getElementById('week-results-prev');
            var next = document.getElementById('week-results-next');
            if (prev) prev.style.opacity = weekResultsIdx <= 0 ? '0.3' : '1';
            if (next) next.style.opacity = weekResultsIdx >= weekResultsWeeks.length - 1 ? '0.3' : '1';
        }

        function loadWeekResults(weekNum) {
            var container = document.getElementById('week-results-content');
            if (!container) return;

            var cacheKey = weekResultsLeagueId + '-' + weekNum;
            if (weekResultsCache[cacheKey]) {
                container.innerHTML = weekResultsCache[cacheKey];
                checkScrollableOverflow();
                return;
            }

            container.innerHTML = '<div style="text-align: center; padding: 30px; color: #888;">Loading...</div>';

            fetch('/leagues/' + weekResultsLeagueId + '/week-results-partial/' + weekNum)
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    weekResultsCache[cacheKey] = html;
                    container.innerHTML = html;
                    checkScrollableOverflow();
                })
                .catch(function() {
                    container.innerHTML = '<div style="text-align: center; padding: 30px; color: #dc3545;">Failed to load results.</div>';
                });
        }

        // Cache the initial (current) week's content
        document.addEventListener('DOMContentLoaded', function() {
            updateWeekArrowVisibility();
            var container = document.getElementById('week-results-content');
            if (container && weekResultsWeeks.length > 0) {
                var currentWeek = weekResultsWeeks[weekResultsIdx];
                weekResultsCache[weekResultsLeagueId + '-' + currentWeek] = container.innerHTML;
            }
        });

        function showQuickLink(view, leagueId) {
            var homeContent = document.getElementById('home-content-' + leagueId);
            var dynamicContent = document.getElementById('dynamic-content-' + leagueId);
            var globalContent = document.getElementById('home-global-content');

            if (view === 'home') {
                homeContent.style.display = '';
                dynamicContent.style.display = 'none';
                if (globalContent) globalContent.style.display = '';
                return;
            }

            homeContent.style.display = 'none';
            dynamicContent.style.display = '';
            if (globalContent) globalContent.style.display = 'none';

            var cacheKey = view + '-' + leagueId;
            if (quickLinkCache[cacheKey]) {
                dynamicContent.innerHTML = quickLinkCache[cacheKey];
                return;
            }

            dynamicContent.innerHTML = '<div class="content-section" style="text-align: center; padding: 40px; color: #888;">Loading...</div>';

            var url = '/leagues/' + leagueId + '/' + view + '-partial';
            fetch(url)
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    quickLinkCache[cacheKey] = html;
                    dynamicContent.innerHTML = html;
                })
                .catch(function() {
                    dynamicContent.innerHTML = '<div class="content-section" style="text-align: center; padding: 40px; color: #dc3545;">Failed to load content.</div>';
                });
        }

        function toggleScheduleWeek(weekNumber, leagueId) {
            var content = document.getElementById('sched-week-' + leagueId + '-' + weekNumber);
            var arrow = document.getElementById('sched-arrow-' + leagueId + '-' + weekNumber);
            if (content.style.display === 'none') {
                content.style.display = 'block';
                arrow.style.transform = 'rotate(90deg)';
            } else {
                content.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            }
        }

        function showHoleStatsMode(mode, leagueId) {
            var grossTable = document.getElementById('hs-table-gross-' + leagueId);
            var netTable = document.getElementById('hs-table-net-' + leagueId);
            var grossBtn = document.getElementById('btn-gross-' + leagueId);
            var netBtn = document.getElementById('btn-net-' + leagueId);
            if (mode === 'gross') {
                grossTable.style.display = '';
                netTable.style.display = 'none';
                grossBtn.style.background = 'var(--primary-color)';
                grossBtn.style.color = 'white';
                netBtn.style.background = 'transparent';
                netBtn.style.color = '#666';
            } else {
                grossTable.style.display = 'none';
                netTable.style.display = '';
                netBtn.style.background = 'var(--primary-color)';
                netBtn.style.color = 'white';
                grossBtn.style.background = 'transparent';
                grossBtn.style.color = '#666';
            }
        }

        function showHoleStatsByHoleMode(mode, leagueId) {
            var grossByHole = document.getElementById('hs-byhole-gross-' + leagueId);
            var netByHole = document.getElementById('hs-byhole-net-' + leagueId);
            var grossBtn = document.getElementById('btn-byhole-gross-' + leagueId);
            var netBtn = document.getElementById('btn-byhole-net-' + leagueId);
            if (mode === 'gross') {
                if (grossByHole) grossByHole.style.display = '';
                if (netByHole) netByHole.style.display = 'none';
                grossBtn.style.background = 'var(--primary-color)';
                grossBtn.style.color = 'white';
                netBtn.style.background = 'transparent';
                netBtn.style.color = '#666';
            } else {
                if (grossByHole) grossByHole.style.display = 'none';
                if (netByHole) netByHole.style.display = '';
                netBtn.style.background = 'var(--primary-color)';
                netBtn.style.color = 'white';
                grossBtn.style.background = 'transparent';
                grossBtn.style.color = '#666';
            }
        }

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

        function changePlayerWeek(leagueId, direction) {
            var weeks = window['playerWeeks_' + leagueId];
            if (!weeks || weeks.length === 0) return;
            var idx = window['playerWeekIdx_' + leagueId];
            idx += direction;
            if (idx < 0) idx = 0;
            if (idx >= weeks.length) idx = weeks.length - 1;
            window['playerWeekIdx_' + leagueId] = idx;
            var weekNum = weeks[idx];
            document.getElementById('player-week-label-' + leagueId).textContent = 'Week ' + weekNum;
            var weekData = window['playerWeekData_' + leagueId];
            var table = document.getElementById('player-standings-table-' + leagueId);
            var rows = table.querySelectorAll('tbody tr[data-player-id]');
            rows.forEach(function(row) {
                var playerId = row.getAttribute('data-player-id');
                var pd = weekData[playerId] && weekData[playerId][weekNum] ? weekData[playerId][weekNum] : null;
                row.querySelector('.pw-par3').textContent = pd && pd.par3 > 0 ? pd.par3 : '-';
                row.querySelector('.pw-gross').textContent = pd && pd.gross !== null ? pd.gross : '-';
                row.querySelector('.pw-pts').textContent = pd && pd.points !== null ? pd.points : '-';
            });
        }

        function showHomeSegmentTab(leagueId, segmentId) {
            // Hide all segment content for this league
            document.querySelectorAll('[id^="home-seg-content-' + leagueId + '-"]').forEach(function(el) {
                el.style.display = 'none';
            });
            // Reset all tab styles for this league
            document.querySelectorAll('[id^="home-seg-tab-' + leagueId + '-"]').forEach(function(btn) {
                btn.style.background = 'white';
                btn.style.color = 'var(--primary-color)';
                btn.style.borderColor = '#e0e0e0';
            });
            // Show selected content and highlight tab
            document.getElementById('home-seg-content-' + leagueId + '-' + segmentId).style.display = '';
            var tab = document.getElementById('home-seg-tab-' + leagueId + '-' + segmentId);
            tab.style.background = 'var(--primary-color)';
            tab.style.color = 'white';
            tab.style.borderColor = 'var(--primary-color)';
            checkScrollableOverflow();
        }

        // Detect horizontally overflowing tables and show scroll fade hint
        function checkScrollableOverflow() {
            document.querySelectorAll('.scrollable-table').forEach(function(el) {
                if (el.scrollWidth > el.clientWidth) {
                    el.classList.add('has-overflow');
                } else {
                    el.classList.remove('has-overflow');
                }
            });
        }
        // Hide the fade hint once user scrolls to the end
        document.querySelectorAll('.scrollable-table').forEach(function(el) {
            el.addEventListener('scroll', function() {
                if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 5) {
                    el.classList.remove('has-overflow');
                } else if (el.scrollWidth > el.clientWidth) {
                    el.classList.add('has-overflow');
                }
            });
        });
        window.addEventListener('load', checkScrollableOverflow);
        window.addEventListener('resize', checkScrollableOverflow);

        // Player Stats: player dropdown
        function showPlayerStats(leagueId) {
            var select = document.getElementById('ps-player-select-' + leagueId);
            var playerId = select.value;
            var noPlayer = document.getElementById('ps-no-player-' + leagueId);

            // Hide all player divs
            document.querySelectorAll('[id^="ps-player-' + leagueId + '-"]').forEach(function(el) {
                el.style.display = 'none';
            });

            if (!playerId) {
                if (noPlayer) noPlayer.style.display = '';
                return;
            }

            if (noPlayer) noPlayer.style.display = 'none';
            var playerDiv = document.getElementById('ps-player-' + leagueId + '-' + playerId);
            if (playerDiv) playerDiv.style.display = '';
            checkScrollableOverflow();
        }

        // Player Stats: gross/net toggle
        var playerStatsMode = {};
        function togglePlayerStatsMode(mode, leagueId) {
            playerStatsMode[leagueId] = mode;
            var grossBtn = document.getElementById('btn-ps-gross-' + leagueId);
            var netBtn = document.getElementById('btn-ps-net-' + leagueId);
            if (mode === 'gross') {
                grossBtn.style.background = 'var(--primary-color)';
                grossBtn.style.color = 'white';
                netBtn.style.background = 'transparent';
                netBtn.style.color = '#666';
            } else {
                netBtn.style.background = 'var(--primary-color)';
                netBtn.style.color = 'white';
                grossBtn.style.background = 'transparent';
                grossBtn.style.color = '#666';
            }

            // Toggle visible tables for all players
            document.querySelectorAll('[id^="ps-gross-' + leagueId + '-"]').forEach(function(el) {
                el.style.display = mode === 'gross' ? '' : 'none';
            });
            document.querySelectorAll('[id^="ps-net-' + leagueId + '-"]').forEach(function(el) {
                el.style.display = mode === 'net' ? '' : 'none';
            });
            // Toggle nine summary tables
            document.querySelectorAll('[id^="ps-nine-gross-' + leagueId + '-"]').forEach(function(el) {
                el.style.display = mode === 'gross' ? '' : 'none';
            });
            document.querySelectorAll('[id^="ps-nine-net-' + leagueId + '-"]').forEach(function(el) {
                el.style.display = mode === 'net' ? '' : 'none';
            });
            checkScrollableOverflow();
        }
    </script>
</body>
</html>

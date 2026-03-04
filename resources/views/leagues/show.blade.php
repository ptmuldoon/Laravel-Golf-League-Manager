<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $league->name }}</title>
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
        .league-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            padding: 15px;
            background: var(--primary-light);
            border-radius: 8px;
        }
        .info-label {
            font-size: 0.85em;
            color: #888;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .content-section {
                padding: 15px;
            }
            .section-title {
                font-size: 1.3em;
                margin-bottom: 15px;
            }
            .league-info-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .info-item {
                padding: 10px;
            }
            .info-label {
                font-size: 0.75em;
            }
            .info-value {
                font-size: 0.95em;
            }
            .action-buttons {
                gap: 8px;
            }
            .action-buttons .btn {
                flex: 1 1 calc(50% - 8px);
                text-align: center;
                padding: 10px 8px;
                font-size: 0.85em;
            }
            table {
                font-size: 0.85em;
            }
            th, td {
                padding: 8px 6px;
            }
            .match-card {
                padding: 15px;
            }
            .match-teams {
                font-size: 1em;
            }
        }
        @media (max-width: 480px) {
            .league-info-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons .btn {
                flex: 1 1 100%;
            }
        }
        .btn {
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
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.8em;
            color: var(--primary-color);
            margin-bottom: 20px;
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
        .team-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .team-link:hover {
            text-decoration: underline;
        }
        .week-section {
            margin-bottom: 30px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .week-header {
            background: var(--primary-light);
            padding: 15px 20px;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.2em;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .week-content {
            padding: 20px;
        }
        .match-card {
            background: #fafafa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
        }
        .match-teams {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .match-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .match-result {
            background: #d4edda;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
            color: #155724;
            font-weight: 600;
        }
        .match-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85em;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-scheduled {
            background: #cce5ff;
            color: #004085;
        }
        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
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
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.dashboard') }}" class="back-link">← Back to Dashboard</a>

        @if(session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div style="background: #dc3545; color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- League Info -->
        <div class="content-section">
            <h2 class="section-title">{{ $league->name }}</h2>
            <div style="color: #666; font-size: 1.1em; margin-bottom: 20px;">{{ $league->season }}</div>

            <div class="league-info-grid">
                <div class="info-item">
                    <div class="info-label">Duration</div>
                    <div class="info-value">{{ $league->start_date->format('M d') }} - {{ $league->end_date->format('M d, Y') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Course</div>
                    <div class="info-value">{{ $league->golfCourse->name ?? 'Not set' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Default Teebox</div>
                    <div class="info-value">{{ $league->default_teebox ?? 'Not set' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teams</div>
                    <div class="info-value">{{ $league->teams->count() }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge {{ $league->is_active ? 'status-completed' : 'status-scheduled' }}">
                            {{ $league->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                @if($league->fee_per_player)
                <div class="info-item">
                    <div class="info-label">Fee Per Player</div>
                    <div class="info-value">${{ number_format($league->fee_per_player, 2) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Par 3 Payout</div>
                    <div class="info-value">${{ number_format($league->par3_payout ?? 0, 2) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Place Payouts</div>
                    <div class="info-value">{{ number_format($league->payout_1st_pct, 0) }}% / {{ number_format($league->payout_2nd_pct, 0) }}% / {{ number_format($league->payout_3rd_pct, 0) }}%</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="content-section">
            <h2 class="section-title">Actions</h2>
            <div class="action-buttons">
                <a href="{{ route('admin.leagues.players.manage', $league->id) }}" class="btn btn-primary">
                    Manage Players
                </a>
                <a href="{{ route('admin.leagues.segments.index', $league->id) }}" class="btn btn-primary">
                    Manage Segments
                </a>
                <a href="{{ route('admin.leagues.teams.manage', $league->id) }}" class="btn btn-primary">
                    Manage Teams
                </a>
                <a href="{{ route('admin.leagues.autoSchedule', $league->id) }}" class="btn btn-success">
                    Auto-Schedule
                </a>
                <a href="{{ route('admin.leagues.scheduleOverview', $league->id) }}" class="btn btn-primary">
                    View Schedule
                </a>
                <a href="{{ route('admin.matches.create', $league->id) }}" class="btn btn-success">
                    Schedule Match
                </a>
                <a href="{{ route('admin.leagues.scores', $league->id) }}" class="btn btn-success">
                    Enter Scores
                </a>
                <a href="{{ route('admin.leagues.scoring', $league->id) }}" class="btn btn-primary">
                    Scoring Settings
                </a>
                @if($emailConfigured)
                    <a href="{{ route('admin.leagues.emailResults', $league->id) }}" class="btn btn-success">
                        Email Results
                    </a>
                    <a href="{{ route('admin.leagues.emailMessage', $league->id) }}" class="btn btn-success">
                        Email Message
                    </a>
                @endif
                <a href="{{ route('admin.leagues.holeStats', $league->id) }}" class="btn btn-primary">
                    Hole Stats
                </a>
                <a href="{{ route('admin.leagues.finances', $league->id) }}" class="btn btn-primary">
                    Finances
                </a>
                <a href="{{ route('admin.leagues.edit', $league->id) }}" class="btn btn-secondary">
                    Edit League
                </a>
                <form action="{{ route('admin.leagues.duplicate', $league->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="font-size: inherit; line-height: inherit;">
                        Duplicate League
                    </button>
                </form>
            </div>
        </div>

        <!-- Standings -->
        <div class="content-section">
            <h2 class="section-title">🏆 Standings</h2>

            @if($league->segments->isNotEmpty())
                {{-- Segment tabs --}}
                <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
                    @foreach($league->segments as $si => $segment)
                        <button type="button" class="btn {{ $si === 0 ? 'btn-primary' : 'btn-secondary' }}" onclick="showSegmentTab({{ $segment->id }})" id="seg-tab-{{ $segment->id }}" style="font-size: 0.9em;">
                            {{ $segment->name }}
                        </button>
                    @endforeach
                </div>

                @foreach($league->segments as $si => $segment)
                    <div class="segment-standings" id="seg-standings-{{ $segment->id }}" style="{{ $si > 0 ? 'display:none;' : '' }}">
                        <div style="color: #666; font-size: 0.9em; margin-bottom: 10px;">Weeks {{ $segment->start_week }} – {{ $segment->end_week }}</div>
                        @php $segTeams = $standingsBySegment[$segment->id] ?? collect(); @endphp
                        @if($segTeams->isEmpty())
                            <div class="empty-state">
                                <p style="font-size: 1.1em; margin-bottom: 15px;">No teams in this segment</p>
                                <a href="{{ route('admin.leagues.teams.manage', $league->id) }}?segment={{ $segment->id }}" class="btn btn-primary">
                                    + Add Teams
                                </a>
                            </div>
                        @else
                            <table>
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Team</th>
                                        <th>Wins</th>
                                        <th>Losses</th>
                                        <th>Ties</th>
                                        <th>Points</th>
                                        <th>Win %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($segTeams as $index => $team)
                                        <tr>
                                            <td style="font-weight: 600; font-size: 1.1em; color: var(--primary-color);">{{ $index + 1 }}</td>
                                            <td>
                                                <a href="{{ route('admin.teams.show', $team->id) }}" class="team-link">
                                                    {{ $team->name }}
                                                </a>
                                            </td>
                                            <td>{{ $team->wins }}</td>
                                            <td>{{ $team->losses }}</td>
                                            <td>{{ $team->ties }}</td>
                                            <td style="font-weight: 600;">{{ $team->totalPoints() }}</td>
                                            <td>{{ $team->winPercentage() }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            @elseif($teams->isEmpty())
                <div class="empty-state">
                    <p style="font-size: 1.2em; margin-bottom: 15px;">No teams yet</p>
                    <a href="{{ route('admin.leagues.teams.manage', $league->id) }}" class="btn btn-primary">
                        + Add Teams
                    </a>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Team</th>
                            <th>Wins</th>
                            <th>Losses</th>
                            <th>Ties</th>
                            <th>Points</th>
                            <th>Win %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teams as $index => $team)
                            <tr>
                                <td style="font-weight: 600; font-size: 1.1em; color: var(--primary-color);">{{ $index + 1 }}</td>
                                <td>
                                    <a href="{{ route('admin.teams.show', $team->id) }}" class="team-link">
                                        {{ $team->name }}
                                    </a>
                                </td>
                                <td>{{ $team->wins }}</td>
                                <td>{{ $team->losses }}</td>
                                <td>{{ $team->ties }}</td>
                                <td style="font-weight: 600;">{{ $team->totalPoints() }}</td>
                                <td>{{ $team->winPercentage() }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Par 3 Winners -->
        @if($par3WinnersByWeek->isNotEmpty())
            <div class="content-section">
                <h2 class="section-title">🎯 Par 3 Winners</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: left;">Week</th>
                                <th style="text-align: left;">Hole</th>
                                <th style="text-align: left;">Winner</th>
                                <th style="text-align: left;">Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($par3WinnersByWeek as $week => $winners)
                                @foreach($winners as $wi => $winner)
                                    <tr>
                                        @if($wi === 0)
                                            <td rowspan="{{ $winners->count() }}" style="vertical-align: top; font-weight: 600; color: var(--primary-color);">Week {{ $week }}</td>
                                        @endif
                                        <td>Hole {{ $winner->hole_number }}</td>
                                        <td style="font-weight: 600;">{{ $winner->player ? $winner->player->name : '—' }}</td>
                                        <td style="color: #666;">{{ $winner->distance ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Match Schedule -->
        <div class="content-section">
            <h2 class="section-title">📅 Match Schedule</h2>

            @if($matchesByWeek->isEmpty())
                <div class="empty-state">
                    <p style="font-size: 1.2em; margin-bottom: 15px;">No matches scheduled yet</p>
                    <a href="{{ route('admin.matches.create', $league->id) }}" class="btn btn-success">
                        + Schedule First Match
                    </a>
                </div>
            @else
                @foreach($matchesByWeek as $week => $matches)
                    <div class="week-section">
                        <div class="week-header">
                            <span>
                                Week {{ $week }}
                                @if(!empty($weekSegmentMap[$week]))
                                    <span style="font-size: 0.75em; color: #888; font-weight: normal; margin-left: 8px;">{{ $weekSegmentMap[$week] }}</span>
                                @endif
                            </span>
                            <span>{{ $matches->count() }} {{ $matches->count() == 1 ? 'match' : 'matches' }}</span>
                        </div>
                        <div class="week-content">
                            @foreach($matches as $match)
                                <div class="match-card">
                                    <div class="match-teams">
                                        @if($match->homeTeam && $match->awayTeam)
                                            {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}
                                        @else
                                            Week {{ $match->week_number }} Match
                                        @endif
                                    </div>
                                    <div class="match-info">
                                        📅 {{ $match->match_date->format('M d, Y') }} |
                                        ⛳ {{ $match->golfCourse->name ?? 'TBD' }} ({{ $match->teebox ?? 'TBD' }})
                                    </div>
                                    <span class="status-badge status-{{ $match->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                    </span>

                                    @if($match->status === 'completed' && $match->result)
                                        @php
                                            $hHome = $match->result->holes_won_home + ($match->result->holes_tied * 0.5);
                                            $hAway = $match->result->holes_won_away + ($match->result->holes_tied * 0.5);
                                            $fH = $hHome == (int)$hHome ? (int)$hHome : number_format($hHome, 1);
                                            $fA = $hAway == (int)$hAway ? (int)$hAway : number_format($hAway, 1);
                                        @endphp
                                        <div class="match-result">
                                            @if($match->result->winning_team_id && $match->result->winningTeam)
                                                🏆 {{ $match->result->winningTeam->name }} wins
                                                ({{ $fH }} - {{ $fA }})
                                            @else
                                                🤝 Match Tied
                                                ({{ $fH }} - {{ $fA }})
                                            @endif
                                        </div>
                                    @endif

                                    <div class="match-actions">
                                        <a href="{{ route('admin.matches.show', $match->id) }}" class="btn btn-primary btn-small">
                                            View Details
                                        </a>
                                        @if($match->status === 'scheduled')
                                            <a href="{{ route('admin.matches.assignPlayers', $match->id) }}" class="btn btn-success btn-small">
                                                Assign Players
                                            </a>
                                        @elseif($match->status === 'in_progress')
                                            <a href="{{ route('admin.matches.scoreEntry', $match->id) }}" class="btn btn-success btn-small">
                                                Enter Scores
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        function showSegmentTab(segmentId) {
            document.querySelectorAll('.segment-standings').forEach(el => el.style.display = 'none');
            document.querySelectorAll('[id^="seg-tab-"]').forEach(btn => {
                btn.className = btn.className.replace('btn-primary', 'btn-secondary');
            });
            document.getElementById('seg-standings-' + segmentId).style.display = 'block';
            const tab = document.getElementById('seg-tab-' + segmentId);
            tab.className = tab.className.replace('btn-secondary', 'btn-primary');
        }
    </script>
</body>
</html>

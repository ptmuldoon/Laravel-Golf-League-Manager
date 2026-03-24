<div class="content-section">
    <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span>Player History</span>
        <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('player-history-body-{{ $league->id }}')" id="toggle-player-history-body-{{ $league->id }}">&#9650;</span>
    </h2>

    <div id="player-history-body-{{ $league->id }}">
        @if($players->isEmpty())
            <div style="text-align: center; padding: 40px; color: #888;">No players in this league.</div>
        @else
            <div style="margin-bottom: 15px;">
                <select id="ph-player-select-{{ $league->id }}" onchange="showPlayerHistory({{ $league->id }})" style="padding: 8px 14px; font-size: 0.9em; font-weight: 600; border: 2px solid #e0e0e0; border-radius: 8px; background: white; color: var(--primary-color); cursor: pointer; min-width: 200px;">
                    <option value="">Select a Player</option>
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">{{ $player->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="ph-no-player-{{ $league->id }}" style="text-align: center; padding: 30px; color: #888;">
                Select a player to view their round history.
            </div>

            @foreach($players as $player)
                @php
                    $rounds = $playerRounds[$player->id] ?? collect();
                    $summary = $playerSummary[$player->id] ?? null;
                    $chartData = $playerChartData[$player->id] ?? [];
                    $handicapData = $playerHandicapData[$player->id] ?? [];
                    $currentHandicap = $summary['current_handicap'] ?? null;
                @endphp
                <div id="ph-player-{{ $league->id }}-{{ $player->id }}" style="display: none;">
                    @if($rounds->isEmpty())
                        <div style="text-align: center; padding: 30px; color: #888;">No round history for this player.</div>
                    @else
                        {{-- Handicap Badge + Stats Grid --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 20px;">
                            @if($currentHandicap)
                                <div style="text-align: center; padding: 15px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 8px; color: white;">
                                    <div style="font-size: 0.85em; margin-bottom: 5px; opacity: 0.9;">Handicap Index</div>
                                    <div style="font-size: 2em; font-weight: bold; line-height: 1;">{{ $currentHandicap->handicap_index }}</div>
                                    <div style="font-size: 0.7em; margin-top: 5px; opacity: 0.8;">Best {{ $currentHandicap->rounds_used }} of {{ count($currentHandicap->score_differentials) }}</div>
                                </div>
                            @endif
                            <div style="text-align: center; padding: 15px; background: var(--primary-light); border-radius: 8px;">
                                <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">Rounds</div>
                                <div style="font-size: 1.8em; font-weight: bold; color: var(--primary-color);">{{ $summary['total_rounds'] }}</div>
                            </div>
                            @if($summary['avg_18'] !== null)
                                <div style="text-align: center; padding: 15px; background: var(--primary-light); border-radius: 8px; border-left: 3px solid var(--primary-color);">
                                    <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">18-Hole Avg</div>
                                    <div style="font-size: 1.8em; font-weight: bold; color: var(--primary-color);">{{ $summary['avg_18'] }}</div>
                                    <div style="font-size: 0.75em; color: #999;">{{ $summary['rounds_18'] }} rounds ({{ $summary['low_18'] }}-{{ $summary['high_18'] }})</div>
                                </div>
                            @endif
                            @if($summary['avg_9'] !== null)
                                <div style="text-align: center; padding: 15px; background: var(--primary-light); border-radius: 8px; border-left: 3px solid #e67e22;">
                                    <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">9-Hole Avg</div>
                                    <div style="font-size: 1.8em; font-weight: bold; color: var(--primary-color);">{{ $summary['avg_9'] }}</div>
                                    <div style="font-size: 0.75em; color: #999;">{{ $summary['rounds_9'] }} rounds (Low: {{ $summary['low_9'] }})</div>
                                </div>
                            @endif
                        </div>

                        {{-- Score / Handicap Chart --}}
                        <div style="margin-bottom: 25px; padding: 20px; background: #fafafa; border-radius: 10px; border: 1px solid #eee;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                                <h3 style="color: var(--primary-color); font-size: 1.1em; margin: 0;">Score History</h3>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                    <div style="display: flex; border-radius: 8px; overflow: hidden; border: 2px solid var(--primary-color);">
                                        <button class="ph-mode-toggle-{{ $league->id }}-{{ $player->id }}" data-mode="scores" onclick="switchPhMode({{ $league->id }}, {{ $player->id }}, 'scores')" style="padding: 6px 14px; border: none; cursor: pointer; font-weight: 600; font-size: 0.82em; transition: all 0.3s ease; background: var(--primary-color); color: white;">Scores</button>
                                        <button class="ph-mode-toggle-{{ $league->id }}-{{ $player->id }}" data-mode="handicap" onclick="switchPhMode({{ $league->id }}, {{ $player->id }}, 'handicap')" style="padding: 6px 14px; border: none; border-left: 1px solid var(--primary-color); cursor: pointer; font-weight: 600; font-size: 0.82em; transition: all 0.3s ease; background: white; color: var(--primary-color);">Handicap</button>
                                    </div>
                                    <div id="ph-holes-filter-{{ $league->id }}-{{ $player->id }}" style="display: flex; border-radius: 8px; overflow: hidden; border: 2px solid #e0e0e0;">
                                        <button class="ph-holes-toggle-{{ $league->id }}-{{ $player->id }}" data-holes="all" onclick="switchPhHoles({{ $league->id }}, {{ $player->id }}, 'all')" style="padding: 6px 12px; border: none; cursor: pointer; font-weight: 600; font-size: 0.82em; transition: all 0.3s ease; background: #666; color: white;">All</button>
                                        <button class="ph-holes-toggle-{{ $league->id }}-{{ $player->id }}" data-holes="18" onclick="switchPhHoles({{ $league->id }}, {{ $player->id }}, '18')" style="padding: 6px 12px; border: none; border-left: 1px solid #e0e0e0; cursor: pointer; font-weight: 600; font-size: 0.82em; transition: all 0.3s ease; background: white; color: #666;">18</button>
                                        <button class="ph-holes-toggle-{{ $league->id }}-{{ $player->id }}" data-holes="9" onclick="switchPhHoles({{ $league->id }}, {{ $player->id }}, '9')" style="padding: 6px 12px; border: none; border-left: 1px solid #e0e0e0; cursor: pointer; font-weight: 600; font-size: 0.82em; transition: all 0.3s ease; background: white; color: #666;">9</button>
                                    </div>
                                    <select id="ph-period-filter-{{ $league->id }}-{{ $player->id }}" onchange="switchPhPeriod({{ $league->id }}, {{ $player->id }}, this.value)" style="padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-weight: 600; font-size: 0.82em; cursor: pointer; background: white; color: #666;">
                                        <option value="last20" selected>Last 20</option>
                                        <option value="3m">Last 3 Months</option>
                                        <option value="6m">Last 6 Months</option>
                                        <option value="1y">Last Year</option>
                                        <option value="ytd">This Year</option>
                                        <option value="all">All Time</option>
                                    </select>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="ph-chart-{{ $league->id }}-{{ $player->id }}"></canvas>
                            </div>
                        </div>

                        {{-- Round History Table --}}
                        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
                            <div class="scrollable-table">
                                <table style="min-width: 650px;">
                                    <thead>
                                        <tr style="background: var(--primary-color);">
                                            <th style="color: white; padding: 12px 10px;">Date</th>
                                            <th style="color: white; padding: 12px 10px;">Course</th>
                                            <th style="color: white; padding: 12px 10px;">Teebox</th>
                                            <th style="color: white; padding: 12px 10px;">Score</th>
                                            <th style="color: white; padding: 12px 10px;">Net</th>
                                            <th style="color: white; padding: 12px 10px;">Diff</th>
                                            <th style="color: white; padding: 12px 10px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rounds->sortByDesc('played_at') as $round)
                                            <tr>
                                                <td style="padding: 10px;">{{ \Carbon\Carbon::parse($round->played_at)->format('M d, Y') }}</td>
                                                <td style="padding: 10px; font-weight: 600; color: var(--primary-color);">
                                                    {{ $round->golfCourse->name }}
                                                    @if($round->holes_played == 9)
                                                        <span style="display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 0.72em; font-weight: 600; background: var(--secondary-color); color: white; margin-left: 4px;">{{ $round->nine_type ?? '9 holes' }}</span>
                                                    @endif
                                                </td>
                                                <td style="padding: 10px;">
                                                    <span style="display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 0.82em; font-weight: 600;
                                                        @if($round->teebox === 'Black') background: #333; color: white;
                                                        @elseif($round->teebox === 'Blue') background: #4169E1; color: white;
                                                        @elseif($round->teebox === 'Red') background: #DC143C; color: white;
                                                        @else background: #f0f0f0; color: #333;
                                                        @endif
                                                    ">{{ $round->teebox }}</span>
                                                </td>
                                                <td style="padding: 10px; font-size: 1.1em; font-weight: bold; color: var(--primary-color);">{{ $round->total_score }}</td>
                                                <td style="padding: 10px; font-size: 1.1em; font-weight: bold; color: var(--primary-color);">{{ $round->net_score !== null ? $round->net_score : '—' }}</td>
                                                <td style="padding: 10px; font-weight: 600; color: var(--primary-color);">
                                                    {{ $round->scoring_differential !== null ? number_format($round->scoring_differential, 1) : '—' }}
                                                </td>
                                                <td style="padding: 10px;">
                                                    <a href="{{ route('players.round', [$player->id, $round->id]) }}" style="display: inline-block; padding: 6px 12px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 5px; font-size: 0.82em;">Scorecard</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var phScoreData = @json($playerChartData);
    var phHandicapData = @json($playerHandicapData);
    var phCharts = {};
    var phChartMode = {};
    var phHolesFilter = {};
    var phPeriodFilter = {};

    function showPlayerHistory(leagueId) {
        var select = document.getElementById('ph-player-select-' + leagueId);
        var playerId = select.value;
        var noPlayer = document.getElementById('ph-no-player-' + leagueId);

        document.querySelectorAll('[id^="ph-player-' + leagueId + '-"]').forEach(function(el) {
            el.style.display = 'none';
        });

        if (!playerId) {
            if (noPlayer) noPlayer.style.display = '';
            return;
        }

        if (noPlayer) noPlayer.style.display = 'none';
        var playerDiv = document.getElementById('ph-player-' + leagueId + '-' + playerId);
        if (playerDiv) {
            playerDiv.style.display = '';
            initPhChart(leagueId, playerId);
        }
        if (typeof checkScrollableOverflow === 'function') checkScrollableOverflow();
    }

    function initPhChart(leagueId, playerId) {
        var key = leagueId + '-' + playerId;
        if (phCharts[key]) return;
        phChartMode[key] = 'scores';
        phHolesFilter[key] = 'all';
        phPeriodFilter[key] = 'last20';
        renderPhChart(leagueId, playerId);
    }

    function getPeriodCutoff(period) {
        if (period === 'all') return null;
        var now = new Date();
        if (period === '3m') return new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
        if (period === '6m') return new Date(now.getFullYear(), now.getMonth() - 6, now.getDate());
        if (period === '1y') return new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
        if (period === 'ytd') return new Date(now.getFullYear(), 0, 1);
        return null;
    }

    function filterByPeriod(data, period) {
        if (period === 'last20') return data.slice(-20);
        var cutoff = getPeriodCutoff(period);
        if (!cutoff) return data;
        return data.filter(function(d) { return new Date(d.date) >= cutoff; });
    }

    function getFilteredScores(playerId, holesFilter, period) {
        var data = phScoreData[playerId] || [];
        if (holesFilter !== 'all') {
            var target = parseInt(holesFilter);
            data = data.filter(function(d) { return d.holes === target; });
        }
        return filterByPeriod(data, period);
    }

    function renderPhChart(leagueId, playerId) {
        var key = leagueId + '-' + playerId;
        var canvas = document.getElementById('ph-chart-' + leagueId + '-' + playerId);
        if (!canvas) return;

        if (phCharts[key]) phCharts[key].destroy();

        var mode = phChartMode[key] || 'scores';

        var period = phPeriodFilter[key] || 'all';

        if (mode === 'scores') {
            var holesFilter = phHolesFilter[key] || 'all';
            var filtered = getFilteredScores(playerId, holesFilter, period);
            if (filtered.length === 0) {
                phCharts[key] = new Chart(canvas, { type: 'line', data: { labels: [], datasets: [] }, options: { responsive: true, maintainAspectRatio: false } });
                return;
            }
            var borderColor = holesFilter === '9' ? '#e67e22' : 'rgba(var(--primary-rgb), 1)';
            var bgColor = holesFilter === '9' ? 'rgba(230, 126, 34, 0.1)' : 'rgba(var(--primary-rgb), 0.1)';

            phCharts[key] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: filtered.map(function(d) { return d.date; }),
                    datasets: [{
                        label: holesFilter === 'all' ? 'All Rounds' : holesFilter + '-Hole Rounds',
                        data: filtered.map(function(d) { return d.score; }),
                        borderColor: borderColor,
                        backgroundColor: bgColor,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: false,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: borderColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(context) { return filtered[context[0].dataIndex].course; },
                                label: function(context) {
                                    var d = filtered[context.dataIndex];
                                    return d.holes + '-hole score: ' + context.parsed.y;
                                },
                                afterLabel: function(context) { return 'Date: ' + context.label; }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)', padding: 12
                        }
                    },
                    scales: {
                        y: { beginAtZero: false, ticks: { stepSize: 5 }, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                        x: { ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } }, grid: { display: false } }
                    },
                    interaction: { intersect: false, mode: 'nearest' }
                }
            });
        } else {
            var hData = filterByPeriod(phHandicapData[playerId] || [], period);
            if (hData.length === 0) {
                phCharts[key] = new Chart(canvas, { type: 'line', data: { labels: [], datasets: [] }, options: { responsive: true, maintainAspectRatio: false } });
                return;
            }

            phCharts[key] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: hData.map(function(d) { return d.date; }),
                    datasets: [{
                        label: 'Handicap Index',
                        data: hData.map(function(d) { return d.handicap; }),
                        borderColor: 'var(--secondary-color)',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: 'var(--secondary-color)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function() { return 'Handicap Index'; },
                                label: function(context) { return 'Index: ' + context.parsed.y.toFixed(1); },
                                afterLabel: function(context) {
                                    var d = hData[context.dataIndex];
                                    return 'Date: ' + d.date + '\nBest ' + d.rounds_used + ' of ' + d.total_differentials + ' rounds';
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)', padding: 12
                        }
                    },
                    scales: {
                        y: { beginAtZero: false, grid: { color: 'rgba(0, 0, 0, 0.05)' }, title: { display: true, text: 'Handicap Index', font: { size: 13, weight: 'bold' }, color: 'var(--secondary-color)' } },
                        x: { ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } }, grid: { display: false } }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }
    }

    function switchPhMode(leagueId, playerId, mode) {
        var key = leagueId + '-' + playerId;
        phChartMode[key] = mode;

        document.querySelectorAll('.ph-mode-toggle-' + leagueId + '-' + playerId).forEach(function(btn) {
            if (btn.dataset.mode === mode) {
                btn.style.background = 'var(--primary-color)';
                btn.style.color = 'white';
            } else {
                btn.style.background = 'white';
                btn.style.color = 'var(--primary-color)';
            }
        });

        var holesFilter = document.getElementById('ph-holes-filter-' + leagueId + '-' + playerId);
        if (holesFilter) holesFilter.style.display = mode === 'scores' ? '' : 'none';

        renderPhChart(leagueId, playerId);
    }

    function switchPhHoles(leagueId, playerId, filter) {
        var key = leagueId + '-' + playerId;
        phHolesFilter[key] = filter;

        document.querySelectorAll('.ph-holes-toggle-' + leagueId + '-' + playerId).forEach(function(btn) {
            if (btn.dataset.holes === filter) {
                btn.style.background = '#666';
                btn.style.color = 'white';
            } else {
                btn.style.background = 'white';
                btn.style.color = '#666';
            }
        });

        renderPhChart(leagueId, playerId);
    }

    function switchPhPeriod(leagueId, playerId, period) {
        var key = leagueId + '-' + playerId;
        phPeriodFilter[key] = period;
        renderPhChart(leagueId, playerId);
    }
</script>

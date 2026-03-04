<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $player->first_name }} {{ $player->last_name }} - Rounds</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1200px;
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
        .player-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 2.5em;
        }
        .player-contact {
            color: #666;
            margin-bottom: 10px;
        }
        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .chart-header h2 {
            color: var(--primary-color);
            font-size: 1.5em;
        }
        .date-filters {
            display: flex;
            gap: 10px;
        }
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }
        .filter-btn:hover {
            background: #f0f0ff;
        }
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
        }
        .chart-toggle {
            display: flex;
            gap: 0;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--primary-color);
        }
        .toggle-btn {
            padding: 8px 20px;
            border: none;
            background: white;
            color: var(--primary-color);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        .toggle-btn:not(:last-child) {
            border-right: 1px solid var(--primary-color);
        }
        .toggle-btn:hover {
            background: #f0f0ff;
        }
        .toggle-btn.active {
            background: var(--primary-color);
            color: white;
        }
        .chart-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background: var(--primary-light);
            border-radius: 8px;
        }
        .stat-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-color);
        }
        .rounds-section h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .rounds-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: var(--primary-color);
            color: white;
        }
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        tbody tr {
            transition: background 0.3s ease;
        }
        tbody tr:hover {
            background: var(--primary-light);
        }
        .course-name {
            font-weight: 600;
            color: var(--primary-color);
        }
        .teebox {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .teebox-Black { background: #333; color: white; }
        .teebox-Blue { background: #4169E1; color: white; }
        .teebox-White { background: #f0f0f0; color: #333; }
        .teebox-Red { background: #DC143C; color: white; }
        .holes-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.75em;
            font-weight: 600;
            background: var(--secondary-color);
            color: white;
            margin-left: 8px;
        }
        .score {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
        }
        .view-scorecard {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }
        .view-scorecard:hover {
            background: var(--secondary-color);
        }
        .btn-enter-scorecard {
            display: inline-block;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .btn-enter-scorecard:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .no-rounds {
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            color: #666;
        }
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }
            h1 {
                font-size: 1.6em;
            }
            .player-header {
                padding: 16px;
                margin-bottom: 16px;
            }
            .player-header > div:first-child {
                flex-direction: column;
                gap: 16px;
            }
            .player-header > div:first-child > div:last-child {
                min-width: 0 !important;
                width: 100%;
            }
            .chart-section {
                padding: 16px;
                margin-bottom: 16px;
            }
            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .chart-header > div:first-child {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .date-filters {
                flex-wrap: wrap;
                gap: 6px;
            }
            .filter-btn {
                padding: 6px 10px;
                font-size: 0.8em;
            }
            .toggle-btn {
                padding: 6px 12px;
                font-size: 0.8em;
            }
            .chart-container {
                height: 250px;
            }
            .stat-value {
                font-size: 1.4em;
            }
            .rounds-section h2 {
                font-size: 1.3em;
            }
            .rounds-table {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .rounds-table table {
                min-width: 700px;
            }
            th, td {
                padding: 10px 8px;
                font-size: 0.85em;
            }
            .view-scorecard {
                padding: 6px 10px;
                font-size: 0.8em;
                white-space: nowrap;
            }
        }
        @media (max-width: 480px) {
            body {
                padding: 8px;
            }
            h1 {
                font-size: 1.3em;
            }
            .player-header {
                padding: 12px;
            }
            .chart-section {
                padding: 12px;
            }
            .chart-container {
                height: 200px;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 8px;
            }
            .stat-box {
                padding: 10px;
            }
            .stat-value {
                font-size: 1.2em;
            }
            th, td {
                padding: 8px 5px;
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if(auth()->check() && auth()->user()->isAdmin())
            <a href="/admin/players" class="back-link">← Back to Players</a>
        @else
            <a href="/" class="back-link">← Back to Home</a>
        @endif

        <div class="player-header">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 30px;">
                <div style="flex: 1;">
                    <h1>🏌️ {{ $player->first_name }} {{ $player->last_name }}</h1>
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <div class="player-contact">📧 {{ $player->email }}</div>
                        @if($player->phone_number)
                            <div class="player-contact">📱 {{ $player->phone_number }}</div>
                        @endif
                    @endif
                </div>
                @if($currentHandicap)
                    <div style="text-align: center; padding: 20px 30px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 12px; color: white; box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3); min-width: 200px;">
                        <div style="font-size: 0.9em; margin-bottom: 5px; opacity: 0.9;">Handicap Index</div>
                        <div style="font-size: 3em; font-weight: bold; line-height: 1;">{{ $currentHandicap->handicap_index }}</div>
                        <div style="font-size: 0.85em; margin-top: 8px; opacity: 0.9;">
                            Best {{ $currentHandicap->rounds_used }} of {{ count($currentHandicap->score_differentials) }} rounds
                        </div>
                        <div style="font-size: 0.75em; margin-top: 5px; opacity: 0.8;">
                            Updated: {{ \Carbon\Carbon::parse($currentHandicap->calculation_date)->format('M d, Y') }}
                        </div>
                    </div>
                @endif
            </div>

            @if(auth()->check() && auth()->user()->isAdmin())
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <a href="{{ route('admin.scorecard.create') }}?player={{ $player->id }}" class="btn-enter-scorecard">
                        📝 Enter New Scorecard for {{ $player->first_name }}
                    </a>
                </div>
            @endif
        </div>

        @if($chartData->count() > 0)
            <div class="chart-section">
                <div class="chart-header">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <h2 id="chartTitle">Score History</h2>
                        <div class="chart-toggle">
                            <button class="toggle-btn active" data-mode="scores" onclick="switchChart('scores')">Scores</button>
                            <button class="toggle-btn" data-mode="handicap" onclick="switchChart('handicap')">Handicap Index</button>
                        </div>
                    </div>
                    <div class="date-filters">
                        <a href="{{ route('players.show', ['id' => $player->id, 'filter' => '7days']) }}"
                           class="filter-btn {{ $filter == '7days' ? 'active' : '' }}">7 Days</a>
                        <a href="{{ route('players.show', ['id' => $player->id, 'filter' => '30days']) }}"
                           class="filter-btn {{ $filter == '30days' ? 'active' : '' }}">30 Days</a>
                        <a href="{{ route('players.show', ['id' => $player->id, 'filter' => '90days']) }}"
                           class="filter-btn {{ $filter == '90days' ? 'active' : '' }}">90 Days</a>
                        <a href="{{ route('players.show', ['id' => $player->id, 'filter' => 'year']) }}"
                           class="filter-btn {{ $filter == 'year' ? 'active' : '' }}">Year</a>
                        <a href="{{ route('players.show', ['id' => $player->id, 'filter' => 'all']) }}"
                           class="filter-btn {{ $filter == 'all' ? 'active' : '' }}">All Time</a>
                    </div>
                </div>

                <div id="holesFilter" style="margin-bottom: 15px;">
                    <div class="chart-toggle">
                        <button class="toggle-btn active" data-holes="all" onclick="switchHoles('all')">All Rounds</button>
                        <button class="toggle-btn" data-holes="18" onclick="switchHoles('18')">18 Holes</button>
                        <button class="toggle-btn" data-holes="9" onclick="switchHoles('9')">9 Holes</button>
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="scoreChart"></canvas>
                </div>

                @php
                    $rounds18 = $rounds->filter(fn($r) => ($r->holes_played ?? 18) == 18);
                    $rounds9 = $rounds->filter(fn($r) => ($r->holes_played ?? 18) == 9);
                @endphp
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">Total Rounds</div>
                        <div class="stat-value">{{ $rounds->count() }}</div>
                    </div>
                    @if($rounds18->count() > 0)
                        <div class="stat-box" style="border-left: 3px solid var(--primary-color);">
                            <div class="stat-label">18-Hole Avg</div>
                            <div class="stat-value">{{ round($rounds18->avg('total_score'), 1) }}</div>
                            <div style="font-size: 0.75em; color: #999;">{{ $rounds18->count() }} rounds ({{ $rounds18->min('total_score') }}-{{ $rounds18->max('total_score') }})</div>
                        </div>
                    @endif
                    @if($rounds9->count() > 0)
                        <div class="stat-box" style="border-left: 3px solid #e67e22;">
                            <div class="stat-label">9-Hole Avg</div>
                            <div class="stat-value">{{ round($rounds9->avg('total_score'), 1) }}</div>
                            <div style="font-size: 0.75em; color: #999;">{{ $rounds9->count() }} rounds ({{ $rounds9->min('total_score') }}-{{ $rounds9->max('total_score') }})</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="rounds-section">
            <h2>Round History ({{ $rounds->count() }} rounds)</h2>

            @if($rounds->count() > 0)
                <div class="rounds-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Teebox</th>
                                <th>Score</th>
                                <th>Net Score</th>
                                <th>Differential</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rounds->sortByDesc('played_at') as $round)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($round->played_at)->format('M d, Y') }}</td>
                                    <td class="course-name">
                                        {{ $round->golfCourse->name }}
                                        @if($round->holes_played == 9)
                                            <span class="holes-badge">{{ $round->nine_type ?? '9 holes' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="teebox teebox-{{ $round->teebox }}">{{ $round->teebox }}</span>
                                    </td>
                                    <td class="score">{{ $round->total_score }}</td>
                                    <td class="score">
                                        {{ $round->net_score !== null ? $round->net_score : '—' }}
                                    </td>
                                    <td style="font-weight: 600; color: var(--primary-color);">
                                        @if($round->scoring_differential !== null)
                                            {{ number_format($round->scoring_differential, 1) }}
                                        @else
                                            <span style="color: #999;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('players.round', [$player->id, $round->id]) }}" class="view-scorecard">
                                            View Scorecard
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="no-rounds">
                    <p>No rounds played in this time period.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        const ctx = document.getElementById('scoreChart');
        const scoreData = @json($chartData);
        const handicapData = @json($handicapChartData);

        let chart = null;
        let currentMode = 'scores';
        let currentHolesFilter = 'all';

        const colors = {
            all: { border: 'var(--primary-color)', bg: 'rgba(var(--primary-rgb), 0.1)', point: 'var(--primary-color)' },
            18:  { border: 'var(--primary-color)', bg: 'rgba(var(--primary-rgb), 0.1)', point: 'var(--primary-color)' },
            9:   { border: '#e67e22', bg: 'rgba(230, 126, 34, 0.1)', point: '#e67e22' }
        };

        function getFilteredScoreData(holesFilter) {
            if (holesFilter === 'all') return scoreData;
            const target = parseInt(holesFilter);
            return scoreData.filter(d => d.holes === target);
        }

        function getScoreConfig(holesFilter) {
            const filtered = getFilteredScoreData(holesFilter);
            const c = colors[holesFilter];
            const label = holesFilter === 'all' ? 'All Rounds' : holesFilter + '-Hole Rounds';

            return {
                type: 'line',
                data: {
                    labels: filtered.map(d => d.date),
                    datasets: [{
                        label: label,
                        data: filtered.map(d => d.score),
                        borderColor: c.border,
                        backgroundColor: c.bg,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: false,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: c.point,
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
                                title: function(context) {
                                    return filtered[context[0].dataIndex].course;
                                },
                                label: function(context) {
                                    const d = filtered[context.dataIndex];
                                    return d.holes + '-hole score: ' + context.parsed.y;
                                },
                                afterLabel: function(context) {
                                    return 'Date: ' + context.label;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: { stepSize: 5, font: { size: 12 } },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            ticks: { maxRotation: 45, minRotation: 45, font: { size: 11 } },
                            grid: { display: false }
                        }
                    },
                    interaction: { intersect: false, mode: 'nearest' }
                }
            };
        }

        function getHandicapConfig() {
            return {
                type: 'line',
                data: {
                    labels: handicapData.map(d => d.date),
                    datasets: [{
                        label: 'Handicap Index',
                        data: handicapData.map(d => d.handicap),
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
                                title: function(context) {
                                    return 'Handicap Index';
                                },
                                label: function(context) {
                                    return 'Index: ' + context.parsed.y.toFixed(1);
                                },
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    const d = handicapData[index];
                                    return 'Date: ' + d.date + '\nBest ' + d.rounds_used + ' of ' + d.total_differentials + ' rounds';
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: { font: { size: 12 } },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            title: {
                                display: true,
                                text: 'Handicap Index',
                                font: { size: 13, weight: 'bold' },
                                color: 'var(--secondary-color)'
                            }
                        },
                        x: {
                            ticks: { maxRotation: 45, minRotation: 45, font: { size: 11 } },
                            grid: { display: false }
                        }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            };
        }

        function renderChart() {
            if (chart) chart.destroy();
            const config = currentMode === 'scores'
                ? getScoreConfig(currentHolesFilter)
                : getHandicapConfig();
            chart = new Chart(ctx, config);
        }

        function switchChart(mode) {
            currentMode = mode;

            document.querySelectorAll('.toggle-btn[data-mode]').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.mode === mode);
            });

            document.getElementById('chartTitle').textContent =
                mode === 'scores' ? 'Score History' : 'Handicap Index History';

            document.getElementById('holesFilter').style.display =
                mode === 'scores' ? '' : 'none';

            renderChart();
        }

        function switchHoles(filter) {
            currentHolesFilter = filter;

            document.querySelectorAll('.toggle-btn[data-holes]').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.holes === filter);
            });

            renderChart();
        }

        // Initialize
        renderChart();
    </script>
</body>
</html>

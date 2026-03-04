<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hole Stats - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1100px; margin: 0 auto; }
        .back-link {
            display: inline-block; color: white; text-decoration: none;
            padding: 10px 20px; background: rgba(255,255,255,0.2);
            border-radius: 5px; margin-bottom: 20px; transition: background 0.3s ease;
        }
        .back-link:hover { background: rgba(255,255,255,0.3); }
        .content-section {
            background: white; padding: 30px; border-radius: 12px;
            margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.8em; color: var(--primary-color); margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .toggle-container {
            display: flex; background: #f0f0f5; border-radius: 8px; overflow: hidden; font-size: 0.5em;
        }
        .toggle-btn {
            padding: 8px 20px; cursor: pointer; font-weight: 600; border: none;
            background: transparent; color: #666; transition: all 0.3s ease; font-size: 14px;
        }
        .toggle-btn.active {
            background: var(--primary-color); color: white; border-radius: 8px;
        }
        .scrollable-table { overflow-x: auto; }
        table {
            width: 100%; border-collapse: collapse; font-size: 0.95em;
        }
        th {
            background: var(--primary-light); color: var(--primary-color); padding: 10px 8px;
            text-align: center; font-size: 0.85em; border-bottom: 2px solid #d0d5e0;
            white-space: nowrap;
        }
        th:first-child, th:nth-child(2) { text-align: left; }
        td {
            padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: center;
        }
        td:first-child { text-align: center; font-weight: 600; color: var(--primary-color); width: 40px; }
        td:nth-child(2) { text-align: left; font-weight: 600; white-space: nowrap; }
        tr:hover { background: var(--primary-light); }
        .stat-albatross { color: #9b59b6; font-weight: 700; }
        .stat-eagle { color: #e67e22; font-weight: 700; }
        .stat-birdie { color: #28a745; font-weight: 600; }
        .stat-par { color: #333; }
        .stat-bogey { color: #dc3545; }
        .stat-double { color: #c0392b; font-weight: 600; }
        .stat-triple { color: #8b0000; font-weight: 700; }
        .stat-total { font-weight: 700; color: var(--primary-color); }
        .empty-state {
            text-align: center; padding: 40px; color: #888; font-size: 1.1em;
        }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-section { padding: 16px; }
            .section-title { font-size: 1.3em; }
            table { font-size: 0.85em; }
            td, th { padding: 6px 4px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-link">&larr; Back</a>

        <div class="content-section">
            <h2 class="section-title">
                <span>{{ $league->name }} - Scoring Distribution</span>
                <div class="toggle-container">
                    <button class="toggle-btn active" id="btn-gross" onclick="showMode('gross')">Gross</button>
                    <button class="toggle-btn" id="btn-net" onclick="showMode('net')">Net</button>
                </div>
            </h2>

            @if($grossStats->isEmpty())
                <div class="empty-state">No completed matches with scores yet.</div>
            @else
                {{-- Gross Table --}}
                <div id="table-gross" class="scrollable-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <th>Albatross</th>
                                <th>Eagle</th>
                                <th>Birdie</th>
                                <th>Par</th>
                                <th>Bogey</th>
                                <th>Double</th>
                                <th>Triple+</th>
                                <th>Holes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grossStats as $index => $stat)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $stat['player']->name }}</td>
                                    <td class="stat-albatross">{{ $stat['albatross'] ?: '-' }}</td>
                                    <td class="stat-eagle">{{ $stat['eagle'] ?: '-' }}</td>
                                    <td class="stat-birdie">{{ $stat['birdie'] ?: '-' }}</td>
                                    <td class="stat-par">{{ $stat['par'] ?: '-' }}</td>
                                    <td class="stat-bogey">{{ $stat['bogey'] ?: '-' }}</td>
                                    <td class="stat-double">{{ $stat['double'] ?: '-' }}</td>
                                    <td class="stat-triple">{{ $stat['triple_plus'] ?: '-' }}</td>
                                    <td class="stat-total">{{ $stat['total_holes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Net Table --}}
                <div id="table-net" class="scrollable-table" style="display: none;">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <th>Albatross</th>
                                <th>Eagle</th>
                                <th>Birdie</th>
                                <th>Par</th>
                                <th>Bogey</th>
                                <th>Double</th>
                                <th>Triple+</th>
                                <th>Holes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($netStats as $index => $stat)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $stat['player']->name }}</td>
                                    <td class="stat-albatross">{{ $stat['albatross'] ?: '-' }}</td>
                                    <td class="stat-eagle">{{ $stat['eagle'] ?: '-' }}</td>
                                    <td class="stat-birdie">{{ $stat['birdie'] ?: '-' }}</td>
                                    <td class="stat-par">{{ $stat['par'] ?: '-' }}</td>
                                    <td class="stat-bogey">{{ $stat['bogey'] ?: '-' }}</td>
                                    <td class="stat-double">{{ $stat['double'] ?: '-' }}</td>
                                    <td class="stat-triple">{{ $stat['triple_plus'] ?: '-' }}</td>
                                    <td class="stat-total">{{ $stat['total_holes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!empty($grossByHole) || !empty($netByHole))
                <div style="margin-top: 30px; border-top: 2px solid #f0f0f0; padding-top: 20px;">
                    <h2 class="section-title" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                        <span>Totals by Hole</span>
                    </h2>
                    @php $idSuffix = 'full'; @endphp
                    @include('leagues.hole-stats-by-hole')
                </div>
            @endif
        </div>
    </div>

    <script>
        function showMode(mode) {
            document.getElementById('table-gross').style.display = mode === 'gross' ? '' : 'none';
            document.getElementById('table-net').style.display = mode === 'net' ? '' : 'none';
            document.getElementById('hs-byhole-gross-full').style.display = mode === 'gross' ? '' : 'none';
            document.getElementById('hs-byhole-net-full').style.display = mode === 'net' ? '' : 'none';
            document.getElementById('btn-gross').classList.toggle('active', mode === 'gross');
            document.getElementById('btn-net').classList.toggle('active', mode === 'net');
        }
    </script>
</body>
</html>

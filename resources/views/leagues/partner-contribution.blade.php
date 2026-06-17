<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Contribution - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1150px; margin: 0 auto; }
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
            font-size: 1.8em; color: var(--primary-color); margin-bottom: 6px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .subtitle { color: #888; margin-bottom: 20px; font-size: 0.95em; }
        .toggle-container { display: flex; gap: 6px; }
        .toggle-btn {
            padding: 8px 18px; cursor: pointer; font-weight: 600; border: none;
            background: #f0f0f5; color: #666; transition: all 0.3s ease; font-size: 13px;
            border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .toggle-btn.active { background: var(--primary-color); color: white; }
        .legend {
            background: var(--primary-light); border-radius: 10px; padding: 16px 20px;
            margin-bottom: 22px; font-size: 0.88em; color: #444; line-height: 1.6;
        }
        .legend b { color: var(--primary-color); }
        .legend .pill { display: inline-block; padding: 1px 7px; border-radius: 6px; font-weight: 700; font-size: 0.9em; }
        .pos { color: #1e7e34; }
        .neg { color: #c0392b; }
        .scrollable-table { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95em; }
        th {
            background: var(--primary-light); color: var(--primary-color); padding: 10px 8px;
            text-align: center; font-size: 0.8em; border-bottom: 2px solid #d0d5e0; white-space: nowrap;
        }
        th:nth-child(2) { text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: center; }
        td:first-child { font-weight: 600; color: var(--primary-color); width: 36px; }
        td:nth-child(2) { text-align: left; font-weight: 600; white-space: nowrap; }
        tr:hover { background: var(--primary-light); }
        .sub-badge {
            display: inline-block; margin-left: 6px; padding: 1px 6px; border-radius: 6px;
            background: #f0f0f5; color: #888; font-size: 0.72em; font-weight: 600; vertical-align: middle;
        }
        .num-pos { color: #1e7e34; font-weight: 700; }
        .num-neg { color: #c0392b; font-weight: 700; }
        .muted { color: #aaa; }
        .empty-state { text-align: center; padding: 40px; color: #888; font-size: 1.1em; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-section { padding: 16px; }
            .section-title { font-size: 1.3em; }
            table { font-size: 0.82em; }
            td, th { padding: 6px 4px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-link">&larr; Back</a>

        <div class="content-section">
            <h2 class="section-title">
                <span>{{ $league->name }} &mdash; Partner Contribution</span>
                @if($hasData)
                    <div class="toggle-container">
                        <button class="toggle-btn active" id="btn-regulars" onclick="showSet('regulars')">Regulars</button>
                        <button class="toggle-btn" id="btn-all" onclick="showSet('all')">All players</button>
                    </div>
                @endif
            </h2>
            <div class="subtitle">Through {{ $weeks }} completed {{ \Illuminate\Support\Str::plural('week', $weeks) }} ({{ $matches }} matches). Net match play.</div>

            @if(!$hasData)
                <div class="empty-state">No completed matches with scores yet.</div>
            @else
                <div class="legend">
                    Each player is measured by how they'd do <b>on their own merits</b> (their net vs. the opponents' best ball each hole; in individual weeks, their own head-to-head), then compared to their partners and to the actual team result.
                    <br>
                    &bull; <b>Solo</b>: avg match points alone (1 win / .5 tie / 0 loss). &nbsp;
                    &bull; <b>Partner</b>: same, averaged over their partners. &nbsp;
                    &bull; <b class="pos">vs Partner</b> = Solo &minus; Partner &mdash; <span class="pos">positive = you outplay your partners (carrying)</span>, <span class="neg">negative = partners are stronger (being carried)</span>. &nbsp;
                    &bull; <b>Team</b>: actual result you shared. &nbsp;
                    &bull; <b>Carry gap</b> = Team &minus; Solo &mdash; how much the team banks above what you'd earn alone. &nbsp;
                    &bull; <b>Carried / Rescued</b> (best-ball holes): holes you won for the team vs. holes your partner won that you'd have lost.
                </div>

                <div class="scrollable-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <th>M</th>
                                <th>Solo<br>/mtch</th>
                                <th>Partner<br>/mtch</th>
                                <th>vs<br>Partner</th>
                                <th>Team<br>/mtch</th>
                                <th>Carry<br>gap</th>
                                <th>Holes<br>carried</th>
                                <th>Holes<br>rescued</th>
                                <th>Solo holes<br>W-L-T</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php $isRegular = $row['matches'] >= 3 && $row['sub_apps'] < $row['matches']; @endphp
                                <tr class="pc-row" data-regular="{{ $isRegular ? 1 : 0 }}">
                                    <td class="rank"></td>
                                    <td>
                                        {{ $row['name'] }}
                                        @if($row['sub_apps'] > 0)
                                            <span class="sub-badge">{{ $row['sub_apps'] }} sub</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['matches'] }}</td>
                                    <td>{{ number_format($row['solo_avg'], 2) }}</td>
                                    <td>{{ number_format($row['partner_avg'], 2) }}</td>
                                    <td class="{{ $row['vs_partner'] > 0.001 ? 'num-pos' : ($row['vs_partner'] < -0.001 ? 'num-neg' : 'muted') }}">
                                        {{ sprintf('%+.2f', $row['vs_partner']) }}
                                    </td>
                                    <td>{{ number_format($row['team_avg'], 2) }}</td>
                                    <td class="{{ $row['carry_gap'] > 0.001 ? 'num-neg' : ($row['carry_gap'] < -0.001 ? 'num-pos' : 'muted') }}">
                                        {{ sprintf('%+.2f', $row['carry_gap']) }}
                                    </td>
                                    <td>{{ $row['carried'] ?: '-' }}</td>
                                    <td>{{ $row['rescued'] ?: '-' }}</td>
                                    <td class="muted">{{ $row['solo_wlt'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p style="margin-top:16px;color:#999;font-size:0.82em;">
                    Sorted by <b>vs Partner</b> (biggest carriers at the top, most-carried at the bottom). Small samples (1&ndash;2 matches) are noisy &mdash; use the toggle to focus on regulars.
                </p>
            @endif
        </div>
    </div>

    <script>
        function showSet(set) {
            var regularsOnly = set === 'regulars';
            document.getElementById('btn-regulars').classList.toggle('active', regularsOnly);
            document.getElementById('btn-all').classList.toggle('active', !regularsOnly);
            var rank = 0;
            document.querySelectorAll('.pc-row').forEach(function (tr) {
                var show = !regularsOnly || tr.dataset.regular === '1';
                tr.style.display = show ? '' : 'none';
                tr.querySelector('.rank').textContent = show ? (++rank) : '';
            });
        }
        showSet('regulars');
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tee Time Distribution - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
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
        }
        .subtitle { color: #666; font-size: 0.95em; margin-bottom: 20px; }
        .scrollable-table { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95em; }
        th {
            background: var(--primary-light); color: var(--primary-color); padding: 10px 8px;
            text-align: center; font-size: 0.85em; border-bottom: 2px solid #d0d5e0;
            white-space: nowrap;
        }
        th:first-child, th:nth-child(2) { text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #f0f0f0; text-align: center; }
        td:first-child { text-align: center; font-weight: 600; color: var(--primary-color); width: 40px; }
        td:nth-child(2) { text-align: left; font-weight: 600; white-space: nowrap; }
        tr:hover { background: var(--primary-light); }
        .total-col { font-weight: 700; color: var(--primary-color); border-left: 2px solid #d0d5e0; }
        .zero { color: #ccc; }
        .empty-state { text-align: center; padding: 40px; color: #888; font-size: 1.1em; }
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
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">&larr; Back to League</a>

        <div class="content-section">
            <h2 class="section-title">{{ $league->name }} &mdash; Tee Time Distribution</h2>
            <div class="subtitle">Number of times each player has been scheduled at each tee time slot. Substitute appearances count toward the rostered player.</div>

            @if($teeTimes->isEmpty() || $rows->isEmpty())
                <div class="empty-state">No tee times have been assigned to matches in this league yet.</div>
            @else
                <div class="scrollable-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                @foreach($teeTimes as $tt)
                                    <th>{{ \Carbon\Carbon::parse($tt)->format('g:i A') }}</th>
                                @endforeach
                                <th class="total-col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['player']->name }}</td>
                                    @foreach($teeTimes as $tt)
                                        @php $c = $row['counts'][$tt] ?? 0; @endphp
                                        <td class="{{ $c === 0 ? 'zero' : '' }}">{{ $c === 0 ? '–' : $c }}</td>
                                    @endforeach
                                    <td class="total-col">{{ $row['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</body>
</html>

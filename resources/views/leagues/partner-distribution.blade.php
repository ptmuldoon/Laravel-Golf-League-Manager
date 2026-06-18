<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Distribution - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
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
        table { border-collapse: collapse; font-size: 0.9em; }
        th {
            background: var(--primary-light); color: var(--primary-color); padding: 8px 6px;
            text-align: center; font-size: 0.8em; border-bottom: 2px solid #d0d5e0;
            white-space: nowrap;
        }
        td { padding: 7px 6px; border-bottom: 1px solid #f0f0f0; text-align: center; min-width: 34px; }
        /* Sticky leading columns (index + player name) */
        th.sticky, td.sticky { position: sticky; background: white; z-index: 2; }
        th.sticky { background: var(--primary-light); }
        th.col-idx, td.col-idx { left: 0; width: 40px; text-align: center; font-weight: 600; color: var(--primary-color); }
        th.col-name, td.col-name { left: 40px; text-align: left; font-weight: 600; white-space: nowrap; border-right: 2px solid #d0d5e0; }
        tr:hover td { background: var(--primary-light); }
        tr:hover td.sticky { background: var(--primary-light); }
        .diag { background: #eceff4; color: #bbb; }
        .zero { color: #ccc; }
        .rowmax { background: var(--primary-color); color: white; font-weight: 700; border-radius: 4px; }
        /* Keep the highlighted partner and diagonal cells fixed on row hover */
        tr:hover td.rowmax { background: var(--primary-color); color: white; }
        tr:hover td.diag { background: #eceff4; color: #bbb; }
        .total-col { font-weight: 700; color: var(--primary-color); border-left: 2px solid #d0d5e0; }
        .empty-state { text-align: center; padding: 40px; color: #888; font-size: 1.1em; }
        .season-toggle { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
        .season-toggle a {
            text-decoration: none; padding: 8px 16px; border-radius: 20px;
            font-size: 0.9em; font-weight: 600; border: 2px solid var(--primary-color);
            color: var(--primary-color); background: white; transition: all 0.2s ease;
        }
        .season-toggle a:hover { background: var(--primary-light); }
        .season-toggle a.active { background: var(--primary-color); color: white; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-section { padding: 16px; }
            .section-title { font-size: 1.3em; }
            table { font-size: 0.8em; }
            td, th { padding: 5px 4px; }
        }
    </style>
</head>
<body>
    @php
        $abbrev = function ($p) {
            if ($p->first_name && $p->last_name) {
                return strtoupper(substr($p->first_name, 0, 1)) . '. ' . $p->last_name;
            }
            return $p->name;
        };
    @endphp
    <div class="container">
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">&larr; Back to League</a>

        <div class="content-section">
            <h2 class="section-title">{{ $league->name }} &mdash; Partner Distribution</h2>
            <div class="subtitle">Number of times each player has been in the same group as every other player. The highlighted cell in each row is that player's most frequent partner.</div>

            @if($segments->isNotEmpty())
                <div class="season-toggle">
                    <a href="{{ route('admin.leagues.partnerDistribution', $league->id) }}" class="{{ $selectedSegment ? '' : 'active' }}">All Seasons</a>
                    @foreach($segments as $segment)
                        <a href="{{ route('admin.leagues.partnerDistribution', ['league_id' => $league->id, 'segment_id' => $segment->id]) }}" class="{{ $selectedSegment && $selectedSegment->id === $segment->id ? 'active' : '' }}">{{ $segment->name }}</a>
                    @endforeach
                </div>
            @endif

            @if($players->isEmpty())
                <div class="empty-state">No grouped matches found for this league yet.</div>
            @else
                <div class="scrollable-table">
                    <table>
                        <thead>
                            <tr>
                                <th class="sticky col-idx">#</th>
                                <th class="sticky col-name">Player</th>
                                @foreach($players as $colPlayer)
                                    <th title="{{ $colPlayer->name }}">{{ $abbrev($colPlayer) }}</th>
                                @endforeach
                                <th class="total-col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($players as $index => $rowPlayer)
                                @php $rowMax = max($matrix[$rowPlayer->id] ?? [0]); @endphp
                                <tr>
                                    <td class="sticky col-idx">{{ $index + 1 }}</td>
                                    <td class="sticky col-name">{{ $rowPlayer->name }}</td>
                                    @foreach($players as $colPlayer)
                                        @if($colPlayer->id === $rowPlayer->id)
                                            <td class="diag">—</td>
                                        @else
                                            @php $c = $matrix[$rowPlayer->id][$colPlayer->id] ?? 0; @endphp
                                            <td class="{{ $c === 0 ? 'zero' : ($c === $rowMax && $rowMax > 0 ? 'rowmax' : '') }}">{{ $c === 0 ? '–' : $c }}</td>
                                        @endif
                                    @endforeach
                                    <td class="total-col">{{ $rowTotals[$rowPlayer->id] ?? 0 }}</td>
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

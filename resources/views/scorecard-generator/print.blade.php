<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scorecard - {{ $course->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; padding: 20px; }
        .no-print { text-align: center; margin-bottom: 20px; }
        .no-print a, .no-print button {
            display: inline-block; padding: 10px 24px; border-radius: 8px; font-weight: 600;
            font-size: 1em; text-decoration: none; border: none; cursor: pointer; margin: 0 5px;
        }
        .btn-back { background: #6c757d; color: white; }
        .btn-print { background: var(--primary-color); color: white; }
        .scorecard {
            background: white; border: 2px solid #333; border-radius: 4px;
            max-width: 1000px; margin: 0 auto 30px; page-break-inside: avoid;
        }
        .scorecard-header {
            padding: 12px 15px; border-bottom: 2px solid #333;
            display: flex; justify-content: space-between; align-items: center;
        }
        .scorecard-title { font-size: 1.1em; font-weight: 700; }
        .scorecard-group { font-size: 1.3em; font-weight: 700; color: #d32f2f; text-align: center; flex: 1; }
        .scorecard-info { font-size: 0.85em; color: #444; text-align: right; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #999; padding: 5px 4px; text-align: center; font-size: 0.8em; }
        th { background: #e8ecf4; font-weight: 700; color: #333; }
        .hole-col { width: 10px; }
        .player-name-cell { text-align: left; font-weight: 600; padding-left: 8px; white-space: nowrap; }
        .par-row { background: #f0f0f0; font-weight: 700; }
        .hdcp-row { background: #fff8e1; font-size: 0.75em; }
        .yardage-row { background: #e8f0fe; font-size: 0.75em; }
        .score-cell { height: 28px; min-width: 28px; position: relative; }
        .score-cell .stroke-dots {
            position: absolute; top: 1px; right: 1px; font-size: 10px; line-height: 1;
            color: var(--secondary-color);
        }
        .total-cell { font-weight: 700; background: var(--primary-light); }
        .subtot { background: #eef1f6; font-weight: 700; }
        th.subtot { background: #dde3ec; }
        .net-row td { height: 22px; font-size: 0.7em; color: #888; border-top: none; }
        .net-label { text-align: left; padding-left: 8px; font-style: italic; }
        .note-row td { height: 24px; }

        /* Force browsers to print background colors */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }

        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print { display: none; }
            .scorecard { margin: 0; border: 2px solid #000; page-break-inside: avoid; break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $hasYardage = $holes->contains(fn($h) => $h->yardage);
        $front = $holes->where('hole_number', '<=', 9);
        $back = $holes->where('hole_number', '>', 9);
        $hasFront = $front->isNotEmpty();
        $hasBack = $back->isNotEmpty();

        // Build the ordered column model. OUT/IN subtotal columns only appear on
        // an 18-hole card; a 9-hole card just shows the holes and a TOT column.
        $columns = [];
        foreach ($holes as $h) {
            $columns[] = ['type' => 'hole', 'h' => $h];
            if (!$nineHole && $h->hole_number == 9 && $hasBack) {
                $columns[] = ['type' => 'out'];
            }
        }
        if (!$nineHole) {
            $columns[] = ['type' => 'in'];
        }
        $columns[] = ['type' => 'tot'];

        $parOut = $front->sum('par'); $parIn = $back->sum('par'); $parTot = $holes->sum('par');
        $ydOut = $front->sum('yardage'); $ydIn = $back->sum('yardage'); $ydTot = $holes->sum('yardage');
        $colSpan = count($columns) + 1; // label + all columns
    @endphp

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print</button>
        <a class="btn-back" href="{{ route('scorecardGenerator') }}">← New Scorecard</a>
    </div>

    <div class="scorecard">
        <div class="scorecard-header">
            <div class="scorecard-title">{{ $course->name }}</div>
            <div class="scorecard-group">Scorecard</div>
            <div class="scorecard-info">
                {{ $teebox }} Tees | {{ $holesLabel }}<br>
                Date: __________________
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 30px; text-align: left; padding-left: 8px;">Hole</th>
                    @foreach($columns as $col)
                        @if($col['type'] === 'hole')
                            <th class="hole-col">{{ $col['h']->hole_number }}</th>
                        @elseif($col['type'] === 'out')
                            <th class="hole-col subtot">OUT</th>
                        @elseif($col['type'] === 'in')
                            <th class="hole-col subtot">IN</th>
                        @else
                            <th class="hole-col subtot">TOT</th>
                        @endif
                    @endforeach
                </tr>
                <tr class="yardage-row">
                    <td style="text-align: left; padding-left: 8px;"><strong>{{ $teebox }}</strong> Yds</td>
                    @foreach($columns as $col)
                        @if($col['type'] === 'hole')
                            <td>{{ $col['h']->yardage ?? '' }}</td>
                        @elseif($col['type'] === 'out')
                            <td class="subtot">{{ $ydOut ?: '' }}</td>
                        @elseif($col['type'] === 'in')
                            <td class="subtot">{{ $ydIn ?: '' }}</td>
                        @else
                            <td class="subtot">{{ $ydTot ?: '' }}</td>
                        @endif
                    @endforeach
                </tr>
                <tr class="par-row">
                    <td style="text-align: left; padding-left: 8px;"><strong>Par</strong></td>
                    @foreach($columns as $col)
                        @if($col['type'] === 'hole')
                            <td>{{ $col['h']->par }}</td>
                        @elseif($col['type'] === 'out')
                            <td class="subtot">{{ $parOut }}</td>
                        @elseif($col['type'] === 'in')
                            <td class="subtot">{{ $parIn }}</td>
                        @else
                            <td class="subtot">{{ $parTot }}</td>
                        @endif
                    @endforeach
                </tr>
                <tr class="hdcp-row">
                    <td style="text-align: left; padding-left: 8px;"><strong>Hdcp</strong></td>
                    @foreach($columns as $col)
                        @if($col['type'] === 'hole')
                            <td>{{ $col['h']->handicap ?? '' }}</td>
                        @else
                            <td class="subtot"></td>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($players as $player)
                    @php
                        // Strokes actually given on the holes being played (may be
                        // ~half the full handicap for a 9-hole round).
                        $cardStrokes = collect($player['strokes'])->only($holes->pluck('hole_number')->all())->sum();
                    @endphp
                    <tr>
                        <td class="player-name-cell">
                            {{ $player['name'] }}
                            <span style="font-size: 0.75em; color: var(--secondary-color); font-weight: 500;">({{ $player['handicap'] }}@if($nineHole)/{{ $cardStrokes }}@endif)</span>
                        </td>
                        @foreach($columns as $col)
                            @if($col['type'] === 'hole')
                                @php $s = $player['strokes'][$col['h']->hole_number] ?? 0; @endphp
                                <td class="score-cell">@if($s > 0)<span class="stroke-dots">{{ str_repeat('●', $s) }}</span>@endif</td>
                            @else
                                <td class="score-cell total-cell"></td>
                            @endif
                        @endforeach
                    </tr>
                    <tr class="net-row">
                        <td class="net-label">Net</td>
                        @foreach($columns as $col)
                            <td class="{{ $col['type'] === 'hole' ? '' : 'subtot' }}"></td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="note-row">
                    <td style="text-align: left; padding-left: 8px; font-size: 0.75em; font-weight: 600; color: #666;">Note:</td>
                    <td colspan="{{ $colSpan - 1 }}"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>

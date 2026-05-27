@php
    $categories = ['albatross', 'eagle', 'birdie', 'par', 'bogey', 'double', 'triple_plus'];
    $catLabels = ['Albatross', 'Eagle', 'Birdie', 'Par', 'Bogey', 'Double', 'Triple+'];
    $catColors = ['#9b59b6', '#e67e22', '#28a745', '#333', '#dc3545', '#c0392b', '#8b0000'];
    $catWeights = ['700', '700', '600', 'normal', 'normal', '600', '700'];

    $frontGross = array_filter($grossByHole, fn($v, $k) => $k >= 1 && $k <= 9, ARRAY_FILTER_USE_BOTH);
    $backGross = array_filter($grossByHole, fn($v, $k) => $k >= 10 && $k <= 18, ARRAY_FILTER_USE_BOTH);
    $frontNet = array_filter($netByHole, fn($v, $k) => $k >= 1 && $k <= 9, ARRAY_FILTER_USE_BOTH);
    $backNet = array_filter($netByHole, fn($v, $k) => $k >= 10 && $k <= 18, ARRAY_FILTER_USE_BOTH);

    $buildTotals = function($holes) use ($categories) {
        $totals = array_fill_keys($categories, 0);
        foreach ($holes as $h) {
            foreach ($categories as $cat) { $totals[$cat] += $h[$cat]; }
        }
        return $totals;
    };

    $vsParColor = function($diff) {
        if ($diff === null) return '#888';
        if ($diff > 0.5) return '#c0392b';
        if ($diff > 0) return '#dc3545';
        if ($diff < -0.5) return '#1e7e34';
        if ($diff < 0) return '#28a745';
        return '#333';
    };
@endphp

@foreach(['gross' => [$frontGross, $backGross], 'net' => [$frontNet, $backNet]] as $mode => $nines)
    <div id="hs-byhole-{{ $mode }}-{{ $idSuffix }}" style="{{ $mode === 'net' ? 'display: none;' : '' }}">
        @foreach([['Front 9', $nines[0], 1], ['Back 9', $nines[1], 10]] as $nine)
            @php
                [$label, $holeData, $startHole] = $nine;
                $holes = [];
                for ($h = $startHole; $h < $startHole + 9; $h++) {
                    if (isset($holeData[$h])) $holes[$h] = $holeData[$h];
                }
                $totals = $buildTotals($holes);
            @endphp
            @if(!empty($holes))
                <h3 style="color: var(--primary-color); font-size: 1.1em; margin: 20px 0 10px 0;">{{ $label }} <span style="font-size: 0.8em; color: #888; font-weight: normal;">({{ ucfirst($mode) }})</span></h3>
                <div class="scrollable-table">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: left; min-width: 75px;">Hole</th>
                                @foreach($holes as $holeNum => $hData)
                                    <th>{{ $holeNum }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                            @php
                                $parTotal = 0;
                                $hasPar = false;
                                foreach ($holes as $holeNum => $hData) {
                                    if (!empty($parByHole[$holeNum])) {
                                        $parTotal += $parByHole[$holeNum];
                                        $hasPar = true;
                                    }
                                }
                            @endphp
                            @if($hasPar)
                                <tr>
                                    <th style="text-align: left; font-weight: 600; color: #666;">Par</th>
                                    @foreach($holes as $holeNum => $hData)
                                        <th style="font-weight: normal; color: #666;">{{ $parByHole[$holeNum] ?? '-' }}</th>
                                    @endforeach
                                    <th style="color: #666;">{{ $parTotal }}</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @for($i = 0; $i < count($categories); $i++)
                                <tr>
                                    <td style="text-align: left; font-weight: 600; color: {{ $catColors[$i] }};">{{ $catLabels[$i] }}</td>
                                    @foreach($holes as $holeNum => $hData)
                                        <td style="color: {{ $catColors[$i] }}; font-weight: {{ $catWeights[$i] }};">{{ $hData[$categories[$i]] ?: '-' }}</td>
                                    @endforeach
                                    <td style="color: {{ $catColors[$i] }}; font-weight: 700;">{{ $totals[$categories[$i]] ?: '-' }}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    </div>
@endforeach

<div id="hs-byhole-vspar-{{ $idSuffix }}" style="display: none;">
    @foreach([['Front 9', 1], ['Back 9', 10]] as $nine)
        @php
            [$label, $startHole] = $nine;
            $holesVsPar = [];
            for ($h = $startHole; $h < $startHole + 9; $h++) {
                if (isset($avgByHole[$h]) && !empty($parByHole[$h])) {
                    $holesVsPar[$h] = $avgByHole[$h];
                }
            }
            $parTotalVs = 0;
            $avgSumVs = 0;
            foreach ($holesVsPar as $holeNum => $a) {
                $parTotalVs += $parByHole[$holeNum];
                $avgSumVs += $a['gross_avg'];
            }
            $diffTotalVs = $avgSumVs - $parTotalVs;
        @endphp
        @if(!empty($holesVsPar))
            <h3 style="color: var(--primary-color); font-size: 1.1em; margin: 20px 0 10px 0;">{{ $label }} <span style="font-size: 0.8em; color: #888; font-weight: normal;">(Avg vs Par)</span></h3>
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left; min-width: 75px;">Hole</th>
                            @foreach($holesVsPar as $holeNum => $a)
                                <th>{{ $holeNum }}</th>
                            @endforeach
                            <th>Total</th>
                        </tr>
                        <tr>
                            <th style="text-align: left; font-weight: 600; color: #666;">Par</th>
                            @foreach($holesVsPar as $holeNum => $a)
                                <th style="font-weight: normal; color: #666;">{{ $parByHole[$holeNum] }}</th>
                            @endforeach
                            <th style="color: #666;">{{ $parTotalVs }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: left; font-weight: 600; color: #333;">Avg Score</td>
                            @foreach($holesVsPar as $holeNum => $a)
                                <td style="color: #333;">{{ number_format($a['gross_avg'], 1) }}</td>
                            @endforeach
                            <td style="color: #333; font-weight: 700;">{{ number_format($avgSumVs, 1) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: 600; color: #333;">vs Par</td>
                            @foreach($holesVsPar as $holeNum => $a)
                                @php $diff = $a['gross_avg'] - $parByHole[$holeNum]; @endphp
                                <td style="color: {{ $vsParColor($diff) }}; font-weight: 600;">{{ ($diff >= 0 ? '+' : '') . number_format($diff, 1) }}</td>
                            @endforeach
                            <td style="color: {{ $vsParColor($diffTotalVs) }}; font-weight: 700;">{{ ($diffTotalVs >= 0 ? '+' : '') . number_format($diffTotalVs, 1) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: 600; color: #888; font-size: 0.9em;">Rounds</td>
                            @foreach($holesVsPar as $holeNum => $a)
                                <td style="color: #888; font-size: 0.9em;">{{ $a['gross_count'] }}</td>
                            @endforeach
                            <td style="color: #888; font-size: 0.9em;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach
</div>

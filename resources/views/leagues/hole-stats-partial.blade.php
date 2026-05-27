<div class="content-section">
    <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span>Scoring Distribution</span>
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="display: flex; background: #f0f0f5; border-radius: 8px; overflow: hidden; font-size: 0.5em;">
                <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: var(--primary-color); color: white; border-radius: 8px; font-size: 14px; transition: all 0.3s ease;" id="btn-gross-{{ $league->id }}" onclick="showHoleStatsMode('gross', {{ $league->id }})">Gross</button>
                <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: transparent; color: #666; font-size: 14px; transition: all 0.3s ease;" id="btn-net-{{ $league->id }}" onclick="showHoleStatsMode('net', {{ $league->id }})">Net</button>
            </div>
            <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('scoring-dist-body-{{ $league->id }}')" id="toggle-scoring-dist-body-{{ $league->id }}">&#9650;</span>
        </div>
    </h2>
    <div id="scoring-dist-body-{{ $league->id }}">
    @if($grossStats->isEmpty())
        <div style="text-align: center; padding: 40px; color: #888;">No completed matches with scores yet.</div>
    @else
        {{-- Gross Table --}}
        <div id="hs-table-gross-{{ $league->id }}" class="scrollable-table">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">#</th>
                        <th style="text-align: left;">Player</th>
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
                            <td style="font-weight: 600; color: var(--primary-color);">{{ $index + 1 }}</td>
                            <td style="text-align: left; font-weight: 600; white-space: nowrap;">{{ $stat['player']->name }}</td>
                            <td style="color: #9b59b6; font-weight: 700;">{{ $stat['albatross'] ?: '-' }}</td>
                            <td style="color: #e67e22; font-weight: 700;">{{ $stat['eagle'] ?: '-' }}</td>
                            <td style="color: #28a745; font-weight: 600;">{{ $stat['birdie'] ?: '-' }}</td>
                            <td>{{ $stat['par'] ?: '-' }}</td>
                            <td style="color: #dc3545;">{{ $stat['bogey'] ?: '-' }}</td>
                            <td style="color: #c0392b; font-weight: 600;">{{ $stat['double'] ?: '-' }}</td>
                            <td style="color: #8b0000; font-weight: 700;">{{ $stat['triple_plus'] ?: '-' }}</td>
                            <td style="font-weight: 700; color: var(--primary-color);">{{ $stat['total_holes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Net Table --}}
        <div id="hs-table-net-{{ $league->id }}" class="scrollable-table" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">#</th>
                        <th style="text-align: left;">Player</th>
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
                            <td style="font-weight: 600; color: var(--primary-color);">{{ $index + 1 }}</td>
                            <td style="text-align: left; font-weight: 600; white-space: nowrap;">{{ $stat['player']->name }}</td>
                            <td style="color: #9b59b6; font-weight: 700;">{{ $stat['albatross'] ?: '-' }}</td>
                            <td style="color: #e67e22; font-weight: 700;">{{ $stat['eagle'] ?: '-' }}</td>
                            <td style="color: #28a745; font-weight: 600;">{{ $stat['birdie'] ?: '-' }}</td>
                            <td>{{ $stat['par'] ?: '-' }}</td>
                            <td style="color: #dc3545;">{{ $stat['bogey'] ?: '-' }}</td>
                            <td style="color: #c0392b; font-weight: 600;">{{ $stat['double'] ?: '-' }}</td>
                            <td style="color: #8b0000; font-weight: 700;">{{ $stat['triple_plus'] ?: '-' }}</td>
                            <td style="font-weight: 700; color: var(--primary-color);">{{ $stat['total_holes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>
</div>

@if(!empty($grossByHole) || !empty($netByHole))
    <div class="content-section">
        <h2 class="section-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <span>Totals by Hole</span>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="display: flex; gap: 6px; font-size: 0.5em;">
                    <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: var(--primary-color); color: white; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" id="btn-byhole-gross-{{ $league->id }}" onclick="showHoleStatsByHoleMode('gross', {{ $league->id }})">Gross</button>
                    <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: #f0f0f5; color: #666; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" id="btn-byhole-net-{{ $league->id }}" onclick="showHoleStatsByHoleMode('net', {{ $league->id }})">Net</button>
                    <button style="padding: 8px 20px; cursor: pointer; font-weight: 600; border: none; background: #f0f0f5; color: #666; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" id="btn-byhole-vspar-{{ $league->id }}" onclick="showHoleStatsByHoleMode('vspar', {{ $league->id }})">vs. Par</button>
                </div>
                <span style="cursor: pointer; user-select: none; color: #e67e22; font-size: 0.6em;" onclick="toggleSection('totals-byhole-body-{{ $league->id }}')" id="toggle-totals-byhole-body-{{ $league->id }}">&#9650;</span>
            </div>
        </h2>
        <div id="totals-byhole-body-{{ $league->id }}">
        @php $idSuffix = $league->id; @endphp
        @include('leagues.hole-stats-by-hole')
        </div>
    </div>
@endif

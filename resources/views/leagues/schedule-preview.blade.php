<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Preview - {{ $league->name }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; padding: 20px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <a href="{{ route('admin.leagues.autoSchedule', $league->id) }}" style="display: inline-block; color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 5px; margin-bottom: 20px;">← Back to Generator</a>

        <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h1 style="color: var(--primary-color); margin-bottom: 10px; font-size: 2em;">📋 Schedule Preview</h1>
            <p style="color: #666; margin-bottom: 10px;">{{ $league->name }} - {{ $weeks }} weeks starting {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}</p>
            <p style="color: #666; margin-bottom: 20px;">
                🏌️ {{ $holes === 'front_9' ? 'Front 9' : 'Back 9' }} |
                📋 {{ $scoringTypes[$scoringType] ?? $scoringType }} |
                🎯 {{ ($scoreMode ?? 'net') === 'gross' ? 'Gross Scoring' : 'Net Scoring' }} |
                🕐 Tee times starting {{ \Carbon\Carbon::createFromFormat('H:i', $startTeeTime)->format('g:i A') }}, {{ $teeTimeInterval }} min intervals
            </p>

            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                <strong>✓ Schedule Generated Successfully!</strong><br>
                Review the pairings below. If you're satisfied, click "Save Schedule" to add these matches to your league.
            </div>

            <form action="{{ route('admin.leagues.saveSchedule', $league->id) }}" method="POST">
                @csrf
                <input type="hidden" name="weeks" value="{{ $weeks }}">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="holes" value="{{ $holes }}">
                <input type="hidden" name="scoring_type" value="{{ $scoringType }}">
                <input type="hidden" name="score_mode" value="{{ $scoreMode ?? 'net' }}">
                <input type="hidden" name="start_tee_time" value="{{ $startTeeTime }}">
                <input type="hidden" name="tee_time_interval" value="{{ $teeTimeInterval }}">

                <div style="display: flex; gap: 15px; margin-bottom: 30px;">
                    <button type="submit" style="padding: 12px 24px; border: none; border-radius: 8px; font-size: 1em; font-weight: 600; cursor: pointer; background: #28a745; color: white;">
                        💾 Save Schedule
                    </button>
                    <a href="{{ route('admin.leagues.autoSchedule', $league->id) }}" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; text-decoration: none; background: #6c757d; color: white; display: inline-block;">
                        🔄 Regenerate
                    </a>
                    <a href="{{ route('admin.leagues.show', $league->id) }}" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; text-decoration: none; background: #6c757d; color: white; display: inline-block;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Schedule by Week -->
        @foreach($scheduleData['schedule'] as $weekNumber => $foursomes)
            @php
                $weekDate = \Carbon\Carbon::parse($startDate)->addWeeks($weekNumber - 1);
            @endphp
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px; font-size: 1.5em;">
                    Week {{ $weekNumber }} - {{ $weekDate->format('M d, Y') }}
                </h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                    @foreach($foursomes as $groupIndex => $foursome)
                        @php
                            $groupTeeTime = \Carbon\Carbon::createFromFormat('H:i', $startTeeTime)->addMinutes($groupIndex * $teeTimeInterval);
                        @endphp
                        <div style="background: var(--primary-light); padding: 20px; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                            <h3 style="color: var(--primary-color); margin-bottom: 15px; font-size: 1.1em;">
                                Match {{ $foursome['group_number'] }}
                                <span style="font-weight: normal; color: #666; font-size: 0.85em;">- {{ $groupTeeTime->format('g:i A') }}</span>
                            </h3>

                            @php
                                $players = $foursome['players'];
                                $homePlayers = array_slice($players, 0, 2);
                                $awayPlayers = array_slice($players, 2, 2);
                                $homeTeamName = isset($homePlayers[0]) ? ($playerTeamNames[$homePlayers[0]] ?? 'Home Side') : 'Home Side';
                                $awayTeamName = isset($awayPlayers[0]) ? ($playerTeamNames[$awayPlayers[0]] ?? 'Away Side') : 'Away Side';
                            @endphp

                            <div style="margin-bottom: 15px;">
                                <strong style="color: #28a745;">{{ $homeTeamName }}:</strong>
                                <ul style="list-style: none; padding-left: 0; margin-top: 5px;">
                                    @foreach($homePlayers as $playerId)
                                        @php
                                            $player = $scheduleData['players']->find($playerId);
                                        @endphp
                                        @if($player)
                                            <li style="padding: 5px 0;">
                                                {{ $player->name }}
                                                <span style="color: #666; font-size: 0.9em;">
                                                    (HI: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }})
                                                </span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>

                            <div style="border-top: 1px solid #e0e0e0; padding-top: 10px;">
                                <strong style="color: #dc3545;">{{ $awayTeamName }}:</strong>
                                <ul style="list-style: none; padding-left: 0; margin-top: 5px;">
                                    @foreach($awayPlayers as $playerId)
                                        @php
                                            $player = $scheduleData['players']->find($playerId);
                                        @endphp
                                        @if($player)
                                            <li style="padding: 5px 0;">
                                                {{ $player->name }}
                                                <span style="color: #666; font-size: 0.9em;">
                                                    (HI: {{ $player->currentHandicap()?->handicap_index ?? 'N/A' }})
                                                </span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Bottom action buttons -->
        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <form action="{{ route('admin.leagues.saveSchedule', $league->id) }}" method="POST">
                @csrf
                <input type="hidden" name="weeks" value="{{ $weeks }}">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="holes" value="{{ $holes }}">
                <input type="hidden" name="scoring_type" value="{{ $scoringType }}">
                <input type="hidden" name="score_mode" value="{{ $scoreMode ?? 'net' }}">
                <input type="hidden" name="start_tee_time" value="{{ $startTeeTime }}">
                <input type="hidden" name="tee_time_interval" value="{{ $teeTimeInterval }}">

                <div style="display: flex; gap: 15px;">
                    <button type="submit" style="padding: 12px 24px; border: none; border-radius: 8px; font-size: 1em; font-weight: 600; cursor: pointer; background: #28a745; color: white;">
                        💾 Save Schedule
                    </button>
                    <a href="{{ route('admin.leagues.autoSchedule', $league->id) }}" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; text-decoration: none; background: #6c757d; color: white; display: inline-block;">
                        🔄 Regenerate
                    </a>
                    <a href="{{ route('admin.leagues.show', $league->id) }}" style="padding: 12px 24px; border-radius: 8px; font-size: 1em; font-weight: 600; text-decoration: none; background: #6c757d; color: white; display: inline-block;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

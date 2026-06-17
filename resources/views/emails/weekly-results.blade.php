<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background: #f4f4f7; font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #333;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f4f4f7; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="700" cellpadding="0" cellspacing="0" style="max-width: 700px; width: 100%;">
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, {{ $themeSettings['primary'] }} 0%, {{ $themeSettings['secondary'] }} 100%); background-color: {{ $themeSettings['primary'] }}; color: white; padding: 25px 20px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0 0 5px 0; font-size: 24px;">{{ $league->name }}</h1>
                            <p style="margin: 0; font-size: 16px; opacity: 0.9;">Week {{ $weekNumber }} Results</p>
                        </td>
                    </tr>

                    {{-- Next Week's Schedule --}}
                    @if($nextWeekMatches->isNotEmpty())
                        <tr>
                            <td style="background: white; padding: 20px;">
                                <h2 style="color: {{ $themeSettings['primary'] }}; font-size: 18px; margin: 0 0 4px 0;">Week {{ $nextWeekNumber }} Schedule</h2>
                                @php $firstNext = $nextWeekMatches->first(); @endphp
                                <p style="color: #888; font-size: 13px; margin: 0 0 12px 0;">
                                    @if($firstNext->match_date)
                                        {{ $firstNext->match_date->format('l, M d, Y') }}
                                        @if($firstNext->golfCourse)
                                            &bull; {{ $firstNext->golfCourse->name }}
                                        @endif
                                        <br>
                                    @endif
                                    {{ $firstNext->holes === 'back_9' ? 'Back 9' : 'Front 9' }}
                                    &bull; {{ \App\Models\ScoringSetting::scoringTypes()[$firstNext->scoring_type] ?? ucfirst(str_replace('_', ' ', $firstNext->scoring_type)) }}
                                </p>
                                <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
                                    <tr>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Time</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Home</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px; width: 30px;"></th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Away</th>
                                    </tr>
                                    @foreach($nextWeekMatches as $nMatch)
                                        @php
                                            $homeName = $nextWeekTeamNames[$nMatch->id]['home'] ?? 'TBD';
                                            $awayName = $nextWeekTeamNames[$nMatch->id]['away'] ?? 'TBD';
                                            $shortName = function($mp) {
                                                if ($mp->player && $mp->player->first_name && $mp->player->last_name) {
                                                    return substr($mp->player->first_name, 0, 1) . '. ' . $mp->player->last_name;
                                                }
                                                return $mp->player ? $mp->player->name : ($mp->substitute_name ?? '');
                                            };
                                            $homePlayers = $nMatch->matchPlayers->where('position_in_pairing', '<=', 2)
                                                ->map($shortName)->filter()->implode(' / ');
                                            $awayPlayers = $nMatch->matchPlayers->where('position_in_pairing', '>', 2)
                                                ->map($shortName)->filter()->implode(' / ');
                                        @endphp
                                        <tr>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; white-space: nowrap;">
                                                @if($nMatch->tee_time)
                                                    <span style="background: {{ $themeSettings['primary'] }}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">{{ \Carbon\Carbon::parse($nMatch->tee_time)->format('g:i A') }}</span>
                                                @else
                                                    <span style="color: #999;">TBD</span>
                                                @endif
                                            </td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0;">
                                                <strong>{{ $homeName }}</strong>
                                                @if($homePlayers)
                                                    <br><span style="color: #888; font-size: 12px;">{{ $homePlayers }}</span>
                                                @endif
                                            </td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; color: {{ $themeSettings['primary'] }}; font-weight: 600;">vs</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0;">
                                                <strong>{{ $awayName }}</strong>
                                                @if($awayPlayers)
                                                    <br><span style="color: #888; font-size: 12px;">{{ $awayPlayers }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- Current Week Team Results --}}
                    @if($weeklyResults->isNotEmpty())
                        <tr>
                            <td style="background: white; padding: 20px; border-top: 3px solid #f0f0f0;">
                                <h2 style="color: {{ $themeSettings['primary'] }}; font-size: 18px; margin: 0 0 12px 0;">Week {{ $weekNumber }} Team Results</h2>
                                <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
                                    <tr>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">#</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Team</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">W</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">L</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">T</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Pts</th>
                                    </tr>
                                    @foreach($weeklyResults as $index => $team)
                                        <tr>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; font-weight: 600; color: {{ $themeSettings['primary'] }};">{{ $index + 1 }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; font-weight: 600;">{{ $team->name }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #28a745; font-weight: 600;">{{ $team->cw_wins }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #dc3545; font-weight: 600;">{{ $team->cw_losses }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #856404; font-weight: 600;">{{ $team->cw_ties }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; font-weight: 600; color: {{ $themeSettings['primary'] }};">{{ $team->cw_points }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- Team Standings --}}
                    @if($standings->isNotEmpty())
                        <tr>
                            <td style="background: white; padding: 20px; border-top: 3px solid #f0f0f0;">
                                <h2 style="color: {{ $themeSettings['primary'] }}; font-size: 18px; margin: 0 0 12px 0;">Team Standings <span style="font-size: 13px; color: #888; font-weight: normal;">through Week {{ $weekNumber }}</span></h2>
                                <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
                                    <tr>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">#</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Team</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">W</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">L</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">T</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Pts</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Win%</th>
                                    </tr>
                                    @foreach($standings as $index => $team)
                                        <tr>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; font-weight: 600; color: {{ $themeSettings['primary'] }};">{{ $index + 1 }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; font-weight: 600;">{{ $team->name }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #28a745; font-weight: 600;">{{ $team->week_wins }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #dc3545; font-weight: 600;">{{ $team->week_losses }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; color: #856404; font-weight: 600;">{{ $team->week_ties }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; font-weight: 600; color: {{ $themeSettings['primary'] }};">{{ $team->week_points }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center;">{{ $team->week_win_pct }}%</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- Player Standings --}}
                    @if($playerStandings->isNotEmpty())
                        <tr>
                            <td style="background: white; padding: 20px; border-top: 3px solid #f0f0f0;">
                                <h2 style="color: {{ $themeSettings['primary'] }}; font-size: 18px; margin: 0 0 12px 0;">Player Standings <span style="font-size: 13px; color: #888; font-weight: normal;">through Week {{ $weekNumber }}</span></h2>
                                <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
                                    <tr>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">#</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Player</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Team</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">MP</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Avg</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Par 3</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">W-L-T</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: center; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Pts</th>
                                    </tr>
                                    @foreach($playerStandings as $index => $stat)
                                        <tr>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; font-weight: 600; color: {{ $themeSettings['primary'] }};">{{ $index + 1 }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; font-weight: 600;">{{ $stat['player']->name }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; color: #666;">{{ $stat['team_name'] }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center;">{{ $stat['matches_played'] }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center;">{{ $stat['avg_score'] ?? '-' }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center;">{{ $stat['total_par3'] > 0 ? $stat['total_par3'] : '-' }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; white-space: nowrap;">{{ $stat['season_wins'] }}-{{ $stat['season_losses'] }}-{{ $stat['season_ties'] }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; text-align: center; font-weight: 700; color: {{ $themeSettings['primary'] }};">{{ number_format($stat['total_season_points'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- Par 3 Winners --}}
                    @if($par3Winners->isNotEmpty())
                        <tr>
                            <td style="background: white; padding: 20px; border-top: 3px solid #f0f0f0;">
                                <h2 style="color: {{ $themeSettings['primary'] }}; font-size: 18px; margin: 0 0 12px 0;">Par 3 Winners - Week {{ $weekNumber }}</h2>
                                <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
                                    <tr>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Hole</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Winner</th>
                                        <th style="background: {{ $themeSettings['primary_light'] }}; color: {{ $themeSettings['primary'] }}; text-align: left; padding: 8px 6px; border-bottom: 2px solid #ddd; font-size: 12px;">Distance</th>
                                    </tr>
                                    @foreach($par3Winners as $winner)
                                        <tr>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0;">Hole {{ $winner->hole_number }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; font-weight: 600;">{{ $winner->player ? $winner->player->name : '-' }}</td>
                                            <td style="padding: 6px; border-bottom: 1px solid #f0f0f0; color: #666;">{{ $winner->distance ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="background: {{ $themeSettings['primary_light'] }}; padding: 15px 20px; text-align: center; color: #999; font-size: 12px; border-radius: 0 0 12px 12px; border-top: 1px solid #eee;">
                            {{ $league->name }} &bull; {{ $league->season }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMatch;
use App\Models\LeagueSegment;
use App\Models\MatchPlayer;
use App\Models\MatchResult;
use App\Models\Par3Winner;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Show the home page with weekly league results
     */
    public function index(Request $request)
    {
        // Get all active leagues for dropdown
        $allActiveLeagues = League::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->get();

        // If a specific league is selected, filter to it; otherwise show first active league
        $selectedLeagueId = $request->query('league');

        if ($selectedLeagueId) {
            $activeLeagues = League::where('id', $selectedLeagueId)
                ->where('is_active', true)
                ->with(['teams' => function($query) {
                    $query->orderByDesc('wins')->orderByDesc('ties');
                }, 'teams.players', 'segments.teams' => function($query) {
                    $query->orderByDesc('wins')->orderByDesc('ties');
                }, 'segments.teams.players', 'golfCourse'])
                ->get();
        } elseif ($allActiveLeagues->isNotEmpty()) {
            $activeLeagues = League::where('id', $allActiveLeagues->first()->id)
                ->with(['teams' => function($query) {
                    $query->orderByDesc('wins')->orderByDesc('ties');
                }, 'teams.players', 'segments.teams' => function($query) {
                    $query->orderByDesc('wins')->orderByDesc('ties');
                }, 'segments.teams.players', 'golfCourse'])
                ->get();
            $selectedLeagueId = $allActiveLeagues->first()->id;
        } else {
            $activeLeagues = collect();
        }

        // Build player standings for each active league
        $playerStandings = [];
        $playerWeeks = []; // completed weeks per league for player standings week toggle
        foreach ($activeLeagues as $league) {
            // Build player-to-team map
            $playerTeamMap = [];
            foreach ($league->teams as $team) {
                foreach ($team->players as $player) {
                    $playerTeamMap[$player->id] = $team->name;
                }
            }

            // Get completed match IDs for this league
            $completedMatchIds = $league->matches()
                ->where('status', 'completed')
                ->pluck('id');

            if ($completedMatchIds->isEmpty()) {
                $playerStandings[$league->id] = collect();
                continue;
            }

            // Find the latest completed week number for this league
            $latestWeek = $league->matches()
                ->where('status', 'completed')
                ->max('week_number');

            // Get all completed week numbers for this league
            $playerCompletedWeeks = $league->matches()
                ->where('status', 'completed')
                ->pluck('week_number')
                ->unique()
                ->sort()
                ->values();

            // Get par 3 wins per player grouped by week
            $allPar3Wins = Par3Winner::where('league_id', $league->id)
                ->get()
                ->groupBy('week_number')
                ->map(function ($weekWins) {
                    return $weekWins->groupBy('player_id')->map->count()->toArray();
                })
                ->toArray();

            // Get total par 3 wins per player for the entire season
            $totalPar3WinCounts = Par3Winner::where('league_id', $league->id)
                ->get()
                ->groupBy('player_id')
                ->map->count()
                ->toArray();

            // Get segment week ranges for per-segment point calculations
            $segments = $league->segments->sortBy('display_order')->values();

            // Get league course info for calculating current CH from current HI
            $leagueCourseInfo = $league->golf_course_id
                ? \App\Models\CourseInfo::where('golf_course_id', $league->golf_course_id)
                    ->where('teebox', $league->default_teebox ?? 'White')
                    ->get()
                : collect();
            $leagueCourseHole1 = $leagueCourseInfo->where('hole_number', 1)->first();
            $leagueSlope = $leagueCourseHole1 ? (float) $leagueCourseHole1->slope : null;
            $leagueRating = $leagueCourseHole1 ? (float) $leagueCourseHole1->rating : null;
            $leaguePar = $leagueCourseInfo->sum('par');

            // Get player stats from match_players in completed matches
            $stats = MatchPlayer::whereIn('match_id', $completedMatchIds)
                ->with(['player.handicapHistory', 'scores', 'match.result'])
                ->get()
                ->groupBy('player_id')
                ->map(function ($entries) use ($playerTeamMap, $latestWeek, $allPar3Wins, $totalPar3WinCounts, $segments, $playerCompletedWeeks, $leagueSlope, $leagueRating, $leaguePar) {
                    $player = $entries->first()->player;
                    $matchesPlayed = $entries->count();

                    // Calculate total strokes per match (only matches with scores)
                    $matchTotals = $entries->map(function ($mp) {
                        $total = $mp->scores->sum('strokes');
                        return $total > 0 ? $total : null;
                    })->filter();

                    $avgScore = $matchTotals->count() > 0
                        ? round($matchTotals->avg(), 1)
                        : null;
                    $lowRound = $matchTotals->count() > 0
                        ? $matchTotals->min()
                        : null;

                    $handicap = $player->currentHandicap();
                    $currentHi = $handicap ? (float)$handicap->handicap_index : null;

                    // Calculate current CH from current HI and league course data
                    $currentCh = null;
                    if ($currentHi !== null && $leagueSlope !== null) {
                        $currentCh = (int) round(($currentHi * $leagueSlope / 113) + ($leagueRating - $leaguePar));
                    }

                    // Build per-week data for all completed weeks
                    $weeklyData = [];
                    foreach ($playerCompletedWeeks as $wk) {
                        $weekEntries = $entries->filter(fn($mp) => ($mp->match->week_number ?? 0) == $wk);
                        $wkGross = null;
                        $wkPoints = null;
                        $wkHi = null;
                        $wkCh = null;
                        foreach ($weekEntries as $mp) {
                            $gross = $mp->scores->sum('strokes');
                            if ($gross > 0) {
                                $wkGross = ($wkGross ?? 0) + $gross;
                            }
                            if ($mp->match->result) {
                                $isHome = $mp->position_in_pairing <= 2;
                                $pts = $isHome
                                    ? ($mp->match->result->team_points_home ?? 0)
                                    : ($mp->match->result->team_points_away ?? 0);
                                $wkPoints = ($wkPoints ?? 0) + $pts;
                            }
                            $wkHi = $mp->handicap_index;
                            $wkCh = $mp->course_handicap;
                        }
                        $weeklyData[$wk] = [
                            'hi' => $wkHi !== null ? number_format((float)$wkHi, 1) : null,
                            'ch' => $wkCh !== null ? (string)$wkCh : null,
                            'par3' => $allPar3Wins[$wk][$player->id] ?? 0,
                            'gross' => $wkGross,
                            'points' => $wkPoints !== null ? number_format((float)$wkPoints, 2) : null,
                        ];
                    }

                    // Season W-L-T and total points per player
                    $wins = 0; $losses = 0; $ties = 0;
                    $totalSeasonPoints = 0;
                    $segmentPoints = [];
                    foreach ($segments as $seg) {
                        $segmentPoints[$seg->id] = 0;
                    }

                    foreach ($entries as $mp) {
                        if (!$mp->match->result) continue;
                        $isHome = $mp->position_in_pairing <= 2;
                        $result = $mp->match->result;

                        // Points
                        $pts = $isHome
                            ? ($result->team_points_home ?? 0)
                            : ($result->team_points_away ?? 0);
                        $totalSeasonPoints += $pts;

                        // Per-segment points
                        $weekNum = $mp->match->week_number;
                        foreach ($segments as $seg) {
                            if ($weekNum >= $seg->start_week && $weekNum <= $seg->end_week) {
                                $segmentPoints[$seg->id] += $pts;
                            }
                        }

                        // W-L-T
                        if ($result->winning_team_id === null) {
                            $ties++;
                        } else {
                            $playerWon = ($isHome && $result->winning_team_id == $mp->match->home_team_id)
                                || (!$isHome && $result->winning_team_id == $mp->match->away_team_id);
                            if ($playerWon) {
                                $wins++;
                            } else {
                                $losses++;
                            }
                        }
                    }

                    $totalMatches = $wins + $losses + $ties;
                    $winPct = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : null;

                    return [
                        'player' => $player,
                        'team_name' => $playerTeamMap[$player->id] ?? '-',
                        'current_hi' => $currentHi !== null ? number_format($currentHi, 1) : null,
                        'current_ch' => $currentCh !== null ? (string)$currentCh : null,
                        'matches_played' => $matchesPlayed,
                        'avg_score' => $avgScore,
                        'low_round' => $lowRound,
                        'weekly_data' => $weeklyData,
                        'total_par3' => $totalPar3WinCounts[$player->id] ?? 0,
                        'season_wins' => $wins,
                        'season_losses' => $losses,
                        'season_ties' => $ties,
                        'win_pct' => $winPct,
                        'segment_points' => $segmentPoints,
                        'total_season_points' => $totalSeasonPoints,
                    ];
                })
                ->sortByDesc('total_season_points')
                ->values();

            // Calculate points rank and points back from leader
            $maxPoints = $stats->max('total_season_points') ?? 0;
            $pointsSorted = $stats->sortByDesc('total_season_points')->values();
            $pointsRankMap = [];
            foreach ($pointsSorted as $ri => $s) {
                $pointsRankMap[$s['player']->id] = $ri + 1;
            }
            $stats = $stats->map(function ($s) use ($maxPoints, $pointsRankMap) {
                $s['points_rank'] = $pointsRankMap[$s['player']->id] ?? null;
                $s['points_back'] = $maxPoints - $s['total_season_points'];
                return $s;
            });

            $playerStandings[$league->id] = $stats;
            $playerWeeks[$league->id] = $playerCompletedWeeks;
        }

        // Build weekly team scores for each active league
        $weeklyTeamScores = [];
        $completedWeeks = [];
        $segmentStandings = []; // per-segment team standings keyed by [league_id][segment_id]
        $segmentWeeklyScores = []; // per-segment weekly scores
        $segmentCompletedWeeks = []; // per-segment completed weeks
        foreach ($activeLeagues as $league) {
            $completedMatches = $league->matches()
                ->where('status', 'completed')
                ->with(['result', 'matchPlayers'])
                ->orderBy('week_number')
                ->get();

            $weeks = $completedMatches->pluck('week_number')->unique()->sort()->values();
            $completedWeeks[$league->id] = $weeks;

            // Build per-segment standings (always, even without completed matches)
            if ($league->segments->isNotEmpty()) {
                $segmentStandings[$league->id] = [];
                $segmentWeeklyScores[$league->id] = [];
                $segmentCompletedWeeks[$league->id] = [];

                foreach ($league->segments as $segment) {
                    $segmentStandings[$league->id][$segment->id] = $segment->teams;
                    $segmentWeeklyScores[$league->id][$segment->id] = [];
                    $segmentCompletedWeeks[$league->id][$segment->id] = collect();
                }
            }

            if ($weeks->isEmpty()) {
                $weeklyTeamScores[$league->id] = [];
                continue;
            }

            // Build player-to-team_id map (non-segment teams only)
            $playerTeamIdMap = [];
            foreach ($league->teams->whereNull('league_segment_id') as $team) {
                foreach ($team->players as $player) {
                    $playerTeamIdMap[$player->id] = $team->id;
                }
            }

            $scores = [];
            foreach ($completedMatches as $match) {
                if (!$match->result) continue;

                if ($match->home_team_id && $match->away_team_id) {
                    $homeTeamId = $match->home_team_id;
                    $awayTeamId = $match->away_team_id;
                } else {
                    $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                    $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                    $firstHome = $homePlayers->first();
                    $firstAway = $awayPlayers->first();
                    $homeTeamId = $firstHome ? ($playerTeamIdMap[$firstHome->player_id] ?? null) : null;
                    $awayTeamId = $firstAway ? ($playerTeamIdMap[$firstAway->player_id] ?? null) : null;
                }

                $week = $match->week_number;
                if ($homeTeamId) {
                    if (!isset($scores[$homeTeamId][$week])) $scores[$homeTeamId][$week] = 0;
                    $scores[$homeTeamId][$week] += $match->result->team_points_home ?? 0;
                }
                if ($awayTeamId) {
                    if (!isset($scores[$awayTeamId][$week])) $scores[$awayTeamId][$week] = 0;
                    $scores[$awayTeamId][$week] += $match->result->team_points_away ?? 0;
                }
            }

            $weeklyTeamScores[$league->id] = $scores;

            // Build per-segment weekly scores from completed matches
            if ($league->segments->isNotEmpty()) {
                foreach ($league->segments as $segment) {
                    // Build player-to-team_id map for this segment
                    $segPlayerTeamIdMap = [];
                    foreach ($segment->teams as $team) {
                        foreach ($team->players as $player) {
                            $segPlayerTeamIdMap[$player->id] = $team->id;
                        }
                    }

                    // Filter completed matches to this segment's week range
                    $segMatches = $completedMatches->filter(function ($m) use ($segment) {
                        return $m->week_number >= $segment->start_week && $m->week_number <= $segment->end_week;
                    });

                    $segWeeks = $segMatches->pluck('week_number')->unique()->sort()->values();
                    $segmentCompletedWeeks[$league->id][$segment->id] = $segWeeks;

                    $segScores = [];
                    foreach ($segMatches as $match) {
                        if (!$match->result) continue;

                        if ($match->home_team_id && $match->away_team_id) {
                            $homeTeamId = $match->home_team_id;
                            $awayTeamId = $match->away_team_id;
                        } else {
                            $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                            $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                            $firstHome = $homePlayers->first();
                            $firstAway = $awayPlayers->first();
                            $homeTeamId = $firstHome ? ($segPlayerTeamIdMap[$firstHome->player_id] ?? null) : null;
                            $awayTeamId = $firstAway ? ($segPlayerTeamIdMap[$firstAway->player_id] ?? null) : null;
                        }

                        $week = $match->week_number;
                        if ($homeTeamId) {
                            if (!isset($segScores[$homeTeamId][$week])) $segScores[$homeTeamId][$week] = 0;
                            $segScores[$homeTeamId][$week] += $match->result->team_points_home ?? 0;
                        }
                        if ($awayTeamId) {
                            if (!isset($segScores[$awayTeamId][$week])) $segScores[$awayTeamId][$week] = 0;
                            $segScores[$awayTeamId][$week] += $match->result->team_points_away ?? 0;
                        }
                    }

                    $segmentWeeklyScores[$league->id][$segment->id] = $segScores;
                }
            }
        }

        // Get recent completed matches (last 2 weeks) for selected league
        $twoWeeksAgo = Carbon::now()->subWeeks(2);
        $recentMatchesQuery = LeagueMatch::with([
                'homeTeam', 'awayTeam', 'league.teams.players',
                'golfCourse', 'result.winningTeam', 'matchPlayers.player'
            ])
            ->where('status', 'completed')
            ->where('match_date', '>=', $twoWeeksAgo);
        if ($selectedLeagueId) {
            $recentMatchesQuery->where('league_id', $selectedLeagueId);
        }
        $recentMatches = $recentMatchesQuery->orderBy('match_date', 'desc')
            ->take(20)
            ->get();

        // Get upcoming matches (next 2 weeks) for selected league
        $twoWeeksLater = Carbon::now()->addWeeks(2);
        $upcomingMatchesQuery = LeagueMatch::with([
                'homeTeam', 'awayTeam', 'league.teams.players',
                'golfCourse', 'matchPlayers.player'
            ])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where('match_date', '<=', $twoWeeksLater);
        if ($selectedLeagueId) {
            $upcomingMatchesQuery->where('league_id', $selectedLeagueId);
        }
        $upcomingMatches = $upcomingMatchesQuery->orderBy('match_date', 'asc')
            ->take(10)
            ->get();

        // Build team name maps for matches with null team IDs
        $matchTeamNames = [];
        foreach ($recentMatches->merge($upcomingMatches) as $match) {
            if ($match->homeTeam && $match->awayTeam) {
                $matchTeamNames[$match->id] = [
                    'home' => $match->homeTeam->name,
                    'away' => $match->awayTeam->name,
                ];
            } else {
                $playerTeamMap = [];
                if ($match->league && $match->league->teams) {
                    foreach ($match->league->teams as $team) {
                        foreach ($team->players as $player) {
                            $playerTeamMap[$player->id] = $team->name;
                        }
                    }
                }
                $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                $firstHome = $homePlayers->first();
                $firstAway = $awayPlayers->first();
                $matchTeamNames[$match->id] = [
                    'home' => $firstHome ? ($playerTeamMap[$firstHome->player_id] ?? 'Home Side') : 'Home Side',
                    'away' => $firstAway ? ($playerTeamMap[$firstAway->player_id] ?? 'Away Side') : 'Away Side',
                ];
            }
        }

        // Load par 3 winners for selected league
        $par3Winners = collect();
        if ($selectedLeagueId) {
            $par3Winners = Par3Winner::where('league_id', $selectedLeagueId)
                ->with('player')
                ->orderBy('week_number')
                ->orderBy('hole_number')
                ->get();
        }

        // Get current (most recently completed) week's match results for selected league
        $currentWeekMatches = collect();
        $currentWeekNumber = null;
        $currentWeekScorecardData = [];
        if ($selectedLeagueId) {
            $currentWeekNumber = LeagueMatch::where('league_id', $selectedLeagueId)
                ->where('status', 'completed')
                ->max('week_number');

            if ($currentWeekNumber) {
                $currentWeekMatches = LeagueMatch::with([
                        'homeTeam', 'awayTeam', 'league.teams.players',
                        'golfCourse.courseInfo', 'result.winningTeam',
                        'matchPlayers.player.handicapHistory',
                        'matchPlayers.substitutePlayer.handicapHistory',
                        'matchPlayers.scores'
                    ])
                    ->where('league_id', $selectedLeagueId)
                    ->where('week_number', $currentWeekNumber)
                    ->where('status', 'completed')
                    ->orderBy('tee_time')
                    ->get();

                // Add team names for current week matches
                foreach ($currentWeekMatches as $match) {
                    if (isset($matchTeamNames[$match->id])) continue;
                    if ($match->homeTeam && $match->awayTeam) {
                        $matchTeamNames[$match->id] = [
                            'home' => $match->homeTeam->name,
                            'away' => $match->awayTeam->name,
                        ];
                    } else {
                        $playerTeamMap = [];
                        if ($match->league && $match->league->teams) {
                            foreach ($match->league->teams as $team) {
                                foreach ($team->players as $player) {
                                    $playerTeamMap[$player->id] = $team->name;
                                }
                            }
                        }
                        $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                        $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                        $firstHome = $homePlayers->first();
                        $firstAway = $awayPlayers->first();
                        $matchTeamNames[$match->id] = [
                            'home' => $firstHome ? ($playerTeamMap[$firstHome->player_id] ?? 'Home Side') : 'Home Side',
                            'away' => $firstAway ? ($playerTeamMap[$firstAway->player_id] ?? 'Away Side') : 'Away Side',
                        ];
                    }
                }

                // Build scorecard data for each current week match
                $currentWeekScorecardData = [];
                foreach ($currentWeekMatches as $cwMatch) {
                    $holeRange = $cwMatch->holes === 'back_9' ? [10, 18] : [1, 9];

                    $allCourseInfoForMatch = $cwMatch->golfCourse->courseInfo
                        ->where('teebox', $cwMatch->teebox)
                        ->sortBy('hole_number')
                        ->values();
                    $courseInfo = $allCourseInfoForMatch->whereBetween('hole_number', $holeRange)->values();

                    // Get slope/rating from hole 1 data
                    $courseInfoHole1 = $cwMatch->golfCourse->courseInfo
                        ->where('teebox', $cwMatch->teebox)
                        ->where('hole_number', 1)
                        ->first();

                    $allCourseInfoForMatch = $cwMatch->golfCourse->courseInfo
                        ->where('teebox', $cwMatch->teebox);
                    $par18 = $allCourseInfoForMatch->sum('par');

                    $slope18 = $courseInfoHole1 ? (float) $courseInfoHole1->slope : null;
                    $rating18 = $courseInfoHole1 ? (float) $courseInfoHole1->rating : null;

                    $playerHandicaps = [];
                    foreach ($cwMatch->matchPlayers as $mp) {
                        $hi = (float) $mp->handicap_index;
                        $ch18 = null;
                        $ch9 = null;
                        if ($slope18 !== null) {
                            $ch18 = round(($hi * $slope18 / 113) + ($rating18 - $par18));
                            $ch9 = round($ch18 / 2);
                        }
                        $playerHandicaps[$mp->id] = ['ch18' => $ch18, 'ch9' => $ch9];
                    }

                    $currentWeekScorecardData[$cwMatch->id] = [
                        'courseInfo' => $courseInfo,
                        'allCourseInfo' => $allCourseInfoForMatch,
                        'holeRange' => $holeRange,
                        'playerHandicaps' => $playerHandicaps,
                    ];
                }

                // Calculate per-hole match results for each match
                foreach ($currentWeekMatches as $cwMatch) {
                    $holeRange = $cwMatch->holes === 'back_9' ? [10, 18] : [1, 9];
                    $useGross = ($cwMatch->score_mode === 'gross');
                    $scoreField = $useGross ? 'strokes' : 'net_score';

                    if ($cwMatch->home_team_id) {
                        $homePlayers = $cwMatch->matchPlayers->where('team_id', $cwMatch->home_team_id)->sortBy('position_in_pairing')->values();
                        $awayPlayers = $cwMatch->matchPlayers->where('team_id', $cwMatch->away_team_id)->sortBy('position_in_pairing')->values();
                    } else {
                        $homePlayers = $cwMatch->matchPlayers->where('position_in_pairing', '<=', 2)->sortBy('position_in_pairing')->values();
                        $awayPlayers = $cwMatch->matchPlayers->where('position_in_pairing', '>', 2)->sortBy('position_in_pairing')->values();
                    }

                    if ($cwMatch->scoring_type === 'individual_match_play') {
                        $playerResults = [];
                        $pairs = min($homePlayers->count(), $awayPlayers->count());

                        for ($p = 0; $p < $pairs; $p++) {
                            $hp = $homePlayers[$p];
                            $ap = $awayPlayers[$p];
                            $hPoints = 0;
                            $aPoints = 0;
                            $hpHoles = [];
                            $apHoles = [];

                            for ($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++) {
                                $hScore = $hp->scores->where('hole_number', $hole)->first();
                                $aScore = $ap->scores->where('hole_number', $hole)->first();

                                if (!$hScore || !$aScore) {
                                    $hpHoles[$hole] = ['display' => '-', 'class' => ''];
                                    $apHoles[$hole] = ['display' => '-', 'class' => ''];
                                    continue;
                                }

                                $hVal = $hScore->{$scoreField};
                                $aVal = $aScore->{$scoreField};

                                if ($hVal < $aVal) {
                                    $hpHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                                    $apHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                                    $hPoints += 1;
                                } elseif ($aVal < $hVal) {
                                    $hpHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                                    $apHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                                    $aPoints += 1;
                                } else {
                                    $hpHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                                    $apHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                                    $hPoints += 0.5;
                                    $aPoints += 0.5;
                                }
                            }

                            $playerResults[$hp->id] = ['holes' => $hpHoles, 'total' => $hPoints];
                            $playerResults[$ap->id] = ['holes' => $apHoles, 'total' => $aPoints];
                        }

                        $currentWeekScorecardData[$cwMatch->id]['holeResults'] = [
                            'type' => 'individual',
                            'playerResults' => $playerResults,
                        ];
                    } else {
                        // Team-based: best_ball or team_2ball
                        $homeHoles = [];
                        $awayHoles = [];
                        $homeTotal = 0;
                        $awayTotal = 0;

                        for ($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++) {
                            $homeScores = $homePlayers->map(function ($mp) use ($hole, $scoreField) {
                                $score = $mp->scores->where('hole_number', $hole)->first();
                                return $score ? $score->{$scoreField} : null;
                            })->filter(fn($s) => $s !== null);

                            $awayScores = $awayPlayers->map(function ($mp) use ($hole, $scoreField) {
                                $score = $mp->scores->where('hole_number', $hole)->first();
                                return $score ? $score->{$scoreField} : null;
                            })->filter(fn($s) => $s !== null);

                            if ($homeScores->isEmpty() || $awayScores->isEmpty()) {
                                $homeHoles[$hole] = ['display' => '-', 'class' => ''];
                                $awayHoles[$hole] = ['display' => '-', 'class' => ''];
                                continue;
                            }

                            if ($cwMatch->scoring_type === 'team_2ball_match_play') {
                                $homeVal = $homeScores->sum();
                                $awayVal = $awayScores->sum();
                            } else {
                                $homeVal = $homeScores->min();
                                $awayVal = $awayScores->min();
                            }

                            if ($homeVal < $awayVal) {
                                $homeHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                                $awayHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                                $homeTotal += 1;
                            } elseif ($awayVal < $homeVal) {
                                $homeHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                                $awayHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                                $awayTotal += 1;
                            } else {
                                $homeHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                                $awayHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                                $homeTotal += 0.5;
                                $awayTotal += 0.5;
                            }
                        }

                        $currentWeekScorecardData[$cwMatch->id]['holeResults'] = [
                            'type' => 'team',
                            'homeResults' => ['holes' => $homeHoles, 'total' => $homeTotal],
                            'awayResults' => ['holes' => $awayHoles, 'total' => $awayTotal],
                        ];
                    }
                }
            }
        }

        // Pass the list of all completed week numbers for the selected league (for week results navigation)
        $allCompletedWeeks = [];
        if ($selectedLeagueId) {
            $allCompletedWeeks = LeagueMatch::where('league_id', $selectedLeagueId)
                ->where('status', 'completed')
                ->pluck('week_number')
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        }

        return view('home', compact('activeLeagues', 'allActiveLeagues', 'selectedLeagueId', 'playerStandings', 'playerWeeks', 'weeklyTeamScores', 'completedWeeks', 'segmentStandings', 'segmentWeeklyScores', 'segmentCompletedWeeks', 'recentMatches', 'upcomingMatches', 'matchTeamNames', 'par3Winners', 'currentWeekMatches', 'currentWeekNumber', 'currentWeekScorecardData', 'allCompletedWeeks'));
    }

    /**
     * Return the week results partial HTML for AJAX navigation.
     */
    public function weekResultsPartial($leagueId, $weekNumber)
    {
        $weekMatches = LeagueMatch::with([
                'homeTeam', 'awayTeam', 'league.teams.players',
                'golfCourse.courseInfo', 'result.winningTeam',
                'matchPlayers.player.handicapHistory',
                'matchPlayers.substitutePlayer.handicapHistory',
                'matchPlayers.scores'
            ])
            ->where('league_id', $leagueId)
            ->where('week_number', $weekNumber)
            ->where('status', 'completed')
            ->orderBy('tee_time')
            ->get();

        // Build team name maps
        $matchTeamNames = [];
        foreach ($weekMatches as $match) {
            if ($match->homeTeam && $match->awayTeam) {
                $matchTeamNames[$match->id] = [
                    'home' => $match->homeTeam->name,
                    'away' => $match->awayTeam->name,
                ];
            } else {
                $playerTeamMap = [];
                if ($match->league && $match->league->teams) {
                    foreach ($match->league->teams as $team) {
                        foreach ($team->players as $player) {
                            $playerTeamMap[$player->id] = $team->name;
                        }
                    }
                }
                $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                $firstHome = $homePlayers->first();
                $firstAway = $awayPlayers->first();
                $matchTeamNames[$match->id] = [
                    'home' => $firstHome ? ($playerTeamMap[$firstHome->player_id] ?? 'Home Side') : 'Home Side',
                    'away' => $firstAway ? ($playerTeamMap[$firstAway->player_id] ?? 'Away Side') : 'Away Side',
                ];
            }
        }

        // Build scorecard data and hole results
        $scorecardData = [];
        foreach ($weekMatches as $match) {
            $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];

            $allCourseInfoForMatch = $match->golfCourse->courseInfo
                ->where('teebox', $match->teebox)
                ->sortBy('hole_number')
                ->values();
            $courseInfo = $allCourseInfoForMatch->whereBetween('hole_number', $holeRange)->values();

            $courseInfoHole1 = $match->golfCourse->courseInfo
                ->where('teebox', $match->teebox)
                ->where('hole_number', 1)
                ->first();

            $par18 = $allCourseInfoForMatch->sum('par');

            $slope18 = $courseInfoHole1 ? (float) $courseInfoHole1->slope : null;
            $rating18 = $courseInfoHole1 ? (float) $courseInfoHole1->rating : null;

            $playerHandicaps = [];
            foreach ($match->matchPlayers as $mp) {
                $hi = (float) $mp->handicap_index;
                $ch18 = null;
                $ch9 = null;
                if ($slope18 !== null) {
                    $ch18 = round(($hi * $slope18 / 113) + ($rating18 - $par18));
                    $ch9 = round($ch18 / 2);
                }
                $playerHandicaps[$mp->id] = ['ch18' => $ch18, 'ch9' => $ch9];
            }

            $scorecardData[$match->id] = [
                'courseInfo' => $courseInfo,
                'allCourseInfo' => $allCourseInfoForMatch,
                'holeRange' => $holeRange,
                'playerHandicaps' => $playerHandicaps,
            ];

            // Calculate per-hole match results
            // Scramble always uses gross scores (no individual handicap adjustments)
            $useGross = ($match->score_mode === 'gross' || $match->scoring_type === 'scramble');
            $scoreField = $useGross ? 'strokes' : 'net_score';

            if ($match->home_team_id) {
                $homePlayers = $match->matchPlayers->where('team_id', $match->home_team_id)->sortBy('position_in_pairing')->values();
                $awayPlayers = $match->matchPlayers->where('team_id', $match->away_team_id)->sortBy('position_in_pairing')->values();
            } else {
                $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2)->sortBy('position_in_pairing')->values();
                $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2)->sortBy('position_in_pairing')->values();
            }

            if ($match->scoring_type === 'individual_match_play') {
                $playerResults = [];
                $pairs = min($homePlayers->count(), $awayPlayers->count());

                for ($p = 0; $p < $pairs; $p++) {
                    $hp = $homePlayers[$p];
                    $ap = $awayPlayers[$p];
                    $hPoints = 0;
                    $aPoints = 0;
                    $hpHoles = [];
                    $apHoles = [];

                    for ($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++) {
                        $hScore = $hp->scores->where('hole_number', $hole)->first();
                        $aScore = $ap->scores->where('hole_number', $hole)->first();

                        if (!$hScore || !$aScore) {
                            $hpHoles[$hole] = ['display' => '-', 'class' => ''];
                            $apHoles[$hole] = ['display' => '-', 'class' => ''];
                            continue;
                        }

                        $hVal = $hScore->{$scoreField};
                        $aVal = $aScore->{$scoreField};

                        if ($hVal < $aVal) {
                            $hpHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                            $apHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                            $hPoints += 1;
                        } elseif ($aVal < $hVal) {
                            $hpHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                            $apHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                            $aPoints += 1;
                        } else {
                            $hpHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                            $apHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                            $hPoints += 0.5;
                            $aPoints += 0.5;
                        }
                    }

                    $playerResults[$hp->id] = ['holes' => $hpHoles, 'total' => $hPoints];
                    $playerResults[$ap->id] = ['holes' => $apHoles, 'total' => $aPoints];
                }

                $scorecardData[$match->id]['holeResults'] = [
                    'type' => 'individual',
                    'playerResults' => $playerResults,
                ];
            } else {
                $homeHoles = [];
                $awayHoles = [];
                $homeTotal = 0;
                $awayTotal = 0;

                for ($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++) {
                    $homeScores = $homePlayers->map(function ($mp) use ($hole, $scoreField) {
                        $score = $mp->scores->where('hole_number', $hole)->first();
                        return $score ? $score->{$scoreField} : null;
                    })->filter(fn($s) => $s !== null);

                    $awayScores = $awayPlayers->map(function ($mp) use ($hole, $scoreField) {
                        $score = $mp->scores->where('hole_number', $hole)->first();
                        return $score ? $score->{$scoreField} : null;
                    })->filter(fn($s) => $s !== null);

                    if ($homeScores->isEmpty() || $awayScores->isEmpty()) {
                        $homeHoles[$hole] = ['display' => '-', 'class' => ''];
                        $awayHoles[$hole] = ['display' => '-', 'class' => ''];
                        continue;
                    }

                    if ($match->scoring_type === 'team_2ball_match_play') {
                        $homeVal = $homeScores->sum();
                        $awayVal = $awayScores->sum();
                    } else {
                        $homeVal = $homeScores->min();
                        $awayVal = $awayScores->min();
                    }

                    if ($homeVal < $awayVal) {
                        $homeHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                        $awayHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                        $homeTotal += 1;
                    } elseif ($awayVal < $homeVal) {
                        $homeHoles[$hole] = ['display' => '0', 'class' => 'sc-result-away'];
                        $awayHoles[$hole] = ['display' => '1', 'class' => 'sc-result-home'];
                        $awayTotal += 1;
                    } else {
                        $homeHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                        $awayHoles[$hole] = ['display' => '½', 'class' => 'sc-result-tie'];
                        $homeTotal += 0.5;
                        $awayTotal += 0.5;
                    }
                }

                $scorecardData[$match->id]['holeResults'] = [
                    'type' => 'team',
                    'homeResults' => ['holes' => $homeHoles, 'total' => $homeTotal],
                    'awayResults' => ['holes' => $awayHoles, 'total' => $awayTotal],
                ];
            }
        }

        return view('leagues.week-results-partial', compact('weekMatches', 'matchTeamNames', 'scorecardData'));
    }
}

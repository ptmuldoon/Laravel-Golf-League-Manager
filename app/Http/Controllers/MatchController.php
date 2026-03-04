<?php

namespace App\Http\Controllers;

use App\Models\LeagueMatch;
use App\Models\League;
use App\Models\MatchPlayer;
use App\Models\MatchScore;
use App\Models\MatchResult;
use App\Models\GolfCourse;
use App\Models\CourseInfo;
use App\Models\Player;
use App\Models\Round;
use App\Models\Score;
use App\Models\ScoringSetting;
use App\Models\HandicapHistory;
use App\Services\HandicapCalculator;
use App\Services\MatchPlayCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    protected $calculator;

    public function __construct(MatchPlayCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Show form to create a new match
     */
    public function create($leagueId)
    {
        $league = League::with('teams', 'golfCourse.courseInfo', 'players')->findOrFail($leagueId);
        $courses = GolfCourse::with('courseInfo')->orderBy('name')->get();

        // Only show players assigned to this league
        $allPlayers = $league->players()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Get next week number
        $nextWeek = $league->matches()->max('week_number') + 1 ?? 1;
        $scoringTypes = ScoringSetting::scoringTypes();

        return view('matches.create', compact('league', 'courses', 'allPlayers', 'nextWeek', 'scoringTypes'));
    }

    /**
     * Store a newly created match
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'league_id' => 'required|exists:leagues,id',
            'week_number' => 'required|integer|min:1',
            'match_date' => 'required|date',
            'golf_course_id' => 'required|exists:golf_courses,id',
            'teebox' => 'required|string',
            'holes' => 'required|in:front_9,back_9',
            'scoring_type' => 'required|in:' . implode(',', array_keys(ScoringSetting::scoringTypes())),
            'score_mode' => 'required|in:gross,net',
            'home_team_id' => 'nullable|exists:teams,id',
            'away_team_id' => 'nullable|exists:teams,id|different:home_team_id',
            'home_players' => 'nullable|array|min:1',
            'home_players.*' => 'required|exists:players,id',
            'away_players' => 'nullable|array|min:1',
            'away_players.*' => 'required|exists:players,id',
        ]);

        // Get course info for handicap calculation (USGA formula needs slope, rating, and par)
        $courseInfo = DB::table('course_info')
            ->where('golf_course_id', $validated['golf_course_id'])
            ->where('teebox', $validated['teebox'])
            ->where('hole_number', 1)
            ->first();
        $totalPar = DB::table('course_info')
            ->where('golf_course_id', $validated['golf_course_id'])
            ->where('teebox', $validated['teebox'])
            ->sum('par');

        DB::transaction(function () use ($validated, $courseInfo, $totalPar, &$match) {
            // Create the match
            $match = LeagueMatch::create([
                'league_id' => $validated['league_id'],
                'week_number' => $validated['week_number'],
                'match_date' => $validated['match_date'],
                'golf_course_id' => $validated['golf_course_id'],
                'teebox' => $validated['teebox'],
                'holes' => $validated['holes'],
                'scoring_type' => $validated['scoring_type'],
                'score_mode' => $validated['score_mode'],
                'home_team_id' => $validated['home_team_id'] ?? null,
                'away_team_id' => $validated['away_team_id'] ?? null,
                'status' => isset($validated['home_players']) ? 'in_progress' : 'scheduled',
            ]);

            // If players were provided, assign them to the match
            if (isset($validated['home_players']) && isset($validated['away_players'])) {
                $matchDate = $match->match_date;

                // Add home players
                foreach ($validated['home_players'] as $position => $playerId) {
                    $player = Player::findOrFail($playerId);
                    $matchDateHandicap = $player->handicapAsOf($matchDate);
                    $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($player->currentHandicap() ? $player->currentHandicap()->handicap_index : 0);
                    $courseHandicap = $this->calculator->calculateCourseHandicap(
                        $handicapIndex,
                        $courseInfo->slope,
                        $courseInfo->rating,
                        $totalPar
                    );

                    MatchPlayer::create([
                        'match_id' => $match->id,
                        'team_id' => $validated['home_team_id'] ?? null,
                        'player_id' => $playerId,
                        'handicap_index' => $handicapIndex,
                        'course_handicap' => $courseHandicap,
                        'position_in_pairing' => $position + 1,
                    ]);
                }

                // Add away players
                foreach ($validated['away_players'] as $position => $playerId) {
                    $player = Player::findOrFail($playerId);
                    $matchDateHandicap = $player->handicapAsOf($matchDate);
                    $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($player->currentHandicap() ? $player->currentHandicap()->handicap_index : 0);
                    $courseHandicap = $this->calculator->calculateCourseHandicap(
                        $handicapIndex,
                        $courseInfo->slope,
                        $courseInfo->rating,
                        $totalPar
                    );

                    MatchPlayer::create([
                        'match_id' => $match->id,
                        'team_id' => $validated['away_team_id'] ?? null,
                        'player_id' => $playerId,
                        'handicap_index' => $handicapIndex,
                        'course_handicap' => $courseHandicap,
                        'position_in_pairing' => $position + 1,
                    ]);
                }
            }
        });

        if (isset($validated['home_players'])) {
            return redirect()->route('admin.matches.scoreEntry', $match->id)
                ->with('success', 'Match scheduled and players assigned successfully!');
        }

        return redirect()->route('admin.leagues.show', $validated['league_id'])
            ->with('success', 'Match scheduled successfully!');
    }

    /**
     * Show match details and score entry form
     */
    public function show($id)
    {
        $match = LeagueMatch::with([
            'league.teams.players',
            'golfCourse.courseInfo',
            'homeTeam',
            'awayTeam',
            'matchPlayers.player.handicapHistory',
            'matchPlayers.substitutePlayer.handicapHistory',
            'matchPlayers.scores',
            'result.winningTeam'
        ])->findOrFail($id);

        // Determine hole range based on match setting
        $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];

        // Get course info for this teebox (played nine + all 18 for stroke allocation)
        $allCourseInfo = $match->golfCourse->courseInfo()
            ->where('teebox', $match->teebox)
            ->orderBy('hole_number')
            ->get();
        $courseInfo = $allCourseInfo->whereBetween('hole_number', $holeRange)->values();

        $scoringTypes = ScoringSetting::scoringTypes();

        // Split players into home/away using position_in_pairing (works for both auto-scheduled and manual)
        if ($match->home_team_id) {
            $homePlayers = $match->matchPlayers->where('team_id', $match->home_team_id);
            $awayPlayers = $match->matchPlayers->where('team_id', $match->away_team_id);
        } else {
            $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
            $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
        }

        // Resolve team names
        $homeTeamName = $match->homeTeam->name ?? null;
        $awayTeamName = $match->awayTeam->name ?? null;

        if (!$homeTeamName || !$awayTeamName) {
            $playerTeamNames = [];
            if ($match->league && $match->league->teams) {
                foreach ($match->league->teams as $team) {
                    foreach ($team->players as $player) {
                        $playerTeamNames[$player->id] = $team->name;
                    }
                }
            }
            $firstHome = $homePlayers->first();
            $firstAway = $awayPlayers->first();
            $homeTeamName = $homeTeamName ?? ($firstHome ? ($playerTeamNames[$firstHome->player_id] ?? 'Home Side') : 'Home Side');
            $awayTeamName = $awayTeamName ?? ($firstAway ? ($playerTeamNames[$firstAway->player_id] ?? 'Away Side') : 'Away Side');
        }

        // Compute 18-hole and 9-hole course handicaps for each match player
        $courseInfoHole1 = $match->golfCourse->courseInfo()
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();

        $allCourseInfo = $match->golfCourse->courseInfo()
            ->where('teebox', $match->teebox)
            ->get();
        $par18 = $allCourseInfo->sum('par');

        $slope18 = $courseInfoHole1 ? (float) $courseInfoHole1->slope : null;
        $rating18 = $courseInfoHole1 ? (float) $courseInfoHole1->rating : null;

        $playerHandicaps = [];
        foreach ($match->matchPlayers as $mp) {
            // Use substitute's handicap when present, otherwise original player's
            $activePlayer = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
            $matchDateHandicap = $activePlayer->handicapAsOf($match->match_date);
            $hi = $matchDateHandicap ? (float) $matchDateHandicap->handicap_index : (float) $mp->handicap_index;

            if ($matchDateHandicap && (float) $mp->handicap_index !== $hi) {
                $newCH = $slope18 ? round(($hi * $slope18 / 113) + ($rating18 - $par18)) : $mp->course_handicap;
                $mp->update(['handicap_index' => $hi, 'course_handicap' => $newCH]);
            }

            $ch18 = null;
            $ch9 = null;
            if ($slope18 !== null) {
                $ch18 = round(($hi * $slope18 / 113) + ($rating18 - $par18));
                $ch9 = round($ch18 / 2);
            }
            $playerHandicaps[$mp->id] = ['ch18' => $ch18, 'ch9' => $ch9];
        }

        // Compute strokes received per hole per player (for net scoring)
        // Always allocate using 18-hole CH across all 18 holes, then filter to played nine
        $strokesOnHoleMap = [];
        foreach ($match->matchPlayers as $mp) {
            $ch = isset($playerHandicaps[$mp->id]) ? (int) $playerHandicaps[$mp->id]['ch18'] : 0;
            $strokesOnHole = [];
            foreach ($allCourseInfo as $h) { $strokesOnHole[$h->hole_number] = 0; }
            $sorted = $allCourseInfo->sortBy('handicap')->pluck('hole_number')->values();
            $remaining = max(0, $ch);
            while ($remaining > 0) {
                foreach ($sorted as $hn) {
                    if ($remaining <= 0) break;
                    $strokesOnHole[$hn]++;
                    $remaining--;
                }
            }
            $strokesOnHoleMap[$mp->id] = $strokesOnHole;
        }

        // Compute hole-by-hole match results
        $holeResults = [];
        $homeWinsTotal = 0;
        $awayWinsTotal = 0;
        $tiesTotal = 0;
        $individualResults = []; // For individual_match_play: keyed by player_id

        $scoringType = $match->scoring_type;
        // Scramble always uses gross scores (no individual handicap adjustments)
        $scoreMode = ($scoringType === 'scramble') ? 'gross' : ($match->score_mode ?? 'net');

        for ($hole = $holeRange[0]; $hole <= $holeRange[1]; $hole++) {
            $homeScoresForHole = [];
            foreach ($homePlayers as $mp) {
                $score = $mp->scores->where('hole_number', $hole)->first();
                if (!$score) continue;
                $val = (int) $score->strokes;
                if ($scoreMode === 'net') {
                    $val -= ($strokesOnHoleMap[$mp->id][$hole] ?? 0);
                }
                $homeScoresForHole[$mp->id] = $val;
            }

            $awayScoresForHole = [];
            foreach ($awayPlayers as $mp) {
                $score = $mp->scores->where('hole_number', $hole)->first();
                if (!$score) continue;
                $val = (int) $score->strokes;
                if ($scoreMode === 'net') {
                    $val -= ($strokesOnHoleMap[$mp->id][$hole] ?? 0);
                }
                $awayScoresForHole[$mp->id] = $val;
            }

            if (empty($homeScoresForHole) || empty($awayScoresForHole)) {
                $holeResults[$hole] = 'none';
                continue;
            }

            if ($scoringType === 'individual_match_play') {
                $homePlayersList = $homePlayers->values();
                $awayPlayersList = $awayPlayers->values();
                $pairs = min($homePlayersList->count(), $awayPlayersList->count());
                for ($p = 0; $p < $pairs; $p++) {
                    $hpId = $homePlayersList[$p]->id;
                    $apId = $awayPlayersList[$p]->id;
                    $hScore = $homeScoresForHole[$hpId] ?? null;
                    $aScore = $awayScoresForHole[$apId] ?? null;
                    if ($hScore !== null && $aScore !== null) {
                        if ($hScore < $aScore) {
                            $individualResults[$hpId][$hole] = 'won';
                            $individualResults[$apId][$hole] = 'lost';
                        } elseif ($aScore < $hScore) {
                            $individualResults[$hpId][$hole] = 'lost';
                            $individualResults[$apId][$hole] = 'won';
                        } else {
                            $individualResults[$hpId][$hole] = 'tie';
                            $individualResults[$apId][$hole] = 'tie';
                        }
                    }
                }
                // For team-level row in individual, count pair wins
                $pairHomeWins = 0;
                $pairAwayWins = 0;
                for ($p = 0; $p < $pairs; $p++) {
                    $hpId = $homePlayersList[$p]->id;
                    $r = $individualResults[$hpId][$hole] ?? null;
                    if ($r === 'won') $pairHomeWins++;
                    elseif ($r === 'lost') $pairAwayWins++;
                }
                if ($pairHomeWins > $pairAwayWins) {
                    $holeResults[$hole] = 'home';
                    $homeWinsTotal++;
                } elseif ($pairAwayWins > $pairHomeWins) {
                    $holeResults[$hole] = 'away';
                    $awayWinsTotal++;
                } else {
                    $holeResults[$hole] = 'tie';
                    $tiesTotal++;
                }
            } else {
                if ($scoringType === 'best_ball_match_play') {
                    $homeVal = min($homeScoresForHole);
                    $awayVal = min($awayScoresForHole);
                } elseif ($scoringType === 'team_2ball_match_play') {
                    $homeVal = array_sum($homeScoresForHole);
                    $awayVal = array_sum($awayScoresForHole);
                } else {
                    $homeVal = min($homeScoresForHole);
                    $awayVal = min($awayScoresForHole);
                }

                if ($homeVal < $awayVal) {
                    $holeResults[$hole] = 'home';
                    $homeWinsTotal++;
                } elseif ($awayVal < $homeVal) {
                    $holeResults[$hole] = 'away';
                    $awayWinsTotal++;
                } else {
                    $holeResults[$hole] = 'tie';
                    $tiesTotal++;
                }
            }
        }

        return view('matches.show', compact('match', 'courseInfo', 'allCourseInfo', 'holeRange', 'scoringTypes', 'homePlayers', 'awayPlayers', 'homeTeamName', 'awayTeamName', 'playerHandicaps', 'holeResults', 'homeWinsTotal', 'awayWinsTotal', 'tiesTotal', 'individualResults'));
    }

    /**
     * Show player assignment form
     */
    public function assignPlayers($matchId)
    {
        $match = LeagueMatch::with([
            'homeTeam.players.handicapHistory',
            'awayTeam.players.handicapHistory',
            'matchPlayers.player'
        ])->findOrFail($matchId);

        // Get course info for handicap calculation (USGA formula)
        $courseInfo = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();
        $totalPar = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->sum('par');

        return view('matches.assign-players', compact('match', 'courseInfo', 'totalPar'));
    }

    /**
     * Store player assignments for a match
     */
    public function storePlayers(Request $request, $matchId)
    {
        $validated = $request->validate([
            'home_players' => 'required|array|min:1',
            'home_players.*' => 'required|exists:players,id',
            'away_players' => 'required|array|min:1',
            'away_players.*' => 'required|exists:players,id',
        ]);

        $match = LeagueMatch::findOrFail($matchId);

        // Get course info for handicap calculation (USGA formula needs slope, rating, and par)
        $courseInfo = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();
        $totalPar = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->sum('par');

        DB::transaction(function () use ($match, $validated, $courseInfo, $totalPar) {
            // Clear existing players
            $match->matchPlayers()->delete();
            $matchDate = $match->match_date;

            // Add home team players
            foreach ($validated['home_players'] as $position => $playerId) {
                $player = Player::findOrFail($playerId);
                $matchDateHandicap = $player->handicapAsOf($matchDate);
                $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($player->currentHandicap() ? $player->currentHandicap()->handicap_index : 0);
                $courseHandicap = $this->calculator->calculateCourseHandicap(
                    $handicapIndex,
                    $courseInfo->slope,
                    $courseInfo->rating,
                    $totalPar
                );

                MatchPlayer::create([
                    'match_id' => $match->id,
                    'team_id' => $match->home_team_id,
                    'player_id' => $playerId,
                    'handicap_index' => $handicapIndex,
                    'course_handicap' => $courseHandicap,
                    'position_in_pairing' => $position + 1,
                ]);
            }

            // Add away team players
            foreach ($validated['away_players'] as $position => $playerId) {
                $player = Player::findOrFail($playerId);
                $matchDateHandicap = $player->handicapAsOf($matchDate);
                $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($player->currentHandicap() ? $player->currentHandicap()->handicap_index : 0);
                $courseHandicap = $this->calculator->calculateCourseHandicap(
                    $handicapIndex,
                    $courseInfo->slope,
                    $courseInfo->rating,
                    $totalPar
                );

                MatchPlayer::create([
                    'match_id' => $match->id,
                    'team_id' => $match->away_team_id,
                    'player_id' => $playerId,
                    'handicap_index' => $handicapIndex,
                    'course_handicap' => $courseHandicap,
                    'position_in_pairing' => $position + 1,
                ]);
            }

            $match->update(['status' => 'in_progress']);
        });

        return redirect()->route('admin.matches.scoreEntry', $matchId)
            ->with('success', 'Players assigned successfully! You can now enter scores.');
    }

    /**
     * Show score entry interface
     */
    public function scoreEntry($matchId)
    {
        $match = LeagueMatch::with([
            'league.teams.players',
            'matchPlayers.player.handicapHistory',
            'matchPlayers.substitutePlayer.handicapHistory',
            'matchPlayers.scores',
            'homeTeam',
            'awayTeam',
            'golfCourse.courseInfo'
        ])->findOrFail($matchId);

        // Determine hole range based on match setting
        $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];

        // Get course info (played nine + all 18 for stroke allocation)
        $allCourseInfo = $match->golfCourse->courseInfo()
            ->where('teebox', $match->teebox)
            ->orderBy('hole_number')
            ->get();
        $courseInfo = $allCourseInfo->whereBetween('hole_number', $holeRange)->values();

        // Split players into home/away using position_in_pairing (works for both auto-scheduled and manual)
        if ($match->home_team_id) {
            $homePlayers = $match->matchPlayers->where('team_id', $match->home_team_id);
            $awayPlayers = $match->matchPlayers->where('team_id', $match->away_team_id);
        } else {
            $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
            $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
        }

        // Resolve team names
        $homeTeamName = $match->homeTeam->name ?? null;
        $awayTeamName = $match->awayTeam->name ?? null;

        if (!$homeTeamName || !$awayTeamName) {
            $playerTeamNames = [];
            if ($match->league && $match->league->teams) {
                foreach ($match->league->teams as $team) {
                    foreach ($team->players as $player) {
                        $playerTeamNames[$player->id] = $team->name;
                    }
                }
            }
            $firstHome = $homePlayers->first();
            $firstAway = $awayPlayers->first();
            $homeTeamName = $homeTeamName ?? ($firstHome ? ($playerTeamNames[$firstHome->player_id] ?? 'Home Side') : 'Home Side');
            $awayTeamName = $awayTeamName ?? ($firstAway ? ($playerTeamNames[$firstAway->player_id] ?? 'Away Side') : 'Away Side');
        }

        // Compute 18-hole and 9-hole course handicaps for each match player
        $courseInfoHole1 = $match->golfCourse->courseInfo()
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();

        $allCourseInfo = $match->golfCourse->courseInfo()
            ->where('teebox', $match->teebox)
            ->get();
        $par18 = $allCourseInfo->sum('par');

        $slope18 = $courseInfoHole1 ? (float) $courseInfoHole1->slope : null;
        $rating18 = $courseInfoHole1 ? (float) $courseInfoHole1->rating : null;

        $playerHandicaps = [];
        foreach ($match->matchPlayers as $mp) {
            // Use substitute's handicap when present, otherwise original player's
            $activePlayer = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
            $matchDateHandicap = $activePlayer->handicapAsOf($match->match_date);
            $hi = $matchDateHandicap ? (float) $matchDateHandicap->handicap_index : (float) $mp->handicap_index;

            if ($matchDateHandicap && (float) $mp->handicap_index !== $hi) {
                $newCH = $slope18 ? round(($hi * $slope18 / 113) + ($rating18 - $par18)) : $mp->course_handicap;
                $mp->update(['handicap_index' => $hi, 'course_handicap' => $newCH]);
            }

            $ch18 = null;
            $ch9 = null;
            if ($slope18 !== null) {
                $ch18 = round(($hi * $slope18 / 113) + ($rating18 - $par18));
                $ch9 = round($ch18 / 2);
            }
            $playerHandicaps[$mp->id] = ['ch18' => $ch18, 'ch9' => $ch9];
        }

        return view('matches.score-entry', compact('match', 'courseInfo', 'allCourseInfo', 'holeRange', 'homePlayers', 'awayPlayers', 'homeTeamName', 'awayTeamName', 'playerHandicaps'));
    }

    /**
     * Store scores for a match
     */
    public function storeScores(Request $request, $matchId)
    {
        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.*' => 'required|integer|min:1|max:15', // scores[match_player_id][hole_number]
        ]);

        $match = LeagueMatch::with(['matchPlayers.player.handicapHistory', 'matchPlayers.substitutePlayer.handicapHistory', 'golfCourse.courseInfo'])->findOrFail($matchId);

        DB::transaction(function () use ($match, $validated) {
            // Refresh handicaps to match-date values before processing
            $courseInfoHole1 = $match->golfCourse->courseInfo()
                ->where('teebox', $match->teebox)
                ->where('hole_number', 1)
                ->first();
            $allCI = $match->golfCourse->courseInfo()
                ->where('teebox', $match->teebox)
                ->get();
            $par18 = $allCI->sum('par');
            $slope = $courseInfoHole1 ? (float) $courseInfoHole1->slope : null;
            $rating = $courseInfoHole1 ? (float) $courseInfoHole1->rating : null;

            foreach ($match->matchPlayers as $mp) {
                $activePlayer = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
                $matchDateHandicap = $activePlayer->handicapAsOf($match->match_date);
                if ($matchDateHandicap) {
                    $hi = (float) $matchDateHandicap->handicap_index;
                    $newCH = $slope ? round(($hi * $slope / 113) + ($rating - $par18)) : $mp->course_handicap;
                    $mp->update(['handicap_index' => $hi, 'course_handicap' => $newCH]);
                }
            }

            // Get course info for hole handicap rankings (all 18 holes for proper stroke allocation)
            $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];
            $allCourseInfoHoles = $match->golfCourse->courseInfo()
                ->where('teebox', $match->teebox)
                ->get()
                ->keyBy('hole_number');
            $totalHoles = 18;

            foreach ($validated['scores'] as $matchPlayerId => $holeScores) {
                $matchPlayer = MatchPlayer::findOrFail($matchPlayerId);
                $courseHandicap = (float) $matchPlayer->course_handicap;

                // Pre-compute strokes received on each hole using 18-hole CH across all 18 holes
                $ch = max(0, (int) round($courseHandicap));
                $strokesMap = [];
                foreach ($allCourseInfoHoles as $hn => $hi) {
                    $ranking = ($hi && $hi->handicap) ? (int) $hi->handicap : (int) $hn;
                    $base = $ch > 0 ? intdiv($ch, $totalHoles) : 0;
                    $remainder = $ch > 0 ? ($ch % $totalHoles) : 0;
                    $strokesMap[$hn] = $base + ($ranking <= $remainder ? 1 : 0);
                }

                foreach ($holeScores as $holeNumber => $strokes) {
                    $holeInfo = $allCourseInfoHoles->get((int) $holeNumber);
                    $par = $holeInfo ? (int) $holeInfo->par : 4;

                    // Strokes received on this hole (from 18-hole allocation)
                    $strokesReceived = $strokesMap[(int) $holeNumber] ?? 0;

                    // Adjusted Gross: capped at Net Double Bogey
                    $maxScore = $par + 2 + $strokesReceived;
                    $adjustedGross = min((int) $strokes, $maxScore);

                    // Net Score: gross minus strokes received
                    $netScore = (int) $strokes - $strokesReceived;

                    MatchScore::updateOrCreate(
                        [
                            'match_player_id' => $matchPlayerId,
                            'hole_number' => $holeNumber,
                        ],
                        [
                            'strokes' => $strokes,
                            'adjusted_gross' => $adjustedGross,
                            'net_score' => $netScore,
                        ]
                    );
                }
            }

            // Calculate and save match result
            $resultData = $this->calculator->calculateMatchResult($match);

            MatchResult::updateOrCreate(
                ['match_id' => $match->id],
                $resultData
            );

            // Update team records (only if teams are assigned)
            $homeTeam = $match->homeTeam;
            $awayTeam = $match->awayTeam;

            if ($homeTeam && $awayTeam) {
                if ($resultData['winning_team_id'] == $homeTeam->id) {
                    $homeTeam->increment('wins');
                    $awayTeam->increment('losses');
                } elseif ($resultData['winning_team_id'] == $awayTeam->id) {
                    $homeTeam->increment('losses');
                    $awayTeam->increment('wins');
                } else {
                    $homeTeam->increment('ties');
                    $awayTeam->increment('ties');
                }
            }

            $match->update(['status' => 'completed']);

            // Create Round + Score records for each player's score history
            $affectedPlayerIds = [];
            $holesPlayed = 9; // League matches are always 9 holes (front or back)

            foreach ($match->matchPlayers as $mp) {
                $activePlayer = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
                if (!$activePlayer) continue;

                $matchScores = $mp->scores()->get();
                if ($matchScores->isEmpty()) continue;

                // Delete existing round for this match_player (handles re-posting)
                $existingRound = Round::where('match_player_id', $mp->id)->first();
                if ($existingRound) {
                    Score::where('round_id', $existingRound->id)->delete();
                    $existingRound->delete();
                }

                $round = Round::create([
                    'player_id' => $activePlayer->id,
                    'match_player_id' => $mp->id,
                    'golf_course_id' => $match->golf_course_id,
                    'teebox' => $match->teebox,
                    'played_at' => $match->match_date->format('Y-m-d'),
                    'holes_played' => $holesPlayed,
                ]);

                foreach ($matchScores as $ms) {
                    Score::create([
                        'round_id' => $round->id,
                        'hole_number' => $ms->hole_number,
                        'strokes' => $ms->strokes,
                        'adjusted_gross' => $ms->adjusted_gross,
                        'net_score' => $ms->net_score,
                    ]);
                }

                $affectedPlayerIds[] = $activePlayer->id;
            }

            // Recalculate handicaps for all affected players
            $handicapCalculator = app(HandicapCalculator::class);
            $affectedPlayerIds = array_unique($affectedPlayerIds);

            foreach ($affectedPlayerIds as $playerId) {
                $player = Player::find($playerId);
                if (!$player) continue;

                $snapshots = $handicapCalculator->computeHistoricalHandicaps($player);
                if (empty($snapshots)) continue;

                // Clear existing handicap history and rebuild
                HandicapHistory::where('player_id', $playerId)->delete();

                $byDate = [];
                foreach ($snapshots as $snapshot) {
                    $byDate[$snapshot['calculation_date']] = $snapshot;
                }

                foreach ($byDate as $snapshot) {
                    HandicapHistory::create([
                        'player_id' => $snapshot['player_id'],
                        'calculation_date' => $snapshot['calculation_date'],
                        'handicap_index' => $snapshot['handicap_index'],
                        'rounds_used' => $snapshot['rounds_used'],
                        'score_differentials' => $snapshot['score_differentials'],
                    ]);
                }
            }
        });

        return redirect()->route('admin.matches.show', $matchId)
            ->with('success', 'Scores saved and match completed!');
    }
}

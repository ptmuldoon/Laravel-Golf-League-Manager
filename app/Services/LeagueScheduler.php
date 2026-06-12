<?php

namespace App\Services;

use App\Models\League;
use App\Models\LeagueSegment;
use App\Models\Player;
use App\Models\LeagueMatch;
use App\Models\MatchPlayer;
use Illuminate\Support\Facades\DB;

class LeagueScheduler
{
    protected $calculator;

    public function __construct(\App\Services\MatchPlayCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Generate a 16-week schedule of foursomes
     */
    public function generateSchedule(League $league, int $weeks = 16, ?LeagueSegment $segment = null)
    {
        // Get team assignments for team-based pairing (segment-scoped if applicable)
        $playerTeams = $segment
            ? $this->getSegmentPlayerTeamMap($segment)
            : $this->getPlayerTeamMap($league);

        // Only use players assigned to this league. When scheduling a specific
        // segment (season), restrict the pool to the players drafted onto that
        // segment's teams — otherwise every other season's players would be
        // scheduled into extra foursomes.
        $playersQuery = $league->players()
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($segment) {
            $playersQuery->whereIn('players.id', array_keys($playerTeams));
        }

        $players = $playersQuery->get();

        if ($players->count() < 4) {
            throw new \Exception('Need at least 4 players assigned to this league to create foursomes. Please add more players to the league first.');
        }

        $schedule = [];
        $playerIds = $players->pluck('id')->toArray();

        // Track player pairings to ensure variety
        $pairings = [];

        for ($week = 1; $week <= $weeks; $week++) {
            $weekGroups = $this->createWeekFoursomes($playerIds, $pairings, $week, $playerTeams);
            $schedule[$week] = $weekGroups;
        }

        return [
            'schedule' => $schedule,
            'players' => $players,
        ];
    }

    /**
     * Get a map of player IDs to their team IDs
     */
    protected function getPlayerTeamMap(League $league)
    {
        $playerTeams = [];

        foreach ($league->teams as $team) {
            foreach ($team->players as $player) {
                $playerTeams[$player->id] = $team->id;
            }
        }

        return $playerTeams;
    }

    protected function getSegmentPlayerTeamMap(LeagueSegment $segment)
    {
        $playerTeams = [];

        foreach ($segment->teams as $team) {
            foreach ($team->players as $player) {
                $playerTeams[$player->id] = $team->id;
            }
        }

        return $playerTeams;
    }

    /**
     * Create foursomes for a single week
     */
    protected function createWeekFoursomes(array $playerIds, array &$pairings, int $week, array $playerTeams = [])
    {
        // If we have team assignments, create proper 2v2 team foursomes
        if (!empty($playerTeams)) {
            return $this->createTeamBasedFoursomes($playerIds, $pairings, $week, $playerTeams);
        }

        $shuffled = $playerIds;

        // Shuffle with some intelligence to avoid repeat pairings
        if ($week > 1) {
            $shuffled = $this->intelligentShuffle($playerIds, $pairings);
        } else {
            shuffle($shuffled);
        }

        $foursomes = [];
        $groupNumber = 1;

        // Create groups of 4
        for ($i = 0; $i < count($shuffled); $i += 4) {
            $group = array_slice($shuffled, $i, 4);

            if (count($group) >= 2) { // At least 2 players needed
                $foursomes[] = [
                    'group_number' => $groupNumber++,
                    'players' => $group,
                ];

                // Track pairings
                $this->recordPairings($group, $pairings);
            }
        }

        return $foursomes;
    }

    /**
     * Create foursomes ensuring 2v2 team pairings.
     * Each foursome has 2 players from one team vs 2 from another.
     */
    protected function createTeamBasedFoursomes(array $playerIds, array &$pairings, int $week, array $playerTeams)
    {
        // Group players by team
        $byTeam = [];
        $noTeam = [];
        foreach ($playerIds as $pid) {
            if (isset($playerTeams[$pid])) {
                $byTeam[$playerTeams[$pid]][] = $pid;
            } else {
                $noTeam[] = $pid;
            }
        }

        // Shuffle each team's players
        foreach ($byTeam as &$teamPlayers) {
            shuffle($teamPlayers);
        }
        unset($teamPlayers);
        shuffle($noTeam);

        $teamIds = array_keys($byTeam);
        $foursomes = [];
        $groupNumber = 1;

        if (count($teamIds) === 2) {
            // Two teams: pair 2 from each team per foursome
            $team1 = $teamIds[0];
            $team2 = $teamIds[1];
            $players1 = $byTeam[$team1];
            $players2 = $byTeam[$team2];

            // Create pairs from each team
            $pairs1 = array_chunk($players1, 2);
            $pairs2 = array_chunk($players2, 2);

            // Match up pairs: team1 pair vs team2 pair
            $maxPairs = max(count($pairs1), count($pairs2));
            $usedPairs1 = 0;
            $usedPairs2 = 0;

            for ($i = 0; $i < $maxPairs; $i++) {
                $pair1 = $pairs1[$usedPairs1 % count($pairs1)] ?? [];
                $pair2 = $pairs2[$usedPairs2 % count($pairs2)] ?? [];

                if (count($pair1) < 2 && count($pair2) < 2) {
                    // Both are incomplete - merge leftovers
                    $leftover = array_merge($pair1, $pair2, $noTeam);
                    $noTeam = [];
                    if (count($leftover) >= 2) {
                        $foursomes[] = [
                            'group_number' => $groupNumber++,
                            'players' => array_slice($leftover, 0, 4),
                        ];
                        $this->recordPairings(array_slice($leftover, 0, 4), $pairings);
                    }
                    break;
                }

                // Build the foursome: home pair (positions 0,1) then away pair (positions 2,3)
                $group = array_merge(
                    count($pair1) >= 2 ? array_slice($pair1, 0, 2) : $pair1,
                    count($pair2) >= 2 ? array_slice($pair2, 0, 2) : $pair2
                );

                // Add any teamless players if group is short
                while (count($group) < 4 && !empty($noTeam)) {
                    $group[] = array_shift($noTeam);
                }

                if (count($group) >= 2) {
                    $foursomes[] = [
                        'group_number' => $groupNumber++,
                        'players' => $group,
                    ];
                    $this->recordPairings($group, $pairings);
                }

                $usedPairs1++;
                $usedPairs2++;

                // Stop when we've used all pairs from both teams
                if ($usedPairs1 >= count($pairs1) && $usedPairs2 >= count($pairs2)) {
                    break;
                }
            }
        } else {
            // More than 2 teams or 1 team: rotate team pairings
            // Build pairs from each team
            $allPairs = [];
            foreach ($byTeam as $teamId => $teamPlayers) {
                foreach (array_chunk($teamPlayers, 2) as $pair) {
                    $allPairs[] = ['team' => $teamId, 'players' => $pair];
                }
            }
            shuffle($allPairs);

            // Match pairs from different teams
            $used = array_fill(0, count($allPairs), false);
            for ($i = 0; $i < count($allPairs); $i++) {
                if ($used[$i]) continue;
                $pair1 = $allPairs[$i];
                $used[$i] = true;

                // Find a pair from a different team
                $matched = false;
                for ($j = $i + 1; $j < count($allPairs); $j++) {
                    if ($used[$j]) continue;
                    if ($allPairs[$j]['team'] !== $pair1['team']) {
                        $pair2 = $allPairs[$j];
                        $used[$j] = true;
                        $matched = true;

                        $group = array_merge($pair1['players'], $pair2['players']);
                        while (count($group) < 4 && !empty($noTeam)) {
                            $group[] = array_shift($noTeam);
                        }
                        if (count($group) >= 2) {
                            $foursomes[] = [
                                'group_number' => $groupNumber++,
                                'players' => $group,
                            ];
                            $this->recordPairings($group, $pairings);
                        }
                        break;
                    }
                }

                // No different-team pair found - intra-team match
                if (!$matched) {
                    for ($j = $i + 1; $j < count($allPairs); $j++) {
                        if ($used[$j]) continue;
                        $pair2 = $allPairs[$j];
                        $used[$j] = true;

                        $group = array_merge($pair1['players'], $pair2['players']);
                        if (count($group) >= 2) {
                            $foursomes[] = [
                                'group_number' => $groupNumber++,
                                'players' => $group,
                            ];
                            $this->recordPairings($group, $pairings);
                        }
                        break;
                    }
                }
            }
        }

        // Handle any remaining teamless players
        if (!empty($noTeam)) {
            foreach (array_chunk($noTeam, 4) as $group) {
                if (count($group) >= 2) {
                    $foursomes[] = [
                        'group_number' => $groupNumber++,
                        'players' => $group,
                    ];
                    $this->recordPairings($group, $pairings);
                }
            }
        }

        return $foursomes;
    }

    /**
     * Arrange players in a foursome so teammates are together
     * Returns array where [0,1] are teammates and [2,3] are teammates
     */
    protected function arrangeTeammates(array $group, array $playerTeams)
    {
        // If no teams, return as-is
        if (empty($playerTeams)) {
            return $group;
        }

        // Group players by team
        $byTeam = [];
        $noTeam = [];

        foreach ($group as $playerId) {
            if (isset($playerTeams[$playerId])) {
                $teamId = $playerTeams[$playerId];
                if (!isset($byTeam[$teamId])) {
                    $byTeam[$teamId] = [];
                }
                $byTeam[$teamId][] = $playerId;
            } else {
                $noTeam[] = $playerId;
            }
        }

        // Try to create 2v2 with teammates
        $arranged = [];

        // Get teams with 2+ players
        $teamsWithPairs = array_filter($byTeam, function($players) {
            return count($players) >= 2;
        });

        if (count($teamsWithPairs) >= 2) {
            // We have at least 2 teams with pairs - perfect!
            $teamPlayers = array_values($teamsWithPairs);
            // First team's first 2 players
            $arranged[] = $teamPlayers[0][0];
            $arranged[] = $teamPlayers[0][1];
            // Second team's first 2 players
            $arranged[] = $teamPlayers[1][0];
            $arranged[] = $teamPlayers[1][1];
        } elseif (count($teamsWithPairs) === 1) {
            // One team with a pair, put them together
            $teamPlayers = array_values($teamsWithPairs)[0];
            $arranged[] = $teamPlayers[0];
            $arranged[] = $teamPlayers[1];

            // Fill the rest
            $remaining = array_diff($group, [$teamPlayers[0], $teamPlayers[1]]);
            foreach ($remaining as $playerId) {
                $arranged[] = $playerId;
            }
        } else {
            // No pairs, just return original order
            $arranged = $group;
        }

        // Ensure we have 4 players
        while (count($arranged) < count($group)) {
            foreach ($group as $playerId) {
                if (!in_array($playerId, $arranged)) {
                    $arranged[] = $playerId;
                    break;
                }
            }
        }

        return array_slice($arranged, 0, count($group));
    }

    /**
     * Shuffle players while trying to avoid recent pairings
     */
    protected function intelligentShuffle(array $playerIds, array $pairings)
    {
        $shuffled = $playerIds;
        shuffle($shuffled);

        // Try a few times to get a good mix
        $bestScore = $this->scoreShuffle($shuffled, $pairings);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $temp = $playerIds;
            shuffle($temp);
            $score = $this->scoreShuffle($temp, $pairings);

            if ($score < $bestScore) {
                $shuffled = $temp;
                $bestScore = $score;
            }
        }

        return $shuffled;
    }

    /**
     * Score a shuffle (lower is better - fewer repeat pairings)
     */
    protected function scoreShuffle(array $shuffled, array $pairings)
    {
        $score = 0;

        for ($i = 0; $i < count($shuffled); $i += 4) {
            $group = array_slice($shuffled, $i, 4);

            foreach ($group as $p1) {
                foreach ($group as $p2) {
                    if ($p1 != $p2) {
                        $key = $this->getPairingKey($p1, $p2);
                        $score += $pairings[$key] ?? 0;
                    }
                }
            }
        }

        return $score;
    }

    /**
     * Record pairings for tracking
     */
    protected function recordPairings(array $group, array &$pairings)
    {
        foreach ($group as $p1) {
            foreach ($group as $p2) {
                if ($p1 != $p2) {
                    $key = $this->getPairingKey($p1, $p2);
                    $pairings[$key] = ($pairings[$key] ?? 0) + 1;
                }
            }
        }
    }

    /**
     * Get a consistent pairing key
     */
    protected function getPairingKey($p1, $p2)
    {
        return $p1 < $p2 ? "{$p1}-{$p2}" : "{$p2}-{$p1}";
    }

    /**
     * Save the generated schedule to the database
     */
    public function saveSchedule(League $league, array $scheduleData, \DateTime $startDate, string $holes = 'front_9', string $scoringType = 'best_ball_match_play', string $startTeeTime = '16:40', int $teeTimeInterval = 10, int $weekOffset = 0, string $scoreMode = 'net', ?LeagueSegment $segment = null)
    {
        // Determine the correct slope based on holes selection
        $courseInfo = DB::table('course_info')
            ->where('golf_course_id', $league->golf_course_id)
            ->where('teebox', $league->default_teebox)
            ->where('hole_number', 1)
            ->first();

        $slope = $courseInfo->slope;
        if ($holes === 'back_9' && isset($courseInfo->slope_9_back) && $courseInfo->slope_9_back) {
            $slope = $courseInfo->slope_9_back;
        } elseif ($holes === 'front_9' && isset($courseInfo->slope_9_front) && $courseInfo->slope_9_front) {
            $slope = $courseInfo->slope_9_front;
        }

        $rating = (float) $courseInfo->rating;
        $totalPar = DB::table('course_info')
            ->where('golf_course_id', $league->golf_course_id)
            ->where('teebox', $league->default_teebox)
            ->sum('par');

        // Build player -> team map for setting team_id
        $playerTeams = $segment
            ? $this->getSegmentPlayerTeamMap($segment)
            : $this->getPlayerTeamMap($league);

        DB::transaction(function () use ($league, $scheduleData, $startDate, $courseInfo, $holes, $scoringType, $scoreMode, $slope, $rating, $totalPar, $startTeeTime, $teeTimeInterval, $weekOffset, $playerTeams) {
            $weekIndex = 0;
            foreach ($scheduleData['schedule'] as $weekNumber => $foursomes) {
                $matchDate = (clone $startDate)->modify('+' . $weekIndex . ' weeks');
                $actualWeekNumber = $weekNumber + $weekOffset;

                foreach ($foursomes as $groupIndex => $foursome) {
                    $teeTime = \Carbon\Carbon::createFromFormat('H:i', $startTeeTime)
                        ->addMinutes($groupIndex * $teeTimeInterval)
                        ->format('H:i:s');
                    // Split players into two sides (2v2 for match play)
                    $players = $foursome['players'];
                    $homePlayers = array_slice($players, 0, 2);
                    $awayPlayers = array_slice($players, 2, 2);

                    // Determine team IDs from the players
                    $homeTeamId = null;
                    $awayTeamId = null;
                    foreach ($homePlayers as $pid) {
                        if (isset($playerTeams[$pid])) { $homeTeamId = $playerTeams[$pid]; break; }
                    }
                    foreach ($awayPlayers as $pid) {
                        if (isset($playerTeams[$pid])) { $awayTeamId = $playerTeams[$pid]; break; }
                    }

                    // Create the match
                    $match = LeagueMatch::create([
                        'league_id' => $league->id,
                        'week_number' => $actualWeekNumber,
                        'match_date' => $matchDate,
                        'tee_time' => $teeTime,
                        'golf_course_id' => $league->golf_course_id,
                        'teebox' => $league->default_teebox,
                        'holes' => $holes,
                        'scoring_type' => $scoringType,
                        'score_mode' => $scoreMode,
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'status' => 'scheduled',
                    ]);

                    // Assign home players
                    foreach ($homePlayers as $position => $playerId) {
                        if ($playerId) {
                            $player = Player::find($playerId);
                            $matchDateHandicap = $player->handicapAsOf($matchDate);
                            $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($player->currentHandicap() ? $player->currentHandicap()->handicap_index : 0);
                            $courseHandicap = $this->calculator->calculateCourseHandicap(
                                $handicapIndex,
                                $slope,
                                $rating,
                                $totalPar
                            );

                            MatchPlayer::create([
                                'match_id' => $match->id,
                                'team_id' => $playerTeams[$playerId] ?? null,
                                'player_id' => $playerId,
                                'handicap_index' => $handicapIndex,
                                'course_handicap' => $courseHandicap,
                                'position_in_pairing' => $position + 1,
                            ]);
                        }
                    }

                    // Assign away players
                    foreach ($awayPlayers as $position => $playerId) {
                        if ($playerId) {
                            $player = Player::find($playerId);
                            $matchDateHandicap = $player->handicapAsOf($matchDate);
                            $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($player->currentHandicap() ? $player->currentHandicap()->handicap_index : 0);
                            $courseHandicap = $this->calculator->calculateCourseHandicap(
                                $handicapIndex,
                                $slope,
                                $rating,
                                $totalPar
                            );

                            MatchPlayer::create([
                                'match_id' => $match->id,
                                'team_id' => $playerTeams[$playerId] ?? null,
                                'player_id' => $playerId,
                                'handicap_index' => $handicapIndex,
                                'course_handicap' => $courseHandicap,
                                'position_in_pairing' => $position + 3,
                            ]);
                        }
                    }
                }
                $weekIndex++;
            }
        });
    }
}

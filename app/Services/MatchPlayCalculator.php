<?php

namespace App\Services;

use App\Models\LeagueMatch;
use App\Models\ScoringSetting;
use Illuminate\Support\Facades\DB;

class MatchPlayCalculator
{
    /**
     * Calculate course handicap from handicap index
     * USGA Formula: (Handicap Index × Slope Rating / 113) + (Course Rating - Par)
     */
    public function calculateCourseHandicap($handicapIndex, $slope, $rating = null, $par = null)
    {
        $ch = ($handicapIndex * $slope) / 113;
        if ($rating !== null && $par !== null) {
            $ch += ($rating - $par);
        }
        return round($ch);
    }

    /**
     * Determine stroke allocation for each hole
     * Lower handicap gives strokes to higher handicap
     *
     * @return array [hole_number => strokes_received]
     */
    public function getStrokeAllocation($courseHandicap1, $courseHandicap2, $courseInfo)
    {
        $handicapDiff = abs($courseHandicap1 - $courseHandicap2);
        $strokeAllocation = [];

        // Order holes by number (using hole number as proxy for difficulty)
        $holesByDifficulty = $courseInfo->sortBy('hole_number')->pluck('hole_number')->toArray();

        foreach ($holesByDifficulty as $index => $holeNumber) {
            // Allocate strokes to harder holes first
            if ($index < $handicapDiff) {
                $strokeAllocation[$holeNumber] = 1;
            } else {
                $strokeAllocation[$holeNumber] = 0;
            }
        }

        return $strokeAllocation;
    }

    /**
     * Calculate net score for a hole given gross strokes and strokes received
     */
    public function calculateNetScore($grossStrokes, $strokesReceived)
    {
        return $grossStrokes - $strokesReceived;
    }

    /**
     * Determine hole winner in match play
     *
     * @return string 'home', 'away', or 'tie'
     */
    public function determineHoleWinner($homeNetScore, $awayNetScore)
    {
        if ($homeNetScore < $awayNetScore) {
            return 'home';
        } elseif ($awayNetScore < $homeNetScore) {
            return 'away';
        } else {
            return 'tie';
        }
    }

    /**
     * Calculate match status (e.g., "2 up with 3 to play")
     *
     * @param int $holesWonHome
     * @param int $holesWonAway
     * @param int $holesPlayed (out of 9)
     * @param int $totalHoles
     * @return array ['leader' => 'home'|'away'|'tied', 'margin' => int, 'holes_remaining' => int, 'status_text' => string]
     */
    public function calculateMatchStatus($holesWonHome, $holesWonAway, $holesPlayed, $totalHoles = 9)
    {
        $holesRemaining = $totalHoles - $holesPlayed;
        $margin = abs($holesWonHome - $holesWonAway);

        if ($holesWonHome > $holesWonAway) {
            $leader = 'home';
            $statusText = $holesRemaining > 0
                ? "{$margin} up with {$holesRemaining} to play"
                : "{$margin} up (Final)";
        } elseif ($holesWonAway > $holesWonHome) {
            $leader = 'away';
            $statusText = $holesRemaining > 0
                ? "{$margin} down with {$holesRemaining} to play"
                : "{$margin} down (Final)";
        } else {
            $leader = 'tied';
            $statusText = $holesRemaining > 0
                ? "All square with {$holesRemaining} to play"
                : "Match tied (Final)";
        }

        return [
            'leader' => $leader,
            'margin' => $margin,
            'holes_remaining' => $holesRemaining,
            'status_text' => $statusText,
        ];
    }

    /**
     * Build per-hole stroke allocation for a player using 18-hole course handicap
     * distributed across all 18 holes by hole handicap ranking.
     *
     * @return array [hole_number => strokes_received]
     */
    protected function buildStrokeMap(int $ch18, $allCourseInfo): array
    {
        $strokesOnHole = [];
        foreach ($allCourseInfo as $h) {
            $strokesOnHole[$h->hole_number] = 0;
        }
        $sorted = $allCourseInfo->sortBy('handicap')->pluck('hole_number')->values();
        $remaining = max(0, $ch18);
        while ($remaining > 0) {
            foreach ($sorted as $hn) {
                if ($remaining <= 0) break;
                $strokesOnHole[$hn]++;
                $remaining--;
            }
        }
        return $strokesOnHole;
    }

    /**
     * Get the effective score for a hole: recomputes net from strokes using
     * 18-hole handicap distribution to match the match show page display.
     */
    protected function getHoleScore($matchPlayer, int $hole, bool $useGross, array $strokeMap): int
    {
        $score = $matchPlayer->scores()->where('hole_number', $hole)->first();
        if (!$score) return 999;
        if ($useGross) return (int) $score->strokes;
        return (int) $score->strokes - ($strokeMap[$hole] ?? 0);
    }

    /**
     * Build stroke maps for all match players using 18-hole course handicap.
     *
     * @return array [match_player_id => [hole_number => strokes_received]]
     */
    protected function buildAllStrokeMaps(LeagueMatch $match): array
    {
        // Multi-nine facilities: course handicap from the combined nine
        // rating/slope/par, distributed across the played positions (1-18) by
        // the combined stroke index.
        if ($match->isNinesMode()) {
            $rsp = $match->ratingSlopePar();
            $strokeIndexes = $match->holeStrokeIndexes(); // position => combined SI
            $maps = [];
            foreach ($match->matchPlayers as $mp) {
                $hi = (float) $mp->handicap_index;
                $ch = (int) round(($hi * $rsp['slope'] / 113) + ($rsp['rating'] - $rsp['par']));
                $maps[$mp->id] = $this->buildStrokeMapByIndex($ch, $strokeIndexes);
            }
            return $maps;
        }

        // Legacy single-course path (unchanged): 18-hole course handicap
        // distributed across all 18 holes by hole handicap ranking.
        $allCourseInfo = $match->golfCourse->courseInfo()
            ->where('teebox', $match->teebox)
            ->orderBy('hole_number')
            ->get();
        $par18 = $allCourseInfo->sum('par');
        $slope = (float) $allCourseInfo->first()->slope;
        $rating = (float) $allCourseInfo->first()->rating;

        $maps = [];
        foreach ($match->matchPlayers as $mp) {
            $hi = (float) $mp->handicap_index;
            $ch18 = (int) round(($hi * $slope / 113) + ($rating - $par18));
            $maps[$mp->id] = $this->buildStrokeMap($ch18, $allCourseInfo);
        }
        return $maps;
    }

    /**
     * Distribute a course handicap across played positions using an explicit
     * position => stroke-index map (hardest hole / lowest index first, wrapping
     * for handicaps above the hole count).
     *
     * @param  array<int,int>  $strokeIndexes  position => stroke index
     * @return array<int,int>  position => strokes received
     */
    protected function buildStrokeMapByIndex(int $courseHandicap, array $strokeIndexes): array
    {
        $strokesOnHole = [];
        foreach (array_keys($strokeIndexes) as $pos) {
            $strokesOnHole[$pos] = 0;
        }
        asort($strokeIndexes); // order positions by stroke index ascending
        $orderedPositions = array_keys($strokeIndexes);
        $remaining = max(0, $courseHandicap);
        while ($remaining > 0) {
            foreach ($orderedPositions as $pos) {
                if ($remaining <= 0) break;
                $strokesOnHole[$pos]++;
                $remaining--;
            }
        }
        return $strokesOnHole;
    }

    /**
     * Process all scores for a match and determine final result.
     * Dispatches to the appropriate calculation method based on scoring type.
     */
    public function calculateMatchResult(LeagueMatch $match)
    {
        if ($match->scoring_type === ScoringSetting::TYPE_INDIVIDUAL_MATCH_PLAY) {
            return $this->calculateIndividualMatchResult($match);
        }

        if ($match->scoring_type === ScoringSetting::TYPE_TEAM_2BALL_MATCH_PLAY) {
            return $this->calculateTeam2BallMatchResult($match);
        }

        return $this->calculateBestBallMatchResult($match);
    }

    /**
     * Individual Match Play: Player 1 vs Player 3, Player 2 vs Player 4.
     * Each pair plays hole-by-hole match play. Overall result is determined
     * by how many individual matches each side wins.
     */
    protected function calculateIndividualMatchResult(LeagueMatch $match)
    {
        $holeRange = $match->holeRange();
        $startHole = $holeRange[0];
        $endHole = $holeRange[1];

        if ($match->home_team_id) {
            $homePlayers = $match->matchPlayers()->where('team_id', $match->home_team_id)
                ->orderBy('position_in_pairing')->get();
            $awayPlayers = $match->matchPlayers()->where('team_id', $match->away_team_id)
                ->orderBy('position_in_pairing')->get();
        } else {
            $homePlayers = $match->matchPlayers()->where('position_in_pairing', '<=', 2)
                ->orderBy('position_in_pairing')->get();
            $awayPlayers = $match->matchPlayers()->where('position_in_pairing', '>', 2)
                ->orderBy('position_in_pairing')->get();
        }

        $useGross = ($match->score_mode === 'gross' || $match->scoring_type === ScoringSetting::TYPE_SCRAMBLE);
        $strokeMaps = $useGross ? [] : $this->buildAllStrokeMaps($match);

        $matchesWonHome = 0;
        $matchesWonAway = 0;
        $matchesTied = 0;

        $pairCount = min($homePlayers->count(), $awayPlayers->count());

        for ($p = 0; $p < $pairCount; $p++) {
            $homePlayer = $homePlayers[$p];
            $awayPlayer = $awayPlayers[$p];

            $pairHolesWonHome = 0;
            $pairHolesWonAway = 0;

            for ($hole = $startHole; $hole <= $endHole; $hole++) {
                $homeVal = $this->getHoleScore($homePlayer, $hole, $useGross, $strokeMaps[$homePlayer->id] ?? []);
                $awayVal = $this->getHoleScore($awayPlayer, $hole, $useGross, $strokeMaps[$awayPlayer->id] ?? []);

                $winner = $this->determineHoleWinner($homeVal, $awayVal);

                if ($winner === 'home') {
                    $pairHolesWonHome++;
                } elseif ($winner === 'away') {
                    $pairHolesWonAway++;
                }
            }

            if ($pairHolesWonHome > $pairHolesWonAway) {
                $matchesWonHome++;
            } elseif ($pairHolesWonAway > $pairHolesWonHome) {
                $matchesWonAway++;
            } else {
                $matchesTied++;
            }
        }

        return $this->buildResultFromHoles($match, $matchesWonHome, $matchesWonAway, $matchesTied);
    }

    /**
     * Best Ball Match Play: compare best score per team on each hole.
     */
    protected function calculateBestBallMatchResult(LeagueMatch $match)
    {
        $holeRange = $match->holeRange();
        $startHole = $holeRange[0];
        $endHole = $holeRange[1];

        if ($match->home_team_id) {
            $homeTeamPlayers = $match->matchPlayers()->where('team_id', $match->home_team_id)->get();
            $awayTeamPlayers = $match->matchPlayers()->where('team_id', $match->away_team_id)->get();
        } else {
            $homeTeamPlayers = $match->matchPlayers()->where('position_in_pairing', '<=', 2)->get();
            $awayTeamPlayers = $match->matchPlayers()->where('position_in_pairing', '>', 2)->get();
        }

        $useGross = ($match->score_mode === 'gross' || $match->scoring_type === ScoringSetting::TYPE_SCRAMBLE);
        $strokeMaps = $useGross ? [] : $this->buildAllStrokeMaps($match);

        $holesWonHome = 0;
        $holesWonAway = 0;
        $holesTied = 0;

        for ($hole = $startHole; $hole <= $endHole; $hole++) {
            $homeScores = $homeTeamPlayers->map(function ($mp) use ($hole, $useGross, $strokeMaps) {
                return $this->getHoleScore($mp, $hole, $useGross, $strokeMaps[$mp->id] ?? []);
            });

            $awayScores = $awayTeamPlayers->map(function ($mp) use ($hole, $useGross, $strokeMaps) {
                return $this->getHoleScore($mp, $hole, $useGross, $strokeMaps[$mp->id] ?? []);
            });

            $bestHomeScore = $homeScores->min();
            $bestAwayScore = $awayScores->min();

            $holeWinner = $this->determineHoleWinner($bestHomeScore, $bestAwayScore);

            if ($holeWinner === 'home') {
                $holesWonHome++;
            } elseif ($holeWinner === 'away') {
                $holesWonAway++;
            } else {
                $holesTied++;
            }
        }

        return $this->buildResultFromHoles($match, $holesWonHome, $holesWonAway, $holesTied);
    }

    /**
     * Team 2 Ball Match Play: on each hole, sum both teammates' scores
     * and compare the combined team totals to determine the hole winner.
     */
    protected function calculateTeam2BallMatchResult(LeagueMatch $match)
    {
        $holeRange = $match->holeRange();
        $startHole = $holeRange[0];
        $endHole = $holeRange[1];

        if ($match->home_team_id) {
            $homePlayers = $match->matchPlayers()->where('team_id', $match->home_team_id)->get();
            $awayPlayers = $match->matchPlayers()->where('team_id', $match->away_team_id)->get();
        } else {
            $homePlayers = $match->matchPlayers()->where('position_in_pairing', '<=', 2)->get();
            $awayPlayers = $match->matchPlayers()->where('position_in_pairing', '>', 2)->get();
        }

        $useGross = ($match->score_mode === 'gross' || $match->scoring_type === ScoringSetting::TYPE_SCRAMBLE);
        $strokeMaps = $useGross ? [] : $this->buildAllStrokeMaps($match);

        $holesWonHome = 0;
        $holesWonAway = 0;
        $holesTied = 0;

        for ($hole = $startHole; $hole <= $endHole; $hole++) {
            $homeCombined = 0;
            $homeHasScores = false;
            foreach ($homePlayers as $mp) {
                $val = $this->getHoleScore($mp, $hole, $useGross, $strokeMaps[$mp->id] ?? []);
                if ($val < 999) {
                    $homeCombined += $val;
                    $homeHasScores = true;
                }
            }

            $awayCombined = 0;
            $awayHasScores = false;
            foreach ($awayPlayers as $mp) {
                $val = $this->getHoleScore($mp, $hole, $useGross, $strokeMaps[$mp->id] ?? []);
                if ($val < 999) {
                    $awayCombined += $val;
                    $awayHasScores = true;
                }
            }

            if (!$homeHasScores || !$awayHasScores) {
                continue;
            }

            $holeWinner = $this->determineHoleWinner($homeCombined, $awayCombined);

            if ($holeWinner === 'home') {
                $holesWonHome++;
            } elseif ($holeWinner === 'away') {
                $holesWonAway++;
            } else {
                $holesTied++;
            }
        }

        return $this->buildResultFromHoles($match, $holesWonHome, $holesWonAway, $holesTied);
    }

    /**
     * Build the final result array with point calculations.
     */
    protected function buildResultFromHoles(LeagueMatch $match, int $wonHome, int $wonAway, int $tied): array
    {
        $winningTeamId = null;
        $teamPointsHome = 0;
        $teamPointsAway = 0;
        $scoringType = $match->scoring_type ?? ScoringSetting::TYPE_BEST_BALL_MATCH_PLAY;
        $leagueId = $match->league_id;

        if ($wonHome > $wonAway) {
            $winningTeamId = $match->home_team_id;
        } elseif ($wonAway > $wonHome) {
            $winningTeamId = $match->away_team_id;
        }

        if ($scoringType === ScoringSetting::TYPE_INDIVIDUAL_MATCH_PLAY) {
            // Individual match play: each matchup awards points independently,
            // then sum for the team total. e.g. Pair 1 ties (0.25 each) +
            // Pair 2 away wins (0.50 away, 0.00 home) = Away 0.75, Home 0.25.
            $winPoints = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_WIN, 0.5, $leagueId);
            $lossPoints = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_LOSS, 0.0, $leagueId);
            $tiePoints = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_TIE, 0.25, $leagueId);

            $teamPointsHome = ($wonHome * $winPoints) + ($wonAway * $lossPoints) + ($tied * $tiePoints);
            $teamPointsAway = ($wonAway * $winPoints) + ($wonHome * $lossPoints) + ($tied * $tiePoints);
        } else {
            // Other formats: single match result (win/loss/tie)
            if ($winningTeamId === $match->home_team_id) {
                $teamPointsHome = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_WIN, 1.0, $leagueId);
                $teamPointsAway = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_LOSS, 0.0, $leagueId);
            } elseif ($winningTeamId === $match->away_team_id) {
                $teamPointsHome = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_LOSS, 0.0, $leagueId);
                $teamPointsAway = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_WIN, 1.0, $leagueId);
            } else {
                $tiePoints = ScoringSetting::getPoints($scoringType, ScoringSetting::OUTCOME_TIE, 0.5, $leagueId);
                $teamPointsHome = $tiePoints;
                $teamPointsAway = $tiePoints;
            }
        }

        return [
            'winning_team_id' => $winningTeamId,
            'holes_won_home' => $wonHome,
            'holes_won_away' => $wonAway,
            'holes_tied' => $tied,
            'team_points_home' => $teamPointsHome,
            'team_points_away' => $teamPointsAway,
        ];
    }
}

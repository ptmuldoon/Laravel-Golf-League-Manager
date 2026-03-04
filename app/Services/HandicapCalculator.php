<?php

namespace App\Services;

use App\Models\CourseInfo;
use App\Models\Player;
use App\Models\Round;
use Illuminate\Support\Collection;

class HandicapCalculator
{
    /**
     * WHS lookup table: given N total rounds, how many best differentials to use.
     * Index = number of rounds available (3..20), value = number of best diffs to use.
     */
    const DIFF_TABLE = [
        3  => 1,
        4  => 1,
        5  => 1,
        6  => 2,
        7  => 2,
        8  => 2,
        9  => 3,
        10 => 3,
        11 => 3,
        12 => 4,
        13 => 4,
        14 => 4,
        15 => 5,
        16 => 5,
        17 => 6,
        18 => 6,
        19 => 7,
        20 => 8,
    ];

    /**
     * WHS adjustment applied to handicap index when fewer than 3 differentials
     * are used (i.e., when total rounds = 3, 4, or 5 -> only 1 diff used).
     */
    const ADJUSTMENT_TABLE = [
        3 => -2.0,
        4 => -1.0,
        5 =>  0.0,
        6 => -1.0,
    ];

    /**
     * Maximum per-hole score for handicap purposes: Net Double Bogey.
     * Net Double Bogey = Par + 2 + strokes received on that hole.
     *
     * For a player with no established handicap, we use a max of par + 5
     * (approximation for a ~36 handicap beginner cap).
     */
    public function adjustedGrossScore(Round $round, ?float $courseHandicap = null): ?float
    {
        $perHole = $this->calculatePerHoleScores($round, $courseHandicap);
        if (empty($perHole)) {
            return null;
        }

        $total = 0;
        foreach ($perHole as $holeData) {
            $total += $holeData['adjusted_gross'];
        }

        return $total;
    }

    /**
     * Calculate per-hole adjusted gross and net scores for a round.
     * Returns array of [hole_number => ['adjusted_gross' => int, 'net_score' => int, 'strokes_received' => int]]
     */
    public function calculatePerHoleScores(Round $round, ?float $courseHandicap = null): array
    {
        $scores = $round->scores->sortBy('hole_number');
        if ($scores->isEmpty()) {
            return [];
        }

        $courseInfo = $this->getCourseInfoForRound($round);
        if ($courseInfo->isEmpty()) {
            // No course info — return gross as adjusted, no net adjustment
            $result = [];
            foreach ($scores as $score) {
                $result[$score->hole_number] = [
                    'adjusted_gross' => $score->strokes,
                    'net_score' => $score->strokes,
                    'strokes_received' => 0,
                ];
            }
            return $result;
        }

        $holesPlayed = $round->holes_played ?? 18;
        $totalHoles = 18; // Always use 18-hole rankings for stroke distribution

        $result = [];

        foreach ($scores as $score) {
            $holeInfo = $courseInfo->firstWhere('hole_number', $score->hole_number);
            $par = $holeInfo ? $holeInfo->par : 4;
            $holeHandicapRanking = ($holeInfo && $holeInfo->handicap) ? (int) $holeInfo->handicap : null;

            // Calculate strokes received on this hole
            $strokesReceived = 0;
            if ($courseHandicap !== null) {
                $strokesReceived = $this->strokesReceivedOnHole(
                    $courseHandicap,
                    $score->hole_number,
                    $totalHoles,
                    $holeHandicapRanking
                );
            } else {
                // No established handicap: assume generous cap (par + 5)
                $strokesReceived = 3; // par + 2 + 3 = par + 5
            }

            // Adjusted Gross: capped at Net Double Bogey = par + 2 + strokes received
            $maxScore = $par + 2 + $strokesReceived;
            $adjustedGross = min($score->strokes, $maxScore);

            // Net Score: gross strokes minus strokes received
            $netScore = $score->strokes - $strokesReceived;

            $result[$score->hole_number] = [
                'adjusted_gross' => $adjustedGross,
                'net_score' => $netScore,
                'strokes_received' => $strokesReceived,
            ];
        }

        return $result;
    }

    /**
     * Allocate strokes to a hole based on course handicap and hole handicap ranking.
     * Uses the hole's handicap ranking from course_info when available.
     * Falls back to hole number as ranking.
     */
    private function strokesReceivedOnHole(float $courseHandicap, int $holeNumber, int $totalHoles, ?int $holeHandicapRanking = null): int
    {
        $ch = max(0, (int) round($courseHandicap));
        if ($ch <= 0) {
            return 0;
        }

        $ranking = $holeHandicapRanking ?? $holeNumber;

        // Each hole gets at least floor(ch / totalHoles) strokes
        $base = intdiv($ch, $totalHoles);
        $remainder = $ch % $totalHoles;

        // Extra stroke goes to holes with ranking <= remainder
        return $base + ($ranking <= $remainder ? 1 : 0);
    }

    /**
     * Calculate 18-hole score differential.
     * Formula: (113 / Slope) × (Adjusted Gross - Rating)
     */
    public function scoreDifferential18(float $adjustedGross, float $rating, float $slope): float
    {
        if ($slope == 0) {
            return 0;
        }
        return (113 / $slope) * ($adjustedGross - $rating);
    }

    /**
     * Calculate 9-hole score differential.
     * Formula: (113 / Slope9) × (Adjusted Gross 9 - Rating9)
     * The resulting differential is for 9 holes only.
     *
     * Under 2024 WHS rules, this is combined with an expected 9-hole differential
     * (based on the player's HI) to create an 18-hole equivalent. If no HI is
     * established, two 9-hole diffs are combined (summed) as a fallback.
     */
    public function scoreDifferential9(float $adjustedGross9, float $rating9, float $slope9): float
    {
        if ($slope9 == 0) {
            return 0;
        }
        return (113 / $slope9) * ($adjustedGross9 - $rating9);
    }

    /**
     * Calculate the expected 9-hole Score Differential based on the player's Handicap Index.
     *
     * Under the 2024 WHS revision, each 9-hole round immediately produces an 18-hole
     * equivalent differential by combining the actual 9-hole differential with an
     * "expected" differential representing the unplayed 9 holes.
     *
     * The exact USGA formula is proprietary. This uses the widely-cited and
     * USGA-example-validated approximation: expected 9-hole diff ≈ HI × 0.607.
     */
    public function expectedNineHoleDifferential(float $handicapIndex): float
    {
        return round($handicapIndex * 0.607, 1);
    }

    /**
     * Get slope and rating for a round.
     * Returns [slope, rating] appropriate for holes played.
     */
    public function getSlopeAndRating(Round $round): ?array
    {
        $courseInfo = $this->getCourseInfoForRound($round);
        if ($courseInfo->isEmpty()) {
            return null;
        }

        $holesPlayed = $round->holes_played ?? 18;
        $firstHole = $courseInfo->first();
        $rating18 = (float) $firstHole->rating;

        if ($holesPlayed == 18) {
            return [
                'slope' => (float) $firstHole->slope,
                'rating' => $rating18,
            ];
        }

        // 9-hole: determine front or back based on scored holes
        $scoredHoles = $round->scores->pluck('hole_number')->sort();
        $isFront = $scoredHoles->max() <= 9;

        if ($isFront && $firstHole->slope_9_front && $firstHole->rating_9_front) {
            $rating9 = (float) $firstHole->rating_9_front;
            // If the 9-hole rating equals the 18-hole rating, it was not properly set — halve it
            if (abs($rating9 - $rating18) < 0.5) {
                $rating9 = $rating18 / 2;
            }
            return [
                'slope' => (float) $firstHole->slope_9_front,
                'rating' => $rating9,
            ];
        } elseif (!$isFront && $firstHole->slope_9_back && $firstHole->rating_9_back) {
            $rating9 = (float) $firstHole->rating_9_back;
            if (abs($rating9 - $rating18) < 0.5) {
                $rating9 = $rating18 / 2;
            }
            return [
                'slope' => (float) $firstHole->slope_9_back,
                'rating' => $rating9,
            ];
        }

        // Fallback: use 18-hole slope, halved rating
        return [
            'slope' => (float) $firstHole->slope,
            'rating' => $rating18 / 2,
        ];
    }

    /**
     * Compute the score differential for a single round.
     * Handles both 9-hole and 18-hole rounds.
     * For 9-hole rounds, returns a 9-hole differential (to be combined later).
     */
    public function computeRoundDifferential(Round $round, ?float $currentHandicapIndex = null): ?array
    {
        $slopeRating = $this->getSlopeAndRating($round);
        if (!$slopeRating) {
            return null;
        }

        $holesPlayed = $round->holes_played ?? 18;
        $isNineHole = ($holesPlayed == 9);

        // Calculate course handicap for net double bogey cap.
        // Always use the full 18-hole CH regardless of holes played, so that
        // stroke allocation uses the full 1–18 handicap rankings correctly.
        $courseHandicap = null;
        if ($currentHandicapIndex !== null) {
            $courseHandicap = ($currentHandicapIndex * $slopeRating['slope']) / 113;
        }

        $adjustedGross = $this->adjustedGrossScore($round, $courseHandicap);
        if ($adjustedGross === null) {
            return null;
        }

        if ($isNineHole) {
            $diff = $this->scoreDifferential9(
                $adjustedGross,
                $slopeRating['rating'],
                $slopeRating['slope']
            );
            return [
                'differential' => round($diff, 1),
                'is_nine_hole' => true,
                'round_id' => $round->id,
                'played_at' => $round->played_at,
                'adjusted_gross' => $adjustedGross,
                'rating' => $slopeRating['rating'],
                'slope' => $slopeRating['slope'],
            ];
        }

        $diff = $this->scoreDifferential18(
            $adjustedGross,
            $slopeRating['rating'],
            $slopeRating['slope']
        );

        return [
            'differential' => round($diff, 1),
            'is_nine_hole' => false,
            'round_id' => $round->id,
            'played_at' => $round->played_at,
            'adjusted_gross' => $adjustedGross,
            'rating' => $slopeRating['rating'],
            'slope' => $slopeRating['slope'],
        ];
    }

    /**
     * Build the list of 18-hole equivalent differentials from a set of rounds.
     *
     * For 9-hole rounds:
     * - If a current Handicap Index is available, each 9-hole round immediately
     *   produces an 18-hole equivalent using the 2024 WHS expected score method.
     * - If no Handicap Index is available (new player), falls back to the pre-2024
     *   method of combining two consecutive 9-hole differentials.
     *
     * Returns up to the most recent 20 differentials.
     */
    public function buildDifferentialsList(Collection $rounds, ?float $currentHandicapIndex = null): array
    {
        $differentials = [];
        $pendingNineHoleDiff = null;

        foreach ($rounds as $round) {
            $result = $this->computeRoundDifferential($round, $currentHandicapIndex);
            if ($result === null) {
                continue;
            }

            if ($result['is_nine_hole']) {
                if ($currentHandicapIndex !== null) {
                    // 2024 WHS: immediate 18-hole equivalent via expected score method
                    $expectedDiff = $this->expectedNineHoleDifferential($currentHandicapIndex);
                    $eighteenHoleEquiv = $result['differential'] + $expectedDiff;
                    $differentials[] = [
                        'differential' => round($eighteenHoleEquiv, 1),
                        'round_id' => $result['round_id'],
                        'played_at' => $result['played_at'],
                        'type' => '9_hole_expected',
                        'actual_9_diff' => $result['differential'],
                        'expected_9_diff' => $expectedDiff,
                    ];
                } else {
                    // Pre-2024 fallback: combine two consecutive 9-hole rounds
                    if ($pendingNineHoleDiff !== null) {
                        $combined = $pendingNineHoleDiff['differential'] + $result['differential'];
                        $differentials[] = [
                            'differential' => round($combined, 1),
                            'round_ids' => [$pendingNineHoleDiff['round_id'], $result['round_id']],
                            'played_at' => $result['played_at'],
                            'type' => 'combined_9',
                        ];
                        $pendingNineHoleDiff = null;
                    } else {
                        $pendingNineHoleDiff = $result;
                    }
                }
            } else {
                $differentials[] = [
                    'differential' => $result['differential'],
                    'round_id' => $result['round_id'],
                    'played_at' => $result['played_at'],
                    'type' => '18_hole',
                    'adjusted_gross' => $result['adjusted_gross'],
                    'rating' => $result['rating'],
                    'slope' => $result['slope'],
                ];
            }
        }

        // Keep only the most recent 20
        return array_slice($differentials, -20);
    }

    /**
     * Calculate handicap index from a list of differentials.
     * Follows WHS rules for number of differentials to use and adjustments.
     */
    public function calculateHandicapIndex(array $differentials): ?array
    {
        $count = count($differentials);

        if ($count < 3) {
            return null; // Need at least 3 rounds
        }

        // Cap at 20 most recent
        if ($count > 20) {
            $differentials = array_slice($differentials, -20);
            $count = 20;
        }

        // Determine how many best differentials to use
        $numToUse = self::DIFF_TABLE[$count] ?? 8;

        // Sort by differential ascending and take the best N
        $sorted = collect($differentials)->sortBy('differential')->values();
        $best = $sorted->take($numToUse);

        $avgDifferential = $best->avg('differential');

        // Apply adjustment for low number of rounds
        $adjustment = self::ADJUSTMENT_TABLE[$count] ?? 0.0;
        $adjustedAvg = $avgDifferential + $adjustment;

        // Multiply by 0.96 (WHS rule)
        $handicapIndex = $adjustedAvg * 0.96;

        // Round to one decimal (WHS Rule 5.2a)
        $handicapIndex = round($handicapIndex, 1);

        // Cap at 54.0 (WHS max)
        $handicapIndex = min(54.0, $handicapIndex);

        // Floor at +54.0 / minimum at -something is not typical; allow negatives for scratch+
        return [
            'handicap_index' => $handicapIndex,
            'rounds_used' => $numToUse,
            'differentials' => $best->pluck('differential')->toArray(),
            'all_differentials' => collect($differentials)->pluck('differential')->toArray(),
        ];
    }

    /**
     * Compute historical handicap snapshots for a player after each round.
     * Returns an array of [calculation_date => handicap data].
     *
     * Differentials are built incrementally to preserve correct WHS phasing:
     * - Before any HI is established: consecutive 9-hole rounds are paired into
     *   18-hole equivalents (pre-2024 / new-player method).
     * - Once an HI exists: each subsequent 9-hole round immediately generates its
     *   own 18-hole equivalent using the 2024 WHS expected-score method.
     *
     * Rebuilding all differentials from scratch on each iteration would
     * retroactively apply the expected-score method to the original paired rounds,
     * inflating the differential count and producing a wrong handicap index.
     */
    public function computeHistoricalHandicaps(Player $player): array
    {
        $rounds = $player->rounds()
            ->with(['scores', 'golfCourse', 'matchPlayer.match'])
            ->orderBy('played_at')
            ->orderBy('id')
            ->get()
            // Exclude scramble rounds — scramble scores don't reflect individual play
            ->filter(function ($round) {
                if ($round->matchPlayer && $round->matchPlayer->match) {
                    return $round->matchPlayer->match->scoring_type !== 'scramble';
                }
                return true; // keep non-match rounds (manual entries)
            })
            ->values();

        if ($rounds->isEmpty()) {
            return [];
        }

        $history = [];
        $currentHandicapIndex = null;
        $allDifferentials = [];
        $pendingNineHoleDiff = null;

        foreach ($rounds as $round) {
            $result = $this->computeRoundDifferential($round, $currentHandicapIndex);
            if ($result === null) {
                continue;
            }

            if ($result['is_nine_hole']) {
                if ($currentHandicapIndex !== null) {
                    // 2024 WHS: each 9-hole round immediately becomes an 18-hole
                    // equivalent using the player's current HI as the expected score.
                    $expectedDiff = $this->expectedNineHoleDifferential($currentHandicapIndex);
                    $eighteenHoleEquiv = $result['differential'] + $expectedDiff;
                    $allDifferentials[] = [
                        'differential' => round($eighteenHoleEquiv, 1),
                        'round_id' => $result['round_id'],
                        'played_at' => $result['played_at'],
                        'type' => '9_hole_expected',
                        'actual_9_diff' => $result['differential'],
                        'expected_9_diff' => $expectedDiff,
                    ];
                } else {
                    // Pre-HI: combine consecutive pairs of 9-hole rounds.
                    if ($pendingNineHoleDiff !== null) {
                        $combined = $pendingNineHoleDiff['differential'] + $result['differential'];
                        $allDifferentials[] = [
                            'differential' => round($combined, 1),
                            'round_ids' => [$pendingNineHoleDiff['round_id'], $result['round_id']],
                            'played_at' => $result['played_at'],
                            'type' => 'combined_9',
                        ];
                        $pendingNineHoleDiff = null;
                    } else {
                        $pendingNineHoleDiff = $result;
                    }
                }
            } else {
                $allDifferentials[] = [
                    'differential' => $result['differential'],
                    'round_id' => $result['round_id'],
                    'played_at' => $result['played_at'],
                    'type' => '18_hole',
                    'adjusted_gross' => $result['adjusted_gross'],
                    'rating' => $result['rating'],
                    'slope' => $result['slope'],
                ];
            }

            // Keep only the most recent 20 differentials.
            $trimmed = array_slice($allDifferentials, -20);
            $calcResult = $this->calculateHandicapIndex($trimmed);
            if ($calcResult === null) {
                continue;
            }

            $currentHandicapIndex = $calcResult['handicap_index'];

            $history[] = [
                'player_id' => $player->id,
                'calculation_date' => $round->played_at,
                'handicap_index' => $calcResult['handicap_index'],
                'rounds_used' => $calcResult['rounds_used'],
                'score_differentials' => $calcResult['all_differentials'],
                'round_id' => $round->id,
            ];
        }

        return $history;
    }

    /**
     * Get course info for a round, keyed by hole number.
     */
    private function getCourseInfoForRound(Round $round): Collection
    {
        return CourseInfo::where('golf_course_id', $round->golf_course_id)
            ->where('teebox', $round->teebox)
            ->orderBy('hole_number')
            ->get();
    }
}

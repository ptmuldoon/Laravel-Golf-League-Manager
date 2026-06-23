<?php

namespace App\Http\Controllers\Concerns;

/**
 * Shared player-level individual match play calculations.
 *
 * In individual match play a team match is decided by several head-to-head
 * player matchups. For player standings each player must be credited with
 * the result of their *own* matchup rather than the team's overall result.
 * Both the home page and the weekly results email rely on this so their
 * player standings stay consistent.
 */
trait ComputesIndividualMatchups
{
    /**
     * Calculate individual match play points for a specific player's standing.
     * In individual format the player is awarded a straight win/loss/tie
     * (1 / 0 / 0.5) rather than the team's per-matchup scoring values.
     */
    private function getIndividualPlayerPoints($mp): float
    {
        $result = $this->getIndividualMatchupResult($mp);

        if ($result === 'win') {
            return 1.0;
        } elseif ($result === 'loss') {
            return 0.0;
        }
        return 0.5;
    }

    /**
     * Determine if a player won, lost, or tied their individual matchup.
     * Uses 18-hole CH distribution for net score calculation.
     */
    private function getIndividualMatchupResult($mp): string
    {
        $match = $mp->match;
        [$holeStart, $holeEnd] = $match->holeRange();
        $useGross = ($match->score_mode === 'gross' || $match->scoring_type === 'scramble');

        // Find the opponent: same position index on the other team
        $isHome = $mp->team_id == $match->home_team_id;
        $myTeamPlayers = $match->matchPlayers
            ->where('team_id', $mp->team_id)
            ->sortBy('position_in_pairing')->values();
        $oppTeamId = $isHome ? $match->away_team_id : $match->home_team_id;
        $oppTeamPlayers = $match->matchPlayers
            ->where('team_id', $oppTeamId)
            ->sortBy('position_in_pairing')->values();

        $myIndex = $myTeamPlayers->search(fn($p) => $p->id === $mp->id);
        $opponent = $oppTeamPlayers[$myIndex] ?? null;
        if (!$opponent) return 'tie';

        // Build stroke maps if net scoring
        $strokeMaps = [];
        if (!$useGross) {
            $allCourseInfo = $match->golfCourse->courseInfo()
                ->where('teebox', $match->teebox)
                ->orderBy('hole_number')->get();
            $par18 = $allCourseInfo->sum('par');
            $slope = (float) $allCourseInfo->first()->slope;
            $rating = (float) $allCourseInfo->first()->rating;
            $sorted = $allCourseInfo->sortBy('handicap')->pluck('hole_number')->values();

            foreach ([$mp, $opponent] as $p) {
                $ch18 = (int) round(((float) $p->handicap_index * $slope / 113) + ($rating - $par18));
                $map = [];
                foreach ($allCourseInfo as $h) { $map[$h->hole_number] = 0; }
                $rem = max(0, $ch18);
                while ($rem > 0) { foreach ($sorted as $hn) { if ($rem <= 0) break; $map[$hn]++; $rem--; } }
                $strokeMaps[$p->id] = $map;
            }
        }

        $myWins = 0;
        $oppWins = 0;
        for ($hole = $holeStart; $hole <= $holeEnd; $hole++) {
            $myScore = $mp->scores->where('hole_number', $hole)->first();
            $oppScore = $opponent->scores->where('hole_number', $hole)->first();
            if (!$myScore || !$oppScore) continue;

            if ($useGross) {
                $myVal = (int) $myScore->strokes;
                $oppVal = (int) $oppScore->strokes;
            } else {
                $myVal = (int) $myScore->strokes - ($strokeMaps[$mp->id][$hole] ?? 0);
                $oppVal = (int) $oppScore->strokes - ($strokeMaps[$opponent->id][$hole] ?? 0);
            }

            if ($myVal < $oppVal) $myWins++;
            elseif ($oppVal < $myVal) $oppWins++;
        }

        if ($myWins > $oppWins) return 'win';
        if ($oppWins > $myWins) return 'loss';
        return 'tie';
    }
}

<?php

namespace App\Services;

use App\Models\League;
use App\Models\LeagueMatch;

/**
 * Partner Contribution Analysis.
 *
 * For every completed match, computes how each player would have done "on their
 * own merits" (solo) versus what the team actually achieved and versus what their
 * partner did. Aggregated across the season this reveals who is carrying their
 * pairing and who is being carried.
 *
 * Net scoring mirrors App\Services\MatchPlayCalculator exactly: an 18-hole course
 * handicap distributed across holes by hole-handicap ranking, with
 * net = gross strokes - strokes received on the hole.
 */
class PartnerContributionAnalyzer
{
    /**
     * @return array{rows: array, weeks: int, matches: int, hasData: bool}
     */
    public function analyze(League $league): array
    {
        $matches = LeagueMatch::where('league_id', $league->id)
            ->where('status', 'completed')
            ->with([
                'matchPlayers.player',
                'matchPlayers.substitutePlayer',
                'matchPlayers.scores',
                'golfCourse.courseInfo',
                'result',
            ])
            ->orderBy('week_number')
            ->get();

        $records = []; // flat per-player-per-match records
        $weeks = [];

        foreach ($matches as $m) {
            $weeks[$m->week_number] = true;
            $ci = $m->golfCourse->courseInfo->where('teebox', $m->teebox)->sortBy('hole_number')->values();
            if ($ci->isEmpty()) {
                continue;
            }
            $par18 = $ci->sum('par');
            $slope = (float) $ci->first()->slope;
            $rating = (float) $ci->first()->rating;
            $useGross = ($m->score_mode === 'gross');

            [$start, $end] = $m->holes === 'back_9' ? [10, 18] : [1, 9];

            $homeMps = $m->matchPlayers->where('team_id', $m->home_team_id)->sortBy('position_in_pairing')->values();
            $awayMps = $m->matchPlayers->where('team_id', $m->away_team_id)->sortBy('position_in_pairing')->values();

            // Per match-player net-on-hole map + effective identity.
            $net = [];
            $name = [];
            $pid = [];
            $isSub = [];
            foreach ($m->matchPlayers as $mp) {
                $hi = (float) $mp->handicap_index;
                $ch18 = (int) round(($hi * $slope / 113) + ($rating - $par18));
                $map = $this->buildStrokeMap($ch18, $ci);
                $row = [];
                foreach ($mp->scores as $s) {
                    $row[$s->hole_number] = $useGross
                        ? (int) $s->strokes
                        : (int) $s->strokes - ($map[$s->hole_number] ?? 0);
                }
                $net[$mp->id] = $row;

                if ($mp->substitute_player_id && $mp->substitutePlayer) {
                    $name[$mp->id] = $mp->substitutePlayer->name;
                    $pid[$mp->id] = 'p' . $mp->substitute_player_id;
                    $isSub[$mp->id] = true;
                } elseif ($mp->substitute_name) {
                    $name[$mp->id] = $mp->substitute_name;
                    $pid[$mp->id] = 'sub_' . $mp->id;
                    $isSub[$mp->id] = true;
                } else {
                    $name[$mp->id] = optional($mp->player)->name ?? 'Unknown';
                    $pid[$mp->id] = 'p' . $mp->player_id;
                    $isSub[$mp->id] = false;
                }
            }

            $getNet = fn ($mpId, $hole) => $net[$mpId][$hole] ?? 999;

            foreach ([[$homeMps, $awayMps], [$awayMps, $homeMps]] as [$us, $them]) {
                foreach ($us as $idx => $mp) {
                    $partner = $us->firstWhere('id', '!=', $mp->id);

                    $soloWon = $soloLost = $soloTied = 0;
                    $carried = $rescued = 0;
                    $holesPlayed = 0;

                    for ($h = $start; $h <= $end; $h++) {
                        $me = $getNet($mp->id, $h);
                        if ($me >= 999) {
                            continue;
                        }
                        $holesPlayed++;

                        if ($m->scoring_type === 'individual_match_play') {
                            $opp = $them[$idx] ?? null;
                            $oppNet = $opp ? $getNet($opp->id, $h) : 999;
                            if ($me < $oppNet) $soloWon++;
                            elseif ($me > $oppNet) $soloLost++;
                            else $soloTied++;
                        } else {
                            // best-ball / team: solo = me vs opponents' best ball
                            $oppBest = 999;
                            foreach ($them as $o) {
                                $oppBest = min($oppBest, $getNet($o->id, $h));
                            }
                            $pNet = $partner ? $getNet($partner->id, $h) : 999;
                            $teamBest = min($me, $pNet);

                            if ($me < $oppBest) $soloWon++;
                            elseif ($me > $oppBest) $soloLost++;
                            else $soloTied++;

                            if ($teamBest < $oppBest) {
                                if ($me <= $oppBest && $me <= $pNet) {
                                    $carried++; // player's score won/held the hole for the team
                                }
                                if ($me > $oppBest && $pNet < $oppBest) {
                                    $rescued++; // partner won it; player alone would have lost
                                }
                            }
                        }
                    }

                    if ($holesPlayed === 0) {
                        continue;
                    }

                    $tr = $m->result;
                    if (!$tr || $tr->winning_team_id === null) {
                        $teamPts = 0.5;
                    } elseif ((int) $tr->winning_team_id === (int) $mp->team_id) {
                        $teamPts = 1.0;
                    } else {
                        $teamPts = 0.0;
                    }

                    $records[] = [
                        'match_id' => $m->id,
                        'week' => $m->week_number,
                        'pid' => $pid[$mp->id],
                        'name' => $name[$mp->id],
                        'partner_pid' => $partner ? $pid[$partner->id] : null,
                        'is_sub' => $isSub[$mp->id],
                        'solo_pts' => $this->resultPoints($soloWon, $soloLost),
                        'team_pts' => $teamPts,
                        'solo_w' => $soloWon,
                        'solo_l' => $soloLost,
                        'solo_t' => $soloTied,
                        'carried' => $carried,
                        'rescued' => $rescued,
                        'holes' => $holesPlayed,
                    ];
                }
            }
        }

        // index solo points by match+player for partner lookup
        $soloByKey = [];
        foreach ($records as $r) {
            $soloByKey[$r['match_id'] . '|' . $r['pid']] = $r['solo_pts'];
        }

        // aggregate per player
        $agg = [];
        foreach ($records as $r) {
            $key = $r['pid'];
            if (!isset($agg[$key])) {
                $agg[$key] = [
                    'name' => $r['name'], 'matches' => 0, 'sub_apps' => 0,
                    'solo_pts' => 0.0, 'team_pts' => 0.0, 'partner_solo_pts' => 0.0,
                    'solo_w' => 0, 'solo_l' => 0, 'solo_t' => 0,
                    'carried' => 0, 'rescued' => 0,
                ];
            }
            $a = &$agg[$key];
            $a['name'] = $r['name'];
            $a['matches']++;
            if ($r['is_sub']) $a['sub_apps']++;
            $a['solo_pts'] += $r['solo_pts'];
            $a['team_pts'] += $r['team_pts'];
            $a['solo_w'] += $r['solo_w'];
            $a['solo_l'] += $r['solo_l'];
            $a['solo_t'] += $r['solo_t'];
            $a['carried'] += $r['carried'];
            $a['rescued'] += $r['rescued'];
            $pk = $r['match_id'] . '|' . $r['partner_pid'];
            $a['partner_solo_pts'] += $soloByKey[$pk] ?? 0.5;
            unset($a);
        }

        $rows = [];
        foreach ($agg as $a) {
            if ($a['matches'] === 0) {
                continue;
            }
            $soloAvg = $a['solo_pts'] / $a['matches'];
            $teamAvg = $a['team_pts'] / $a['matches'];
            $partnerAvg = $a['partner_solo_pts'] / $a['matches'];
            $rows[] = [
                'name' => $a['name'],
                'matches' => $a['matches'],
                'sub_apps' => $a['sub_apps'],
                'solo_avg' => $soloAvg,
                'team_avg' => $teamAvg,
                'partner_avg' => $partnerAvg,
                'vs_partner' => $soloAvg - $partnerAvg,
                'carry_gap' => $teamAvg - $soloAvg,
                'carried' => $a['carried'],
                'rescued' => $a['rescued'],
                'solo_wlt' => $a['solo_w'] . '-' . $a['solo_l'] . '-' . $a['solo_t'],
            ];
        }

        // carriers (highest vs-partner) first
        usort($rows, fn ($x, $y) => $y['vs_partner'] <=> $x['vs_partner']);

        return [
            'rows' => $rows,
            'weeks' => count($weeks),
            'matches' => $matches->count(),
            'hasData' => count($rows) > 0,
        ];
    }

    private function buildStrokeMap(int $ch18, $allCourseInfo): array
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

    private function resultPoints(int $won, int $lost): float
    {
        if ($won > $lost) return 1.0;
        if ($lost > $won) return 0.0;
        return 0.5;
    }
}

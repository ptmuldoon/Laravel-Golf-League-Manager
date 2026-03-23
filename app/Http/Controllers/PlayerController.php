<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Round;
use App\Models\GolfCourse;
use App\Models\Score;
use App\Services\HandicapCalculator;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index()
    {
        $players = Player::withCount('rounds')->orderBy('last_name')->get();
        return view('players.index', compact('players'));
    }

    public function show($id, Request $request)
    {
        $player = Player::with(['rounds.golfCourse', 'handicapHistory'])->findOrFail($id);
        $currentHandicap = $player->currentHandicap();

        // Get date filter (default to 'all')
        $filter = $request->get('filter', 'all');

        // Apply date filter
        $query = $player->rounds();
        switch ($filter) {
            case '7days':
                $query->where('played_at', '>=', now()->subDays(7));
                break;
            case '30days':
                $query->where('played_at', '>=', now()->subDays(30));
                break;
            case '90days':
                $query->where('played_at', '>=', now()->subDays(90));
                break;
            case 'year':
                $query->where('played_at', '>=', now()->subYear());
                break;
        }

        // Calculate total rounds and average score for each round
        $calculator = app(HandicapCalculator::class);
        $currentHI = $currentHandicap ? (float) $currentHandicap->handicap_index : null;

        $rounds = $query->with(['golfCourse', 'scores'])->get()->map(function ($round) use ($calculator, $currentHI) {
            $round->total_score = $round->scores->sum('strokes');

            $hasNetScores = $round->scores->contains(fn($s) => $s->net_score !== null);
            $round->net_score = $hasNetScores ? $round->scores->sum('net_score') : null;

            $isNineHole = ($round->holes_played ?? 18) == 9;
            $slopeRating = $calculator->getSlopeAndRating($round);
            $hasStoredAG = $round->scores->contains(fn($s) => $s->adjusted_gross !== null);

            if ($slopeRating && $hasStoredAG) {
                $totalAG = $round->scores->sum('adjusted_gross');
                if ($isNineHole && $currentHI !== null) {
                    $diff9 = $calculator->scoreDifferential9($totalAG, $slopeRating['rating'], $slopeRating['slope']);
                    $round->scoring_differential = round($diff9 + $calculator->expectedNineHoleDifferential($currentHI), 1);
                } elseif (!$isNineHole) {
                    $round->scoring_differential = round($calculator->scoreDifferential18($totalAG, $slopeRating['rating'], $slopeRating['slope']), 1);
                } else {
                    $round->scoring_differential = null;
                }
            } else {
                // Fallback: re-derive from raw strokes when adjusted_gross not stored
                $roundDiff = $calculator->computeRoundDifferential($round, $currentHI);
                if ($roundDiff && $roundDiff['is_nine_hole'] && $currentHI !== null) {
                    $round->scoring_differential = round($roundDiff['differential'] + $calculator->expectedNineHoleDifferential($currentHI), 1);
                } elseif ($roundDiff && !$roundDiff['is_nine_hole']) {
                    $round->scoring_differential = round($roundDiff['differential'], 1);
                } else {
                    $round->scoring_differential = null;
                }
            }

            // Determine if it's front 9 or back 9 for 9-hole rounds
            if ($round->holes_played == 9) {
                $holeNumbers = $round->scores->pluck('hole_number')->toArray();
                if (!empty($holeNumbers)) {
                    $round->nine_type = max($holeNumbers) <= 9 ? 'Front 9' : 'Back 9';
                } else {
                    $round->nine_type = '9 holes';
                }
            }

            return $round;
        });

        // Prepare chart data (sorted by date)
        $chartData = $rounds->sortBy('played_at')->map(function ($round) {
            return [
                'date' => \Carbon\Carbon::parse($round->played_at)->format('M d, Y'),
                'score' => $round->total_score,
                'course' => $round->golfCourse->name,
                'holes' => $round->holes_played ?? 18,
            ];
        })->values();

        // Prepare handicap history chart data (filtered by same date range)
        $handicapQuery = $player->handicapHistory()->orderBy('calculation_date');
        switch ($filter) {
            case '7days':
                $handicapQuery->where('calculation_date', '>=', now()->subDays(7));
                break;
            case '30days':
                $handicapQuery->where('calculation_date', '>=', now()->subDays(30));
                break;
            case '90days':
                $handicapQuery->where('calculation_date', '>=', now()->subDays(90));
                break;
            case 'year':
                $handicapQuery->where('calculation_date', '>=', now()->subYear());
                break;
        }
        $handicapChartData = $handicapQuery->get()->map(function ($h) {
            $diffs = $h->score_differentials;
            return [
                'date' => \Carbon\Carbon::parse($h->calculation_date)->format('M d, Y'),
                'handicap' => (float) $h->handicap_index,
                'rounds_used' => $h->rounds_used,
                'total_differentials' => is_array($diffs) ? count($diffs) : 0,
            ];
        })->values();

        return view('players.show', compact('player', 'rounds', 'chartData', 'handicapChartData', 'filter', 'currentHandicap'));
    }

    public function showRound($playerId, $roundId)
    {
        $player = Player::findOrFail($playerId);
        $round = Round::with(['golfCourse', 'scores'])->findOrFail($roundId);

        // Get course info for the played teebox
        $courseInfo = $round->golfCourse->courseInfo()
            ->where('teebox', $round->teebox)
            ->orderBy('hole_number')
            ->get();

        // Organize scores with par information
        $scorecard = $round->scores->map(function ($score) use ($courseInfo) {
            $hole = $courseInfo->firstWhere('hole_number', $score->hole_number);
            return [
                'hole_number' => $score->hole_number,
                'par' => $hole ? $hole->par : null,
                'strokes' => $score->strokes,
                'score' => $hole ? $score->strokes - $hole->par : null,
            ];
        });

        // Calculate course handicaps (18-hole and 9-hole) for this course/teebox
        $courseHandicap18 = null;
        $courseHandicap9 = null;
        $currentHandicap = $player->currentHandicap();
        if ($currentHandicap && $courseInfo->isNotEmpty()) {
            $handicapIndex = (float) $currentHandicap->handicap_index;
            $firstHole = $courseInfo->first();

            // 18-hole: (Index × Slope / 113) + (Rating - Par)
            $slope18 = (float) $firstHole->slope;
            $rating18 = (float) $firstHole->rating;
            $par18 = $courseInfo->sum('par');
            $courseHandicap18 = round(($handicapIndex * $slope18 / 113) + ($rating18 - $par18));

            // 9-hole: half of 18-hole course handicap (WHS standard)
            $courseHandicap9 = round($courseHandicap18 / 2);
        }

        return view('players.round', compact('player', 'round', 'scorecard', 'courseHandicap18', 'courseHandicap9'));
    }

    public function createScorecard()
    {
        $players = Player::orderBy('last_name')->orderBy('first_name')->get();
        $courses = GolfCourse::with('courseInfo')->orderBy('name')->get();

        return view('scorecard.create', compact('players', 'courses'));
    }

    public function storeScorecard(Request $request)
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'golf_course_id' => 'required|exists:golf_courses,id',
            'teebox' => 'required|string',
            'played_at' => 'required|date',
            'entry_type' => 'required|in:hole_by_hole,total_only',
            'holes_played' => 'required|in:9,18',
            'nine_type' => 'nullable|in:front,back',
            'total_score' => 'required_if:entry_type,total_only|nullable|integer|min:18|max:200',
            'scores' => 'required_if:entry_type,hole_by_hole|nullable|array',
            'scores.*' => 'integer|min:1|max:15',
        ]);

        // Create the round
        $round = Round::create([
            'player_id' => $validated['player_id'],
            'golf_course_id' => $validated['golf_course_id'],
            'teebox' => $validated['teebox'],
            'holes_played' => (int) $validated['holes_played'],
            'played_at' => $validated['played_at'],
        ]);

        if ($validated['entry_type'] === 'hole_by_hole') {
            // Store hole-by-hole scores
            foreach ($validated['scores'] as $holeNumber => $strokes) {
                if ($strokes !== null && $strokes > 0) {
                    Score::create([
                        'round_id' => $round->id,
                        'hole_number' => $holeNumber,
                        'strokes' => $strokes,
                    ]);
                }
            }
        } else {
            // Total score only - distribute evenly across holes
            $totalScore = $validated['total_score'];
            $holesPlayed = (int) $validated['holes_played'];

            // Get course par for the holes
            $startHole = 1;
            $endHole = $holesPlayed;

            if ($holesPlayed == 9) {
                $startHole = $validated['nine_type'] === 'back' ? 10 : 1;
                $endHole = $validated['nine_type'] === 'back' ? 18 : 9;
            }

            // Get pars for these holes
            $coursePars = \DB::table('course_info')
                ->where('golf_course_id', $validated['golf_course_id'])
                ->where('teebox', $validated['teebox'])
                ->whereBetween('hole_number', [$startHole, $endHole])
                ->orderBy('hole_number')
                ->pluck('par', 'hole_number');

            $totalPar = $coursePars->sum();
            $scoreDiff = $totalScore - $totalPar;
            $holeCount = $coursePars->count();

            // Distribute the score difference evenly across holes
            $perHole = $holeCount > 0 ? intdiv($scoreDiff, $holeCount) : 0;
            $remainder = $holeCount > 0 ? abs($scoreDiff) - abs($perHole) * $holeCount : 0;

            foreach ($coursePars as $holeNumber => $par) {
                $strokes = $par + $perHole;
                if ($remainder > 0) {
                    $strokes += ($scoreDiff >= 0 ? 1 : -1);
                    $remainder--;
                }
                $strokes = max(1, $strokes); // Minimum 1 stroke per hole

                Score::create([
                    'round_id' => $round->id,
                    'hole_number' => $holeNumber,
                    'strokes' => $strokes,
                ]);
            }
        }

        // Compute and store per-hole adjusted gross and net scores
        $round->load('scores');
        $player = Player::findOrFail($validated['player_id']);
        $calculator = app(HandicapCalculator::class);

        $slopeRating = $calculator->getSlopeAndRating($round);
        $courseHandicap = null;
        $currentHandicap = $player->currentHandicap();
        if ($currentHandicap && $slopeRating) {
            $hi = (float) $currentHandicap->handicap_index;
            $courseHandicap = ($hi * $slopeRating['slope']) / 113;
            if (($round->holes_played ?? 18) == 9) {
                $courseHandicap = $courseHandicap / 2;
            }
        }

        $perHoleScores = $calculator->calculatePerHoleScores($round, $courseHandicap);
        foreach ($perHoleScores as $holeNumber => $holeData) {
            Score::where('round_id', $round->id)
                ->where('hole_number', $holeNumber)
                ->update([
                    'adjusted_gross' => $holeData['adjusted_gross'],
                    'net_score' => $holeData['net_score'],
                ]);
        }

        // Recalculate handicap
        $calculator->recalculateForPlayer($player);

        return redirect()->route('players.round', [$validated['player_id'], $round->id])
            ->with('success', 'Scorecard entered successfully!');
    }
}

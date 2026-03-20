<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use App\Models\LeagueMatch;
use App\Models\MatchPlayer;
use App\Models\MatchScore;
use App\Models\Player;
use App\Models\Round;
use App\Models\Score;
use App\Models\SiteSetting;
use App\Models\HandicapHistory;
use App\Services\HandicapCalculator;
use App\Services\MatchPlayCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlayerDashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $player = $user->player;

        $currentHandicap = $player->currentHandicap();

        // Get player's upcoming and recent matches
        $matchPlayerIds = MatchPlayer::where('player_id', $player->id)
            ->orWhere('substitute_player_id', $player->id)
            ->pluck('id');

        $upcomingMatches = LeagueMatch::whereHas('matchPlayers', function ($q) use ($player) {
                $q->where('player_id', $player->id)
                  ->orWhere('substitute_player_id', $player->id);
            })
            ->where('status', 'scheduled')
            ->with(['homeTeam', 'awayTeam', 'golfCourse', 'league'])
            ->orderBy('match_date')
            ->limit(10)
            ->get();

        $recentMatches = LeagueMatch::whereHas('matchPlayers', function ($q) use ($player) {
                $q->where('player_id', $player->id)
                  ->orWhere('substitute_player_id', $player->id);
            })
            ->where('status', 'completed')
            ->with(['homeTeam', 'awayTeam', 'golfCourse', 'league', 'matchPlayers.scores'])
            ->orderByDesc('match_date')
            ->limit(10)
            ->get();

        // Get player's recent rounds
        $recentRounds = $player->rounds()
            ->with(['golfCourse', 'scores'])
            ->orderByDesc('played_at')
            ->limit(10)
            ->get()
            ->map(function ($round) {
                $round->total_score = $round->scores->sum('strokes');
                return $round;
            });

        $leagues = $player->leagues()->where('is_active', true)->get();

        $scorePostingEnabled = SiteSetting::get('player_score_posting_enabled', '1') === '1';

        return view('player.dashboard', compact(
            'player', 'currentHandicap', 'upcomingMatches', 'recentMatches',
            'recentRounds', 'leagues', 'scorePostingEnabled'
        ));
    }

    public function scoreEntry()
    {
        if (SiteSetting::get('player_score_posting_enabled', '1') !== '1') {
            return redirect()->route('player.dashboard')->with('error', 'Score posting is currently disabled.');
        }

        $user = Auth::user();
        $player = $user->player;
        $courses = GolfCourse::with('courseInfo')->orderBy('name')->get();

        return view('player.score-entry', compact('player', 'courses'));
    }

    public function storeScore(Request $request)
    {
        if (SiteSetting::get('player_score_posting_enabled', '1') !== '1') {
            return redirect()->route('player.dashboard')->with('error', 'Score posting is currently disabled.');
        }

        $user = Auth::user();
        $player = $user->player;

        $validated = $request->validate([
            'golf_course_id' => 'required|exists:golf_courses,id',
            'teebox' => 'required|string',
            'played_at' => 'required|date|before_or_equal:today',
            'entry_type' => 'required|in:hole_by_hole,total_only',
            'holes_played' => 'required|in:9,18',
            'nine_type' => 'nullable|in:front,back',
            'total_score' => 'required_if:entry_type,total_only|nullable|integer|min:18|max:200',
            'scores' => 'required_if:entry_type,hole_by_hole|nullable|array',
            'scores.*' => 'integer|min:1|max:15',
        ]);

        // Create the round
        $round = Round::create([
            'player_id' => $player->id,
            'golf_course_id' => $validated['golf_course_id'],
            'teebox' => $validated['teebox'],
            'holes_played' => (int) $validated['holes_played'],
            'played_at' => $validated['played_at'],
        ]);

        if ($validated['entry_type'] === 'hole_by_hole') {
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
            $totalScore = $validated['total_score'];
            $holesPlayed = (int) $validated['holes_played'];

            $startHole = 1;
            $endHole = $holesPlayed;

            if ($holesPlayed == 9) {
                $startHole = $validated['nine_type'] === 'back' ? 10 : 1;
                $endHole = $validated['nine_type'] === 'back' ? 18 : 9;
            }

            $coursePars = DB::table('course_info')
                ->where('golf_course_id', $validated['golf_course_id'])
                ->where('teebox', $validated['teebox'])
                ->whereBetween('hole_number', [$startHole, $endHole])
                ->orderBy('hole_number')
                ->pluck('par', 'hole_number');

            $totalPar = $coursePars->sum();
            $scoreDiff = $totalScore - $totalPar;
            $holeCount = $coursePars->count();

            $perHole = $holeCount > 0 ? intdiv($scoreDiff, $holeCount) : 0;
            $remainder = $holeCount > 0 ? abs($scoreDiff) - abs($perHole) * $holeCount : 0;

            foreach ($coursePars as $holeNumber => $par) {
                $strokes = $par + $perHole;
                if ($remainder > 0) {
                    $strokes += ($scoreDiff >= 0 ? 1 : -1);
                    $remainder--;
                }
                $strokes = max(1, $strokes);

                Score::create([
                    'round_id' => $round->id,
                    'hole_number' => $holeNumber,
                    'strokes' => $strokes,
                ]);
            }
        }

        // Compute per-hole adjusted gross and net scores
        $round->load('scores');
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
        $snapshots = $calculator->computeHistoricalHandicaps($player);
        if (!empty($snapshots)) {
            HandicapHistory::where('player_id', $player->id)->delete();
            $byDate = [];
            foreach ($snapshots as $snapshot) {
                $byDate[$snapshot['calculation_date']] = $snapshot;
            }
            foreach ($byDate as $date => $snapshot) {
                HandicapHistory::create([
                    'player_id' => $player->id,
                    'calculation_date' => $date,
                    'handicap_index' => $snapshot['handicap_index'],
                    'rounds_used' => $snapshot['rounds_used'],
                    'score_differentials' => $snapshot['score_differentials'],
                ]);
            }
        }

        return redirect()->route('players.round', [$player->id, $round->id])
            ->with('success', 'Scorecard submitted successfully!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\LeagueMessageEmail;
use App\Mail\WeeklyResultsEmail;
use App\Models\League;
use App\Models\LeagueMatch;
use App\Models\LeagueSegment;
use App\Models\CourseInfo;
use App\Models\GolfCourse;
use App\Models\MatchPlayer;
use App\Models\MatchResult;
use App\Models\MatchScore;
use App\Models\Par3Winner;
use App\Models\ScoringSetting;
use App\Models\HandicapHistory;
use App\Models\Player;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;
use App\Services\HandicapCalculator;
use App\Services\MatchPlayCalculator;
use App\Services\SmsService;
use Database\Seeders\ScoringSettingsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LeagueController extends Controller
{
    /**
     * Display a listing of leagues
     */
    public function index()
    {
        $leagues = League::with('golfCourse', 'teams')
            ->withCount('teams')
            ->orderBy('is_active', 'desc')
            ->orderBy('start_date', 'desc')
            ->get();

        // League IDs that have recorded scores (cannot be deleted)
        $leaguesWithScores = DB::table('match_scores')
            ->join('match_players', 'match_scores.match_player_id', '=', 'match_players.id')
            ->join('matches', 'match_players.match_id', '=', 'matches.id')
            ->whereIn('matches.league_id', $leagues->pluck('id'))
            ->distinct()
            ->pluck('matches.league_id')
            ->toArray();

        return view('leagues.index', compact('leagues', 'leaguesWithScores'));
    }

    /**
     * Show the form for creating a new league
     */
    public function create()
    {
        $courses = GolfCourse::with('courseInfo')->orderBy('name')->get();
        return view('leagues.create', compact('courses'));
    }

    /**
     * Store a newly created league
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'golf_course_id' => 'required|exists:golf_courses,id',
            'default_teebox' => 'required|string',
            'default_tee_time' => 'nullable|date_format:H:i',
            'tee_time_interval' => 'nullable|integer|min:1|max:30',
            'fee_per_player' => 'nullable|numeric|min:0',
            'par3_payout' => 'nullable|numeric|min:0',
            'payout_1st_pct' => 'nullable|numeric|min:0|max:100',
            'payout_2nd_pct' => 'nullable|numeric|min:0|max:100',
            'payout_3rd_pct' => 'nullable|numeric|min:0|max:100',
            'sub_request_code' => 'nullable|string|max:50',
        ]);

        $league = League::create($validated);

        ScoringSettingsSeeder::seedForLeague($league->id);

        return redirect()->route('admin.leagues.show', $league->id)
            ->with('success', 'League created successfully!');
    }

    /**
     * Display the specified league with teams and standings
     */
    public function show($id)
    {
        $league = League::with([
            'golfCourse',
            'segments.teams.players',
            'segments.teams.captain',
            'teams.players',
            'teams.captain',
            'matches.homeTeam',
            'matches.awayTeam',
            'matches.golfCourse',
            'matches.result.winningTeam'
        ])->findOrFail($id);

        // Calculate standings per segment or league-wide
        $standingsBySegment = [];
        if ($league->segments->isNotEmpty()) {
            foreach ($league->segments as $segment) {
                $standingsBySegment[$segment->id] = $segment->teams()
                    ->withCount('players')
                    ->get()
                    ->sortByDesc(fn($team) => $team->totalPoints())
                    ->values();
            }
            $teams = collect(); // Empty; shown per segment instead
        } else {
            $teams = $league->teams()
                ->withCount('players')
                ->get()
                ->sortByDesc(fn($team) => $team->totalPoints())
                ->values();
        }

        // Get matches by week
        $matchesByWeek = $league->matches()
            ->with(['homeTeam', 'awayTeam', 'result.winningTeam', 'matchPlayers.scores', 'matchPlayers.player'])
            ->orderBy('week_number')
            ->orderBy('match_date')
            ->get()
            ->groupBy('week_number');

        // Build week-to-segment map for display
        $weekSegmentMap = [];
        foreach ($league->segments as $segment) {
            for ($w = $segment->start_week; $w <= $segment->end_week; $w++) {
                $weekSegmentMap[$w] = $segment->name;
            }
        }

        // Load par 3 winners grouped by week
        $par3WinnersByWeek = $league->par3Winners()
            ->with('player')
            ->orderBy('week_number')
            ->orderBy('hole_number')
            ->get()
            ->groupBy('week_number');

        $emailConfigured = !empty(config('mail.mailers.smtp.username'));
        $smsConfigured = !empty(config('services.vonage.key')) && !empty(config('services.vonage.secret')) && !empty(config('services.vonage.sms_from'));

        return view('leagues.show', compact('league', 'teams', 'matchesByWeek', 'standingsBySegment', 'weekSegmentMap', 'par3WinnersByWeek', 'emailConfigured', 'smsConfigured'));
    }

    /**
     * Show the form for editing the league
     */
    public function edit($id)
    {
        $league = League::findOrFail($id);
        $courses = GolfCourse::with('courseInfo')->orderBy('name')->get();

        return view('leagues.edit', compact('league', 'courses'));
    }

    /**
     * Update the specified league
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'golf_course_id' => 'required|exists:golf_courses,id',
            'default_teebox' => 'required|string',
            'default_tee_time' => 'nullable|date_format:H:i',
            'tee_time_interval' => 'nullable|integer|min:1|max:30',
            'is_active' => 'boolean',
            'fee_per_player' => 'nullable|numeric|min:0',
            'par3_payout' => 'nullable|numeric|min:0',
            'payout_1st_pct' => 'nullable|numeric|min:0|max:100',
            'payout_2nd_pct' => 'nullable|numeric|min:0|max:100',
            'payout_3rd_pct' => 'nullable|numeric|min:0|max:100',
            'sub_request_code' => 'nullable|string|max:50',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $league = League::findOrFail($id);
        $league->update($validated);

        return redirect()->route('admin.leagues.show', $id)
            ->with('success', 'League updated successfully!');
    }

    /**
     * Remove the specified league (only if no scores have been recorded)
     */
    public function destroy($id)
    {
        $league = League::findOrFail($id);

        $hasScores = MatchScore::whereHas('matchPlayer', function ($q) use ($league) {
            $q->whereHas('match', function ($q2) use ($league) {
                $q2->where('league_id', $league->id);
            });
        })->exists();

        if ($league->is_active && $hasScores) {
            return redirect()->back()
                ->with('error', "Cannot delete '{$league->name}' because it is an active league with scores recorded.");
        }

        if ($hasScores) {
            return redirect()->back()
                ->with('error', "Cannot delete '{$league->name}' because scores have been recorded. Remove all scores first.");
        }

        $leagueName = $league->name;
        $league->delete();

        return redirect()->back()
            ->with('success', "League '{$leagueName}' deleted successfully!");
    }

    /**
     * Duplicate a league with its teams, players, and scoring settings
     */
    public function duplicate($id)
    {
        $source = League::with(['teams.players', 'players', 'scoringSettings', 'segments.teams.players'])->findOrFail($id);

        $newLeague = DB::transaction(function () use ($source) {
            // Create new league
            $newLeague = League::create([
                'name' => $source->name . ' (Copy)',
                'season' => $source->season,
                'start_date' => $source->start_date,
                'end_date' => $source->end_date,
                'golf_course_id' => $source->golf_course_id,
                'default_teebox' => $source->default_teebox,
                'default_tee_time' => $source->default_tee_time,
                'tee_time_interval' => $source->tee_time_interval,
                'is_active' => false,
                'fee_per_player' => $source->fee_per_player,
                'par3_payout' => $source->par3_payout,
                'payout_1st_pct' => $source->payout_1st_pct,
                'payout_2nd_pct' => $source->payout_2nd_pct,
                'payout_3rd_pct' => $source->payout_3rd_pct,
            ]);

            // Duplicate league players
            $playerIds = $source->players->pluck('id')->toArray();
            $newLeague->players()->attach($playerIds);

            // Duplicate segments and their teams
            if ($source->segments->isNotEmpty()) {
                foreach ($source->segments as $segment) {
                    $newSegment = LeagueSegment::create([
                        'league_id' => $newLeague->id,
                        'name' => $segment->name,
                        'start_week' => $segment->start_week,
                        'end_week' => $segment->end_week,
                        'display_order' => $segment->display_order,
                    ]);
                    foreach ($segment->teams as $team) {
                        $newTeam = Team::create([
                            'league_id' => $newLeague->id,
                            'league_segment_id' => $newSegment->id,
                            'name' => $team->name,
                            'captain_id' => $team->captain_id,
                            'wins' => 0, 'losses' => 0, 'ties' => 0,
                        ]);
                        $newTeam->players()->attach($team->players->pluck('id'));
                    }
                }
            }

            // Duplicate non-segment teams
            foreach ($source->teams->whereNull('league_segment_id') as $team) {
                $newTeam = Team::create([
                    'league_id' => $newLeague->id,
                    'name' => $team->name,
                    'captain_id' => $team->captain_id,
                    'wins' => 0,
                    'losses' => 0,
                    'ties' => 0,
                ]);
                $teamPlayerIds = $team->players->pluck('id')->toArray();
                $newTeam->players()->attach($teamPlayerIds);
            }

            // Duplicate scoring settings
            foreach ($source->scoringSettings as $setting) {
                ScoringSetting::create([
                    'league_id' => $newLeague->id,
                    'scoring_type' => $setting->scoring_type,
                    'outcome' => $setting->outcome,
                    'points' => $setting->points,
                    'description' => $setting->description,
                ]);
            }

            return $newLeague;
        });

        return redirect()->route('admin.leagues.show', $newLeague->id)
            ->with('success', "League duplicated from '{$source->name}' successfully!");
    }

    /**
     * Show team management page
     */
    public function manageTeams($leagueId)
    {
        $league = League::with(['teams.players', 'segments'])->findOrFail($leagueId);

        // Only show players assigned to this league
        $allPlayers = $league->players()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $segments = $league->segments;
        $selectedSegment = null;

        if ($segments->isNotEmpty()) {
            $segmentId = request()->query('segment', $segments->first()->id);
            $selectedSegment = $segments->firstWhere('id', (int) $segmentId);
            if (!$selectedSegment) {
                $selectedSegment = $segments->first();
            }
            // Filter teams to this segment only
            $league->setRelation('teams',
                $league->teams->where('league_segment_id', $selectedSegment->id)->values()
            );
        }

        return view('leagues.manage-teams', compact('league', 'allPlayers', 'segments', 'selectedSegment'));
    }

    /**
     * Show auto-schedule generator
     */
    public function showAutoSchedule($leagueId)
    {
        $league = League::with(['golfCourse', 'segments.teams'])->findOrFail($leagueId);
        $playerCount = $league->players()->count();
        $scoringTypes = ScoringSetting::scoringTypes();
        $segments = $league->segments;

        return view('leagues.auto-schedule', compact('league', 'playerCount', 'scoringTypes', 'segments'));
    }

    /**
     * Generate auto-schedule preview
     */
    public function generateAutoSchedule(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'weeks' => 'required|integer|min:1|max:52',
            'start_date' => 'required|date',
            'holes' => 'required|in:front_9,back_9',
            'scoring_type' => 'required|in:' . implode(',', array_keys(ScoringSetting::scoringTypes())),
            'score_mode' => 'required|in:gross,net',
            'start_tee_time' => 'required|date_format:H:i',
            'tee_time_interval' => 'required|integer|min:5|max:30',
            'segment_id' => 'nullable|exists:league_segments,id',
        ]);

        $league = League::with('teams.players')->findOrFail($leagueId);
        $scheduler = new \App\Services\LeagueScheduler(app(\App\Services\MatchPlayCalculator::class));

        $segment = null;
        if (!empty($validated['segment_id'])) {
            $segment = LeagueSegment::with('teams.players')->findOrFail($validated['segment_id']);
        }

        try {
            $scheduleData = $scheduler->generateSchedule($league, $validated['weeks'], $segment);

            // Store schedule and segment in session so save uses the exact same data
            session(['schedule_preview_' . $leagueId => $scheduleData]);
            if ($segment) {
                session(['schedule_segment_' . $leagueId => $segment->id]);
            }

            // Build player ID -> team name map
            $teams = $segment ? $segment->teams : $league->teams;
            $playerTeamNames = [];
            foreach ($teams as $team) {
                foreach ($team->players as $player) {
                    $playerTeamNames[$player->id] = $team->name;
                }
            }

            return view('leagues.schedule-preview', [
                'league' => $league,
                'scheduleData' => $scheduleData,
                'weeks' => $validated['weeks'],
                'startDate' => $validated['start_date'],
                'holes' => $validated['holes'],
                'scoringType' => $validated['scoring_type'],
                'scoreMode' => $validated['score_mode'],
                'scoringTypes' => ScoringSetting::scoringTypes(),
                'playerTeamNames' => $playerTeamNames,
                'startTeeTime' => $validated['start_tee_time'],
                'teeTimeInterval' => $validated['tee_time_interval'],
                'segmentId' => $segment?->id,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('admin.leagues.autoSchedule', $leagueId)
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Save auto-schedule to database
     */
    public function saveAutoSchedule(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'weeks' => 'required|integer|min:1|max:52',
            'start_date' => 'required|date',
            'holes' => 'required|in:front_9,back_9',
            'scoring_type' => 'required|in:' . implode(',', array_keys(ScoringSetting::scoringTypes())),
            'score_mode' => 'required|in:gross,net',
            'start_tee_time' => 'required|date_format:H:i',
            'tee_time_interval' => 'required|integer|min:5|max:30',
            'segment_id' => 'nullable|exists:league_segments,id',
        ]);

        $league = League::with('teams.players')->findOrFail($leagueId);
        $scheduler = new \App\Services\LeagueScheduler(app(\App\Services\MatchPlayCalculator::class));

        $segment = null;
        $segmentId = $validated['segment_id'] ?? session('schedule_segment_' . $leagueId);
        if ($segmentId) {
            $segment = LeagueSegment::with('teams.players')->findOrFail($segmentId);
        }

        try {
            // Use the previewed schedule from session if available, otherwise regenerate
            $scheduleData = session('schedule_preview_' . $leagueId);
            if (!$scheduleData) {
                $scheduleData = $scheduler->generateSchedule($league, $validated['weeks'], $segment);
            }
            session()->forget('schedule_preview_' . $leagueId);
            session()->forget('schedule_segment_' . $leagueId);

            // Delete existing scheduled matches (only for the segment's week range if applicable)
            if ($segment) {
                $league->matches()
                    ->where('status', 'scheduled')
                    ->whereBetween('week_number', [$segment->start_week, $segment->end_week])
                    ->delete();
                $weekOffset = $segment->start_week - 1;
            } else {
                $league->matches()->where('status', 'scheduled')->delete();
                $weekOffset = 0;
            }

            $startDate = new \DateTime($validated['start_date']);
            $scheduler->saveSchedule($league, $scheduleData, $startDate, $validated['holes'], $validated['scoring_type'], $validated['start_tee_time'], (int) $validated['tee_time_interval'], $weekOffset, $validated['score_mode'], $segment);

            return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
                ->with('success', "Successfully generated {$validated['weeks']}-week schedule!");
        } catch (\Exception $e) {
            return redirect()->route('admin.leagues.autoSchedule', $leagueId)
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show schedule overview with edit capabilities
     */
    public function scheduleOverview($leagueId)
    {
        $league = League::with([
            'matches' => function ($query) {
                $query->orderBy('week_number')
                    ->orderBy('tee_time')
                    ->orderBy('match_date')
                    ->with('matchPlayers.player', 'matchPlayers.substitutePlayer', 'golfCourse', 'homeTeam', 'awayTeam');
            },
            'players',
            'teams.players',
            'segments'
        ])->findOrFail($leagueId);

        $matchesByWeek = $league->matches->groupBy('week_number');
        $scoringTypes = ScoringSetting::scoringTypes();

        // Build player ID -> team name map and per-team player lists
        $playerTeamNames = [];
        $playerTeamIds = [];
        $teamPlayersMap = [];
        foreach ($league->teams as $team) {
            $teamPlayersMap[$team->id] = $team->players->sortBy(['first_name', 'last_name'])->values();
            foreach ($team->players as $player) {
                $playerTeamNames[$player->id] = $team->name;
                $playerTeamIds[$player->id] = $team->id;
            }
        }

        // Compute handicap-as-of-date for each match player (use substitute's handicap when present)
        $playerIds = $league->matches->flatMap(function ($m) {
            return $m->matchPlayers->pluck('player_id');
        })->unique()->values();

        $subPlayerIds = $league->matches->flatMap(function ($m) {
            return $m->matchPlayers->pluck('substitute_player_id');
        })->filter()->unique()->values();

        $allPlayerIds = $playerIds->merge($subPlayerIds)->unique();

        $allHandicapHistory = HandicapHistory::whereIn('player_id', $allPlayerIds)
            ->orderByDesc('calculation_date')
            ->get()
            ->groupBy('player_id');

        $matchPlayerHandicaps = [];
        foreach ($league->matches as $match) {
            foreach ($match->matchPlayers as $mp) {
                $effectivePlayerId = $mp->substitute_player_id ?? $mp->player_id;
                $history = $allHandicapHistory[$effectivePlayerId] ?? collect();
                $record = $history->where('calculation_date', '<=', $match->match_date)->first();
                $matchPlayerHandicaps[$mp->id] = $record
                    ? (float) $record->handicap_index
                    : (float) $mp->handicap_index;
            }
        }

        // Build week-to-segment map
        $weekSegmentMap = [];
        foreach ($league->segments as $segment) {
            for ($w = $segment->start_week; $w <= $segment->end_week; $w++) {
                $weekSegmentMap[$w] = $segment->name;
            }
        }

        return view('leagues.schedule-overview', compact('league', 'matchesByWeek', 'scoringTypes', 'playerTeamNames', 'playerTeamIds', 'teamPlayersMap', 'matchPlayerHandicaps', 'weekSegmentMap'));
    }

    /**
     * Update holes, scoring type, and date for all matches in a given week
     */
    public function updateWeekSettings(Request $request, $leagueId, $weekNumber)
    {
        $validated = $request->validate([
            'holes' => 'required|in:front_9,back_9',
            'scoring_type' => 'required|in:' . implode(',', array_keys(ScoringSetting::scoringTypes())),
            'match_date' => 'required|date',
            'ride_with_opponent' => 'nullable|boolean',
        ]);

        $league = League::findOrFail($leagueId);

        // Update this week's matches
        $league->matches()
            ->where('week_number', $weekNumber)
            ->update([
                'holes' => $validated['holes'],
                'scoring_type' => $validated['scoring_type'],
                'match_date' => $validated['match_date'],
                'ride_with_opponent' => $request->boolean('ride_with_opponent'),
            ]);

        // Cascade dates and alternating holes to subsequent weeks
        $subsequentWeeks = $league->matches()
            ->where('week_number', '>', $weekNumber)
            ->select('week_number')
            ->distinct()
            ->orderBy('week_number')
            ->pluck('week_number');

        $baseDate = \Carbon\Carbon::parse($validated['match_date']);
        $currentHoles = $validated['holes'];
        foreach ($subsequentWeeks as $index => $futureWeek) {
            $newDate = $baseDate->copy()->addWeeks($index + 1);
            $currentHoles = ($currentHoles === 'front_9') ? 'back_9' : 'front_9';
            $league->matches()
                ->where('week_number', $futureWeek)
                ->update([
                    'match_date' => $newDate->toDateString(),
                    'holes' => $currentHoles,
                ]);
        }

        $weeksUpdated = $subsequentWeeks->count() + 1;

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'weeks_updated' => $weeksUpdated]);
        }

        return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
            ->with('success', "Week {$weekNumber} updated ({$weeksUpdated} " . ($weeksUpdated == 1 ? 'week' : 'weeks') . " adjusted): " . ($validated['holes'] === 'back_9' ? 'Back 9' : 'Front 9') . ' / ' . ScoringSetting::scoringTypes()[$validated['scoring_type']]);
    }

    /**
     * Reorder matches within a week by swapping tee times
     */
    public function reorderWeekMatches(Request $request, $leagueId, $weekNumber)
    {
        $validated = $request->validate([
            'match_ids' => 'required|array',
            'match_ids.*' => 'integer|exists:matches,id',
        ]);

        $league = League::findOrFail($leagueId);

        // Get all tee times for this week sorted chronologically
        $teeTimes = $league->matches()
            ->where('week_number', $weekNumber)
            ->whereIn('id', $validated['match_ids'])
            ->orderBy('tee_time')
            ->pluck('tee_time')
            ->values()
            ->toArray();

        // Assign tee times in the new row order (first match_id gets earliest tee time, etc.)
        \Illuminate\Support\Facades\DB::transaction(function () use ($league, $weekNumber, $validated, $teeTimes) {
            foreach ($validated['match_ids'] as $index => $matchId) {
                if (isset($teeTimes[$index])) {
                    $league->matches()
                        ->where('id', $matchId)
                        ->where('week_number', $weekNumber)
                        ->update(['tee_time' => $teeTimes[$index]]);
                }
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Delete all matches in a week (only if no scores have been posted)
     */
    public function deleteWeek($leagueId, $weekNumber)
    {
        $league = League::findOrFail($leagueId);
        $matches = $league->matches()->where('week_number', $weekNumber)->get();

        if ($matches->isEmpty()) {
            return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
                ->withErrors(['error' => 'No matches found for week ' . $weekNumber]);
        }

        // Check if any match player in this week has scores
        $matchIds = $matches->pluck('id');
        $hasScores = MatchScore::whereIn('match_player_id', function ($query) use ($matchIds) {
            $query->select('id')->from('match_players')->whereIn('match_id', $matchIds);
        })->exists();

        if ($hasScores) {
            return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
                ->withErrors(['error' => 'Cannot delete week ' . $weekNumber . ' — scores have already been posted.']);
        }

        DB::transaction(function () use ($matchIds) {
            MatchPlayer::whereIn('match_id', $matchIds)->delete();
            LeagueMatch::whereIn('id', $matchIds)->delete();
        });

        return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
            ->with('success', 'Week ' . $weekNumber . ' has been deleted.');
    }

    /**
     * Reorder weeks by renumbering them.
     * Accepts { week_order: [oldWeek1, oldWeek2, ...] } in desired new order.
     */
    public function reorderWeeks(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'week_order' => 'required|array',
            'week_order.*' => 'integer',
        ]);

        $league = League::findOrFail($leagueId);
        $weekOrder = $validated['week_order'];

        DB::transaction(function () use ($league, $weekOrder) {
            // Phase 1: Renumber to negative offsets to avoid conflicts
            foreach ($weekOrder as $index => $oldWeekNumber) {
                $league->matches()
                    ->where('week_number', $oldWeekNumber)
                    ->update(['week_number' => -($index + 1)]);
            }

            // Phase 2: Renumber from negative to final sequential values
            foreach ($weekOrder as $index => $oldWeekNumber) {
                $league->matches()
                    ->where('week_number', -($index + 1))
                    ->update(['week_number' => $index + 1]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Swap a player in a match_player slot
     */
    public function swapMatchPlayer(Request $request, $matchPlayerId)
    {
        $validated = $request->validate([
            'new_player_id' => 'required|integer|exists:players,id',
        ]);

        $matchPlayer = \App\Models\MatchPlayer::findOrFail($matchPlayerId);
        $newPlayer = \App\Models\Player::findOrFail($validated['new_player_id']);

        // If swapping to the same player, nothing to do
        if ($matchPlayer->player_id == $validated['new_player_id']) {
            return response()->json(['success' => true, 'player_name' => $newPlayer->name, 'handicap_index' => $matchPlayer->handicap_index]);
        }

        $match = $matchPlayer->match;
        $courseInfo = \Illuminate\Support\Facades\DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();
        $totalPar = \Illuminate\Support\Facades\DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->sum('par');

        $slope = $courseInfo ? $courseInfo->slope : 113;
        $rating = $courseInfo ? $courseInfo->rating : null;
        $calculator = app(\App\Services\MatchPlayCalculator::class);

        $matchDateHandicap = $newPlayer->handicapAsOf($match->match_date);
        $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($newPlayer->currentHandicap() ? $newPlayer->currentHandicap()->handicap_index : 0);
        $courseHandicap = $calculator->calculateCourseHandicap($handicapIndex, $slope, $rating, $totalPar);

        $matchPlayer->update([
            'player_id' => $validated['new_player_id'],
            'substitute_player_id' => null,
            'substitute_name' => null,
            'handicap_index' => $handicapIndex,
            'course_handicap' => $courseHandicap,
        ]);

        return response()->json([
            'success' => true,
            'player_name' => $newPlayer->name,
            'handicap_index' => $handicapIndex,
        ]);
    }

    /**
     * Update a match player's handicap index and recalculate course handicap
     */
    public function updateMatchPlayerHandicap(Request $request, $matchPlayerId)
    {
        $validated = $request->validate([
            'handicap_index' => 'required|numeric|min:-10|max:54',
        ]);

        $matchPlayer = MatchPlayer::findOrFail($matchPlayerId);
        $match = $matchPlayer->match;

        $courseInfo = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();
        $totalPar = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->sum('par');

        $slope = $courseInfo ? $courseInfo->slope : 113;
        $rating = $courseInfo ? $courseInfo->rating : null;
        $calculator = app(MatchPlayCalculator::class);
        $courseHandicap = $calculator->calculateCourseHandicap($validated['handicap_index'], $slope, $rating, $totalPar);

        $matchPlayer->update([
            'handicap_index' => $validated['handicap_index'],
            'course_handicap' => $courseHandicap,
        ]);

        return response()->json([
            'success' => true,
            'handicap_index' => (float) $validated['handicap_index'],
            'course_handicap' => $courseHandicap,
        ]);
    }

    /**
     * Search all players in the database for substitute autocomplete
     */
    public function searchPlayers(Request $request)
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $players = Player::where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($players->map(function ($p) {
            return ['id' => $p->id, 'name' => $p->name];
        }));
    }

    /**
     * Assign a substitute player to a match player slot
     */
    public function assignSubstitute(Request $request, $matchPlayerId)
    {
        $matchPlayer = MatchPlayer::with('match')->findOrFail($matchPlayerId);
        $match = $matchPlayer->match;

        $existingPlayerId = $request->input('substitute_player_id');
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');

        $courseInfo = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();
        $totalPar = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->sum('par');
        $slope = $courseInfo ? $courseInfo->slope : 113;
        $rating = $courseInfo ? $courseInfo->rating : null;
        $calculator = app(MatchPlayCalculator::class);

        if ($existingPlayerId) {
            $subPlayer = Player::findOrFail($existingPlayerId);

            $matchDateHandicap = $subPlayer->handicapAsOf($match->match_date);
            $handicapIndex = $matchDateHandicap
                ? $matchDateHandicap->handicap_index
                : ($subPlayer->currentHandicap()
                    ? $subPlayer->currentHandicap()->handicap_index
                    : 0);

            $courseHandicap = $calculator->calculateCourseHandicap($handicapIndex, $slope, $rating, $totalPar);

            $matchPlayer->update([
                'substitute_player_id' => $subPlayer->id,
                'substitute_name' => null,
                'handicap_index' => $handicapIndex,
                'course_handicap' => $courseHandicap,
            ]);

            return response()->json([
                'success' => true,
                'substitute_name' => $subPlayer->name,
                'handicap_index' => (float) $handicapIndex,
            ]);

        } elseif ($firstName) {
            $subPlayer = Player::create([
                'first_name' => trim($firstName),
                'last_name' => trim($lastName ?? ''),
                'email' => null,
            ]);

            $matchPlayer->update([
                'substitute_player_id' => $subPlayer->id,
                'substitute_name' => null,
                'handicap_index' => 0,
                'course_handicap' => 0,
            ]);

            return response()->json([
                'success' => true,
                'substitute_name' => $subPlayer->name,
                'substitute_player_id' => $subPlayer->id,
                'handicap_index' => 0,
            ]);

        } else {
            return response()->json(['success' => false, 'error' => 'Must provide substitute_player_id or first_name'], 422);
        }
    }

    /**
     * Remove a substitute and restore the original player's handicap
     */
    public function removeSubstitute(Request $request, $matchPlayerId)
    {
        $matchPlayer = MatchPlayer::with(['match', 'player'])->findOrFail($matchPlayerId);
        $match = $matchPlayer->match;
        $origPlayer = $matchPlayer->player;

        $matchDateHandicap = $origPlayer->handicapAsOf($match->match_date);
        $handicapIndex = $matchDateHandicap
            ? $matchDateHandicap->handicap_index
            : ($origPlayer->currentHandicap()
                ? $origPlayer->currentHandicap()->handicap_index
                : 0);

        $courseInfo = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();
        $totalPar = DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->sum('par');
        $slope = $courseInfo ? $courseInfo->slope : 113;
        $rating = $courseInfo ? $courseInfo->rating : null;
        $calculator = app(MatchPlayCalculator::class);
        $courseHandicap = $calculator->calculateCourseHandicap($handicapIndex, $slope, $rating, $totalPar);

        $matchPlayer->update([
            'substitute_player_id' => null,
            'substitute_name' => null,
            'handicap_index' => $handicapIndex,
            'course_handicap' => $courseHandicap,
        ]);

        return response()->json([
            'success' => true,
            'player_name' => $origPlayer->name,
            'handicap_index' => (float) $handicapIndex,
        ]);
    }

    /**
     * Add additional weeks to an existing schedule
     */
    public function addWeeks(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'additional_weeks' => 'required|integer|min:1|max:52',
        ]);

        $league = League::with(['matches', 'teams.players'])->findOrFail($leagueId);

        // Get current schedule info from the last week
        $lastMatch = $league->matches()->orderByDesc('week_number')->orderByDesc('match_date')->first();

        if (!$lastMatch) {
            return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
                ->withErrors(['error' => 'No existing schedule found. Use auto-schedule to create one first.']);
        }

        $maxWeek = $league->matches()->max('week_number');
        $lastWeekDate = $league->matches()->where('week_number', $maxWeek)->max('match_date');
        $lastHoles = $lastMatch->holes ?? 'front_9';
        $lastScoringType = $lastMatch->scoring_type ?? 'best_ball_match_play';
        $lastScoreMode = $lastMatch->score_mode ?? 'net';

        // Get tee time settings from league settings, falling back to last week's matches
        $startTeeTime = $league->default_tee_time
            ? \Carbon\Carbon::parse($league->default_tee_time)->format('H:i')
            : null;
        $teeTimeInterval = $league->tee_time_interval ?? null;

        if (!$startTeeTime || !$teeTimeInterval) {
            $lastWeekMatches = $league->matches()->where('week_number', $maxWeek)->orderBy('tee_time')->get();
            if (!$startTeeTime) {
                $startTeeTime = $lastWeekMatches->first()->tee_time
                    ? \Carbon\Carbon::parse($lastWeekMatches->first()->tee_time)->format('H:i')
                    : '16:40';
            }
            if (!$teeTimeInterval && $lastWeekMatches->count() >= 2) {
                $first = \Carbon\Carbon::parse($lastWeekMatches->first()->tee_time);
                $second = \Carbon\Carbon::parse($lastWeekMatches->skip(1)->first()->tee_time);
                $teeTimeInterval = max(5, $first->diffInMinutes($second));
            }
        }
        $teeTimeInterval = $teeTimeInterval ?? 10;

        $scheduler = new \App\Services\LeagueScheduler(app(\App\Services\MatchPlayCalculator::class));

        try {
            $additionalWeeks = $validated['additional_weeks'];
            $scheduleData = $scheduler->generateSchedule($league, $additionalWeeks);

            // Start date is one week after the last week's date
            $startDate = \Carbon\Carbon::parse($lastWeekDate)->addWeek();

            // Alternate holes from the last week
            $newHoles = ($lastHoles === 'front_9') ? 'back_9' : 'front_9';

            $scheduler->saveSchedule(
                $league,
                $scheduleData,
                new \DateTime($startDate->toDateString()),
                $newHoles,
                $lastScoringType,
                $startTeeTime,
                $teeTimeInterval,
                $maxWeek,
                $lastScoreMode
            );

            return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
                ->with('success', "Added {$additionalWeeks} additional " . ($additionalWeeks == 1 ? 'week' : 'weeks') . " to the schedule (Weeks " . ($maxWeek + 1) . "-" . ($maxWeek + $additionalWeeks) . ")!");
        } catch (\Exception $e) {
            return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Add empty weeks (no player assignments) so admin can manually assign players
     */
    public function addEmptyWeeks(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'additional_weeks' => 'required|integer|min:1|max:52',
        ]);

        $league = League::with(['matches', 'teams.players'])->findOrFail($leagueId);

        $lastMatch = $league->matches()->orderByDesc('week_number')->orderByDesc('match_date')->first();

        $maxWeek = $lastMatch ? $league->matches()->max('week_number') : 0;
        $lastWeekDate = $lastMatch
            ? $league->matches()->where('week_number', $maxWeek)->max('match_date')
            : now()->toDateString();
        $lastHoles = $lastMatch->holes ?? 'front_9';
        $lastScoringType = $lastMatch->scoring_type ?? 'best_ball_match_play';
        $lastScoreMode = $lastMatch->score_mode ?? 'net';

        // Calculate matches per week based on league players (4 per match)
        // For team-based leagues, use the largest team's pair count so all players get a slot
        $matchesPerWeek = 1;
        if ($league->teams->count() >= 2) {
            $maxTeamSize = $league->teams->max(fn($t) => $t->players->count());
            $matchesPerWeek = max(1, (int) ceil($maxTeamSize / 2));
        } elseif ($league->players->count() > 0) {
            $matchesPerWeek = max(1, (int) ceil($league->players->count() / 4));
        }

        // Get tee time settings from league settings, falling back to last week's matches
        $startTeeTime = $league->default_tee_time
            ? \Carbon\Carbon::parse($league->default_tee_time)->format('H:i')
            : null;
        $teeTimeInterval = $league->tee_time_interval ?? null;

        if (!$startTeeTime || !$teeTimeInterval) {
            if ($lastMatch) {
                $lastWeekMatches = $league->matches()->where('week_number', $maxWeek)->orderBy('tee_time')->get();
                if (!$startTeeTime && $lastWeekMatches->first()->tee_time) {
                    $startTeeTime = \Carbon\Carbon::parse($lastWeekMatches->first()->tee_time)->format('H:i');
                }
                if (!$teeTimeInterval && $lastWeekMatches->count() >= 2) {
                    $first = \Carbon\Carbon::parse($lastWeekMatches->first()->tee_time);
                    $second = \Carbon\Carbon::parse($lastWeekMatches->skip(1)->first()->tee_time);
                    $teeTimeInterval = max(5, $first->diffInMinutes($second));
                }
            }
        }
        $startTeeTime = $startTeeTime ?? '16:40';
        $teeTimeInterval = $teeTimeInterval ?? 10;

        $additionalWeeks = $validated['additional_weeks'];
        $newHoles = ($lastHoles === 'front_9') ? 'back_9' : 'front_9';
        $startDate = \Carbon\Carbon::parse($lastWeekDate)->addWeek();

        // Determine team IDs for empty matches
        $homeTeamId = null;
        $awayTeamId = null;
        if ($lastMatch && $lastMatch->home_team_id) {
            $homeTeamId = $lastMatch->home_team_id;
            $awayTeamId = $lastMatch->away_team_id;
        } elseif ($league->teams->count() >= 2) {
            $homeTeamId = $league->teams->first()->id;
            $awayTeamId = $league->teams->skip(1)->first()->id;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($league, $additionalWeeks, $maxWeek, $startDate, $newHoles, $lastScoringType, $lastScoreMode, $matchesPerWeek, $startTeeTime, $teeTimeInterval, $homeTeamId, $awayTeamId) {
            for ($w = 1; $w <= $additionalWeeks; $w++) {
                $weekNumber = $maxWeek + $w;
                $matchDate = (clone $startDate)->addWeeks($w - 1);
                $holes = (($w % 2) === 1) ? $newHoles : (($newHoles === 'front_9') ? 'back_9' : 'front_9');

                for ($m = 0; $m < $matchesPerWeek; $m++) {
                    $teeTime = \Carbon\Carbon::createFromFormat('H:i', $startTeeTime)
                        ->addMinutes($m * $teeTimeInterval)
                        ->format('H:i:s');

                    LeagueMatch::create([
                        'league_id' => $league->id,
                        'week_number' => $weekNumber,
                        'match_date' => $matchDate,
                        'tee_time' => $teeTime,
                        'golf_course_id' => $league->golf_course_id,
                        'teebox' => $league->default_teebox,
                        'holes' => $holes,
                        'scoring_type' => $lastScoringType,
                        'score_mode' => $lastScoreMode,
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'status' => 'scheduled',
                    ]);
                }
            }
        });

        return redirect()->route('admin.leagues.scheduleOverview', $leagueId)
            ->with('success', "Added {$additionalWeeks} empty " . ($additionalWeeks == 1 ? 'week' : 'weeks') . " to the schedule (Weeks " . ($maxWeek + 1) . "-" . ($maxWeek + $additionalWeeks) . "). Use the dropdowns to assign players.");
    }

    /**
     * Assign a player to an empty match position via AJAX
     */
    public function assignPlayerToMatch(Request $request, $matchId)
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
            'position_in_pairing' => 'required|integer|in:1,2,3,4',
        ]);

        $match = LeagueMatch::findOrFail($matchId);
        $player = \App\Models\Player::findOrFail($validated['player_id']);

        // Check if position is already taken
        $existing = MatchPlayer::where('match_id', $matchId)
            ->where('position_in_pairing', $validated['position_in_pairing'])
            ->first();
        if ($existing) {
            return response()->json(['success' => false, 'error' => 'Position already assigned'], 422);
        }

        // Calculate handicap
        $courseInfo = \Illuminate\Support\Facades\DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->where('hole_number', 1)
            ->first();
        $totalPar = \Illuminate\Support\Facades\DB::table('course_info')
            ->where('golf_course_id', $match->golf_course_id)
            ->where('teebox', $match->teebox)
            ->sum('par');

        $slope = $courseInfo ? $courseInfo->slope : 113;
        $rating = $courseInfo ? (float) $courseInfo->rating : null;
        $calculator = app(\App\Services\MatchPlayCalculator::class);

        $matchDateHandicap = $player->handicapAsOf($match->match_date);
        $handicapIndex = $matchDateHandicap ? $matchDateHandicap->handicap_index : ($player->currentHandicap() ? $player->currentHandicap()->handicap_index : 0);
        $courseHandicap = $calculator->calculateCourseHandicap($handicapIndex, $slope, $rating, $totalPar);

        // Determine team_id from league teams
        $league = $match->league()->with('teams.players')->first();
        $teamId = null;
        foreach ($league->teams as $team) {
            if ($team->players->contains('id', $player->id)) {
                $teamId = $team->id;
                break;
            }
        }

        // Set home/away team on match if not set
        $position = $validated['position_in_pairing'];
        if ($teamId) {
            if ($position <= 2 && !$match->home_team_id) {
                $match->update(['home_team_id' => $teamId]);
            } elseif ($position > 2 && !$match->away_team_id) {
                $match->update(['away_team_id' => $teamId]);
            }
        }

        $mp = MatchPlayer::create([
            'match_id' => $match->id,
            'team_id' => $teamId,
            'player_id' => $player->id,
            'handicap_index' => $handicapIndex,
            'course_handicap' => $courseHandicap,
            'position_in_pairing' => $position,
        ]);

        return response()->json([
            'success' => true,
            'match_player_id' => $mp->id,
            'player_name' => $player->name,
            'player_id' => $player->id,
            'handicap_index' => $handicapIndex,
            'team_id' => $teamId,
        ]);
    }

    /**
     * Show league players management page
     */
    public function managePlayers($leagueId)
    {
        $league = League::with('players')->findOrFail($leagueId);
        $allPlayers = \App\Models\Player::orderBy('first_name')->orderBy('last_name')->get();

        // Get players not yet in the league
        $availablePlayers = $allPlayers->filter(function ($player) use ($league) {
            return !$league->players->contains($player->id);
        });

        return view('leagues.manage-players', compact('league', 'availablePlayers'));
    }

    /**
     * Add one or more players to the league
     */
    public function addPlayer(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'player_ids' => 'required|array|min:1',
            'player_ids.*' => 'required|exists:players,id',
        ]);

        $league = League::findOrFail($leagueId);

        // Get existing player IDs in the league
        $existingPlayerIds = $league->players()->pluck('player_id')->toArray();

        // Filter out players already in the league
        $newPlayerIds = array_diff($validated['player_ids'], $existingPlayerIds);

        if (empty($newPlayerIds)) {
            return back()->withErrors(['player_ids' => 'All selected players are already in this league.']);
        }

        // Add new players
        $league->players()->attach($newPlayerIds);

        $count = count($newPlayerIds);
        $message = $count === 1 ? 'Player added to league successfully!' : "{$count} players added to league successfully!";

        return back()->with('success', $message);
    }

    /**
     * Remove a player from the league
     */
    public function removePlayer($leagueId, $playerId)
    {
        $league = League::findOrFail($leagueId);
        $league->players()->detach($playerId);

        return back()->with('success', 'Player removed from league successfully!');
    }

    /**
     * Show weekly score entry page
     */
    public function weeklyScores(Request $request, $leagueId)
    {
        $league = League::with(['golfCourse', 'teams.players'])->findOrFail($leagueId);

        // Build player-to-team-name map for resolving team names when home_team_id/away_team_id are null
        $playerTeamNames = [];
        foreach ($league->teams as $team) {
            foreach ($team->players as $player) {
                $playerTeamNames[$player->id] = $team->name;
            }
        }

        // Get all week numbers
        $weeks = $league->matches()
            ->select('week_number')
            ->distinct()
            ->orderBy('week_number')
            ->pluck('week_number');

        $selectedWeek = $request->query('week', $weeks->first());

        // Get matches for selected week, ordered by tee_time
        $matches = collect();
        $courseInfoMap = [];
        $playerHandicaps = [];
        if ($selectedWeek) {
            $matches = $league->matches()
                ->where('week_number', $selectedWeek)
                ->with([
                    'homeTeam', 'awayTeam',
                    'matchPlayers.player.handicapHistory', 'matchPlayers.substitutePlayer', 'matchPlayers.scores',
                    'golfCourse.courseInfo',
                ])
                ->orderBy('tee_time')
                ->orderBy('id')
                ->get();

            // Build course info and handicap data for each match
            foreach ($matches as $match) {
                $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];
                $allHolesForMatch = $match->golfCourse->courseInfo()
                    ->where('teebox', $match->teebox)
                    ->orderBy('hole_number')
                    ->get();
                $courseInfoMap[$match->id] = [
                    'holeRange' => $holeRange,
                    'holes' => $allHolesForMatch->whereBetween('hole_number', $holeRange)->values(),
                    'allHoles' => $allHolesForMatch,
                ];

                // Compute handicaps
                $courseInfoHole1 = $match->golfCourse->courseInfo()
                    ->where('teebox', $match->teebox)
                    ->where('hole_number', 1)
                    ->first();
                $allCI = $match->golfCourse->courseInfo()
                    ->where('teebox', $match->teebox)
                    ->get();
                $par18 = $allCI->sum('par');

                $slope18 = $courseInfoHole1 ? (float) $courseInfoHole1->slope : null;
                $rating18 = $courseInfoHole1 ? (float) $courseInfoHole1->rating : null;

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
            }
        }

        // Determine par 3 holes for this week and load existing winners
        $par3Holes = collect();
        $par3Winners = [];
        $weekPlayers = collect();
        if ($selectedWeek && $matches->isNotEmpty()) {
            $firstMatch = $matches->first();
            $holeRange = $firstMatch->holes === 'back_9' ? [10, 18] : [1, 9];
            $par3Holes = $firstMatch->golfCourse->courseInfo()
                ->where('teebox', $firstMatch->teebox)
                ->where('par', 3)
                ->whereBetween('hole_number', $holeRange)
                ->orderBy('hole_number')
                ->get();

            // Collect all unique players participating this week
            $weekPlayers = $matches->flatMap(function ($match) {
                return $match->matchPlayers->map(function ($mp) {
                    return $mp->player;
                });
            })->unique('id')->sortBy('last_name')->values();

            // Load existing par 3 winners for this week
            $existingWinners = Par3Winner::where('league_id', $league->id)
                ->where('week_number', $selectedWeek)
                ->get()
                ->keyBy('hole_number');
            foreach ($existingWinners as $holeNum => $winner) {
                $par3Winners[$holeNum] = $winner;
            }
        }

        $scoringTypes = ScoringSetting::scoringTypes();

        return view('leagues.weekly-scores', compact(
            'league', 'weeks', 'selectedWeek', 'matches', 'courseInfoMap', 'playerHandicaps', 'playerTeamNames',
            'par3Holes', 'par3Winners', 'weekPlayers', 'scoringTypes'
        ));
    }

    /**
     * Store par 3 winners for a week
     */
    public function storePar3Winners(Request $request, $leagueId)
    {
        $league = League::findOrFail($leagueId);
        $week = $request->input('week_number');

        $validated = $request->validate([
            'week_number' => 'required|integer|min:1',
            'par3_winners' => 'nullable|array',
            'par3_winners.*' => 'nullable|integer|exists:players,id',
            'par3_distances' => 'nullable|array',
            'par3_distances.*' => 'nullable|string|max:50',
        ]);

        $winners = $request->input('par3_winners', []);
        $distances = $request->input('par3_distances', []);

        // Remove all existing winners for this week (cascades to linked finance entries)
        Par3Winner::where('league_id', $league->id)
            ->where('week_number', $week)
            ->delete();

        // Get the match date for this week (for finance transaction date)
        $matchDate = $league->matches()
            ->where('week_number', $week)
            ->min('match_date');

        $par3Payout = (float) ($league->par3_payout ?? 0);

        // Insert new winners and auto-create finance entries
        foreach ($winners as $holeNumber => $playerId) {
            if ($playerId) {
                $par3Winner = Par3Winner::create([
                    'league_id' => $league->id,
                    'week_number' => $week,
                    'hole_number' => $holeNumber,
                    'player_id' => $playerId,
                    'distance' => $distances[$holeNumber] ?? null,
                ]);

                if ($par3Payout > 0) {
                    \App\Models\LeagueFinance::create([
                        'league_id' => $league->id,
                        'player_id' => $playerId,
                        'type' => 'winnings',
                        'amount' => $par3Payout,
                        'date' => $matchDate ?? now()->toDateString(),
                        'notes' => 'Par 3 Winner - Week ' . $week . ', Hole ' . $holeNumber,
                        'par3_winner_id' => $par3Winner->id,
                    ]);
                }
            }
        }

        return redirect()->route('admin.leagues.scores', ['league_id' => $league->id, 'week' => $week])
            ->with('success', 'Par 3 winners saved.');
    }

    /**
     * Store scores for all matches in a week
     */
    public function storeWeeklyScores(Request $request, $leagueId)
    {
        $league = League::findOrFail($leagueId);
        $week = $request->input('week_number');

        $validated = $request->validate([
            'week_number' => 'required|integer|min:1',
            'scores' => 'required|array',
            'scores.*.*' => 'required|integer|min:1|max:15',
        ]);

        $matches = $league->matches()
            ->where('week_number', $week)
            ->with(['matchPlayers.player.handicapHistory', 'homeTeam', 'awayTeam', 'result', 'golfCourse.courseInfo'])
            ->get();

        $calculator = app(MatchPlayCalculator::class);

        DB::transaction(function () use ($matches, $validated, $calculator) {
            $affectedPlayerIds = [];

            foreach ($matches as $match) {
                // Refresh handicaps to match date before processing scores
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
                    $matchDateHandicap = $mp->player->handicapAsOf($match->match_date);
                    if ($matchDateHandicap) {
                        $hi = (float) $matchDateHandicap->handicap_index;
                        $newCH = $slope ? round(($hi * $slope / 113) + ($rating - $par18)) : $mp->course_handicap;
                        $mp->update(['handicap_index' => $hi, 'course_handicap' => $newCH]);
                    }
                }

                // Get course info for hole handicap rankings
                $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];
                $courseInfoHoles = $match->golfCourse->courseInfo()
                    ->where('teebox', $match->teebox)
                    ->whereBetween('hole_number', $holeRange)
                    ->get()
                    ->keyBy('hole_number');
                $totalHoles = 9;

                $matchHasScores = false;

                foreach ($match->matchPlayers as $mp) {
                    if (!isset($validated['scores'][$mp->id])) continue;

                    $courseHandicap = (float) $mp->course_handicap;
                    $ch9 = round($courseHandicap / 2);

                    foreach ($validated['scores'][$mp->id] as $holeNumber => $strokes) {
                        $holeInfo = $courseInfoHoles->get((int) $holeNumber);
                        $par = $holeInfo ? (int) $holeInfo->par : 4;
                        $holeHandicapRanking = ($holeInfo && $holeInfo->handicap) ? (int) $holeInfo->handicap : (int) $holeNumber;

                        // Strokes received on this hole
                        $ch = max(0, (int) $ch9);
                        $strokesReceived = 0;
                        if ($ch > 0) {
                            $base = intdiv($ch, $totalHoles);
                            $remainder = $ch % $totalHoles;
                            $strokesReceived = $base + ($holeHandicapRanking <= $remainder ? 1 : 0);
                        }

                        // Adjusted Gross: capped at Net Double Bogey
                        $maxScore = $par + 2 + $strokesReceived;
                        $adjustedGross = min((int) $strokes, $maxScore);

                        // Net Score: gross minus strokes received
                        $netScore = (int) $strokes - $strokesReceived;

                        MatchScore::updateOrCreate(
                            [
                                'match_player_id' => $mp->id,
                                'hole_number' => $holeNumber,
                            ],
                            [
                                'strokes' => $strokes,
                                'adjusted_gross' => $adjustedGross,
                                'net_score' => $netScore,
                            ]
                        );
                        $matchHasScores = true;
                    }
                }

                if (!$matchHasScores) continue;

                // Reverse old team records if match was already completed
                $oldResult = $match->result;
                if ($oldResult && $match->status === 'completed') {
                    $homeTeam = $match->homeTeam;
                    $awayTeam = $match->awayTeam;
                    if ($homeTeam && $awayTeam) {
                        if ($oldResult->winning_team_id == $homeTeam->id) {
                            $homeTeam->decrement('wins');
                            $awayTeam->decrement('losses');
                        } elseif ($oldResult->winning_team_id == $awayTeam->id) {
                            $homeTeam->decrement('losses');
                            $awayTeam->decrement('wins');
                        } else {
                            $homeTeam->decrement('ties');
                            $awayTeam->decrement('ties');
                        }
                    }
                }

                // Calculate and save match result
                $resultData = $calculator->calculateMatchResult($match);
                MatchResult::updateOrCreate(
                    ['match_id' => $match->id],
                    $resultData
                );

                // Update team records
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

                // Create Round + Score records for each player's handicap history
                $holesPlayed = 9;
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
            }

            // Recalculate handicaps for all affected players
            $handicapCalculator = app(HandicapCalculator::class);
            foreach (array_unique($affectedPlayerIds) as $playerId) {
                $player = Player::find($playerId);
                if ($player) {
                    $handicapCalculator->recalculateForPlayer($player);
                }
            }
        });

        return redirect()->route('admin.leagues.scores', ['league_id' => $leagueId, 'week' => $week])
            ->with('success', "Week {$week} scores saved successfully!");
    }

    /**
     * Print blank scorecards for all matches in a given week.
     */
    public function printScorecards($leagueId, $weekNumber)
    {
        $league = League::with('teams.players')->findOrFail($leagueId);

        $matches = LeagueMatch::where('league_id', $leagueId)
            ->where('week_number', $weekNumber)
            ->with(['matchPlayers.player.handicapHistory', 'matchPlayers.substitutePlayer.handicapHistory', 'golfCourse.courseInfo', 'homeTeam', 'awayTeam'])
            ->orderBy('tee_time')
            ->orderBy('id')
            ->get();

        // Build player -> team name map
        $playerTeamNames = [];
        foreach ($league->teams as $team) {
            foreach ($team->players as $player) {
                $playerTeamNames[$player->id] = $team->name;
            }
        }

        // Prepare scorecard data for each match
        $scorecards = [];
        foreach ($matches as $match) {
            $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];

            $allCourseInfo = $match->golfCourse->courseInfo()
                ->where('teebox', $match->teebox)
                ->orderBy('hole_number')
                ->get();
            $courseInfo = $allCourseInfo->whereBetween('hole_number', $holeRange)->values();

            if ($match->home_team_id) {
                $homePlayers = $match->matchPlayers->where('team_id', $match->home_team_id);
                $awayPlayers = $match->matchPlayers->where('team_id', $match->away_team_id);
            } else {
                $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
            }

            $homeTeamName = $match->homeTeam->name
                ?? ($homePlayers->first() ? ($playerTeamNames[$homePlayers->first()->player_id] ?? 'Home') : 'Home');
            $awayTeamName = $match->awayTeam->name
                ?? ($awayPlayers->first() ? ($playerTeamNames[$awayPlayers->first()->player_id] ?? 'Away') : 'Away');

            // Compute handicaps as of match date (USGA formula)
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
                $activePlayer = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
                $matchDateHandicap = $activePlayer->handicapAsOf($match->match_date);
                $hi = $matchDateHandicap ? (float) $matchDateHandicap->handicap_index : (float) $mp->handicap_index;

                // Update stored values if they differ
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
                $playerHandicaps[$mp->id] = ['hi' => $hi, 'ch18' => $ch18, 'ch9' => $ch9];
            }

            $scorecards[] = [
                'match' => $match,
                'courseInfo' => $courseInfo,
                'allCourseInfo' => $allCourseInfo,
                'holeRange' => $holeRange,
                'homePlayers' => $homePlayers,
                'awayPlayers' => $awayPlayers,
                'homeTeamName' => $homeTeamName,
                'awayTeamName' => $awayTeamName,
                'playerHandicaps' => $playerHandicaps,
            ];
        }

        return view('leagues.print-scorecards', compact('league', 'weekNumber', 'scorecards'));
    }

    /**
     * Show the email results form
     */
    public function showEmailResults($leagueId)
    {
        $league = League::with('players')->findOrFail($leagueId);

        $completedWeeks = $league->matches()
            ->where('status', 'completed')
            ->pluck('week_number')
            ->unique()
            ->sort()
            ->values();

        $playersWithEmail = $league->players()->whereNotNull('email')->where('email', '!=', '')->where('email_enabled', true)->count();
        $totalPlayers = $league->players()->count();

        return view('leagues.email-results', compact('league', 'completedWeeks', 'playersWithEmail', 'totalPlayers'));
    }

    /**
     * Preview the weekly results email as HTML
     */
    public function previewEmailResults(Request $request, $leagueId)
    {
        $weekNumber = (int) $request->query('week', 1);
        $league = League::with(['teams.players', 'golfCourse', 'segments.teams'])->findOrFail($leagueId);
        $data = $this->assembleWeeklyResultsData($league, $weekNumber);

        return view('emails.weekly-results', array_merge(['league' => $league, 'weekNumber' => $weekNumber], $data));
    }

    /**
     * Send weekly results email to all league players
     */
    public function sendEmailResults(Request $request, $leagueId)
    {
        $request->validate([
            'week_number' => 'required|integer|min:1',
            'test_email' => 'nullable|string|max:500',
        ]);
        $weekNumber = (int) $request->input('week_number');
        $testEmail = $request->input('test_email');

        $league = League::with(['teams.players', 'golfCourse', 'segments.teams'])->findOrFail($leagueId);

        if ($testEmail) {
            $recipients = array_filter(array_map('trim', explode(',', $testEmail)));
            foreach ($recipients as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return redirect()->route('admin.leagues.emailResults', $leagueId)
                        ->withErrors(['error' => "Invalid email address: {$email}"]);
                }
            }
        } else {
            $recipients = $league->players()
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->where('email_enabled', true)
                ->pluck('email')
                ->unique()
                ->toArray();

            if (empty($recipients)) {
                return redirect()->route('admin.leagues.emailResults', $leagueId)
                    ->withErrors(['error' => 'No players have email enabled.']);
            }
        }

        $data = $this->assembleWeeklyResultsData($league, $weekNumber);

        try {
            $mailable = new WeeklyResultsEmail(
                $league, $weekNumber,
                $data['standings'], $data['par3Winners'],
                $data['playerStandings'], $data['nextWeekNumber'],
                $data['nextWeekMatches'], $data['nextWeekTeamNames']
            );

            $adminEmails = User::where('is_admin', true)
                ->where('email_notifications', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->pluck('email')
                ->unique()
                ->toArray();

            if ($testEmail) {
                $mail = Mail::to($recipients);
            } elseif (!empty($adminEmails)) {
                $mail = Mail::to($adminEmails)
                    ->bcc($recipients);
                $mailable->replyTo($adminEmails);
            } else {
                $mail = Mail::to(config('mail.from.address'))
                    ->bcc($recipients);
            }

            $mail->send($mailable);

            $successMsg = $testEmail
                ? "Test email for Week {$weekNumber} results sent to " . implode(', ', $recipients) . "!"
                : "Week {$weekNumber} results emailed to " . count($recipients) . " players!";

            return redirect()->route('admin.leagues.emailResults', $leagueId)
                ->with('success', $successMsg);
        } catch (\Exception $e) {
            return redirect()->route('admin.leagues.emailResults', $leagueId)
                ->withErrors(['error' => 'Failed to send email: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the email message compose form
     */
    public function showEmailMessage($leagueId)
    {
        $league = League::with('players')->findOrFail($leagueId);

        $playersWithEmail = $league->players()->whereNotNull('email')->where('email', '!=', '')->where('email_enabled', true)->count();
        $totalPlayers = $league->players()->count();

        return view('leagues.email-message', compact('league', 'playersWithEmail', 'totalPlayers'));
    }

    /**
     * Send a custom league message to all players
     */
    public function sendEmailMessage(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message_body' => 'required|string|max:5000',
            'test_email' => 'nullable|string|max:500',
        ]);

        $league = League::with('players')->findOrFail($leagueId);
        $testEmail = $request->input('test_email');

        if ($testEmail) {
            $recipients = array_filter(array_map('trim', explode(',', $testEmail)));
            foreach ($recipients as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return redirect()->route('admin.leagues.emailMessage', $leagueId)
                        ->withErrors(['error' => "Invalid email address: {$email}"]);
                }
            }
        } else {
            $recipients = $league->players()
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->where('email_enabled', true)
                ->pluck('email')
                ->unique()
                ->toArray();

            if (empty($recipients)) {
                return redirect()->route('admin.leagues.emailMessage', $leagueId)
                    ->withErrors(['error' => 'No players have email enabled.']);
            }
        }

        try {
            $mailable = new LeagueMessageEmail($league, $validated['subject'], $validated['message_body']);

            $adminEmails = User::where('is_admin', true)
                ->where('email_notifications', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->pluck('email')
                ->unique()
                ->toArray();

            if ($testEmail) {
                $mail = Mail::to($recipients);
            } elseif (!empty($adminEmails)) {
                $mail = Mail::to($adminEmails)
                    ->bcc($recipients);
                $mailable->replyTo($adminEmails);
            } else {
                $mail = Mail::to(config('mail.from.address'))
                    ->bcc($recipients);
            }

            $mail->send($mailable);

            $successMsg = $testEmail
                ? "Test message sent to " . implode(', ', $recipients) . "!"
                : "Message emailed to " . count($recipients) . " players!";

            return redirect()->route('admin.leagues.emailMessage', $leagueId)
                ->with('success', $successMsg);
        } catch (\Exception $e) {
            return redirect()->route('admin.leagues.emailMessage', $leagueId)
                ->withErrors(['error' => 'Failed to send email: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the SMS results form
     */
    public function showSmsResults($leagueId)
    {
        $league = League::with('players')->findOrFail($leagueId);

        $completedWeeks = $league->matches()
            ->where('status', 'completed')
            ->pluck('week_number')
            ->unique()
            ->sort()
            ->values();

        $playersWithPhone = $league->players()
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->where('sms_enabled', true)
            ->count();
        $totalPlayers = $league->players()->count();

        return view('leagues.sms-results', compact('league', 'completedWeeks', 'playersWithPhone', 'totalPlayers'));
    }

    /**
     * Preview the condensed SMS results text
     */
    public function previewSmsResults(Request $request, $leagueId)
    {
        $weekNumber = (int) $request->query('week', 1);
        $league = League::with(['teams.players', 'golfCourse', 'segments.teams'])->findOrFail($leagueId);
        $data = $this->assembleWeeklyResultsData($league, $weekNumber);

        $smsText = $this->buildSmsResultsText($league, $weekNumber, $data);

        return response()->json([
            'text' => $smsText,
            'length' => strlen($smsText),
            'segments' => ceil(strlen($smsText) / 160),
        ]);
    }

    /**
     * Send weekly results SMS to all league players
     */
    public function sendSmsResults(Request $request, $leagueId)
    {
        $request->validate([
            'week_number' => 'required|integer|min:1',
            'test_phone' => 'nullable|string|max:20',
        ]);
        $weekNumber = (int) $request->input('week_number');
        $testPhone = $request->input('test_phone');

        $league = League::with(['teams.players', 'golfCourse', 'segments.teams'])->findOrFail($leagueId);

        if ($testPhone) {
            $formatted = SmsService::formatPhoneNumber($testPhone);
            if (!$formatted) {
                return redirect()->route('admin.leagues.smsResults', $leagueId)
                    ->withErrors(['error' => 'Invalid phone number format.']);
            }
            $recipients = [$formatted];
        } else {
            $recipients = $league->players()
                ->whereNotNull('phone_number')
                ->where('phone_number', '!=', '')
                ->where('sms_enabled', true)
                ->pluck('phone_number')
                ->map(fn($p) => SmsService::formatPhoneNumber($p))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (empty($recipients)) {
                return redirect()->route('admin.leagues.smsResults', $leagueId)
                    ->withErrors(['error' => 'No players have SMS enabled.']);
            }
        }

        $data = $this->assembleWeeklyResultsData($league, $weekNumber);
        $smsText = $this->buildSmsResultsText($league, $weekNumber, $data);

        try {
            $sms = new SmsService();
            $result = $sms->sendBulkSms($recipients, $smsText);

            $successMsg = $testPhone
                ? "Test SMS for Week {$weekNumber} results sent to {$testPhone}!"
                : "Week {$weekNumber} results sent to {$result['sent']} players via SMS!";

            if (!empty($result['failed'])) {
                $successMsg .= " (" . count($result['failed']) . " failed)";
            }

            return redirect()->route('admin.leagues.smsResults', $leagueId)
                ->with('success', $successMsg);
        } catch (\Exception $e) {
            return redirect()->route('admin.leagues.smsResults', $leagueId)
                ->withErrors(['error' => 'Failed to send SMS: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the SMS message compose form
     */
    public function showSmsMessage($leagueId)
    {
        $league = League::with('players')->findOrFail($leagueId);

        $playersWithPhone = $league->players()
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->where('sms_enabled', true)
            ->count();
        $totalPlayers = $league->players()->count();

        return view('leagues.sms-message', compact('league', 'playersWithPhone', 'totalPlayers'));
    }

    /**
     * Send a custom SMS message to all players
     */
    public function sendSmsMessage(Request $request, $leagueId)
    {
        $validated = $request->validate([
            'message_body' => 'required|string|max:1600',
            'test_phone' => 'nullable|string|max:20',
        ]);

        $league = League::with('players')->findOrFail($leagueId);
        $testPhone = $request->input('test_phone');

        if ($testPhone) {
            $formatted = SmsService::formatPhoneNumber($testPhone);
            if (!$formatted) {
                return redirect()->route('admin.leagues.smsMessage', $leagueId)
                    ->withErrors(['error' => 'Invalid phone number format.']);
            }
            $recipients = [$formatted];
        } else {
            $recipients = $league->players()
                ->whereNotNull('phone_number')
                ->where('phone_number', '!=', '')
                ->where('sms_enabled', true)
                ->pluck('phone_number')
                ->map(fn($p) => SmsService::formatPhoneNumber($p))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (empty($recipients)) {
                return redirect()->route('admin.leagues.smsMessage', $leagueId)
                    ->withErrors(['error' => 'No players have SMS enabled.']);
            }
        }

        $body = $league->name . ': ' . $validated['message_body'];

        try {
            $sms = new SmsService();
            $result = $sms->sendBulkSms($recipients, $body);

            $successMsg = $testPhone
                ? "Test SMS sent to {$testPhone}!"
                : "SMS sent to {$result['sent']} players!";

            if (!empty($result['failed'])) {
                $successMsg .= " (" . count($result['failed']) . " failed)";
            }

            return redirect()->route('admin.leagues.smsMessage', $leagueId)
                ->with('success', $successMsg);
        } catch (\Exception $e) {
            return redirect()->route('admin.leagues.smsMessage', $leagueId)
                ->withErrors(['error' => 'Failed to send SMS: ' . $e->getMessage()]);
        }
    }

    /**
     * Build condensed plain-text SMS from weekly results data.
     */
    private function buildSmsResultsText(League $league, int $weekNumber, array $data): string
    {
        $lines = [];
        $lines[] = "{$league->name} Wk{$weekNumber} Results";
        $lines[] = '';

        // Team standings
        if ($data['standings']->isNotEmpty()) {
            $lines[] = 'STANDINGS:';
            foreach ($data['standings'] as $i => $team) {
                $pos = $i + 1;
                $name = mb_substr($team->name, 0, 12);
                $lines[] = "{$pos}. {$name} {$team->week_wins}-{$team->week_losses}-{$team->week_ties} ({$team->week_points}pts)";
            }
            $lines[] = '';
        }

        // Par 3 winners
        if ($data['par3Winners']->isNotEmpty()) {
            $lines[] = 'PAR 3:';
            foreach ($data['par3Winners'] as $w) {
                $pName = $w->player ? $w->player->name : '-';
                $dist = $w->distance ? " ({$w->distance})" : '';
                $lines[] = "H{$w->hole_number}: {$pName}{$dist}";
            }
            $lines[] = '';
        }

        // Next week schedule
        if ($data['nextWeekMatches']->isNotEmpty()) {
            $lines[] = "WK{$data['nextWeekNumber']} SCHEDULE:";
            foreach ($data['nextWeekMatches'] as $match) {
                $home = $data['nextWeekTeamNames'][$match->id]['home'] ?? 'TBD';
                $away = $data['nextWeekTeamNames'][$match->id]['away'] ?? 'TBD';
                $home = mb_substr($home, 0, 10);
                $away = mb_substr($away, 0, 10);
                $time = $match->tee_time ? \Carbon\Carbon::parse($match->tee_time)->format('g:iA') : '';
                $lines[] = $time ? "{$time} {$home} v {$away}" : "{$home} v {$away}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Show league finances page
     */
    public function showFinances($leagueId)
    {
        $league = League::with(['players', 'finances.player'])->findOrFail($leagueId);

        $finances = $league->finances->groupBy('player_id');

        $feePerPlayer = (float) ($league->fee_per_player ?? 0);
        $playerCount = $league->players->count();

        $playerSummaries = [];
        foreach ($league->players->sortBy('first_name') as $player) {
            $playerFinances = $finances->get($player->id, collect());
            $feesPaid = $playerFinances->where('type', 'fee_paid')->sum('amount');
            $winnings = $playerFinances->where('type', 'winnings')->sum('amount');
            $payouts = $playerFinances->where('type', 'payout')->sum('amount');
            $feesOwed = max(0, $feePerPlayer - $feesPaid);

            $playerSummaries[] = [
                'player' => $player,
                'fees_owed' => $feesOwed,
                'fees_paid' => $feesPaid,
                'winnings' => $winnings,
                'payouts' => $payouts,
                'balance' => $feesPaid - $feePerPlayer + $winnings - $payouts,
                'transactions' => $playerFinances->sortByDesc('date')->values(),
            ];
        }

        $totalFeesOwed = $feePerPlayer * $playerCount;
        $totals = [
            'fees_owed' => $totalFeesOwed,
            'fees_paid' => $league->finances->where('type', 'fee_paid')->sum('amount'),
            'winnings' => $league->finances->where('type', 'winnings')->sum('amount'),
            'payouts' => $league->finances->where('type', 'payout')->sum('amount'),
        ];
        $totals['fees_outstanding'] = max(0, $totals['fees_owed'] - $totals['fees_paid']);
        $totals['balance'] = $totals['fees_paid'] - $totals['fees_owed'] + $totals['winnings'] - $totals['payouts'];

        return view('leagues.finances', compact('league', 'playerSummaries', 'totals'));
    }

    /**
     * Store a new finance transaction
     */
    public function storeFinance(Request $request, $leagueId)
    {
        $league = League::findOrFail($leagueId);

        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'type' => 'required|in:fee_paid,winnings,payout',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $validated['league_id'] = $league->id;

        \App\Models\LeagueFinance::create($validated);

        $typeLabels = ['fee_paid' => 'Fee payment', 'winnings' => 'Winnings', 'payout' => 'Payout'];
        $player = Player::find($validated['player_id']);

        return redirect()->route('admin.leagues.finances', $leagueId)
            ->with('success', $typeLabels[$validated['type']] . ' of $' . number_format($validated['amount'], 2) . ' recorded for ' . $player->name . '.');
    }

    /**
     * Delete a finance transaction
     */
    public function deleteFinance($leagueId, $id)
    {
        $finance = \App\Models\LeagueFinance::where('league_id', $leagueId)->findOrFail($id);
        $finance->delete();

        return redirect()->route('admin.leagues.finances', $leagueId)
            ->with('success', 'Transaction deleted.');
    }

    /**
     * Player hole summary statistics (scoring distribution)
     */
    public function holeStats($leagueId)
    {
        $league = League::with(['teams.players'])->findOrFail($leagueId);
        $data = $this->computeHoleStats($league);
        return view('leagues.hole-stats', array_merge(['league' => $league], $data));
    }

    /**
     * Update the per-league flash message and its on/off toggle.
     */
    public function updateFlashMessage(Request $request, $leagueId)
    {
        $league = League::findOrFail($leagueId);

        $validated = $request->validate([
            'flash_message' => 'nullable|string|max:1000',
            'flash_message_enabled' => 'sometimes|boolean',
        ]);

        $league->update([
            'flash_message' => $validated['flash_message'] ?? null,
            'flash_message_enabled' => (bool) ($validated['flash_message_enabled'] ?? false),
        ]);

        return redirect()->route('admin.leagues.show', $league->id)
            ->with('success', 'Flash message updated.');
    }

    /**
     * Show how often each player has been scheduled at each tee time slot.
     */
    public function teeTimeDistribution($leagueId)
    {
        $league = League::with(['players'])->findOrFail($leagueId);

        $matchPlayers = MatchPlayer::whereHas('match', function ($q) use ($leagueId) {
                $q->where('league_id', $leagueId)->whereNotNull('tee_time');
            })
            ->with(['match:id,league_id,tee_time,week_number,match_date,status', 'player'])
            ->get();

        $teeTimes = $matchPlayers
            ->pluck('match.tee_time')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $counts = [];
        foreach ($matchPlayers as $mp) {
            $tt = $mp->match->tee_time ?? null;
            if (!$tt || !$mp->player_id) continue;
            $counts[$mp->player_id][$tt] = ($counts[$mp->player_id][$tt] ?? 0) + 1;
        }

        $rows = $league->players->map(function ($player) use ($counts, $teeTimes) {
            $playerCounts = $counts[$player->id] ?? [];
            $row = [
                'player' => $player,
                'counts' => [],
                'total' => 0,
            ];
            foreach ($teeTimes as $tt) {
                $c = $playerCounts[$tt] ?? 0;
                $row['counts'][$tt] = $c;
                $row['total'] += $c;
            }
            return $row;
        })->filter(fn($r) => $r['total'] > 0)
          ->sortBy(fn($r) => strtolower($r['player']->name))
          ->values();

        return view('leagues.tee-time-distribution', compact('league', 'teeTimes', 'rows'));
    }

    /**
     * Return hole stats as an HTML partial for AJAX loading
     */
    public function holeStatsPartial($leagueId)
    {
        $league = League::with(['teams.players'])->findOrFail($leagueId);
        $data = $this->computeHoleStats($league);
        return view('leagues.hole-stats-partial', array_merge(['league' => $league], $data));
    }

    public function playerStatsPartial($leagueId)
    {
        $league = League::with(['teams', 'players'])->findOrFail($leagueId);

        $players = $league->players->sortBy('name')->values();

        // Get all completed matches with scores for this league
        $completedMatches = LeagueMatch::where('league_id', $league->id)
            ->where('status', 'completed')
            ->with(['matchPlayers.scores', 'matchPlayers.player', 'matchPlayers.substitutePlayer', 'golfCourse.courseInfo'])
            ->orderBy('week_number')
            ->get();

        // Build per-player, per-week score data
        $playerWeekData = [];
        foreach ($completedMatches as $match) {
            $holeRange = $match->holes === 'back_9' ? [10, 18] : [1, 9];
            $side = $match->holes === 'back_9' ? 'Back' : 'Front';

            // Get par values for this match's holes
            $parByHole = $match->golfCourse->courseInfo
                ->where('teebox', $match->teebox)
                ->pluck('par', 'hole_number')
                ->toArray();

            foreach ($match->matchPlayers as $mp) {
                $activePlayer = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
                if (!$activePlayer) continue;

                $playerId = $activePlayer->id;
                $scores = $mp->scores->sortBy('hole_number');
                if ($scores->isEmpty()) continue;

                $grossByHole = [];
                $netByHole = [];
                foreach ($scores as $s) {
                    $grossByHole[$s->hole_number] = $s->strokes;
                    $netByHole[$s->hole_number] = $s->net_score;
                }

                $grossTotal = array_sum($grossByHole);
                $netTotal = array_sum($netByHole);

                $playerWeekData[$playerId][] = [
                    'week' => $match->week_number,
                    'date' => $match->match_date,
                    'side' => $side,
                    'hole_start' => $holeRange[0],
                    'hole_end' => $holeRange[1],
                    'gross' => $grossByHole,
                    'net' => $netByHole,
                    'par' => $parByHole,
                    'gross_total' => $grossTotal,
                    'net_total' => $netTotal,
                ];
            }
        }

        // Build front 9 / back 9 summary per player
        $playerNineSummary = [];
        foreach ($playerWeekData as $playerId => $weeks) {
            $front = collect($weeks)->where('side', 'Front');
            $back = collect($weeks)->where('side', 'Back');

            $buildSummary = function ($rounds) {
                if ($rounds->isEmpty()) return null;
                $grossTotals = $rounds->pluck('gross_total')->filter();
                $netTotals = $rounds->pluck('net_total')->filter();

                // Per-hole averages (using relative positions 1-9)
                $holeGrossAvgs = [];
                $holeNetAvgs = [];
                $holeParAvgs = [];
                for ($i = 0; $i < 9; $i++) {
                    $holeGross = [];
                    $holeNet = [];
                    $holePar = null;
                    foreach ($rounds as $r) {
                        $holeNum = $r['hole_start'] + $i;
                        if (isset($r['gross'][$holeNum])) $holeGross[] = $r['gross'][$holeNum];
                        if (isset($r['net'][$holeNum])) $holeNet[] = $r['net'][$holeNum];
                        if ($holePar === null && isset($r['par'][$holeNum])) $holePar = $r['par'][$holeNum];
                    }
                    $holeGrossAvgs[$i + 1] = count($holeGross) ? round(array_sum($holeGross) / count($holeGross), 1) : null;
                    $holeNetAvgs[$i + 1] = count($holeNet) ? round(array_sum($holeNet) / count($holeNet), 1) : null;
                    $holeParAvgs[$i + 1] = $holePar;
                }

                return [
                    'count' => $rounds->count(),
                    'avg_gross' => $grossTotals->isNotEmpty() ? round($grossTotals->avg(), 1) : null,
                    'low_gross' => $grossTotals->isNotEmpty() ? $grossTotals->min() : null,
                    'high_gross' => $grossTotals->isNotEmpty() ? $grossTotals->max() : null,
                    'avg_net' => $netTotals->isNotEmpty() ? round($netTotals->avg(), 1) : null,
                    'low_net' => $netTotals->isNotEmpty() ? $netTotals->min() : null,
                    'hole_gross_avg' => $holeGrossAvgs,
                    'hole_net_avg' => $holeNetAvgs,
                    'hole_par' => $holeParAvgs,
                ];
            };

            $playerNineSummary[$playerId] = [
                'front' => $buildSummary($front),
                'back' => $buildSummary($back),
            ];
        }

        return view('leagues.player-stats-partial', compact('league', 'players', 'playerWeekData', 'playerNineSummary'));
    }

    public function playerHistoryPartial($leagueId)
    {
        $league = League::with(['teams', 'players'])->findOrFail($leagueId);

        $players = $league->players->sortBy('name')->values();

        $calculator = app(HandicapCalculator::class);

        // Build per-player round history and handicap chart data
        $playerRounds = [];
        $playerChartData = [];
        $playerHandicapData = [];
        $playerSummary = [];

        foreach ($players as $player) {
            $currentHandicap = $player->currentHandicap();
            $currentHI = $currentHandicap ? (float) $currentHandicap->handicap_index : null;

            $rounds = $player->rounds()->with(['golfCourse', 'scores'])->orderBy('played_at')->get()->map(function ($round) use ($calculator, $currentHI) {
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
                    $roundDiff = $calculator->computeRoundDifferential($round, $currentHI);
                    if ($roundDiff && $roundDiff['is_nine_hole'] && $currentHI !== null) {
                        $round->scoring_differential = round($roundDiff['differential'] + $calculator->expectedNineHoleDifferential($currentHI), 1);
                    } elseif ($roundDiff && !$roundDiff['is_nine_hole']) {
                        $round->scoring_differential = round($roundDiff['differential'], 1);
                    } else {
                        $round->scoring_differential = null;
                    }
                }

                if ($round->holes_played == 9) {
                    $holeNumbers = $round->scores->pluck('hole_number')->toArray();
                    $round->nine_type = !empty($holeNumbers) ? (max($holeNumbers) <= 9 ? 'Front 9' : 'Back 9') : '9 holes';
                }

                return $round;
            });

            $playerRounds[$player->id] = $rounds;

            // Score chart data
            $playerChartData[$player->id] = $rounds->map(function ($round) {
                return [
                    'date' => \Carbon\Carbon::parse($round->played_at)->format('M d, Y'),
                    'score' => $round->total_score,
                    'course' => $round->golfCourse->name,
                    'holes' => $round->holes_played ?? 18,
                ];
            })->values()->toArray();

            // Handicap history chart data
            $playerHandicapData[$player->id] = $player->handicapHistory()->orderBy('calculation_date')->get()->map(function ($h) {
                $diffs = $h->score_differentials;
                return [
                    'date' => \Carbon\Carbon::parse($h->calculation_date)->format('M d, Y'),
                    'handicap' => (float) $h->handicap_index,
                    'rounds_used' => $h->rounds_used,
                    'total_differentials' => is_array($diffs) ? count($diffs) : 0,
                ];
            })->values()->toArray();

            // Summary stats
            $rounds18 = $rounds->filter(fn($r) => ($r->holes_played ?? 18) == 18);
            $rounds9 = $rounds->filter(fn($r) => ($r->holes_played ?? 18) == 9);
            $playerSummary[$player->id] = [
                'total_rounds' => $rounds->count(),
                'avg_18' => $rounds18->count() > 0 ? round($rounds18->avg('total_score'), 1) : null,
                'avg_9' => $rounds9->count() > 0 ? round($rounds9->avg('total_score'), 1) : null,
                'low_18' => $rounds18->count() > 0 ? $rounds18->min('total_score') : null,
                'high_18' => $rounds18->count() > 0 ? $rounds18->max('total_score') : null,
                'low_9' => $rounds9->count() > 0 ? $rounds9->min('total_score') : null,
                'rounds_18' => $rounds18->count(),
                'rounds_9' => $rounds9->count(),
                'current_handicap' => $currentHandicap,
            ];
        }

        return view('leagues.player-history-partial', compact('league', 'players', 'playerRounds', 'playerChartData', 'playerHandicapData', 'playerSummary'));
    }

    public function schedulePartial($leagueId)
    {
        $league = League::with([
            'matches' => function ($query) {
                $query->orderBy('week_number')
                    ->orderBy('tee_time')
                    ->orderBy('match_date')
                    ->with(['matchPlayers.player', 'matchPlayers.substitutePlayer', 'golfCourse', 'homeTeam', 'awayTeam']);
            },
            'segments'
        ])->findOrFail($leagueId);

        $matchesByWeek = $league->matches->groupBy('week_number');
        $totalMatches = $league->matches->count();
        $completedMatches = $league->matches->where('status', 'completed')->count();

        $weekSegmentMap = [];
        foreach ($league->segments as $segment) {
            for ($w = $segment->start_week; $w <= $segment->end_week; $w++) {
                $weekSegmentMap[$w] = $segment->name;
            }
        }

        return view('leagues.schedule-partial', compact('league', 'matchesByWeek', 'totalMatches', 'completedMatches', 'weekSegmentMap'));
    }

    private function computeHoleStats(League $league): array
    {
        $empty = [
            'grossStats' => collect(),
            'netStats' => collect(),
            'grossByHole' => [],
            'netByHole' => [],
        ];

        $completedMatches = LeagueMatch::where('league_id', $league->id)
            ->where('status', 'completed')
            ->pluck('id', 'id');

        if ($completedMatches->isEmpty()) {
            return $empty;
        }

        $matchPlayers = MatchPlayer::whereIn('match_id', $completedMatches->keys())
            ->with(['player', 'substitutePlayer', 'scores', 'match.golfCourse.courseInfo'])
            ->get();

        $parLookup = [];
        foreach ($matchPlayers as $mp) {
            $match = $mp->match;
            $key = $match->golf_course_id . '_' . $match->teebox;
            if (!isset($parLookup[$key])) {
                $parLookup[$key] = $match->golfCourse->courseInfo
                    ->where('teebox', $match->teebox)
                    ->pluck('par', 'hole_number')
                    ->toArray();
            }
        }

        $grossByPlayer = [];
        $netByPlayer = [];
        $initHole = fn() => ['albatross' => 0, 'eagle' => 0, 'birdie' => 0, 'par' => 0, 'bogey' => 0, 'double' => 0, 'triple_plus' => 0, 'total' => 0];
        $grossByHole = [];
        $netByHole = [];

        foreach ($matchPlayers as $mp) {
            $activePlayer = $mp->substitute_player_id ? $mp->substitutePlayer : $mp->player;
            if (!$activePlayer) continue;

            $playerId = $activePlayer->id;
            $match = $mp->match;
            $parKey = $match->golf_course_id . '_' . $match->teebox;
            $pars = $parLookup[$parKey] ?? [];

            if (!isset($grossByPlayer[$playerId])) {
                $grossByPlayer[$playerId] = [
                    'player' => $activePlayer,
                    'albatross' => 0, 'eagle' => 0, 'birdie' => 0,
                    'par' => 0, 'bogey' => 0, 'double' => 0, 'triple_plus' => 0,
                    'total_holes' => 0,
                ];
                $netByPlayer[$playerId] = [
                    'player' => $activePlayer,
                    'albatross' => 0, 'eagle' => 0, 'birdie' => 0,
                    'par' => 0, 'bogey' => 0, 'double' => 0, 'triple_plus' => 0,
                    'total_holes' => 0,
                ];
            }

            foreach ($mp->scores as $score) {
                $holeNum = $score->hole_number;
                $holePar = $pars[$holeNum] ?? null;
                if ($holePar === null) continue;

                // Gross
                if ($score->strokes && $score->strokes > 0) {
                    $grossByPlayer[$playerId]['total_holes']++;
                    if (!isset($grossByHole[$holeNum])) $grossByHole[$holeNum] = $initHole();
                    $grossByHole[$holeNum]['total']++;
                    $diff = $score->strokes - $holePar;
                    $cat = $diff <= -3 ? 'albatross' : ($diff == -2 ? 'eagle' : ($diff == -1 ? 'birdie' : ($diff == 0 ? 'par' : ($diff == 1 ? 'bogey' : ($diff == 2 ? 'double' : 'triple_plus')))));
                    $grossByPlayer[$playerId][$cat]++;
                    $grossByHole[$holeNum][$cat]++;
                }

                // Net
                $netVal = $score->net_score;
                if ($netVal !== null && $netVal > 0) {
                    $netByPlayer[$playerId]['total_holes']++;
                    if (!isset($netByHole[$holeNum])) $netByHole[$holeNum] = $initHole();
                    $netByHole[$holeNum]['total']++;
                    $diff = $netVal - $holePar;
                    $cat = $diff <= -3 ? 'albatross' : ($diff == -2 ? 'eagle' : ($diff == -1 ? 'birdie' : ($diff == 0 ? 'par' : ($diff == 1 ? 'bogey' : ($diff == 2 ? 'double' : 'triple_plus')))));
                    $netByPlayer[$playerId][$cat]++;
                    $netByHole[$holeNum][$cat]++;
                }
            }
        }

        $sortByName = fn($a, $b) => strcmp($a['player']->name, $b['player']->name);

        $grossStats = collect(array_values($grossByPlayer))->filter(fn($s) => $s['total_holes'] > 0);
        $grossStats = $grossStats->sort($sortByName)->values();

        $netStats = collect(array_values($netByPlayer))->filter(fn($s) => $s['total_holes'] > 0);
        $netStats = $netStats->sort($sortByName)->values();

        ksort($grossByHole);
        ksort($netByHole);

        return compact('grossStats', 'netStats', 'grossByHole', 'netByHole');
    }

    /**
     * Assemble all data needed for the weekly results email
     */
    private function assembleWeeklyResultsData(League $league, int $weekNumber): array
    {
        // Get completed matches through the selected week only
        $matchesThroughWeek = LeagueMatch::with(['result'])
            ->where('league_id', $league->id)
            ->where('status', 'completed')
            ->where('week_number', '<=', $weekNumber)
            ->get();

        // Build team standings through Week X
        $teamStats = [];
        foreach ($league->teams as $team) {
            $teamStats[$team->id] = ['wins' => 0, 'losses' => 0, 'ties' => 0, 'points' => 0];
        }
        foreach ($matchesThroughWeek as $match) {
            if (!$match->result) continue;
            $result = $match->result;
            $homeId = $match->home_team_id;
            $awayId = $match->away_team_id;

            if ($homeId && isset($teamStats[$homeId])) {
                $teamStats[$homeId]['points'] += ($result->team_points_home ?? 0);
                if ($result->winning_team_id === null) {
                    $teamStats[$homeId]['ties']++;
                } elseif ($result->winning_team_id == $homeId) {
                    $teamStats[$homeId]['wins']++;
                } else {
                    $teamStats[$homeId]['losses']++;
                }
            }
            if ($awayId && isset($teamStats[$awayId])) {
                $teamStats[$awayId]['points'] += ($result->team_points_away ?? 0);
                if ($result->winning_team_id === null) {
                    $teamStats[$awayId]['ties']++;
                } elseif ($result->winning_team_id == $awayId) {
                    $teamStats[$awayId]['wins']++;
                } else {
                    $teamStats[$awayId]['losses']++;
                }
            }
        }

        // Build standings collection with calculated stats
        $allTeams = $league->segments->isNotEmpty()
            ? $league->segments->flatMap(fn($seg) => $seg->teams)
            : $league->teams;

        $standings = $allTeams->map(function ($team) use ($teamStats) {
            $stats = $teamStats[$team->id] ?? ['wins' => 0, 'losses' => 0, 'ties' => 0, 'points' => 0];
            $team->setAttribute('week_wins', $stats['wins']);
            $team->setAttribute('week_losses', $stats['losses']);
            $team->setAttribute('week_ties', $stats['ties']);
            $team->setAttribute('week_points', $stats['points']);
            $total = $stats['wins'] + $stats['losses'] + $stats['ties'];
            $team->setAttribute('week_win_pct', $total > 0 ? round(($stats['wins'] / $total) * 100, 1) : 0);
            return $team;
        })->sortByDesc('week_points')->values();

        // Par 3 winners for this week
        $par3Winners = Par3Winner::where('league_id', $league->id)
            ->where('week_number', $weekNumber)
            ->with('player')
            ->orderBy('hole_number')
            ->get();

        // Build player standings through Week X
        $playerTeamMap = [];
        foreach ($league->teams as $team) {
            foreach ($team->players as $player) {
                $playerTeamMap[$player->id] = $team->name;
            }
        }

        $completedMatchIds = $matchesThroughWeek->pluck('id');

        $playerStandings = collect();
        if ($completedMatchIds->isNotEmpty()) {
            $totalPar3WinCounts = Par3Winner::where('league_id', $league->id)
                ->where('week_number', '<=', $weekNumber)
                ->get()
                ->groupBy('player_id')
                ->map->count()
                ->toArray();

            $playerStandings = MatchPlayer::whereIn('match_id', $completedMatchIds)
                ->with(['player', 'scores', 'match.result'])
                ->get()
                ->groupBy('player_id')
                ->map(function ($entries) use ($playerTeamMap, $totalPar3WinCounts) {
                    $player = $entries->first()->player;
                    $matchesPlayed = $entries->count();

                    $matchTotals = $entries->map(function ($mp) {
                        $total = $mp->scores->sum('strokes');
                        return $total > 0 ? $total : null;
                    })->filter();

                    $avgScore = $matchTotals->count() > 0 ? round($matchTotals->avg(), 1) : null;

                    $wins = 0; $losses = 0; $ties = 0;
                    $totalSeasonPoints = 0;
                    foreach ($entries as $mp) {
                        if (!$mp->match->result) continue;
                        $isHome = $mp->position_in_pairing <= 2;
                        $result = $mp->match->result;
                        $pts = $isHome ? ($result->team_points_home ?? 0) : ($result->team_points_away ?? 0);
                        $totalSeasonPoints += $pts;
                        if ($result->winning_team_id === null) {
                            $ties++;
                        } else {
                            $playerWon = ($isHome && $result->winning_team_id == $mp->match->home_team_id)
                                || (!$isHome && $result->winning_team_id == $mp->match->away_team_id);
                            $playerWon ? $wins++ : $losses++;
                        }
                    }

                    return [
                        'player' => $player,
                        'team_name' => $playerTeamMap[$player->id] ?? '-',
                        'matches_played' => $matchesPlayed,
                        'avg_score' => $avgScore,
                        'total_par3' => $totalPar3WinCounts[$player->id] ?? 0,
                        'season_wins' => $wins,
                        'season_losses' => $losses,
                        'season_ties' => $ties,
                        'total_season_points' => $totalSeasonPoints,
                    ];
                })
                ->sortByDesc('total_season_points')
                ->values();
        }

        // Next week's schedule
        $nextWeekNumber = $weekNumber + 1;
        $nextWeekMatches = LeagueMatch::with([
                'homeTeam', 'awayTeam', 'league.teams.players',
                'golfCourse', 'matchPlayers.player'
            ])
            ->where('league_id', $league->id)
            ->where('week_number', $nextWeekNumber)
            ->orderBy('tee_time')
            ->get();

        $nextWeekTeamNames = [];
        foreach ($nextWeekMatches as $match) {
            if ($match->homeTeam && $match->awayTeam) {
                $nextWeekTeamNames[$match->id] = [
                    'home' => $match->homeTeam->name,
                    'away' => $match->awayTeam->name,
                ];
            } else {
                $homePlayers = $match->matchPlayers->where('position_in_pairing', '<=', 2);
                $awayPlayers = $match->matchPlayers->where('position_in_pairing', '>', 2);
                $firstHome = $homePlayers->first();
                $firstAway = $awayPlayers->first();
                $nextWeekTeamNames[$match->id] = [
                    'home' => $firstHome ? ($playerTeamMap[$firstHome->player_id] ?? 'Home') : 'Home',
                    'away' => $firstAway ? ($playerTeamMap[$firstAway->player_id] ?? 'Away') : 'Away',
                ];
            }
        }

        return compact('standings', 'par3Winners', 'playerStandings', 'nextWeekNumber', 'nextWeekMatches', 'nextWeekTeamNames');
    }
}

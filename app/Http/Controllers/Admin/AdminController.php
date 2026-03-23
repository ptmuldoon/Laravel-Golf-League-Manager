<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HandicapHistory;
use App\Models\League;
use App\Models\Player;
use App\Models\Round;
use App\Models\User;
use App\Models\GolfCourse;
use App\Models\LeagueMatch;
use App\Models\MatchPlayer;
use App\Services\HandicapCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_leagues' => League::count(),
            'active_leagues' => League::where('is_active', true)->count(),
            'total_players' => Player::count(),
            'total_courses' => GolfCourse::count(),
            'scheduled_matches' => LeagueMatch::where('status', 'scheduled')->count(),
            'in_progress_matches' => LeagueMatch::where('status', 'in_progress')->count(),
        ];

        $activeLeagues = League::where('is_active', true)
            ->with('teams.players', 'golfCourse', 'matches.matchPlayers.player', 'matches.matchPlayers.substitutePlayer', 'matches.homeTeam', 'matches.awayTeam')
            ->orderBy('start_date', 'desc')
            ->take(5)
            ->get();

        $recentMatches = LeagueMatch::with(['homeTeam', 'awayTeam', 'league', 'result.winningTeam'])
            ->orderBy('match_date', 'desc')
            ->take(10)
            ->get();

        $leaguesWithScores = \Illuminate\Support\Facades\DB::table('match_scores')
            ->join('match_players', 'match_scores.match_player_id', '=', 'match_players.id')
            ->join('matches', 'match_players.match_id', '=', 'matches.id')
            ->whereIn('matches.league_id', $activeLeagues->pluck('id'))
            ->distinct()
            ->pluck('matches.league_id')
            ->toArray();

        $emailConfigured = !empty(config('mail.mailers.smtp.username'));
        $smsConfigured = !empty(config('services.vonage.key')) && !empty(config('services.vonage.secret')) && !empty(config('services.vonage.sms_from'));

        return view('admin.dashboard', compact('stats', 'activeLeagues', 'recentMatches', 'leaguesWithScores', 'emailConfigured', 'smsConfigured'));
    }

    /**
     * Show players management page
     */
    public function players()
    {
        $players = Player::withCount('rounds')
            ->with('latestHandicap')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(50);

        return view('admin.players', compact('players'));
    }

    /**
     * Store one or more new players
     */
    public function storePlayer(Request $request)
    {
        $validated = $request->validate([
            'players' => 'required|array|min:1',
            'players.*.first_name' => 'required|string|max:255',
            'players.*.last_name' => 'required|string|max:255',
            'players.*.email' => 'required|email|unique:players,email',
            'players.*.phone_number' => 'nullable|string|max:20',
        ]);

        $count = 0;
        foreach ($validated['players'] as $playerData) {
            Player::create($playerData);
            $count++;
        }

        $label = $count === 1 ? '1 player' : "{$count} players";
        return redirect()->route('admin.players')->with('success', "{$label} added successfully.");
    }

    /**
     * Show edit player form
     */
    public function editPlayer($id)
    {
        $player = Player::findOrFail($id);

        return view('admin.player-edit', compact('player'));
    }

    /**
     * Update a player's email and phone number
     */
    public function updatePlayer(Request $request, $id)
    {
        $player = Player::findOrFail($id);

        $validated = $request->validate([
            'email' => 'nullable|email|unique:players,email,' . $player->id,
            'phone_number' => 'nullable|string|max:50',
            'email_enabled' => 'nullable',
            'sms_enabled' => 'nullable',
        ]);

        if ($request->has('email_enabled')) {
            $validated['email_enabled'] = $request->boolean('email_enabled');
        }
        if ($request->has('sms_enabled')) {
            $validated['sms_enabled'] = $request->boolean('sms_enabled');
        }

        $player->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'player' => $player]);
        }

        return redirect()->route('admin.players')->with('success', "Player {$player->name} updated successfully.");
    }

    /**
     * Delete a player
     */
    public function deletePlayer($id)
    {
        $player = Player::findOrFail($id);
        $name = $player->name;

        $player->delete();

        return redirect()->route('admin.players')->with('success', "Player {$name} deleted successfully.");
    }

    /**
     * Bulk update players' email and phone number
     */
    public function bulkUpdatePlayers(Request $request)
    {
        $validated = $request->validate([
            'players' => 'required|array',
            'players.*.email' => 'nullable|email',
            'players.*.phone_number' => 'nullable|string|max:20',
            'players.*.email_enabled' => 'nullable',
            'players.*.sms_enabled' => 'nullable',
        ]);

        $updated = 0;
        foreach ($validated['players'] as $id => $data) {
            $player = Player::find($id);
            if (!$player) continue;

            $changed = false;
            if (array_key_exists('email', $data) && $player->email !== $data['email']) {
                $player->email = $data['email'];
                $changed = true;
            }
            if (array_key_exists('phone_number', $data) && $player->phone_number !== $data['phone_number']) {
                $player->phone_number = $data['phone_number'];
                $changed = true;
            }
            if (array_key_exists('email_enabled', $data)) {
                $val = filter_var($data['email_enabled'], FILTER_VALIDATE_BOOLEAN);
                if ($player->email_enabled !== $val) {
                    $player->email_enabled = $val;
                    $changed = true;
                }
            }
            if (array_key_exists('sms_enabled', $data)) {
                $val = filter_var($data['sms_enabled'], FILTER_VALIDATE_BOOLEAN);
                if ($player->sms_enabled !== $val) {
                    $player->sms_enabled = $val;
                    $changed = true;
                }
            }
            if ($changed) {
                $player->save();
                $updated++;
            }
        }

        $label = $updated === 1 ? '1 player' : "{$updated} players";
        return redirect()->route('admin.players')->with('success', "{$label} updated successfully.");
    }

    /**
     * Show leagues management page
     */
    public function leagues()
    {
        $leagues = League::withCount(['teams', 'matches'])
            ->with('golfCourse')
            ->orderBy('start_date', 'desc')
            ->paginate(20);

        $leaguesWithScores = \Illuminate\Support\Facades\DB::table('match_scores')
            ->join('match_players', 'match_scores.match_player_id', '=', 'match_players.id')
            ->join('matches', 'match_players.match_id', '=', 'matches.id')
            ->whereIn('matches.league_id', $leagues->pluck('id'))
            ->distinct()
            ->pluck('matches.league_id')
            ->toArray();

        return view('admin.leagues', compact('leagues', 'leaguesWithScores'));
    }

    /**
     * Show users management page
     */
    public function users()
    {
        $users = User::orderBy('name')->get();

        return view('admin.users', compact('users'));
    }

    /**
     * Show create user form
     */
    public function createUser()
    {
        return view('admin.user-edit', ['user' => null]);
    }

    /**
     * Store a new user
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_admin' => $request->boolean('is_admin'),
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }

    /**
     * Show edit user form
     */
    public function editUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.users')->with('error', 'You cannot edit the super admin account.');
        }

        return view('admin.user-edit', compact('user'));
    }

    /**
     * Update a user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.users')->with('error', 'You cannot edit the super admin account.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Only super admins can change admin/role status
        if (auth()->user()->isSuperAdmin()) {
            if ($user->id === auth()->id()) {
                $user->is_admin = true;
                $user->is_super_admin = true;
            } else {
                $user->is_admin = $request->boolean('is_admin');
            }
        }

        $user->email_notifications = $request->boolean('email_notifications');
        $user->sms_notifications = $request->boolean('sms_notifications');

        $user->save();

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.users')->with('error', 'The super admin account cannot be deleted.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }

    /**
     * Recompute all player handicaps from scratch
     */
    public function recomputeHandicaps()
    {
        $calculator = new HandicapCalculator();

        $players = Player::all();
        $playersUpdated = 0;

        foreach ($players as $player) {
            $calculator->recalculateForPlayer($player);
            if ($player->currentHandicap()) {
                $playersUpdated++;
            }
        }

        return redirect()->route('admin.players')
            ->with('success', "Handicaps recomputed for {$playersUpdated} players.");
    }

    /**
     * Export all player scores to CSV.
     */
    public function exportPlayerScoresCsv(): StreamedResponse
    {
        $rounds = Round::with(['player', 'golfCourse', 'scores' => function ($q) {
                $q->orderBy('hole_number');
            }])
            ->orderBy('played_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="player-scores-' . now()->format('Y-m-d') . '.csv"',
        ];

        return new StreamedResponse(function () use ($rounds) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            $header = [
                'First Name', 'Last Name', 'Email',
                'Course', 'Teebox', 'Date Played', 'Holes Played',
            ];
            for ($i = 1; $i <= 18; $i++) {
                $header[] = "Hole {$i}";
            }
            $header[] = 'Total Strokes';
            fputcsv($handle, $header);

            foreach ($rounds as $round) {
                $row = [
                    $round->player->first_name ?? '',
                    $round->player->last_name ?? '',
                    $round->player->email ?? '',
                    $round->golfCourse->name ?? '',
                    $round->teebox ?? '',
                    $round->played_at ?? '',
                    $round->holes_played ?? 18,
                ];

                $scoresByHole = $round->scores->keyBy('hole_number');
                $total = 0;
                for ($i = 1; $i <= 18; $i++) {
                    $strokes = $scoresByHole->has($i) ? $scoresByHole[$i]->strokes : '';
                    $row[] = $strokes;
                    if ($strokes !== '') {
                        $total += $strokes;
                    }
                }
                $row[] = $total > 0 ? $total : '';

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Return schedule modal body HTML for a specific league week (AJAX).
     */
    public function scheduleModalWeek($leagueId, $weekNumber)
    {
        $league = League::with('teams.players')->findOrFail($leagueId);

        $weekMatches = LeagueMatch::where('league_id', $leagueId)
            ->where('week_number', $weekNumber)
            ->with(['matchPlayers.player', 'matchPlayers.substitutePlayer', 'homeTeam', 'awayTeam'])
            ->orderBy('tee_time')
            ->get();

        if ($weekMatches->isEmpty()) {
            return response()->json(['html' => '<div style="text-align:center;padding:20px;color:#888;">No matches for this week.</div>', 'week' => (int)$weekNumber]);
        }

        $firstMatch = $weekMatches->first();

        // Build team player map
        $modalTeamPlayers = [];
        $modalPlayerTeamId = [];
        foreach ($league->teams as $team) {
            $modalTeamPlayers[$team->id] = $team->players->sortBy(['first_name', 'last_name'])->values();
            foreach ($team->players as $p) {
                $modalPlayerTeamId[$p->id] = $team->id;
            }
        }

        $html = view('admin.schedule-modal-body', compact(
            'league', 'weekMatches', 'firstMatch', 'weekNumber',
            'modalTeamPlayers', 'modalPlayerTeamId'
        ))->render();

        return response()->json(['html' => $html, 'week' => (int)$weekNumber]);
    }
}

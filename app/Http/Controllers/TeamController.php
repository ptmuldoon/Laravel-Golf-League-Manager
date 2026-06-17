<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Player;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Build redirect URL preserving segment query param
     */
    private function teamRedirect($team, $flash = null)
    {
        $url = route('admin.leagues.teams.manage', $team->league_id);
        if ($team->league_segment_id) {
            $url .= '?segment=' . $team->league_segment_id;
        }
        $redirect = redirect($url);
        if ($flash) {
            $redirect->with('success', $flash);
        }
        return $redirect;
    }

    /**
     * Store a newly created team
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'league_id' => 'required|exists:leagues,id',
            'league_segment_id' => 'nullable|exists:league_segments,id',
            'name' => 'required|string|max:255',
            'color' => ['nullable', \Illuminate\Validation\Rule::in(Team::colorPalette())],
            'captain_id' => 'nullable|exists:players,id',
        ]);

        $team = Team::create($validated);

        return $this->teamRedirect($team, "Team '{$validated['name']}' created successfully!");
    }

    /**
     * Display the specified team
     */
    public function show($id)
    {
        $team = Team::with([
            'league',
            'segment',
            'players.handicapHistory',
            'captain',
            'homeMatches.result.winningTeam',
            'homeMatches.awayTeam',
            'awayMatches.result.winningTeam',
            'awayMatches.homeTeam',
        ])->findOrFail($id);

        // Merge home and away matches and sort by date
        $matches = $team->homeMatches
            ->merge($team->awayMatches)
            ->sortBy('match_date');

        return view('teams.show', compact('team', 'matches'));
    }

    /**
     * Update the specified team
     */
    public function update(Request $request, $id)
    {
        // A blank swatch ("clear") arrives as "" — normalize to null so it
        // passes the palette rule and clears the stored color.
        if ($request->has('color') && $request->input('color') === '') {
            $request->merge(['color' => null]);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => ['sometimes', 'nullable', \Illuminate\Validation\Rule::in(Team::colorPalette())],
            'captain_id' => 'nullable|exists:players,id',
        ]);

        $team = Team::findOrFail($id);
        $team->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'team' => $team]);
        }

        return $this->teamRedirect($team, 'Team updated successfully!');
    }

    /**
     * Remove the specified team
     */
    public function destroy($id)
    {
        $team = Team::findOrFail($id);
        $teamName = $team->name;

        $redirect = $this->teamRedirect($team, "Team '{$teamName}' deleted successfully!");
        $team->delete();

        return $redirect;
    }

    /**
     * Add one or more players to a team
     */
    public function addPlayer(Request $request, $teamId)
    {
        $validated = $request->validate([
            'player_ids' => 'required|array|min:1',
            'player_ids.*' => 'required|exists:players,id',
        ]);

        $team = Team::findOrFail($teamId);
        $league = $team->league;

        // Get league's player IDs
        $leaguePlayerIds = $league->players()->pluck('player_id')->toArray();

        // Validate all players are assigned to this league
        $invalidPlayers = [];
        foreach ($validated['player_ids'] as $playerId) {
            if (!in_array($playerId, $leaguePlayerIds)) {
                $player = Player::find($playerId);
                $invalidPlayers[] = $player->name;
            }
        }

        if (!empty($invalidPlayers)) {
            return back()->withErrors(['player_ids' => 'The following players are not assigned to this league: ' . implode(', ', $invalidPlayers)]);
        }

        // Get existing player IDs on the team
        $existingPlayerIds = $team->players()->pluck('player_id')->toArray();

        // Filter out players already on this team
        $newPlayerIds = array_diff($validated['player_ids'], $existingPlayerIds);

        if (empty($newPlayerIds)) {
            return back()->withErrors(['player_ids' => 'All selected players are already on this team.']);
        }

        // Check if any players are already on another team in this segment/league
        $playersOnOtherTeams = [];
        $availablePlayerIds = [];

        foreach ($newPlayerIds as $playerId) {
            // Scope duplicate check to segment if team belongs to one
            if ($team->league_segment_id) {
                $existingTeam = Team::where('league_segment_id', $team->league_segment_id)
                    ->where('id', '!=', $team->id)
                    ->whereHas('players', fn($q) => $q->where('player_id', $playerId))
                    ->first();
            } else {
                $existingTeam = $league->teams()
                    ->where('id', '!=', $team->id)
                    ->whereHas('players', fn($q) => $q->where('player_id', $playerId))
                    ->first();
            }

            if ($existingTeam) {
                $player = Player::find($playerId);
                $playersOnOtherTeams[] = "{$player->name} (already on {$existingTeam->name})";
            } else {
                $availablePlayerIds[] = $playerId;
            }
        }

        // Add available players
        if (!empty($availablePlayerIds)) {
            $team->players()->attach($availablePlayerIds);
        }

        // Build response message
        $messages = [];
        if (!empty($availablePlayerIds)) {
            $count = count($availablePlayerIds);
            $messages[] = $count === 1 ? 'Player added to team successfully!' : "{$count} players added to team successfully!";
        }

        if (!empty($playersOnOtherTeams)) {
            $errorMsg = 'The following players could not be added because they are already on another team: ' . implode(', ', $playersOnOtherTeams);
            if (empty($availablePlayerIds)) {
                return back()->withErrors(['player_ids' => $errorMsg]);
            }
            $messages[] = $errorMsg;
        }

        return $this->teamRedirect($team, implode(' ', $messages));
    }

    /**
     * Remove a player from a team
     */
    public function removePlayer($teamId, $playerId)
    {
        $team = Team::findOrFail($teamId);
        $team->players()->detach($playerId);

        return $this->teamRedirect($team, 'Player removed from team successfully!');
    }
}

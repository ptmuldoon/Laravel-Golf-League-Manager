<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'league_id',
        'league_segment_id',
        'name',
        'captain_id',
        'wins',
        'losses',
        'ties',
    ];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function segment()
    {
        return $this->belongsTo(LeagueSegment::class, 'league_segment_id');
    }

    public function captain()
    {
        return $this->belongsTo(Player::class, 'captain_id');
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'team_players')
            ->withTimestamps();
    }

    public function homeMatches()
    {
        return $this->hasMany(LeagueMatch::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(LeagueMatch::class, 'away_team_id');
    }

    public function winPercentage()
    {
        $total = $this->wins + $this->losses + $this->ties;
        return $total > 0 ? round((($this->wins + 0.5 * $this->ties) / $total) * 100, 1) : 0;
    }

    public function totalPoints()
    {
        $homePoints = MatchResult::whereHas('match', function ($query) {
            $query->where('home_team_id', $this->id);
        })->sum('team_points_home');

        $awayPoints = MatchResult::whereHas('match', function ($query) {
            $query->where('away_team_id', $this->id);
        })->sum('team_points_away');

        return $homePoints + $awayPoints;
    }
}

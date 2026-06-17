<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'league_id',
        'league_segment_id',
        'name',
        'color',
        'captain_id',
        'wins',
        'losses',
        'ties',
    ];

    /**
     * Palette of selectable team colors (hex). Used by the team manager's
     * color picker and validated on save.
     */
    public static function colorPalette(): array
    {
        return [
            '#dc3545', // red
            '#2563eb', // blue
            '#16a34a', // green
            '#f59e0b', // amber
            '#7c3aed', // purple
            '#0d9488', // teal
            '#db2777', // pink
            '#ea580c', // orange
            '#475569', // slate
            '#000000', // black
        ];
    }

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

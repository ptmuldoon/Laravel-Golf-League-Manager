<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueFinance extends Model
{
    protected $fillable = [
        'league_id',
        'league_segment_id',
        'player_id',
        'type',
        'amount',
        'date',
        'notes',
        'par3_winner_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function par3Winner()
    {
        return $this->belongsTo(Par3Winner::class);
    }
}

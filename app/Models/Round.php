<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $fillable = ['player_id', 'match_player_id', 'golf_course_id', 'teebox', 'played_at', 'holes_played'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function golfCourse()
    {
        return $this->belongsTo(GolfCourse::class);
    }

    public function matchPlayer()
    {
        return $this->belongsTo(MatchPlayer::class);
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}

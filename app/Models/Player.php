<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Player extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email', 'phone_number', 'email_enabled', 'sms_enabled'];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
    ];

    protected $appends = ['name'];

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }

    public function handicapHistory()
    {
        return $this->hasMany(HandicapHistory::class);
    }

    public function latestHandicap()
    {
        return $this->hasOne(HandicapHistory::class)->latestOfMany('calculation_date');
    }

    public function currentHandicap()
    {
        return $this->latestHandicap;
    }

    /**
     * Get the player's handicap as of a specific date.
     * Returns the most recent HandicapHistory record on or before the given date.
     */
    public function handicapAsOf($date)
    {
        return $this->handicapHistory()
            ->where('calculation_date', '<=', $date)
            ->orderByDesc('calculation_date')
            ->first();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_players')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function leagues()
    {
        return $this->belongsToMany(League::class, 'league_players');
    }

    /**
     * Get the player's full name
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}")
        );
    }
}

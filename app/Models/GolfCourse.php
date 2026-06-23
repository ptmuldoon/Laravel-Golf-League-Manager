<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GolfCourse extends Model
{
    protected $fillable = ['name', 'address', 'address_link'];

    public function courseInfo()
    {
        return $this->hasMany(CourseInfo::class);
    }

    /**
     * First-class nines for multi-nine facilities. Empty for standard
     * 18-hole courses (which use courseInfo directly).
     */
    public function nines()
    {
        return $this->hasMany(CourseNine::class)->orderBy('display_order');
    }

    public function hasNines(): bool
    {
        return $this->nines()->exists();
    }
}

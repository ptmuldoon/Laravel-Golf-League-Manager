<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseInfo extends Model
{
    protected $table = 'course_info';

    protected $fillable = ['golf_course_id', 'course_nine_id', 'teebox', 'slope', 'slope_9_front', 'slope_9_back', 'rating', 'rating_9_front', 'rating_9_back', 'hole_number', 'par', 'handicap', 'yardage'];

    public function golfCourse()
    {
        return $this->belongsTo(GolfCourse::class);
    }

    public function nine()
    {
        return $this->belongsTo(CourseNine::class, 'course_nine_id');
    }
}

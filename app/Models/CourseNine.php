<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseNine extends Model
{
    protected $fillable = [
        'golf_course_id',
        'name',
        'display_order',
    ];

    public function golfCourse()
    {
        return $this->belongsTo(GolfCourse::class);
    }

    /**
     * The hole rows (per teebox) that make up this nine.
     */
    public function courseInfo()
    {
        return $this->hasMany(CourseInfo::class);
    }

    /**
     * Build a positional hole collection for one or two nines on a teebox:
     * front nine -> positions 1-9, back nine -> 10-18, with the stroke index
     * combined into a 1-18 ranking (front = odd, back = even) when two nines
     * are played. Matches LeagueMatch's resolver so ad-hoc scorecards align
     * with league scoring.
     *
     * @return \Illuminate\Support\Collection<int, CourseInfo>
     */
    public static function positionalHoles(?int $frontNineId, ?int $backNineId, string $teebox)
    {
        $twoNines = $frontNineId && $backNineId;
        $sides = [];
        if ($frontNineId) { $sides[] = [$frontNineId, 0]; }
        if ($backNineId) { $sides[] = [$backNineId, 9]; }

        $out = collect();
        foreach ($sides as [$nineId, $offset]) {
            $rows = CourseInfo::where('course_nine_id', $nineId)
                ->where('teebox', $teebox)->get()->keyBy('hole_number');
            foreach (range(1, 9) as $h) {
                if (!isset($rows[$h])) continue;
                $clone = clone $rows[$h];
                $si = (int) $clone->handicap;
                $clone->hole_number = $h + $offset;
                $clone->handicap = $twoNines ? ($offset === 0 ? 2 * $si - 1 : 2 * $si) : $si;
                $out->push($clone);
            }
        }

        return $out->values();
    }
}

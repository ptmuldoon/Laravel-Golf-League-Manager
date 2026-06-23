<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'league_id',
        'week_number',
        'match_date',
        'tee_time',
        'golf_course_id',
        'front_nine_id',
        'back_nine_id',
        'teebox',
        'holes',
        'scoring_type',
        'score_mode',
        'home_team_id',
        'away_team_id',
        'ride_with_opponent',
        'status',
    ];

    protected $casts = [
        'match_date' => 'date',
        'ride_with_opponent' => 'boolean',
    ];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function golfCourse()
    {
        return $this->belongsTo(GolfCourse::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function matchPlayers()
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function result()
    {
        return $this->hasOne(MatchResult::class, 'match_id');
    }

    public function frontNine()
    {
        return $this->belongsTo(CourseNine::class, 'front_nine_id');
    }

    public function backNine()
    {
        return $this->belongsTo(CourseNine::class, 'back_nine_id');
    }

    /**
     * Whether this match is played over first-class nines (multi-nine facility)
     * rather than a single course's front/back nine.
     */
    public function isNinesMode(): bool
    {
        return $this->front_nine_id !== null || $this->back_nine_id !== null;
    }

    /**
     * The ordered list of hole positions this match is played over.
     *
     * Single source of truth for "which holes" a match uses. In nines mode the
     * front nine occupies positions 1-9 and the back nine 10-18; legacy single
     * courses use 1-9 (front_9) or 10-18 (back_9).
     */
    public function holeNumbers(): array
    {
        if ($this->isNinesMode()) {
            $positions = [];
            if ($this->front_nine_id) {
                $positions = array_merge($positions, range(1, 9));
            }
            if ($this->back_nine_id) {
                $positions = array_merge($positions, range(10, 18));
            }
            return $positions ?: range(1, 9);
        }

        return $this->holes === 'back_9' ? range(10, 18) : range(1, 9);
    }

    /**
     * The [start, end] hole range for this match. Convenience wrapper around
     * holeNumbers() for the many call sites that iterate a contiguous range.
     * NOTE: only meaningful for contiguous ranges (legacy + standard nines).
     */
    public function holeRange(): array
    {
        $nums = $this->holeNumbers();
        return [min($nums), max($nums)];
    }

    /**
     * Resolve each played hole position to its underlying CourseInfo row for
     * this match's teebox. Bridges legacy single-course matches and nines-mode
     * matches so consumers don't need to know which mode is in play.
     *
     * @return array<int, CourseInfo>  position (1-18) => hole row
     */
    public function holeInfo(): array
    {
        $teebox = $this->teebox;
        $map = [];

        if ($this->isNinesMode()) {
            if ($this->front_nine_id) {
                $rows = CourseInfo::where('course_nine_id', $this->front_nine_id)
                    ->where('teebox', $teebox)->get()->keyBy('hole_number');
                foreach (range(1, 9) as $pos) {
                    if (isset($rows[$pos])) {
                        $map[$pos] = $rows[$pos];
                    }
                }
            }
            if ($this->back_nine_id) {
                $rows = CourseInfo::where('course_nine_id', $this->back_nine_id)
                    ->where('teebox', $teebox)->get()->keyBy('hole_number');
                foreach (range(1, 9) as $h) {
                    if (isset($rows[$h])) {
                        $map[$h + 9] = $rows[$h];
                    }
                }
            }
            return $map;
        }

        $rows = CourseInfo::where('golf_course_id', $this->golf_course_id)
            ->where('teebox', $teebox)->get()->keyBy('hole_number');
        foreach ($this->holeNumbers() as $pos) {
            if (isset($rows[$pos])) {
                $map[$pos] = $rows[$pos];
            }
        }

        return $map;
    }

    /**
     * Display-ready hole rows for this match, numbered by played position with
     * the combined stroke index in `handicap`. Lets scorecard views/builders
     * treat legacy and nines matches identically (they key off hole_number and
     * sort by handicap). For nines this is the full 18 positional rows; for
     * legacy it's the played holes as stored.
     *
     * @return \Illuminate\Support\Collection<int, CourseInfo>
     */
    public function playedCourseInfo()
    {
        $info = $this->holeInfo();
        $si = $this->holeStrokeIndexes();

        return collect($info)->map(function ($row, $pos) use ($si) {
            $clone = clone $row;
            $clone->hole_number = $pos;
            $clone->handicap = $si[$pos] ?? $row->handicap;
            return $clone;
        })->values();
    }

    /**
     * Effective stroke index (hole-handicap ranking) per played position.
     *
     * When two distinct nines are combined into 18, each nine carries its own
     * 1-9 stroke index, so they are interleaved into a 1-18 ranking the standard
     * way: front nine -> odd ranks (2*si-1), back nine -> even ranks (2*si).
     * Single-nine and legacy matches keep each hole's stored stroke index.
     *
     * @return array<int, int>  position => stroke index
     */
    public function holeStrokeIndexes(): array
    {
        $info = $this->holeInfo();
        $twoNines = $this->isNinesMode() && $this->front_nine_id && $this->back_nine_id;

        $out = [];
        foreach ($info as $pos => $row) {
            $si = (int) $row->handicap;
            if ($twoNines) {
                $out[$pos] = $pos <= 9 ? (2 * $si - 1) : (2 * $si);
            } else {
                $out[$pos] = $si;
            }
        }

        return $out;
    }

    /**
     * The course rating, slope and par used for this match's handicap math.
     *
     * Nines mode: 18-hole rating = sum of the played nines' 9-hole ratings,
     * slope = average of their slopes (WHS combination), par = total par.
     * Legacy mode: the course/teebox values for the played nine.
     *
     * @return array{rating: float, slope: float, par: int}
     */
    public function ratingSlopePar(): array
    {
        if ($this->isNinesMode()) {
            $rating = 0.0;
            $slopeSum = 0.0;
            $slopeCount = 0;
            $par = 0;
            foreach (array_filter([$this->front_nine_id, $this->back_nine_id]) as $nineId) {
                $rows = CourseInfo::where('course_nine_id', $nineId)
                    ->where('teebox', $this->teebox)->get();
                if ($rows->isEmpty()) continue;
                $rating += (float) $rows->first()->rating; // nine's 9-hole rating
                $slopeSum += (float) $rows->first()->slope; // nine's 9-hole slope
                $slopeCount++;
                $par += (int) $rows->sum('par');
            }
            return [
                'rating' => $rating,
                'slope' => $slopeCount ? round($slopeSum / $slopeCount) : 113.0,
                'par' => $par,
            ];
        }

        $rows = CourseInfo::where('golf_course_id', $this->golf_course_id)
            ->where('teebox', $this->teebox)->get();
        $hole1 = $rows->firstWhere('hole_number', 1) ?? $rows->first();
        $playedPar = (int) $rows->whereIn('hole_number', $this->holeNumbers())->sum('par');

        return [
            'rating' => (float) ($hole1->rating ?? 0),
            'slope' => (float) ($hole1->slope ?? 113),
            'par' => $playedPar,
        ];
    }

    public function scopeByWeek($query, $week)
    {
        return $query->where('week_number', $week);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}

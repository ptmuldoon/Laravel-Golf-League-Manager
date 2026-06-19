<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use Illuminate\Http\Request;

class ScorecardGeneratorController extends Controller
{
    /**
     * Show the ad-hoc scorecard generator form.
     */
    public function form()
    {
        $courses = GolfCourse::with('courseInfo')->orderBy('name')->get();

        // Map of course id => sorted list of distinct teebox names (for the
        // dependent teebox dropdown).
        $courseTeeboxes = $courses->mapWithKeys(function ($course) {
            return [$course->id => $course->courseInfo->pluck('teebox')->unique()->sort()->values()->all()];
        });

        return view('scorecard-generator.form', compact('courses', 'courseTeeboxes'));
    }

    /**
     * Build a printable scorecard from the typed-in players and selected course.
     */
    public function print(Request $request)
    {
        $validated = $request->validate([
            'golf_course_id' => 'required|exists:golf_courses,id',
            'teebox' => 'required|string',
            'holes' => 'required|in:front_9,back_9,full_18',
            'players' => 'required|array|min:1|max:4',
            'players.*.name' => 'nullable|string|max:60',
            'players.*.handicap' => 'nullable|integer|min:0|max:54',
        ]);

        $course = GolfCourse::findOrFail($validated['golf_course_id']);

        $range = match ($validated['holes']) {
            'front_9' => [1, 9],
            'back_9' => [10, 18],
            default => [1, 18],
        };
        $holesLabel = match ($validated['holes']) {
            'front_9' => 'Front 9',
            'back_9' => 'Back 9',
            default => '18 Holes',
        };

        // Load the full set of holes for the teebox so handicap strokes are
        // allocated across all 18 by true stroke index, then only the holes
        // being played are shown. This naturally "halves" the strokes for a
        // 9-hole round and assigns the odd stroke to the nine whose holes carry
        // the lower stroke indexes.
        $allHoles = $course->courseInfo()
            ->where('teebox', $validated['teebox'])
            ->orderBy('hole_number')
            ->get();

        $holes = $allHoles->whereBetween('hole_number', $range)->values();

        if ($holes->isEmpty()) {
            return back()->withErrors(['error' => 'No hole data found for that course/teebox.'])->withInput();
        }

        // Stroke index order across all holes (lowest handicap = hardest hole =
        // first stroke given).
        $siOrder = $allHoles->sortBy('handicap')->pluck('hole_number')->values()->all();

        $players = [];
        foreach ($validated['players'] as $p) {
            $name = trim($p['name'] ?? '');
            if ($name === '') continue; // skip empty rows
            $hcp = (int) ($p['handicap'] ?? 0);

            $strokes = [];
            foreach ($allHoles as $h) {
                $strokes[$h->hole_number] = 0;
            }
            $remaining = max(0, $hcp);
            while ($remaining > 0) {
                foreach ($siOrder as $hn) {
                    if ($remaining <= 0) break;
                    $strokes[$hn]++;
                    $remaining--;
                }
            }

            $players[] = ['name' => $name, 'handicap' => $hcp, 'strokes' => $strokes];
        }

        if (empty($players)) {
            return back()->withErrors(['error' => 'Enter at least one player name.'])->withInput();
        }

        return view('scorecard-generator.print', [
            'course' => $course,
            'teebox' => $validated['teebox'],
            'holesLabel' => $holesLabel,
            'holes' => $holes,
            'players' => $players,
            'nineHole' => $validated['holes'] !== 'full_18',
        ]);
    }
}

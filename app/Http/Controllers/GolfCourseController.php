<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GolfCourseController extends Controller
{
    public function index()
    {
        $courses = GolfCourse::orderBy('name')->get();
        return view('golf-courses.index', compact('courses'));
    }

    public function show($id)
    {
        $course = GolfCourse::with('courseInfo')->findOrFail($id);

        // Group course info by teebox
        $teeboxes = $course->courseInfo->groupBy('teebox');

        return view('golf-courses.show', compact('course', 'teeboxes'));
    }

    public function create()
    {
        return view('golf-courses.create');
    }

    public function searchCourse(Request $request)
    {
        $request->validate(['query' => 'required|string|max:255']);

        $apiKey = config('services.anthropic.key');
        if (!$apiKey) {
            return response()->json(['error' => 'Anthropic API key not configured. Add ANTHROPIC_API_KEY to your .env file.'], 503);
        }

        $query = trim($request->input('query'));

        $prompt = <<<PROMPT
Find detailed golf course information for: "{$query}"

Search the web for accurate data including tee box ratings and slopes from official sources (USGA, state golf associations, course websites, GolfAdvisor, etc.).

Return ONLY a valid JSON object (no markdown fences, no explanation text) with this exact structure:
{
  "found": true,
  "name": "Full Official Course Name",
  "address": "Street Address, City, State ZIP",
  "address_link": "https://maps.google.com/?q=Course+Name+City+State",
  "holes": 18,
  "pars": [4,4,3,5,4,4,3,5,4,4,4,3,5,4,4,3,5,4],
  "teeboxes": [
    {
      "name": "Black",
      "rating": 74.5,
      "slope": 140,
      "rating_9_front": 37.2,
      "rating_9_back": 37.3,
      "slope_9_front": 139,
      "slope_9_back": 141
    }
  ],
  "notes": "Brief note about data source and confidence"
}

Rules:
- Include ALL available tee boxes ordered from most to least difficult
- If 9-hole ratings are not available, estimate: rating_9_front = rating_9_back = rating/2, slope_9_front = slope-1, slope_9_back = slope+1
- The pars array must contain exactly 18 integers (or 9 for a 9-hole course), each between 3 and 6
- If the course cannot be found or data is uncertain, set "found": false and explain in "notes"
- Return ONLY the JSON object, nothing else
PROMPT;

        $response = Http::timeout(60)->withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta' => 'web-search-2025-03-05',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 2048,
            'tools' => [
                ['type' => 'web_search_20250305', 'name' => 'web_search', 'max_uses' => 5],
            ],
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Search service unavailable. Please enter details manually.'], 500);
        }

        $data = $response->json();

        // Collect all text blocks from the response (web search may produce multiple content blocks)
        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        // Extract the JSON object from the text
        if (preg_match('/\{[\s\S]*\}/m', $text, $matches)) {
            $courseData = json_decode($matches[0], true);
            if ($courseData) {
                return response()->json($courseData);
            }
        }

        return response()->json(['error' => 'Could not parse course information. Please enter details manually.'], 422);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'address_link' => 'nullable|url',
            'holes' => 'required|in:9,18',
            'teeboxes' => 'required|array|min:1',
            'teeboxes.*.name' => 'required|string',
            'teeboxes.*.rating' => 'required|numeric|min:50|max:85',
            'teeboxes.*.slope' => 'required|numeric|min:55|max:155',
            'teeboxes.*.rating_9_front' => 'nullable|numeric|min:20|max:45',
            'teeboxes.*.rating_9_back' => 'nullable|numeric|min:20|max:45',
            'teeboxes.*.slope_9_front' => 'nullable|numeric|min:55|max:155',
            'teeboxes.*.slope_9_back' => 'nullable|numeric|min:55|max:155',
            'pars' => 'required|array',
            'pars.*' => 'required|integer|min:3|max:6',
            'yardages' => 'nullable|array',
            'yardages.*' => 'nullable|integer|min:50|max:700',
            'handicaps' => 'nullable|array',
            'handicaps.*' => 'nullable|integer|min:1|max:18',
        ]);

        // Create the course
        $course = GolfCourse::create([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'address_link' => $validated['address_link'],
        ]);

        // Create course info for each teebox
        foreach ($validated['teeboxes'] as $teeboxData) {
            $holesCount = (int) $validated['holes'];

            for ($holeNumber = 1; $holeNumber <= $holesCount; $holeNumber++) {
                $course->courseInfo()->create([
                    'teebox' => $teeboxData['name'],
                    'slope' => $teeboxData['slope'],
                    'rating' => $teeboxData['rating'],
                    'slope_9_front' => $teeboxData['slope_9_front'] ?? null,
                    'slope_9_back' => $teeboxData['slope_9_back'] ?? null,
                    'rating_9_front' => $teeboxData['rating_9_front'] ?? null,
                    'rating_9_back' => $teeboxData['rating_9_back'] ?? null,
                    'hole_number' => $holeNumber,
                    'par' => $validated['pars'][$holeNumber - 1],
                    'yardage' => $validated['yardages'][$holeNumber - 1] ?? null,
                    'handicap' => $validated['handicaps'][$holeNumber - 1] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.courses.show', $course->id)
            ->with('success', 'Golf course created successfully!');
    }

    public function edit($id)
    {
        $course = GolfCourse::with('courseInfo')->findOrFail($id);

        // Group course info by teebox
        $teeboxes = $course->courseInfo->groupBy('teebox');

        return view('golf-courses.edit', compact('course', 'teeboxes'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'address_link' => 'nullable|url',
        ]);

        $course = GolfCourse::findOrFail($id);
        $course->update($validated);

        return redirect()->route('admin.courses.show', $id)
            ->with('success', 'Golf course updated successfully!');
    }

    public function destroy($id)
    {
        $course = GolfCourse::findOrFail($id);
        $courseName = $course->name;

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', "Golf course '{$courseName}' deleted successfully!");
    }

    public function manageTeeboxes($courseId)
    {
        $course = GolfCourse::with('courseInfo')->findOrFail($courseId);
        $teeboxes = $course->courseInfo->groupBy('teebox');

        return view('golf-courses.manage-teeboxes', compact('course', 'teeboxes'));
    }

    public function addTeebox(Request $request, $courseId)
    {
        $course = GolfCourse::findOrFail($courseId);

        $validated = $request->validate([
            'teebox_name' => 'required|string|max:50',
            'rating' => 'required|numeric|min:50|max:85',
            'slope' => 'required|numeric|min:55|max:155',
            'rating_9_front' => 'nullable|numeric|min:20|max:45',
            'rating_9_back' => 'nullable|numeric|min:20|max:45',
            'slope_9_front' => 'nullable|numeric|min:55|max:155',
            'slope_9_back' => 'nullable|numeric|min:55|max:155',
            'pars' => 'required|array',
            'pars.*' => 'required|integer|min:3|max:6',
            'yardages' => 'nullable|array',
            'yardages.*' => 'nullable|integer|min:50|max:700',
            'handicaps' => 'nullable|array',
            'handicaps.*' => 'nullable|integer|min:1|max:18',
        ]);

        // Check if teebox already exists
        $existingTeebox = $course->courseInfo()->where('teebox', $validated['teebox_name'])->first();
        if ($existingTeebox) {
            return back()->withErrors(['teebox_name' => 'A teebox with this name already exists for this course.']);
        }

        // Create course info for each hole
        foreach ($validated['pars'] as $holeNumber => $par) {
            $course->courseInfo()->create([
                'teebox' => $validated['teebox_name'],
                'slope' => $validated['slope'],
                'rating' => $validated['rating'],
                'slope_9_front' => $validated['slope_9_front'] ?? null,
                'slope_9_back' => $validated['slope_9_back'] ?? null,
                'rating_9_front' => $validated['rating_9_front'] ?? null,
                'rating_9_back' => $validated['rating_9_back'] ?? null,
                'hole_number' => $holeNumber + 1,
                'par' => $par,
                'yardage' => $validated['yardages'][$holeNumber] ?? null,
                'handicap' => $validated['handicaps'][$holeNumber] ?? null,
            ]);
        }

        return redirect()->route('admin.courses.teeboxes.manage', $courseId)
            ->with('success', "Teebox '{$validated['teebox_name']}' added successfully!");
    }

    public function updateTeebox(Request $request, $courseId, $teeboxName)
    {
        $course = GolfCourse::findOrFail($courseId);

        $validated = $request->validate([
            'rating' => 'required|numeric|min:50|max:85',
            'slope' => 'required|numeric|min:55|max:155',
            'rating_9_front' => 'nullable|numeric|min:20|max:45',
            'rating_9_back' => 'nullable|numeric|min:20|max:45',
            'slope_9_front' => 'nullable|numeric|min:55|max:155',
            'slope_9_back' => 'nullable|numeric|min:55|max:155',
            'pars' => 'required|array',
            'pars.*' => 'required|integer|min:3|max:6',
            'yardages' => 'nullable|array',
            'yardages.*' => 'nullable|integer|min:50|max:700',
            'handicaps' => 'nullable|array',
            'handicaps.*' => 'nullable|integer|min:1|max:18',
        ]);

        // Get all holes for this teebox
        $holes = $course->courseInfo()->where('teebox', $teeboxName)->orderBy('hole_number')->get();

        if ($holes->isEmpty()) {
            return back()->withErrors(['error' => 'Teebox not found.']);
        }

        // Update each hole
        foreach ($holes as $index => $hole) {
            $hole->update([
                'slope' => $validated['slope'],
                'rating' => $validated['rating'],
                'slope_9_front' => $validated['slope_9_front'] ?? null,
                'slope_9_back' => $validated['slope_9_back'] ?? null,
                'rating_9_front' => $validated['rating_9_front'] ?? null,
                'rating_9_back' => $validated['rating_9_back'] ?? null,
                'par' => $validated['pars'][$index],
                'yardage' => $validated['yardages'][$index] ?? null,
                'handicap' => $validated['handicaps'][$index] ?? null,
            ]);
        }

        return redirect()->route('admin.courses.teeboxes.manage', $courseId)
            ->with('success', "Teebox '{$teeboxName}' updated successfully!");
    }

    public function deleteTeebox($courseId, $teeboxName)
    {
        $course = GolfCourse::findOrFail($courseId);

        // Check if this is the last teebox
        $teeboxCount = $course->courseInfo()->groupBy('teebox')->get()->count();
        if ($teeboxCount <= 1) {
            return back()->withErrors(['error' => 'Cannot delete the last teebox. A course must have at least one teebox.']);
        }

        // Delete all course info for this teebox
        $deleted = $course->courseInfo()->where('teebox', $teeboxName)->delete();

        if ($deleted === 0) {
            return back()->withErrors(['error' => 'Teebox not found.']);
        }

        return redirect()->route('admin.courses.teeboxes.manage', $courseId)
            ->with('success', "Teebox '{$teeboxName}' deleted successfully!");
    }

    /**
     * Manage first-class nines for a multi-nine facility (e.g. a 27-hole course
     * with three nines that combine into 18).
     */
    public function manageNines($courseId)
    {
        $course = GolfCourse::with(['nines.courseInfo'])->findOrFail($courseId);

        return view('golf-courses.manage-nines', compact('course'));
    }

    public function addNine(Request $request, $courseId)
    {
        $course = GolfCourse::findOrFail($courseId);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'teebox' => 'required|string|max:50',
            'rating' => 'required|numeric|min:20|max:45',  // 9-hole rating
            'slope' => 'required|numeric|min:55|max:155',  // 9-hole slope
            'pars' => 'required|array|size:9',
            'pars.*' => 'required|integer|min:3|max:6',
            'handicaps' => 'nullable|array',
            'handicaps.*' => 'nullable|integer|min:1|max:9',
            'yardages' => 'nullable|array',
            'yardages.*' => 'nullable|integer|min:50|max:700',
        ]);

        $nine = $course->nines()->create([
            'name' => $validated['name'],
            'display_order' => $course->nines()->count() + 1,
        ]);

        foreach ($validated['pars'] as $i => $par) {
            $course->courseInfo()->create([
                'course_nine_id' => $nine->id,
                'teebox' => $validated['teebox'],
                'slope' => $validated['slope'],
                'rating' => $validated['rating'],
                'hole_number' => $i + 1,
                'par' => $par,
                'handicap' => $validated['handicaps'][$i] ?? ($i + 1),
                'yardage' => $validated['yardages'][$i] ?? null,
            ]);
        }

        return redirect()->route('admin.courses.nines.manage', $courseId)
            ->with('success', "Nine '{$validated['name']}' added.");
    }

    public function deleteNine($courseId, $nineId)
    {
        $course = GolfCourse::findOrFail($courseId);
        $nine = $course->nines()->findOrFail($nineId);

        // Remove the nine's hole rows, then the nine itself.
        $course->courseInfo()->where('course_nine_id', $nine->id)->delete();
        $nine->delete();

        return redirect()->route('admin.courses.nines.manage', $courseId)
            ->with('success', 'Nine deleted.');
    }
}

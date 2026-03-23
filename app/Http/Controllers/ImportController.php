<?php

namespace App\Http\Controllers;

use App\Models\GolfCourse;
use App\Models\CourseInfo;
use App\Models\Player;
use App\Models\Round;
use App\Models\Score;
use App\Services\HandicapCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    /**
     * Show the course import form
     */
    public function showCourseImport()
    {
        return view('import.courses');
    }

    /**
     * Process course CSV import
     */
    public function importCourses(Request $request)
    {
        // Validate file upload
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            // Parse CSV
            $csvData = $this->parseCsvFile($request->file('csv_file'));

            // Validate headers
            $requiredHeaders = ['course_name', 'address', 'teebox', 'hole_number', 'par', 'rating', 'slope'];
            $missingHeaders = array_diff($requiredHeaders, $csvData['headers']);

            if (!empty($missingHeaders)) {
                return back()->withErrors(['csv_file' => 'Missing required columns: ' . implode(', ', $missingHeaders)]);
            }

            // Validate and group data
            $errors = [];
            $groupedData = [];

            foreach ($csvData['rows'] as $index => $row) {
                $rowNumber = $index + 2; // +2 for header row and 0-index
                $rowErrors = $this->validateCourseRow($row, $rowNumber);

                if (!empty($rowErrors)) {
                    $courseName = $row['course_name'] ?? 'Unknown';
                    if (!isset($errors[$courseName])) {
                        $errors[$courseName] = [];
                    }
                    $errors[$courseName] = array_merge($errors[$courseName], $rowErrors);
                    continue;
                }

                // Group by course name, then teebox
                $courseName = $row['course_name'];
                $teebox = $row['teebox'];

                if (!isset($groupedData[$courseName])) {
                    $groupedData[$courseName] = [
                        'address' => $row['address'],
                        'address_link' => $row['address_link'] ?? null,
                        'teeboxes' => [],
                    ];
                }

                if (!isset($groupedData[$courseName]['teeboxes'][$teebox])) {
                    $groupedData[$courseName]['teeboxes'][$teebox] = [
                        'rating' => $row['rating'],
                        'slope' => $row['slope'],
                        'rating_9_front' => $row['rating_9_front'] ?? null,
                        'rating_9_back' => $row['rating_9_back'] ?? null,
                        'slope_9_front' => $row['slope_9_front'] ?? null,
                        'slope_9_back' => $row['slope_9_back'] ?? null,
                        'holes' => [],
                    ];
                }

                $groupedData[$courseName]['teeboxes'][$teebox]['holes'][] = [
                    'hole_number' => (int) $row['hole_number'],
                    'par' => (int) $row['par'],
                    'handicap' => isset($row['handicap']) && $row['handicap'] !== '' ? (int) $row['handicap'] : null,
                    'yardage' => isset($row['yardage']) && $row['yardage'] !== '' ? (int) $row['yardage'] : null,
                ];
            }

            if (!empty($errors)) {
                return back()->with('importErrors', $errors);
            }

            // Validate hole sequences
            foreach ($groupedData as $courseName => $courseData) {
                foreach ($courseData['teeboxes'] as $teebox => $teeboxData) {
                    $holeNumbers = array_column($teeboxData['holes'], 'hole_number');
                    sort($holeNumbers);

                    $expected = range(1, count($holeNumbers));
                    if ($holeNumbers !== $expected && $holeNumbers !== range(1, 9) && $holeNumbers !== range(1, 18)) {
                        if (!isset($errors[$courseName])) {
                            $errors[$courseName] = [];
                        }
                        $errors[$courseName][] = "Teebox '$teebox': Invalid hole sequence. Must be 1-9 or 1-18.";
                    }
                }
            }

            if (!empty($errors)) {
                return back()->with('importErrors', $errors);
            }

            // Import data in transaction
            $courseCount = 0;
            $teeboxCount = 0;
            $holeCount = 0;

            DB::transaction(function () use ($groupedData, &$courseCount, &$teeboxCount, &$holeCount) {
                foreach ($groupedData as $courseName => $courseData) {
                    // Create or update course
                    $course = GolfCourse::updateOrCreate(
                        ['name' => $courseName],
                        [
                            'address' => $courseData['address'],
                            'address_link' => $courseData['address_link'],
                        ]
                    );

                    $courseCount++;

                    foreach ($courseData['teeboxes'] as $teeboxName => $teeboxData) {
                        // Delete existing course info for this teebox
                        CourseInfo::where('golf_course_id', $course->id)
                            ->where('teebox', $teeboxName)
                            ->delete();

                        $teeboxCount++;

                        // Bulk insert holes
                        $holesData = [];
                        foreach ($teeboxData['holes'] as $hole) {
                            $holesData[] = [
                                'golf_course_id' => $course->id,
                                'teebox' => $teeboxName,
                                'hole_number' => $hole['hole_number'],
                                'par' => $hole['par'],
                                'handicap' => $hole['handicap'] ?? null,
                                'yardage' => $hole['yardage'] ?? null,
                                'rating' => $teeboxData['rating'],
                                'slope' => $teeboxData['slope'],
                                'rating_9_front' => $teeboxData['rating_9_front'],
                                'rating_9_back' => $teeboxData['rating_9_back'],
                                'slope_9_front' => $teeboxData['slope_9_front'],
                                'slope_9_back' => $teeboxData['slope_9_back'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $holeCount++;
                        }

                        CourseInfo::insert($holesData);
                    }
                }
            });

            return redirect()->route('admin.courses.index')
                ->with('success', "Imported $courseCount courses, $teeboxCount teeboxes, $holeCount holes total.");

        } catch (\Exception $e) {
            return back()->withErrors(['csv_file' => 'Error processing CSV: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the score import form
     */
    public function showScoreImport()
    {
        return view('import.scores');
    }

    /**
     * Process score CSV import
     */
    public function importScores(Request $request)
    {
        // Validate file upload
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            // Parse CSV
            $csvData = $this->parseCsvFile($request->file('csv_file'));

            // Detect format type
            $format = in_array('total_score', $csvData['headers']) ? 'total_only' : 'hole_by_hole';

            // Validate headers
            $requiredHeaders = ['first_name', 'last_name', 'email', 'course_name', 'teebox', 'played_at', 'holes_played'];

            if ($format === 'total_only') {
                $requiredHeaders[] = 'total_score';
            }

            $missingHeaders = array_diff($requiredHeaders, $csvData['headers']);

            if (!empty($missingHeaders)) {
                return back()->withErrors(['csv_file' => 'Missing required columns: ' . implode(', ', $missingHeaders)]);
            }

            // Validate rows
            $errors = [];
            $validRows = [];

            foreach ($csvData['rows'] as $index => $row) {
                $rowNumber = $index + 2;
                $rowErrors = $this->validateScoreRow($row, $rowNumber, $format);

                if (!empty($rowErrors)) {
                    $errors["Row $rowNumber"] = $rowErrors;
                    continue;
                }

                $validRows[] = $row;
            }

            if (!empty($errors)) {
                return back()->with('importErrors', $errors);
            }

            // Cross-validate courses and teeboxes
            $courseCache = [];
            foreach ($validRows as $index => $row) {
                $rowNumber = $index + 2;
                $courseName = $row['course_name'];

                if (!isset($courseCache[$courseName])) {
                    $course = $this->getCourseByName($courseName);
                    if (!$course) {
                        $errors["Row $rowNumber"][] = "Course '$courseName' not found in database.";
                        continue;
                    }
                    $courseCache[$courseName] = $course;
                }

                $course = $courseCache[$courseName];
                $teebox = $row['teebox'];

                if (!$this->teeboxExists($course->id, $teebox)) {
                    $errors["Row $rowNumber"][] = "Teebox '$teebox' not found for course '$courseName'.";
                }
            }

            if (!empty($errors)) {
                return back()->with('importErrors', $errors);
            }

            // Import data in transaction
            $roundCount = 0;
            $playerCount = 0;
            $updatedRoundCount = 0;
            $playerEmails = [];

            DB::transaction(function () use ($validRows, $format, $courseCache, &$roundCount, &$playerCount, &$updatedRoundCount, &$playerEmails) {
                $affectedPlayerIds = [];
                foreach ($validRows as $row) {
                    // Create or update player
                    $player = Player::updateOrCreate(
                        ['email' => $row['email']],
                        [
                            'first_name' => $row['first_name'],
                            'last_name' => $row['last_name'],
                            'phone_number' => $row['phone_number'] ?? null,
                        ]
                    );

                    if (!in_array($player->email, $playerEmails)) {
                        $playerEmails[] = $player->email;
                        $playerCount++;
                    }

                    // Get course
                    $course = $courseCache[$row['course_name']];

                    // Check for duplicate round
                    $existingRound = Round::where('player_id', $player->id)
                        ->where('golf_course_id', $course->id)
                        ->where('teebox', $row['teebox'])
                        ->where('played_at', $row['played_at'])
                        ->first();

                    if ($existingRound) {
                        $existingRound->delete(); // Cascade deletes scores
                        $updatedRoundCount++;
                    }

                    // Determine nine_type if 9 holes
                    $nineType = null;
                    if ($row['holes_played'] == 9) {
                        $nineType = $row['nine_type'] ?? 'front';
                    }

                    // Create round
                    $round = Round::create([
                        'player_id' => $player->id,
                        'golf_course_id' => $course->id,
                        'teebox' => $row['teebox'],
                        'played_at' => $row['played_at'],
                        'holes_played' => (int) $row['holes_played'],
                    ]);

                    $roundCount++;

                    // Create scores
                    if ($format === 'hole_by_hole') {
                        // Create scores from individual holes
                        for ($i = 1; $i <= 18; $i++) {
                            $holeKey = "hole_$i";
                            if (isset($row[$holeKey]) && $row[$holeKey] !== '' && $row[$holeKey] !== null) {
                                Score::create([
                                    'round_id' => $round->id,
                                    'hole_number' => $i,
                                    'strokes' => (int) $row[$holeKey],
                                ]);
                            }
                        }
                    } else {
                        // Total only - distribute score
                        $totalScore = (int) $row['total_score'];
                        $holesPlayed = (int) $row['holes_played'];

                        $startHole = 1;
                        $endHole = $holesPlayed;

                        if ($holesPlayed == 9) {
                            $startHole = $nineType === 'back' ? 10 : 1;
                            $endHole = $nineType === 'back' ? 18 : 9;
                        }

                        $scores = $this->distributeTotalScore($totalScore, $course->id, $row['teebox'], $startHole, $endHole);

                        foreach ($scores as $holeNumber => $strokes) {
                            Score::create([
                                'round_id' => $round->id,
                                'hole_number' => $holeNumber,
                                'strokes' => $strokes,
                            ]);
                        }
                    }

                    // Compute and store per-hole adjusted gross and net scores
                    $round->load('scores');
                    $calculator = app(HandicapCalculator::class);

                    $slopeRating = $calculator->getSlopeAndRating($round);
                    $courseHandicap = null;
                    $currentHandicap = $player->currentHandicap();
                    if ($currentHandicap && $slopeRating) {
                        $hi = (float) $currentHandicap->handicap_index;
                        $courseHandicap = ($hi * $slopeRating['slope']) / 113;
                        if (($round->holes_played ?? 18) == 9) {
                            $courseHandicap = $courseHandicap / 2;
                        }
                    }

                    $perHoleScores = $calculator->calculatePerHoleScores($round, $courseHandicap);
                    foreach ($perHoleScores as $holeNumber => $holeData) {
                        Score::where('round_id', $round->id)
                            ->where('hole_number', $holeNumber)
                            ->update([
                                'adjusted_gross' => $holeData['adjusted_gross'],
                                'net_score' => $holeData['net_score'],
                            ]);
                    }

                    // Track player for handicap recalculation
                    $affectedPlayerIds[$player->id] = $player;
                }

                // Recalculate handicaps for all affected players
                $calculator = app(HandicapCalculator::class);
                foreach ($affectedPlayerIds as $player) {
                    $calculator->recalculateForPlayer($player);
                }
            });

            $message = "Imported $roundCount rounds for $playerCount players.";
            if ($updatedRoundCount > 0) {
                $message .= " Note: $updatedRoundCount duplicate rounds were updated.";
            }

            return redirect()->route('admin.players')->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['csv_file' => 'Error processing CSV: ' . $e->getMessage()]);
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCsvFile($file)
    {
        $path = $file->getRealPath();
        $content = file_get_contents($path);

        // Strip UTF-8 BOM if present (common in Excel exports)
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $lines = explode("\n", str_replace("\r\n", "\n", $content));
        $csv = array_map(function($line) {
            return str_getcsv($line, ',', '"', '\\');
        }, array_filter($lines, fn($line) => trim($line) !== ''));

        if (empty($csv)) {
            throw new \Exception('CSV file is empty');
        }

        $headers = array_map('trim', array_shift($csv));
        $rows = [];

        foreach ($csv as $row) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, array_map('trim', $row));
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Validate a course CSV row
     */
    private function validateCourseRow($row, $rowNumber)
    {
        $errors = [];

        $validator = Validator::make($row, [
            'course_name' => 'required|string|max:255',
            'address' => 'required|string',
            'address_link' => 'nullable|url',
            'teebox' => 'required|string|max:50',
            'hole_number' => 'required|integer|min:1|max:18',
            'par' => 'required|integer|min:3|max:6',
            'rating' => 'required|numeric|min:50|max:85',
            'slope' => 'required|numeric|min:55|max:155',
            'rating_9_front' => 'nullable|numeric|min:20|max:45',
            'rating_9_back' => 'nullable|numeric|min:20|max:45',
            'slope_9_front' => 'nullable|numeric|min:55|max:155',
            'slope_9_back' => 'nullable|numeric|min:55|max:155',
            'handicap' => 'nullable|integer|min:1|max:18',
            'yardage' => 'nullable|integer|min:50|max:700',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $errors[] = "Row $rowNumber: $error";
            }
        }

        return $errors;
    }

    /**
     * Validate a score CSV row
     */
    private function validateScoreRow($row, $rowNumber, $format)
    {
        $errors = [];

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'course_name' => 'required|string',
            'teebox' => 'required|string',
            'played_at' => 'required|date_format:Y-m-d|before_or_equal:today',
            'holes_played' => 'required|in:9,18',
            'nine_type' => 'required_if:holes_played,9|in:front,back',
        ];

        if ($format === 'hole_by_hole') {
            for ($i = 1; $i <= 18; $i++) {
                $rules["hole_$i"] = 'nullable|integer|min:1|max:15';
            }
        } else {
            $rules['total_score'] = 'required|integer|min:18|max:200';
        }

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Distribute total score across holes based on par
     * Extracted from PlayerController::storeScorecard
     */
    private function distributeTotalScore($totalScore, $courseId, $teebox, $startHole, $endHole)
    {
        // Get pars for these holes
        $coursePars = DB::table('course_info')
            ->where('golf_course_id', $courseId)
            ->where('teebox', $teebox)
            ->whereBetween('hole_number', [$startHole, $endHole])
            ->orderBy('hole_number')
            ->pluck('par', 'hole_number');

        $totalPar = $coursePars->sum();
        $scoreDiff = $totalScore - $totalPar;
        $holeCount = $coursePars->count();

        $scores = [];

        // Distribute the score difference evenly across holes
        $perHole = $holeCount > 0 ? intdiv($scoreDiff, $holeCount) : 0;
        $remainder = $holeCount > 0 ? abs($scoreDiff) - abs($perHole) * $holeCount : 0;

        foreach ($coursePars as $holeNumber => $par) {
            $strokes = $par + $perHole;
            if ($remainder > 0) {
                $strokes += ($scoreDiff >= 0 ? 1 : -1);
                $remainder--;
            }
            $strokes = max(1, $strokes); // Minimum 1 stroke per hole

            $scores[$holeNumber] = $strokes;
        }

        return $scores;
    }

    /**
     * Get course by name (case-insensitive)
     */
    private function getCourseByName($name)
    {
        return GolfCourse::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
    }

    /**
     * Check if teebox exists for a course
     */
    private function teeboxExists($courseId, $teeboxName)
    {
        return CourseInfo::where('golf_course_id', $courseId)
            ->where('teebox', $teeboxName)
            ->exists();
    }
}

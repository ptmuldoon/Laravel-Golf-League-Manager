<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Player;
use App\Models\League;
use App\Models\Team;
use App\Models\LeagueSegment;
use App\Models\ScoringSetting;
use Carbon\Carbon;

/**
 * Demo database seeder — creates a complete, realistic demo environment
 * with fake player names, phone numbers, historical rounds, handicaps,
 * a full league with teams, schedule, and scored matches.
 *
 * Usage:
 *   php artisan migrate:fresh --seed --seeder=DemoSeeder
 *
 * Or after a fresh migration:
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    /** Player skill tiers keyed by index (0-based). */
    private array $playerTiers = [];

    /** Course hole info cache. */
    private $courseHoles = null;

    public function run(): void
    {
        $this->command->info('Seeding demo database...');

        // 1. Admin user
        $this->seedAdminUser();

        // 2. Golf courses (reuse existing seeder)
        $this->call(GolfCourseSeeder::class);

        // 3. Create the demo league course with real-feeling data
        $courseId = $this->seedDemoCourse();

        // 4. Create fake players
        $players = $this->seedPlayers();

        // 5. Site settings (theme)
        $this->seedSiteSettings();

        // 6. Historical rounds for handicap data
        $this->seedHistoricalRounds($players, $courseId);

        // 7. Calculate handicaps
        $this->command->info('Calculating handicaps...');
        Artisan::call('handicaps:calculate', ['--historical' => true]);
        $this->command->info('Handicaps calculated.');

        // 8. Create league, segments, teams
        $league = $this->seedLeague($courseId);
        $segments = $this->seedSegments($league);
        [$teamA, $teamB] = $this->seedTeams($league, $segments[0], $players);

        // 9. Scoring settings
        ScoringSettingsSeeder::seedForLeague($league->id);
        // Adjust individual match play to 0.5 like real data
        ScoringSetting::where('league_id', $league->id)
            ->where('scoring_type', 'individual_match_play')
            ->where('outcome', 'win')
            ->update(['points' => 0.50]);
        ScoringSetting::where('league_id', $league->id)
            ->where('scoring_type', 'individual_match_play')
            ->where('outcome', 'tie')
            ->update(['points' => 0.25]);

        // 10. Generate 16-week schedule and score first 3 weeks
        $this->seedScheduleAndScores($league, $teamA, $teamB, $courseId);

        $this->command->info('Demo database seeded successfully!');
        $this->command->info('Login: admin@demo.com / password');
    }

    private function seedAdminUser(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name'              => 'Demo Admin',
                'password'          => Hash::make('password'),
                'is_admin'          => true,
                'is_super_admin'    => true,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Admin user created (admin@demo.com / password)');
    }

    private function seedDemoCourse(): int
    {
        $courseId = DB::table('golf_courses')->insertGetId([
            'name'         => 'Riverside Golf Club',
            'address'      => '450 River Road, Springfield, NY 14001',
            'address_link' => 'https://maps.google.com/?q=Riverside+Golf+Club',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Realistic 18-hole layout
        $holes = [
            // hole_number, par, yardage, handicap_ranking
            [1, 4, 385, 7],  [2, 4, 410, 3],  [3, 3, 165, 11], [4, 5, 510, 1],
            [5, 4, 375, 13], [6, 4, 405, 5],  [7, 3, 185, 17], [8, 4, 350, 9],
            [9, 5, 495, 15], [10, 4, 395, 8], [11, 4, 420, 4], [12, 3, 175, 12],
            [13, 5, 525, 2], [14, 4, 370, 14],[15, 4, 390, 6], [16, 3, 155, 18],
            [17, 4, 415, 10],[18, 5, 505, 16],
        ];

        $teeboxes = [
            ['name' => 'Blue',  'slope' => 132.0, 'rating' => 72.1, 'slope_9f' => 131.0, 'slope_9b' => 133.0, 'rating_9f' => 36.2, 'rating_9b' => 35.9],
            ['name' => 'White', 'slope' => 126.0, 'rating' => 70.3, 'slope_9f' => 125.0, 'slope_9b' => 127.0, 'rating_9f' => 35.3, 'rating_9b' => 35.0],
            ['name' => 'Red',   'slope' => 118.0, 'rating' => 67.8, 'slope_9f' => 117.0, 'slope_9b' => 119.0, 'rating_9f' => 34.1, 'rating_9b' => 33.7],
        ];

        foreach ($teeboxes as $tee) {
            // Scale yardage for different tees
            $yardageMultiplier = match ($tee['name']) {
                'Blue'  => 1.0,
                'White' => 0.93,
                'Red'   => 0.85,
            };

            foreach ($holes as [$holeNum, $par, $yardage, $hdcp]) {
                DB::table('course_info')->insert([
                    'golf_course_id' => $courseId,
                    'teebox'         => $tee['name'],
                    'slope'          => $tee['slope'],
                    'rating'         => $tee['rating'],
                    'slope_9_front'  => $tee['slope_9f'],
                    'slope_9_back'   => $tee['slope_9b'],
                    'rating_9_front' => $tee['rating_9f'],
                    'rating_9_back'  => $tee['rating_9b'],
                    'hole_number'    => $holeNum,
                    'par'            => $par,
                    'yardage'        => (int) round($yardage * $yardageMultiplier),
                    'handicap'       => $hdcp,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        $this->command->info('Demo course "Riverside Golf Club" created with 3 tee boxes.');
        return $courseId;
    }

    private function seedPlayers(): array
    {
        $demoPlayers = [
            // Low handicap players (positions 0-5)
            ['first_name' => 'Mike',    'last_name' => 'Sullivan'],
            ['first_name' => 'Tom',     'last_name' => 'Brennan'],
            ['first_name' => 'Jake',    'last_name' => 'Moretti'],
            ['first_name' => 'Chris',   'last_name' => 'Palmer'],
            ['first_name' => 'Dave',    'last_name' => 'Kowalski'],
            ['first_name' => 'Greg',    'last_name' => 'Hawkins'],
            // Mid handicap players (positions 6-15)
            ['first_name' => 'Steve',   'last_name' => 'Callahan'],
            ['first_name' => 'Rich',    'last_name' => 'Fontaine'],
            ['first_name' => 'Pat',     'last_name' => 'Gallagher'],
            ['first_name' => 'Dan',     'last_name' => 'Novak'],
            ['first_name' => 'Mark',    'last_name' => 'Hennessy'],
            ['first_name' => 'Brian',   'last_name' => 'Caputo'],
            ['first_name' => 'Joe',     'last_name' => 'Rinaldi'],
            ['first_name' => 'Kevin',   'last_name' => 'Walsh'],
            ['first_name' => 'Bill',    'last_name' => 'Donovan'],
            ['first_name' => 'Matt',    'last_name' => 'Tierney'],
            // High handicap players (positions 16-23)
            ['first_name' => 'Frank',   'last_name' => 'Esposito'],
            ['first_name' => 'Tony',    'last_name' => 'Bianchi'],
            ['first_name' => 'Bob',     'last_name' => 'Fitzgerald'],
            ['first_name' => 'Jim',     'last_name' => 'Kowalczyk'],
            ['first_name' => 'Ed',      'last_name' => 'Santoro'],
            ['first_name' => 'Pete',    'last_name' => 'Lombardi'],
            ['first_name' => 'Rick',    'last_name' => 'Mahoney'],
            ['first_name' => 'Sam',     'last_name' => 'Provenzano'],
        ];

        $players = [];
        foreach ($demoPlayers as $i => $p) {
            $areaCodes = ['716', '585', '607', '315', '518'];
            $areaCode = $areaCodes[array_rand($areaCodes)];
            $phone = $areaCode . '-' . rand(200, 999) . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $player = Player::create([
                'first_name'    => $p['first_name'],
                'last_name'     => $p['last_name'],
                'email'         => strtolower($p['first_name'] . '.' . $p['last_name']) . '@demo.com',
                'phone_number'  => $phone,
                'email_enabled' => true,
                'sms_enabled'   => true,
            ]);
            $players[] = $player;

            // Assign skill tier
            if ($i < 6) {
                $this->playerTiers[$player->id] = 'low';
            } elseif ($i < 16) {
                $this->playerTiers[$player->id] = 'mid';
            } else {
                $this->playerTiers[$player->id] = 'high';
            }
        }

        $this->command->info(count($players) . ' demo players created.');
        return $players;
    }

    private function seedSiteSettings(): void
    {
        $settings = [
            'theme_primary_color'   => '#2d6a4f',
            'theme_secondary_color' => '#1b4332',
            'theme_name'            => 'masters',
        ];

        foreach ($settings as $key => $value) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Generate 3 years of 9-hole rounds per player at the demo course (White tees).
     * This gives enough history for realistic handicap calculations.
     */
    private function seedHistoricalRounds(array $players, int $courseId): void
    {
        $this->command->info('Generating historical rounds (this may take a moment)...');

        $teebox = 'White';
        $courseHoles = DB::table('course_info')
            ->where('golf_course_id', $courseId)
            ->where('teebox', $teebox)
            ->orderBy('hole_number')
            ->get();

        $frontHoles = $courseHoles->where('hole_number', '<=', 9);
        $backHoles  = $courseHoles->where('hole_number', '>=', 10);

        // Generate ~60 rounds per player spanning 3 seasons
        $seasons = [
            ['start' => '2023-05-06', 'end' => '2023-08-26', 'weeks' => 16],
            ['start' => '2024-05-07', 'end' => '2024-08-27', 'weeks' => 16],
            ['start' => '2025-05-06', 'end' => '2025-08-26', 'weeks' => 16],
        ];

        $roundData = [];
        $scoreData = [];

        foreach ($players as $player) {
            $tier = $this->playerTiers[$player->id];

            foreach ($seasons as $season) {
                $startDate = Carbon::parse($season['start']);

                for ($week = 0; $week < $season['weeks']; $week++) {
                    $playedAt = $startDate->copy()->addWeeks($week)->format('Y-m-d');
                    $isFront = ($week % 2 === 0);
                    $holes = $isFront ? $frontHoles : $backHoles;

                    $roundData[] = [
                        'player_id'      => $player->id,
                        'golf_course_id' => $courseId,
                        'teebox'         => $teebox,
                        'holes_played'   => 9,
                        'played_at'      => $playedAt,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                        '_holes'         => $holes,
                        '_tier'          => $tier,
                    ];
                }
            }
        }

        // Insert rounds in batches
        foreach (array_chunk($roundData, 100) as $chunk) {
            foreach ($chunk as $rd) {
                $holes = $rd['_holes'];
                $tier = $rd['_tier'];
                unset($rd['_holes'], $rd['_tier']);

                $roundId = DB::table('rounds')->insertGetId($rd);

                foreach ($holes as $hole) {
                    $scoreData[] = [
                        'round_id'    => $roundId,
                        'hole_number' => $hole->hole_number,
                        'strokes'     => $this->generateScore($hole->par, $tier, $hole->handicap),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                if (count($scoreData) >= 500) {
                    DB::table('scores')->insert($scoreData);
                    $scoreData = [];
                }
            }
        }

        if (!empty($scoreData)) {
            DB::table('scores')->insert($scoreData);
        }

        $this->command->info(count($roundData) . ' historical rounds generated.');
    }

    private function seedLeague(int $courseId): League
    {
        $league = League::create([
            'name'            => 'Riverside Tuesday League',
            'season'          => 'Summer 2026',
            'start_date'      => '2026-05-05',
            'end_date'        => '2026-08-25',
            'golf_course_id'  => $courseId,
            'default_teebox'  => 'White',
            'is_active'       => true,
            'fee_per_player'  => 150.00,
            'par3_payout'     => 20.00,
            'payout_1st_pct'  => 50.00,
            'payout_2nd_pct'  => 30.00,
            'payout_3rd_pct'  => 20.00,
        ]);

        $this->command->info('League "' . $league->name . '" created.');
        return $league;
    }

    private function seedSegments(League $league): array
    {
        $seg1 = LeagueSegment::create([
            'league_id'     => $league->id,
            'name'          => 'First Half',
            'start_week'    => 1,
            'end_week'      => 8,
            'display_order' => 1,
        ]);

        $seg2 = LeagueSegment::create([
            'league_id'     => $league->id,
            'name'          => 'Second Half',
            'start_week'    => 9,
            'end_week'      => 16,
            'display_order' => 2,
        ]);

        return [$seg1, $seg2];
    }

    private function seedTeams(League $league, LeagueSegment $segment, array $players): array
    {
        $teamA = Team::create([
            'league_id'          => $league->id,
            'league_segment_id'  => $segment->id,
            'name'               => 'The Eagles',
            'captain_id'         => $players[0]->id,
        ]);

        $teamB = Team::create([
            'league_id'          => $league->id,
            'league_segment_id'  => $segment->id,
            'name'               => 'The Birdies',
            'captain_id'         => $players[1]->id,
        ]);

        // Split 24 players: 12 per team
        foreach ($players as $i => $player) {
            // Add all to league
            DB::table('league_players')->insert([
                'league_id'  => $league->id,
                'player_id'  => $player->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Alternate teams, mixing skill levels
            $team = ($i % 2 === 0) ? $teamA : $teamB;
            DB::table('team_players')->insert([
                'team_id'    => $team->id,
                'player_id'  => $player->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Teams created: "The Eagles" and "The Birdies" (12 players each).');
        return [$teamA, $teamB];
    }

    /**
     * Generate a 16-week schedule and score the first 3 weeks.
     */
    private function seedScheduleAndScores(League $league, Team $teamA, Team $teamB, int $courseId): void
    {
        $teebox = 'White';
        $completedWeeks = 5;
        $startDate = Carbon::parse('2026-05-05'); // Match league start date

        // Get players per team
        $teamAPlayerIds = DB::table('team_players')->where('team_id', $teamA->id)->pluck('player_id')->toArray();
        $teamBPlayerIds = DB::table('team_players')->where('team_id', $teamB->id)->pluck('player_id')->toArray();

        // Load course holes
        $courseHoles = DB::table('course_info')
            ->where('golf_course_id', $courseId)
            ->where('teebox', $teebox)
            ->orderBy('hole_number')
            ->get();

        $frontHoles = $courseHoles->where('hole_number', '<=', 9);

        // Load slope/rating for course handicap calculations
        $firstHole = $courseHoles->first();
        $slope = $firstHole->slope;
        $rating = $firstHole->rating;
        $par = $courseHoles->sum('par');
        $frontPar = $frontHoles->sum('par');

        for ($week = 1; $week <= 16; $week++) {
            $matchDate = $startDate->copy()->addWeeks($week - 1);
            $isCompleted = ($week <= $completedWeeks);
            $isBackNine = ($week % 2 === 0);
            $holes = $isBackNine ? 'back_9' : 'front_9';
            $holeRange = $isBackNine ? $courseHoles->where('hole_number', '>=', 10) : $frontHoles;
            $holeStart = $isBackNine ? 10 : 1;
            $holeEnd = $isBackNine ? 18 : 9;

            // Shuffle players each week for different pairings
            $shuffledA = $teamAPlayerIds;
            $shuffledB = $teamBPlayerIds;
            shuffle($shuffledA);
            shuffle($shuffledB);

            // 6 matches per week (pair up 12 players: 2 per team per match)
            for ($matchNum = 0; $matchNum < 6; $matchNum++) {
                $teeTime = Carbon::createFromTime(16, 30 + ($matchNum * 10), 0)->format('H:i:s');

                $matchId = DB::table('matches')->insertGetId([
                    'league_id'           => $league->id,
                    'week_number'         => $week,
                    'match_date'          => $matchDate->format('Y-m-d'),
                    'tee_time'            => $teeTime,
                    'golf_course_id'      => $courseId,
                    'teebox'              => $teebox,
                    'holes'               => $holes,
                    'scoring_type'        => 'best_ball_match_play',
                    'score_mode'          => 'net',
                    'ride_with_opponent'  => false,
                    'home_team_id'        => $teamB->id,
                    'away_team_id'        => $teamA->id,
                    'status'              => $isCompleted ? 'completed' : 'scheduled',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // 2 players per team per match
                $awayP1 = $shuffledA[$matchNum * 2] ?? $shuffledA[0];
                $awayP2 = $shuffledA[$matchNum * 2 + 1] ?? $shuffledA[1];
                $homeP1 = $shuffledB[$matchNum * 2] ?? $shuffledB[0];
                $homeP2 = $shuffledB[$matchNum * 2 + 1] ?? $shuffledB[1];

                $matchPlayerIds = [];
                foreach ([
                    [$awayP1, $teamA->id, 1],
                    [$awayP2, $teamA->id, 2],
                    [$homeP1, $teamB->id, 3],
                    [$homeP2, $teamB->id, 4],
                ] as [$playerId, $teamId, $position]) {
                    // Get player's handicap index
                    $latestHI = DB::table('handicap_history')
                        ->where('player_id', $playerId)
                        ->orderByDesc('calculation_date')
                        ->value('handicap_index') ?? 20.0;

                    $ch18 = round(($latestHI * $slope / 113) + ($rating - $par));
                    $ch9 = round($ch18 / 2);

                    $mpId = DB::table('match_players')->insertGetId([
                        'match_id'         => $matchId,
                        'team_id'          => $teamId,
                        'player_id'        => $playerId,
                        'handicap_index'   => $latestHI,
                        'course_handicap'  => $ch18,
                        'position_in_pairing' => $position,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                    $matchPlayerIds[] = ['mp_id' => $mpId, 'player_id' => $playerId, 'team_id' => $teamId, 'ch9' => $ch9];
                }

                // Score completed weeks
                if ($isCompleted) {
                    $teamScores = [$teamA->id => [], $teamB->id => []];

                    foreach ($matchPlayerIds as $mp) {
                        $tier = $this->playerTiers[$mp['player_id']] ?? 'mid';

                        foreach ($holeRange as $hole) {
                            $strokes = $this->generateScore($hole->par, $tier, $hole->handicap);

                            // Calculate stroke allocation for net
                            $ch = max(0, (int) $mp['ch9']);
                            $base = intdiv($ch, 9);
                            $remainder = $ch % 9;
                            $strokesReceived = $base + ($hole->handicap <= $remainder ? 1 : 0);

                            $netScore = $strokes - $strokesReceived;
                            $maxScore = $hole->par + 2 + $strokesReceived;
                            $adjustedGross = min($strokes, $maxScore);

                            DB::table('match_scores')->insert([
                                'match_player_id' => $mp['mp_id'],
                                'hole_number'     => $hole->hole_number,
                                'strokes'         => $strokes,
                                'adjusted_gross'  => $adjustedGross,
                                'net_score'       => $netScore,
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ]);

                            $teamScores[$mp['team_id']][$hole->hole_number][] = $netScore;
                        }
                    }

                    // Calculate match result (best ball: compare best net per team per hole)
                    $holesWonHome = 0;
                    $holesWonAway = 0;
                    $holesTied = 0;

                    for ($h = $holeStart; $h <= $holeEnd; $h++) {
                        $bestAway = min($teamScores[$teamA->id][$h] ?? [99]);
                        $bestHome = min($teamScores[$teamB->id][$h] ?? [99]);

                        if ($bestHome < $bestAway) {
                            $holesWonHome++;
                        } elseif ($bestAway < $bestHome) {
                            $holesWonAway++;
                        } else {
                            $holesTied++;
                        }
                    }

                    $winningTeamId = null;
                    $pointsHome = 0.50;
                    $pointsAway = 0.50;
                    if ($holesWonHome > $holesWonAway) {
                        $winningTeamId = $teamB->id;
                        $pointsHome = 1.00;
                        $pointsAway = 0.00;
                    } elseif ($holesWonAway > $holesWonHome) {
                        $winningTeamId = $teamA->id;
                        $pointsHome = 0.00;
                        $pointsAway = 1.00;
                    }

                    DB::table('match_results')->insert([
                        'match_id'         => $matchId,
                        'winning_team_id'  => $winningTeamId,
                        'holes_won_home'   => $holesWonHome,
                        'holes_won_away'   => $holesWonAway,
                        'holes_tied'       => $holesTied,
                        'team_points_home' => $pointsHome,
                        'team_points_away' => $pointsAway,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }

        // Update team win/loss/tie totals
        $this->updateTeamRecords($teamA);
        $this->updateTeamRecords($teamB);

        // Seed a few par 3 winners for completed weeks
        $this->seedPar3Winners($league, $completedWeeks);

        $this->command->info("16-week schedule created. First {$completedWeeks} weeks scored with results.");
    }

    private function updateTeamRecords(Team $team): void
    {
        $wins   = DB::table('match_results')->where('winning_team_id', $team->id)->count();
        $losses = DB::table('match_results')
            ->whereNotNull('winning_team_id')
            ->where('winning_team_id', '!=', $team->id)
            ->whereIn('match_id', function ($q) use ($team) {
                $q->select('id')->from('matches')
                    ->where(function ($q2) use ($team) {
                        $q2->where('home_team_id', $team->id)
                           ->orWhere('away_team_id', $team->id);
                    });
            })->count();
        $ties = DB::table('match_results')
            ->whereNull('winning_team_id')
            ->whereIn('match_id', function ($q) use ($team) {
                $q->select('id')->from('matches')
                    ->where(function ($q2) use ($team) {
                        $q2->where('home_team_id', $team->id)
                           ->orWhere('away_team_id', $team->id);
                    });
            })->count();

        $team->update(['wins' => $wins, 'losses' => $losses, 'ties' => $ties]);
    }

    private function seedPar3Winners(League $league, int $completedWeeks): void
    {
        $par3HolesFront = [3, 7];   // Front 9 par 3s
        $par3HolesBack  = [12, 16]; // Back 9 par 3s

        $leaguePlayerIds = DB::table('league_players')
            ->where('league_id', $league->id)
            ->pluck('player_id')
            ->toArray();

        for ($week = 1; $week <= $completedWeeks; $week++) {
            $par3s = ($week % 2 === 0) ? $par3HolesBack : $par3HolesFront;

            foreach ($par3s as $hole) {
                $winnerId = $leaguePlayerIds[array_rand($leaguePlayerIds)];

                $p3Id = DB::table('par3_winners')->insertGetId([
                    'league_id'   => $league->id,
                    'week_number' => $week,
                    'hole_number' => $hole,
                    'player_id'   => $winnerId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $matchDate = Carbon::parse('2026-05-05')->addWeeks($week - 1)->format('Y-m-d');
                DB::table('league_finances')->insert([
                    'league_id'      => $league->id,
                    'player_id'      => $winnerId,
                    'type'           => 'winnings',
                    'amount'         => 20.00,
                    'date'           => $matchDate,
                    'notes'          => "Par 3 Winner - Week {$week}, Hole {$hole}",
                    'par3_winner_id' => $p3Id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    // ─── Score generation (same distributions as other seeders) ───────────

    private function generateScore(int $par, string $tier, ?int $holeHandicap): int
    {
        $rand = rand(1, 100);

        $difficultyShift = 0;
        if ($holeHandicap !== null) {
            if ($holeHandicap <= 6) {
                $difficultyShift = rand(0, 1);
            } elseif ($holeHandicap >= 13) {
                $difficultyShift = -rand(0, 1);
            }
        }

        $score = match ($tier) {
            'low'  => $this->scoreLow($par, $rand),
            'mid'  => $this->scoreMid($par, $rand),
            'high' => $this->scoreHigh($par, $rand),
        };

        return max(1, $score + $difficultyShift);
    }

    private function scoreLow(int $par, int $rand): int
    {
        if ($rand <= 1) return max(1, $par - 2);
        if ($rand <= 8) return $par - 1;
        if ($rand <= 45) return $par;
        if ($rand <= 78) return $par + 1;
        if ($rand <= 94) return $par + 2;
        if ($rand <= 99) return $par + 3;
        return $par + 4;
    }

    private function scoreMid(int $par, int $rand): int
    {
        if ($rand <= 3) return $par - 1;
        if ($rand <= 23) return $par;
        if ($rand <= 56) return $par + 1;
        if ($rand <= 81) return $par + 2;
        if ($rand <= 94) return $par + 3;
        if ($rand <= 99) return $par + 4;
        return $par + 5;
    }

    private function scoreHigh(int $par, int $rand): int
    {
        if ($rand <= 8) return $par;
        if ($rand <= 29) return $par + 1;
        if ($rand <= 58) return $par + 2;
        if ($rand <= 80) return $par + 3;
        if ($rand <= 93) return $par + 4;
        return $par + 5;
    }
}

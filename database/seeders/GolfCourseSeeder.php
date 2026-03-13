<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GolfCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Pebble Beach Golf Links',
                'address' => '1700 17-Mile Drive, Pebble Beach, CA 93953',
                'address_link' => 'https://maps.google.com/?q=Pebble+Beach+Golf+Links',
                'teeboxes' => [
                    ['name' => 'Black', 'slope' => 145.0, 'rating' => 75.5],
                    ['name' => 'Blue', 'slope' => 142.0, 'rating' => 73.8],
                    ['name' => 'White', 'slope' => 135.0, 'rating' => 71.2],
                    ['name' => 'Red', 'slope' => 128.0, 'rating' => 68.5],
                ],
            ],
            [
                'name' => 'Augusta National Golf Club',
                'address' => '2604 Washington Road, Augusta, GA 30904',
                'address_link' => 'https://maps.google.com/?q=Augusta+National+Golf+Club',
                'teeboxes' => [
                    ['name' => 'Black', 'slope' => 148.0, 'rating' => 76.2],
                    ['name' => 'Blue', 'slope' => 144.0, 'rating' => 74.5],
                    ['name' => 'White', 'slope' => 137.0, 'rating' => 72.1],
                    ['name' => 'Red', 'slope' => 130.0, 'rating' => 69.3],
                ],
            ],
            [
                'name' => 'St. Andrews Old Course',
                'address' => 'Pilmour Links, St Andrews KY16 9SF, Scotland',
                'address_link' => 'https://maps.google.com/?q=St+Andrews+Old+Course',
                'teeboxes' => [
                    ['name' => 'Black', 'slope' => 140.0, 'rating' => 74.1],
                    ['name' => 'Blue', 'slope' => 136.0, 'rating' => 72.5],
                    ['name' => 'White', 'slope' => 130.0, 'rating' => 70.3],
                    ['name' => 'Red', 'slope' => 124.0, 'rating' => 67.8],
                ],
            ],
            [
                'name' => 'Pinehurst No. 2',
                'address' => '1 Carolina Vista Drive, Pinehurst, NC 28374',
                'address_link' => 'https://maps.google.com/?q=Pinehurst+No+2',
                'teeboxes' => [
                    ['name' => 'Black', 'slope' => 143.0, 'rating' => 75.0],
                    ['name' => 'Blue', 'slope' => 139.0, 'rating' => 73.2],
                    ['name' => 'White', 'slope' => 133.0, 'rating' => 70.8],
                    ['name' => 'Red', 'slope' => 127.0, 'rating' => 68.2],
                ],
            ],
            [
                'name' => 'Torrey Pines Golf Course',
                'address' => '11480 N Torrey Pines Rd, La Jolla, CA 92037',
                'address_link' => 'https://maps.google.com/?q=Torrey+Pines+Golf+Course',
                'teeboxes' => [
                    ['name' => 'Black', 'slope' => 141.0, 'rating' => 74.6],
                    ['name' => 'Blue', 'slope' => 137.0, 'rating' => 72.9],
                    ['name' => 'White', 'slope' => 131.0, 'rating' => 70.5],
                    ['name' => 'Red', 'slope' => 125.0, 'rating' => 68.0],
                ],
            ],
        ];

        // Standard 18-hole par layout (typical championship course)
        $holePars = [4, 4, 3, 5, 4, 4, 3, 4, 5, 4, 4, 3, 5, 4, 4, 3, 4, 5];

        // Hole handicap rankings (1=hardest, 18=easiest)
        // Odd numbers on front 9, even numbers on back 9 (standard allocation)
        $holeHandicaps = [7, 3, 11, 1, 13, 5, 17, 9, 15, 8, 4, 12, 2, 14, 6, 18, 10, 16];

        foreach ($courses as $courseData) {
            // Create the golf course
            $courseId = DB::table('golf_courses')->insertGetId([
                'name' => $courseData['name'],
                'address' => $courseData['address'],
                'address_link' => $courseData['address_link'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create course info for each teebox
            foreach ($courseData['teeboxes'] as $teebox) {
                // Calculate 9-hole ratings (WHS formula approximation)
                $frontNinePar = array_sum(array_slice($holePars, 0, 9));
                $backNinePar = array_sum(array_slice($holePars, 9, 9));

                // Front 9 rating: approximately proportional to par
                $rating9Front = round(($teebox['rating'] * $frontNinePar) / 36, 1);
                $rating9Back = round(($teebox['rating'] * $backNinePar) / 36, 1);

                // 9-hole slopes are typically similar to 18-hole but slightly adjusted
                $slope9Front = $teebox['slope'] - 1.0;
                $slope9Back = $teebox['slope'] + 1.0;

                foreach ($holePars as $holeNumber => $par) {
                    DB::table('course_info')->insert([
                        'golf_course_id' => $courseId,
                        'teebox' => $teebox['name'],
                        'slope' => $teebox['slope'],
                        'rating' => $teebox['rating'],
                        'slope_9_front' => $slope9Front,
                        'slope_9_back' => $slope9Back,
                        'rating_9_front' => $rating9Front,
                        'rating_9_back' => $rating9Back,
                        'hole_number' => $holeNumber + 1,
                        'par' => $par,
                        'handicap' => $holeHandicaps[$holeNumber],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}

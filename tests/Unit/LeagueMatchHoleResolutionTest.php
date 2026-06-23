<?php

namespace Tests\Unit;

use App\Models\LeagueMatch;
use PHPUnit\Framework\TestCase;

class LeagueMatchHoleResolutionTest extends TestCase
{
    public function test_front_nine_returns_holes_1_to_9(): void
    {
        $match = new LeagueMatch(['holes' => 'front_9']);
        $this->assertSame(range(1, 9), $match->holeNumbers());
        $this->assertSame([1, 9], $match->holeRange());
    }

    public function test_back_nine_returns_holes_10_to_18(): void
    {
        $match = new LeagueMatch(['holes' => 'back_9']);
        $this->assertSame(range(10, 18), $match->holeNumbers());
        $this->assertSame([10, 18], $match->holeRange());
    }

    public function test_defaults_to_front_nine_when_unset(): void
    {
        $match = new LeagueMatch();
        $this->assertSame(range(1, 9), $match->holeNumbers());
        $this->assertSame([1, 9], $match->holeRange());
        $this->assertFalse($match->isNinesMode());
    }

    public function test_nines_mode_two_nines_span_1_to_18(): void
    {
        $match = new LeagueMatch(['front_nine_id' => 5, 'back_nine_id' => 6]);
        $this->assertTrue($match->isNinesMode());
        $this->assertSame(range(1, 18), $match->holeNumbers());
        $this->assertSame([1, 18], $match->holeRange());
    }

    public function test_nines_mode_single_nine_is_1_to_9(): void
    {
        $match = new LeagueMatch(['front_nine_id' => 5]);
        $this->assertTrue($match->isNinesMode());
        $this->assertSame(range(1, 9), $match->holeNumbers());
    }
}

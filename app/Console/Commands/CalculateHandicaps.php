<?php

namespace App\Console\Commands;

use App\Models\HandicapHistory;
use App\Models\Player;
use App\Services\HandicapCalculator;
use Illuminate\Console\Command;

class CalculateHandicaps extends Command
{
    protected $signature = 'handicaps:calculate
                            {--player= : Calculate for a specific player ID only}
                            {--historical : Compute full historical handicap after each round}
                            {--fresh : Clear existing handicap history before calculating}';

    protected $description = 'Calculate handicap index for all players using World Handicap System rules with hole-by-hole scoring adjustment';

    public function handle()
    {
        $calculator = new HandicapCalculator();

        if ($this->option('fresh')) {
            $this->warn('Clearing existing handicap history...');
            if ($this->option('player')) {
                HandicapHistory::where('player_id', $this->option('player'))->delete();
            } else {
                HandicapHistory::truncate();
            }
        }

        $query = Player::query();
        if ($this->option('player')) {
            $query->where('id', $this->option('player'));
        }
        $players = $query->get();

        $this->info("Calculating handicaps for {$players->count()} players...");
        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        $totalRecords = 0;

        foreach ($players as $player) {
            if ($this->option('historical')) {
                $totalRecords += $this->calculateHistorical($player, $calculator);
            } else {
                $totalRecords += $this->calculateCurrent($player, $calculator);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Created {$totalRecords} handicap history records.");
    }

    private function calculateHistorical(Player $player, HandicapCalculator $calculator): int
    {
        $snapshots = $calculator->computeHistoricalHandicaps($player);

        if (empty($snapshots)) {
            return 0;
        }

        // Group by calculation_date; if multiple rounds on the same day,
        // keep only the last computation for that date.
        $byDate = [];
        foreach ($snapshots as $snapshot) {
            $date = $snapshot['calculation_date'];
            $byDate[$date] = $snapshot;
        }

        $records = [];
        foreach ($byDate as $snapshot) {
            $records[] = [
                'player_id' => $snapshot['player_id'],
                'calculation_date' => $snapshot['calculation_date'],
                'handicap_index' => $snapshot['handicap_index'],
                'rounds_used' => $snapshot['rounds_used'],
                'score_differentials' => json_encode($snapshot['score_differentials']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert
        foreach (array_chunk($records, 100) as $chunk) {
            HandicapHistory::insert($chunk);
        }

        $latest = end($records);
        $this->line("  {$player->name}: {$latest['handicap_index']} (from " . count($records) . " snapshots)");

        return count($records);
    }

    private function calculateCurrent(Player $player, HandicapCalculator $calculator): int
    {
        $rounds = $player->rounds()
            ->with(['scores', 'golfCourse', 'matchPlayer.match'])
            ->orderBy('played_at')
            ->orderBy('id')
            ->get()
            // Exclude scramble rounds — scramble scores don't reflect individual play
            ->filter(function ($round) {
                if ($round->matchPlayer && $round->matchPlayer->match) {
                    return $round->matchPlayer->match->scoring_type !== 'scramble';
                }
                return true;
            })
            ->values();

        if ($rounds->count() < 3) {
            $this->line("  {$player->name}: Not enough rounds ({$rounds->count()})");
            return 0;
        }

        // Get current handicap for net double bogey calculation
        $latestHandicap = $player->latestHandicap;
        $currentIndex = $latestHandicap ? (float) $latestHandicap->handicap_index : null;

        $differentials = $calculator->buildDifferentialsList($rounds, $currentIndex);
        $result = $calculator->calculateHandicapIndex($differentials);

        if ($result === null) {
            $this->line("  {$player->name}: Could not compute handicap");
            return 0;
        }

        HandicapHistory::create([
            'player_id' => $player->id,
            'calculation_date' => now()->format('Y-m-d'),
            'handicap_index' => $result['handicap_index'],
            'rounds_used' => $result['rounds_used'],
            'score_differentials' => $result['all_differentials'],
        ]);

        $this->line("  {$player->name}: {$result['handicap_index']}");

        return 1;
    }
}

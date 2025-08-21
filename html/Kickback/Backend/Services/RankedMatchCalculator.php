<?php
declare(strict_types=1);

namespace Kickback\Backend\Services;

class RankedMatchCalculator
{
    private int $baseRating;
    private int $kFactor;

    public function __construct(int $baseRating = 1500, int $kFactor = 30)
    {
        $this->baseRating = $baseRating;
        $this->kFactor = $kFactor;
    }

    /**
     * Calculate new ratings for a match with exactly one winning team.
     *
     * @param array<int,array{players:array<int>,rank:int}> $teams Teams with players and their rank (1 = winner).
     * @param array<int,int> $ratings Current ratings keyed by account id.
     * @return array<int,int> Updated ratings keyed by account id.
     */
    public function calculate(array $teams, array $ratings): array
    {
        $ratingLookup = $ratings;
        foreach ($teams as $team) {
            foreach ($team['players'] as $accountId) {
                if (!isset($ratingLookup[$accountId])) {
                    $ratingLookup[$accountId] = $this->baseRating;
                }
            }
        }

        $winnerTeams = array_values(array_filter($teams, fn($t) => 1 === $t['rank']));
        if (1 !== count($winnerTeams)) {
            $result = [];
            foreach ($ratingLookup as $accountId => $rating) {
                $result[$accountId] = $rating;
            }
            return $result; // no changes if there isn't exactly one winner
        }

        $winnerPlayers = $winnerTeams[0]['players'];
        $loserTeams = [];
        foreach ($teams as $team) {
            if (1 !== $team['rank']) {
                $loserTeams[] = $team['players'];
            }
        }

        $changes = array_fill_keys(array_keys($ratingLookup), 0);
        $winnerAvg = $this->teamAverage($ratingLookup, $winnerPlayers);

        foreach ($loserTeams as $loserPlayers) {
            $loserAvg = $this->teamAverage($ratingLookup, $loserPlayers);

            $expectedWin = $this->expectedScore($winnerAvg, $loserAvg);
            $expectedLose = 1 - $expectedWin;

            $winnerAvgNew = $winnerAvg + $this->kFactor * (1 - $expectedWin);
            $loserAvgNew = $loserAvg + $this->kFactor * (0 - $expectedLose);

            $winnerDelta = (int) round($winnerAvgNew - $winnerAvg);
            $loserDelta = (int) round($loserAvgNew - $loserAvg);

            foreach ($winnerPlayers as $id) {
                $ratingLookup[$id] += $winnerDelta;
                $changes[$id] += $winnerDelta;
            }
            foreach ($loserPlayers as $id) {
                $ratingLookup[$id] += $loserDelta;
                $changes[$id] += $loserDelta;
            }

            $winnerAvg = $this->teamAverage($ratingLookup, $winnerPlayers);
        }

        $result = [];
        foreach ($changes as $id => $delta) {
            $result[$id] = $ratings[$id] ?? $this->baseRating;
            $result[$id] += $delta;
        }

        return $result;
    }

    /**
     * @param array<int,int> $ratings
     * @param array<int> $team
     */
    private function teamAverage(array $ratings, array $team): float
    {
        $total = 0;
        foreach ($team as $id) {
            $total += $ratings[$id];
        }
        return $total / count($team);
    }

    private function expectedScore(float $ratingA, float $ratingB): float
    {
        return 1 / (1 + pow(10, ($ratingB - $ratingA) / 400));
    }
}

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

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        $calc = new self();

        // 1v1
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $ratings = [];
        $result = $calc->calculate($teams, $ratings);
        assert($result === [1 => 1515, 2 => 1485]);

        // 1v1v1
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
            ['players' => [3], 'rank' => 3],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1529, 2 => 1485, 3 => 1486]);

        // 1v2v3
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2, 3], 'rank' => 2],
            ['players' => [4, 5, 6], 'rank' => 3],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1529, 2 => 1485, 3 => 1485, 4 => 1486, 5 => 1486, 6 => 1486]);

        // 2v2v4
        $teams = [
            ['players' => [1, 2], 'rank' => 1],
            ['players' => [3, 4], 'rank' => 2],
            ['players' => [5, 6, 7, 8], 'rank' => 3],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1529, 2 => 1529, 3 => 1485, 4 => 1485, 5 => 1486, 6 => 1486, 7 => 1486, 8 => 1486]);

        // 1v1v1v1
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
            ['players' => [3], 'rank' => 3],
            ['players' => [4], 'rank' => 4],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1543, 2 => 1485, 3 => 1486, 4 => 1486]);

        echo("  ... passed.\n\n");
    }
}

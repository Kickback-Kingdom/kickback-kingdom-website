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
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1515, 2 => 1485]);

        // 1v1v1 (two teams tied for second)
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
            ['players' => [3], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1529, 2 => 1485, 3 => 1486]);

        // 1v2v3 (two teams tied for second)
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2, 3], 'rank' => 2],
            ['players' => [4, 5, 6], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1529, 2 => 1485, 3 => 1485, 4 => 1486, 5 => 1486, 6 => 1486]);

        // 2v2v4 (second and third teams tied)
        $teams = [
            ['players' => [1, 2], 'rank' => 1],
            ['players' => [3, 4], 'rank' => 2],
            ['players' => [5, 6, 7, 8], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1529, 2 => 1529, 3 => 1485, 4 => 1485, 5 => 1486, 6 => 1486, 7 => 1486, 8 => 1486]);

        // 1v1v1v1 (three teams tied for second)
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
            ['players' => [3], 'rank' => 2],
            ['players' => [4], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1543, 2 => 1485, 3 => 1486, 4 => 1486]);

        // Upset 1v1 with existing ratings
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $ratings = [1 => 1800, 2 => 2200];
        $result = $calc->calculate($teams, $ratings);
        assert($result === [1 => 1827, 2 => 2173]);

        // Favorite beats underdog - no change
        $ratings = [1 => 2400, 2 => 1500];
        $result = $calc->calculate($teams, $ratings);
        assert($result === [1 => 2400, 2 => 1500]);

        // Team upset 2v2 with existing ratings
        $teams = [
            ['players' => [3, 4], 'rank' => 1],
            ['players' => [1, 2], 'rank' => 2],
        ];
        $ratings = [1 => 2200, 2 => 2100, 3 => 1500, 4 => 1500];
        $result = $calc->calculate($teams, $ratings);
        assert($result === [1 => 2171, 2 => 2071, 3 => 1529, 4 => 1529]);

        // Tie results in no changes
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 1],
            ['players' => [3], 'rank' => 2],
        ];
        $ratings = [1 => 1600, 2 => 1500, 3 => 1400];
        $result = $calc->calculate($teams, $ratings);
        assert($result === [1 => 1600, 2 => 1500, 3 => 1400]);

        // Mixed rated and new player team
        $teams = [
            ['players' => [1, 3], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $ratings = [1 => 1600, 2 => 1700];
        $result = $calc->calculate($teams, $ratings);
        assert($result === [1 => 1621, 2 => 1679, 3 => 1521]);

        // Five player free for all
        $teams = [
            ['players' => [2], 'rank' => 1],
            ['players' => [5], 'rank' => 2],
            ['players' => [4], 'rank' => 3],
            ['players' => [3], 'rank' => 4],
            ['players' => [1], 'rank' => 5],
        ];
        $ratings = [1 => 1200, 2 => 1500, 3 => 1800, 4 => 2100, 5 => 2400];
        $result = $calc->calculate($teams, $ratings);
        assert($result === [1 => 1197, 2 => 1586, 3 => 1776, 4 => 2071, 5 => 2370]);

        // Custom base rating and k-factor
        $calc = new self(1000, 50);
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        assert($result === [1 => 1025, 2 => 975]);

        echo("  ... passed.\n\n");
    }
}

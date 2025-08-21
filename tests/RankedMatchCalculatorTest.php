<?php
declare(strict_types=1);

use Kickback\Backend\Services\RankedMatchCalculator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../html/Kickback/Backend/Services/RankedMatchCalculator.php';

final class RankedMatchCalculatorTest extends TestCase
{
    public function test1v1(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        $this->assertSame(1515, $result[1]);
        $this->assertSame(1485, $result[2]);
    }

    public function test1v1v1(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
            ['players' => [3], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        $this->assertSame(1529, $result[1]);
        $this->assertSame(1485, $result[2]);
        $this->assertSame(1486, $result[3]);
    }

    public function test1v2v3(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2,3], 'rank' => 2],
            ['players' => [4,5,6], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        $this->assertSame(1529, $result[1]);
        $this->assertSame(1485, $result[2]);
        $this->assertSame(1485, $result[3]);
        $this->assertSame(1486, $result[4]);
        $this->assertSame(1486, $result[5]);
        $this->assertSame(1486, $result[6]);
    }

    public function test2v2v4(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1,2], 'rank' => 1],
            ['players' => [3,4], 'rank' => 2],
            ['players' => [5,6,7,8], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        $this->assertSame(1529, $result[1]);
        $this->assertSame(1529, $result[2]);
        $this->assertSame(1485, $result[3]);
        $this->assertSame(1485, $result[4]);
        $this->assertSame(1486, $result[5]);
        $this->assertSame(1486, $result[6]);
        $this->assertSame(1486, $result[7]);
        $this->assertSame(1486, $result[8]);
    }

    public function test1v1v1v1(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
            ['players' => [3], 'rank' => 2],
            ['players' => [4], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        $this->assertSame(1543, $result[1]);
        $this->assertSame(1485, $result[2]);
        $this->assertSame(1486, $result[3]);
        $this->assertSame(1486, $result[4]);
    }

    public function testUpset1v1WithExistingRatings(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $ratings = [1 => 1800, 2 => 2200];
        $result = $calc->calculate($teams, $ratings);
        $this->assertSame(1827, $result[1]);
        $this->assertSame(2173, $result[2]);
    }

    public function testFavoriteBeatsUnderdogNoChange(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $ratings = [1 => 2400, 2 => 1500];
        $result = $calc->calculate($teams, $ratings);
        $this->assertSame(2400, $result[1]);
        $this->assertSame(1500, $result[2]);
    }

    public function testTeamUpset2v2WithExistingRatings(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [3,4], 'rank' => 1],
            ['players' => [1,2], 'rank' => 2],
        ];
        $ratings = [1 => 2200, 2 => 2100, 3 => 1500, 4 => 1500];
        $result = $calc->calculate($teams, $ratings);
        $this->assertSame(2171, $result[1]);
        $this->assertSame(2071, $result[2]);
        $this->assertSame(1529, $result[3]);
        $this->assertSame(1529, $result[4]);
    }

    public function testTieResultsInNoChanges(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 1],
            ['players' => [3], 'rank' => 2],
        ];
        $ratings = [1 => 1600, 2 => 1500, 3 => 1400];
        $result = $calc->calculate($teams, $ratings);
        $this->assertSame(1600, $result[1]);
        $this->assertSame(1500, $result[2]);
        $this->assertSame(1400, $result[3]);
    }

    public function testMixedRatedAndNewPlayerTeam(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [1,3], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $ratings = [1 => 1600, 2 => 1700];
        $result = $calc->calculate($teams, $ratings);
        $this->assertSame(1621, $result[1]);
        $this->assertSame(1679, $result[2]);
        $this->assertSame(1521, $result[3]);
    }

    public function testFivePlayerFreeForAll(): void
    {
        $calc = new RankedMatchCalculator();
        $teams = [
            ['players' => [2], 'rank' => 1],
            ['players' => [5], 'rank' => 2],
            ['players' => [4], 'rank' => 3],
            ['players' => [3], 'rank' => 4],
            ['players' => [1], 'rank' => 5],
        ];
        $ratings = [1 => 1200, 2 => 1500, 3 => 1800, 4 => 2100, 5 => 2400];
        $result = $calc->calculate($teams, $ratings);
        $this->assertSame(1197, $result[1]);
        $this->assertSame(1586, $result[2]);
        $this->assertSame(1776, $result[3]);
        $this->assertSame(2071, $result[4]);
        $this->assertSame(2370, $result[5]);
    }

    public function testCustomBaseRatingAndKFactor(): void
    {
        $calc = new RankedMatchCalculator(1000, 50);
        $teams = [
            ['players' => [1], 'rank' => 1],
            ['players' => [2], 'rank' => 2],
        ];
        $result = $calc->calculate($teams, []);
        $this->assertSame(1025, $result[1]);
        $this->assertSame(975, $result[2]);
    }
}

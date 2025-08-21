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
}

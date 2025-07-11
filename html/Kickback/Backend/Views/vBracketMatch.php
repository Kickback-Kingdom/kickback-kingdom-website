<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vBracketMatch
{
    /** @var array<string> */
    public array $teams;

    /** @var array<int> */
    public array $scores;

    /** @var array<?string> */
    public array $displayNames;

    public int $bracketNum;
    public int $roundNum;
    public int $matchNum;

    /**
    * @var array<array<array{}|array{int, string}>>
    * // Names of things in the array: array<array<array{}|array{score: int,  characterHint: string}>>
    */
    public array $sets;

    public float $setsCount;
}

?>

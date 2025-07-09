<?php
declare(strict_types=1);

namespace Kickback\Common\Unittesting\Tests;

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Backend\Views\vDateTime;

$results = [
    'passed' => 0,
    'failed' => 0
];

function test(string $name, callable $fn): void {
    global $results;

    $padded = str_pad("Running: $name", 40, ' ');
    echo $padded;

    try {
        $fn();
        echo "✅ PASS<br/>";
        $results['passed']++;
    } catch (\Throwable $e) {
        echo "❌ FAIL<br/>      ↳ {$e->getMessage()}<br/>";
        $results['failed']++;
    }
}

function assertEqual(
    string|int|float|bool  $a,
    string|int|float|bool  $b,
    string                 $msg = ''
) : void
{
    if ($a !== $b) {
        throw new \Exception("Assertion failed: $a !== $b. $msg");
    }
}

echo "=== vDateTime Unit Tests ===<br/>";

test("Construct from ISO string", function () {
    $d = new vDateTime("2025-04-10T12:34:56Z");
    assertEqual($d->formattedYmd, "2025-04-10");
});

test("Construct from DB string", function () {
    $d = vDateTime::fromDB("2025-04-10 12:34:56");
    assertEqual($d->formattedHi, "12:34");
});

test("FormattedYm produces YYYY-MM", function () {
    $d = new vDateTime("2023-01-15");
    assertEqual($d->formattedYm, "2023-01");
});

test("GetValueString works", function () {
    $d = new vDateTime("2025-01-01T00:00:00Z");
    assertEqual(vDateTime::getValueString($d), $d->valueString);
});

test("GetFormattedYmd null-safe", function () {
    assertEqual(vDateTime::getFormattedYmd(null), "");
});

test("Elapsed string: just now", function () {
    $now = new vDateTime(gmdate("Y-m-d H:i:s"));
    assertEqual($now->timeElapsedString(), "just now");
});

test("Elapsed string: minutes ago", function () {
    $past = (new vDateTime())->subMinutes(5);
    assertEqual($past->timeElapsedString(), "5 minutes ago");
});

test("Add years", function () {
    $d = new vDateTime("2020-01-01");
    assertEqual($d->addYears(2)->formattedYmd, "2022-01-01");
});

test("Subtract years", function () {
    $d = new vDateTime("2020-01-01");
    assertEqual($d->subYears(1)->formattedYmd, "2019-01-01");
});

test("Add months", function () {
    $d = new vDateTime("2020-01-01");
    assertEqual($d->addMonths(1)->formattedYmd, "2020-02-01");
});

test("Subtract months", function () {
    $d = new vDateTime("2020-01-01");
    assertEqual($d->subMonths(1)->formattedYmd, "2019-12-01");
});

test("Add days", function () {
    $d = new vDateTime("2020-01-01");
    assertEqual($d->addDays(10)->formattedYmd, "2020-01-11");
});

test("Subtract days", function () {
    $d = new vDateTime("2020-01-10");
    assertEqual($d->subDays(5)->formattedYmd, "2020-01-05");
});

test("Add hours", function () {
    $d = new vDateTime("2020-01-01 00:00:00");
    assertEqual($d->addHours(2)->formattedHi, "02:00");
});

test("Subtract hours", function () {
    $d = new vDateTime("2020-01-01 05:00:00");
    assertEqual($d->subHours(2)->formattedHi, "03:00");
});

test("Add minutes", function () {
    $d = new vDateTime("2020-01-01 00:00:00");
    assertEqual($d->addMinutes(30)->formattedHi, "00:30");
});

test("Subtract minutes", function () {
    $d = new vDateTime("2020-01-01 01:00:00");
    assertEqual($d->subMinutes(30)->formattedHi, "00:30");
});

test("Add seconds", function () {
    $d = new vDateTime("2020-01-01 00:00:00");
    $new = $d->addSeconds(60);
    assertEqual($new->formattedHi, "00:01");
});

test("Subtract seconds", function () {
    $d = new vDateTime("2020-01-01 00:01:00");
    $new = $d->subSeconds(60);
    assertEqual($new->formattedHi, "00:00");
});

test("Clone modify does not mutate original", function () {
    $original = new vDateTime("2020-01-01");
    $modified = $original->addDays(1);
    assertEqual($original->formattedYmd, "2020-01-01");
    assertEqual($modified->formattedYmd, "2020-01-02");
});

test("expired() returns false for future", function () {
    $future = (new vDateTime())->addDays(1);
    assertEqual($future->expired(), false);
});

test("expired() returns true for past", function () {
    $past = (new vDateTime())->subDays(1);
    assertEqual($past->expired(), true);
});

test("getDateTimeElement produces HTML", function () {
    $d = new vDateTime("2025-04-10T12:00:00Z");
    $html = $d->getDateTimeElement("test-id");
    assertEqual(strpos($html, "data-bs-title=") !== false, true);
});

test("Multiple formats are all consistent", function () {
    $d = new vDateTime("2024-12-31 23:59:59");
    assertEqual($d->formattedBasic, "Dec 31, 2024");
    assertEqual($d->formattedDetailed, "Dec 31, 2024 23:59:59");
    assertEqual($d->formattedYmd, "2024-12-31");
    assertEqual($d->formattedYm, "2024-12");
    assertEqual($d->formattedHi, "23:59");
});

test("Handles leap year correctly", function () {
    $d = new vDateTime("2020-02-29");
    assertEqual($d->addYears(1)->formattedYmd, "2021-03-01");

});

test("Handles new year correctly", function () {
    $d = new vDateTime("2023-12-31");
    assertEqual($d->addDays(1)->formattedYmd, "2024-01-01");
});
// === Component Setters ===

test("Set day to 1", function () {
    $d = new vDateTime("2025-04-15 12:00:00");
    $set = $d->setDay(1);
    assertEqual($set->formattedYmd, "2025-04-01");
});

test("Set month to January", function () {
    $d = new vDateTime("2025-04-15 12:00:00");
    $set = $d->setMonth(1);
    assertEqual($set->formattedYmd, "2025-01-15");
});

test("Set year to 2030", function () {
    $d = new vDateTime("2025-04-15 12:00:00");
    $set = $d->setYear(2030);
    assertEqual($set->formattedYmd, "2030-04-15");
});

test("Set time to 08:30:00", function () {
    $d = new vDateTime("2025-04-15 12:45:15");
    $set = $d->setTime(8, 30, 0);
    assertEqual($set->formattedHi, "08:30");
});

// === Component Getters ===

test("Get day returns correct value", function () {
    $d = new vDateTime("2025-04-15");
    assertEqual($d->getDay(), 15);
});

test("Get month returns correct value", function () {
    $d = new vDateTime("2025-04-15");
    assertEqual($d->getMonth(), 4);
});

test("Get year returns correct value", function () {
    $d = new vDateTime("2025-04-15");
    assertEqual($d->getYear(), 2025);
});

test("Get hour returns correct value", function () {
    $d = new vDateTime("2025-04-15 18:30:00");
    assertEqual($d->getHour(), 18);
});

test("Get minute returns correct value", function () {
    $d = new vDateTime("2025-04-15 18:30:00");
    assertEqual($d->getMinute(), 30);
});

test("Get second returns correct value", function () {
    $d = new vDateTime("2025-04-15 18:30:45");
    assertEqual($d->getSecond(), 45);
});

test("Chained setYear/setMonth/setDay/setTime", function () {
    $d = new vDateTime("2020-01-01 00:00:00");
    $modified = $d->setYear(2024)->setMonth(12)->setDay(25)->setTime(14, 45, 30);
    assertEqual($modified->formattedDetailed, "Dec 25, 2024 14:45:30");
});

echo "<br/>";
echo "=== Test Summary ===<br/>";
echo "✅ Passed: {$results['passed']}<br/>";
echo "❌ Failed: {$results['failed']}<br/>";
echo "====================<br/>";

/** @phpstan-ignore greater.alwaysFalse */
if ($results['failed'] > 0) {
    exit(1);
}
?>

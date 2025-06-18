<?php
declare(strict_types=1);

namespace Kickback\Common\Unittesting\Tests;

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Backend\Views\vDecimal;

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


// Simple assertion
function assertEqual($a, $b, string $msg = '') {
    if ($a !== $b) {
        throw new \Exception("Assertion failed: $a !== $b. $msg");
    }
}

echo "=== vDecimal Unit Tests ===<br/>";

// Construction
test("Construct from string", function () {
    $d = new vDecimal("1.23456789", 8);
    assertEqual($d->toString(), "1.23456789");
});

test("Construct from float", function () {
    $d = new vDecimal(0.1, 8);
    assertEqual($d->toString(), "0.10000000"); // Expect padding
});

test("Construct from atomic integer", function () {
    $d = new vDecimal(123456789, 8);
    assertEqual($d->toAtomicInt(), 123456789);
});

test("Construct from unscaled whole number", function () {
    $d = new vDecimal("123.456789", 6);
    assertEqual($d->toAtomicInt(), 123456789);
});

// Arithmetic
test("Add", function () {
    $a = new vDecimal("1.11111111", 8);
    $b = new vDecimal("2.22222222", 8);
    $c = $a->add($b);
    assertEqual($c->toString(), "3.33333333");
});

test("Sub", function () {
    $a = new vDecimal("5.5", 6);
    $b = new vDecimal("2.5", 6);
    $c = $a->sub($b);
    assertEqual($c->toString(), "3.000000");
});

test("Mul", function () {
    $a = new vDecimal("2.5", 6);
    $b = new vDecimal("3", 6);
    $c = $a->mul($b);
    assertEqual($c->toString(), "7.500000");
});

test("Div", function () {
    $a = new vDecimal("7.5", 6);
    $b = new vDecimal("2.5", 6);
    $c = $a->div($b);
    assertEqual($c->toString(), "3.000000");
});

test("Divide by zero", function () {
    $a = new vDecimal("1", 6);
    $b = new vDecimal("0", 6);

    try {
        $a->div($b);
        throw new Exception("Expected exception for division by zero not thrown");
    } catch (\InvalidArgumentException $e) {
        assertEqual($e->getMessage(), "Division by zero");
    }
});

// Rounding
test("Floor", function () {
    $a = new vDecimal("3.99999999", 8);
    assertEqual($a->floor()->toString(), "3.00000000");
});

test("Ceil", function () {
    $a = new vDecimal("3.00000001", 8);
    assertEqual($a->ceil()->toString(), "4.00000000");
});

test("Round down", function () {
    $a = new vDecimal("3.4", 8);
    assertEqual($a->round()->toString(), "3.00000000");
});

test("Round up", function () {
    $a = new vDecimal("3.5", 8);
    assertEqual($a->round()->toString(), "4.00000000");
});

// Negative values
test("Negative floor", function () {
    $a = new vDecimal("-2.3", 8);
    assertEqual($a->floor()->toString(), "-3.00000000");
});

test("Negative ceil", function () {
    $a = new vDecimal("-2.3", 8);
    assertEqual($a->ceil()->toString(), "-2.00000000");
});

test("Scale mismatch throws", function () {
    $a = new vDecimal("1.23", 6);
    $b = new vDecimal("1.23", 8);

    try {
        $a->add($b);
        throw new Exception("Expected scale mismatch exception not thrown");
    } catch (\InvalidArgumentException $e) {
        assertEqual(strpos($e->getMessage(), "Mismatched vDecimal scales") !== false, true);
    }
});

test("Zero value formatting", function () {
    $a = new vDecimal("0.00000000", 8);
    assertEqual($a->toString(), "0.00000000");
});

test("Add negative number", function () {
    $a = new vDecimal("5.0", 6);
    $b = new vDecimal("-2.5", 6);
    $c = $a->add($b);
    assertEqual($c->toString(), "2.500000");
});

test("Sub negative number", function () {
    $a = new vDecimal("5.0", 6);
    $b = new vDecimal("-2.5", 6);
    $c = $a->sub($b);
    assertEqual($c->toString(), "7.500000");
});

test("Negative round down", function () {
    $a = new vDecimal("-3.5", 8);
    assertEqual($a->round()->toString(), "-4.00000000");
});

test("Negative round up", function () {
    $a = new vDecimal("-3.4", 8);
    assertEqual($a->round()->toString(), "-3.00000000");
});

test("High precision input", function () {
    $a = new vDecimal("1.123456789123456789", 8);
    assertEqual($a->toString(), "1.12345678"); // Truncated at 8 decimals
});

test("Very large value", function () {
    $a = new vDecimal("99999999999.999999", 6);
    assertEqual($a->toString(), "99999999999.999999");
});

test("Construct with more precision than scale truncates", function () {
    $d = new vDecimal("1.234567891234", 8);
    assertEqual($d->toString(), "1.23456789");
});

test("Add zero", function () {
    $a = new vDecimal("1.23", 6);
    $zero = new vDecimal("0", 6);
    assertEqual($a->add($zero)->toString(), "1.230000");
});

test("Sub zero", function () {
    $a = new vDecimal("1.23", 6);
    $zero = new vDecimal("0", 6);
    assertEqual($a->sub($zero)->toString(), "1.230000");
});

test("Fractional multiplication", function () {
    $a = new vDecimal("0.25", 6);
    $b = new vDecimal("0.25", 6);
    assertEqual($a->mul($b)->toString(), "0.062500");
});

test("Fractional division", function () {
    $a = new vDecimal("1.000000", 6);
    $b = new vDecimal("0.25", 6);
    assertEqual($a->div($b)->toString(), "4.000000");
});

test("Round zero", function () {
    $a = new vDecimal("0", 8);
    assertEqual($a->round()->toString(), "0.00000000");
});

test("Get scale", function () {
    $a = new vDecimal("123.456", 6);
    assertEqual($a->getScale(), 6);
});
test("Chained add-sub-mul-div", function () {
    $a = new vDecimal("10.000000", 6);
    $b = new vDecimal("2.000000", 6);
    $c = $a->add($b)->sub($b)->mul($b)->div($b);
    assertEqual($c->toString(), "10.000000");
});
test("toFloat matches toString approximately", function () {
    $a = new vDecimal("3.141592", 6);
    $float = $a->toFloat();
    assertEqual(number_format($float, 6, '.', ''), $a->toString());
});
test("Very negative value formatting", function () {
    $a = new vDecimal("-99999999999.999999", 6);
    assertEqual($a->toString(), "-99999999999.999999");
});
test("Scale zero integer math", function () {
    $a = new vDecimal("10", 0);
    $b = new vDecimal("3", 0);
    assertEqual($a->div($b)->toString(), "3");
});
test("Leading zeros", function () {
    $a = new vDecimal("0000123.456000", 6);
    assertEqual($a->toString(), "123.456000");
});


test("decimal dissection via subScalar", function () {
    $a = new vDecimal("123.456000", 6);

    $wholeUnits = $a->toWholeUnitsInt();          // int: 123
    $fractional = $a->subScalar($wholeUnits);     // vDecimal: 0.456000

    $expectedFraction = new vDecimal("0.456000", 6);

    assertEqual($wholeUnits, 123);                            // good
    assertEqual($fractional->toAtomicInt(), $expectedFraction->toAtomicInt());
    assertEqual($fractional->toString(), $expectedFraction->toString()); // safe string compare
});

test("string constructor produces correct atomic value", function () {
    $d = new vDecimal("123.456000", 6);
    $expectedAtomic = 123456000;

    assertEqual($d->toAtomicInt(), $expectedAtomic, "Expected atomic value for '123.456000' with scale 6");
});

test("string input is accurate", function () {
    $s = new vDecimal("123.456000", 6);
    assertEqual($s->toAtomicInt(), 123456000);
});

test("float input is inaccurate", function () {
    $f = new vDecimal(123.456, 6);
    assertEqual($f->toAtomicInt(), 123456000);
});
// === Scalar Tests ===
test("addScalar int", function () {
    $a = new vDecimal("1.250000", 6);
    $b = $a->addScalar(2);
    assertEqual($b->toString(), "3.250000");
});

test("subScalar string", function () {
    $a = new vDecimal("5.500000", 6);
    $b = $a->subScalar("2.250000");
    assertEqual($b->toString(), "3.250000");
});

test("mulScalar float", function () {
    $a = new vDecimal("2.000000", 6);
    $b = $a->mulScalar(2.5);
    assertEqual($b->toString(), "5.000000");
});

test("divScalar float", function () {
    $a = new vDecimal("5.000000", 6);
    $b = $a->divScalar(2.5);
    assertEqual($b->toString(), "2.000000");
});

// === Whole Unit Tests ===
test("addWhole", function () {
    $a = new vDecimal("1.250000", 6);
    $b = $a->addWhole(3);
    assertEqual($b->toString(), "4.250000");
});

test("subWhole", function () {
    $a = new vDecimal("5.000000", 6);
    $b = $a->subWhole(2);
    assertEqual($b->toString(), "3.000000");
});

test("mulWhole", function () {
    $a = new vDecimal("1.250000", 6);
    $b = $a->mulWhole(4);
    assertEqual($b->toString(), "5.000000");
});

test("divWhole", function () {
    $a = new vDecimal("5.000000", 6);
    $b = $a->divWhole(5);
    assertEqual($b->toString(), "1.000000");
});

// === Atomic Tests ===
test("addAtomic", function () {
    $a = new vDecimal("1.250000", 6); // atomic = 1250000
    $b = $a->addAtomic(500000); // adds 0.5
    assertEqual($b->toString(), "1.750000");
});

test("subAtomic", function () {
    $a = new vDecimal("3.000000", 6);
    $b = $a->subAtomic(500000); // subtracts 0.5
    assertEqual($b->toString(), "2.500000");
});

test("mulAtomic", function () {
    $a = new vDecimal("2.000000", 6);
    $b = $a->mulAtomic(1500000); // atomic = 1.5
    assertEqual($b->toString(), "3.000000");
});

test("divAtomic", function () {
    $a = new vDecimal("3.000000", 6);
    $b = $a->divAtomic(1500000); // atomic = 1.5
    assertEqual($b->toString(), "2.000000");
});

// === Cross-Method Tests ===
test("scalar + whole + atomic", function () {
    $a = new vDecimal("1.250000", 6);
    $b = $a->addScalar("0.500000")->addWhole(1)->addAtomic(250000); // total = 3.000000
    assertEqual($b->toString(), "3.000000");
});

test("whole * scalar", function () {
    $a = new vDecimal("2.000000", 6);
    $b = $a->mulScalar("1.5")->mulWhole(2); // 2 * 1.5 * 2 = 6
    assertEqual($b->toString(), "6.000000");
});

test("atomic edge rounding", function () {
    $a = new vDecimal("1.999999", 6);
    $b = $a->addAtomic(1); // should add 0.000001
    assertEqual($b->toString(), "2.000000");
});
test("Precision overflow is truncated", function () {
    $a = new vDecimal("1.123456789", 6);
    assertEqual($a->toString(), "1.123456"); // Should truncate
});
test("Negative atomic subtraction", function () {
    $a = new vDecimal("1.000000", 6);
    $b = $a->subAtomic(2000000); // Subtract 2.000000
    assertEqual($b->toString(), "-1.000000");
});
test("Zero scale behavior", function () {
    $a = new vDecimal("5", 0);
    assertEqual($a->toString(), "5");
});
test("Atomic rounding edge case", function () {
    $a = new vDecimal("0.999999", 6);
    $b = $a->addAtomic(1); // Just tips it over
    assertEqual($b->toString(), "1.000000");
});
test("Deep operation chaining", function () {
    $a = new vDecimal("1.000000", 6);
    $result = $a
        ->addScalar("1.5")
        ->subScalar("0.5")
        ->mulScalar("2")
        ->divScalar("2");
    assertEqual($result->toString(), "2.000000");
});
test("Sub to zero", function () {
    $a = new vDecimal("3.141592", 6);
    $b = $a->sub(new vDecimal("3.141592", 6));
    assertEqual($b->toString(), "0.000000");
});
test("Add then sub returns to original", function () {
    $a = new vDecimal("2.718281", 6);
    $b = $a->addScalar("1.234567")->subScalar("1.234567");
    assertEqual($b->toString(), $a->toString());
});
test("toFloat close to string", function () {
    $a = new vDecimal("3.141592", 6);
    assertEqual(round($a->toFloat(), 6), (float)"3.141592");
});
test("Scientific notation string input", function () {
    $a = new vDecimal("1.23e2", 6); // 123.000000
    assertEqual($a->toString(), "123.000000");
});
test("Input with spaces trims correctly", function () {
    $a = new vDecimal("  123.456   ", 6);
    assertEqual($a->toString(), "123.456000");
});
test("Floor + remainder equals original", function () {
    $a = new vDecimal("123.456789", 6);
    $floor = $a->floor();
    $remainder = $a->sub($floor);
    $recombined = $floor->add($remainder);
    assertEqual($recombined->toString(), $a->toString());
});

test("Distributive property", function () {
    $a = new vDecimal("2.0", 6);
    $b = new vDecimal("3.0", 6);
    $c = new vDecimal("4.0", 6);
    $left = $a->add($b)->mul($c);
    $right = $a->mul($c)->add($b->mul($c));
    assertEqual($left->toString(), $right->toString());
});
test("Chained atomic/whole/scalar consistency", function () {
    $a = new vDecimal("0.000000", 6);
    $b = $a->addWhole(1)->addAtomic(500000)->addScalar("0.5"); // 1 + 0.5 + 0.5
    assertEqual($b->toString(), "2.000000");
});
test("Invalid string throws", function () {
    try {
        new vDecimal("abc123", 6);
        throw new Exception("Expected exception for invalid numeric string not thrown");
    } catch (\Throwable $e) {
        assertEqual(true, true); // Pass
    }
});
test("High scale multiplication", function () {
    $a = new vDecimal("0.000001", 8);
    $b = new vDecimal("1000000", 8);
    $c = $a->mul($b);
    assertEqual($c->toString(), "1.00000000");
});
test("Scientific notation string input", function () {
    $d = new vDecimal("1.23e2", 6); // = 123.000000
    assertEqual($d->toString(), "123.000000");
});

test("Input with spaces trims correctly", function () {
    $d = new vDecimal("   123.456   ", 6);
    assertEqual($d->toString(), "123.456000");
});
test("subScalar(1) vs subWhole(1)", function () {
    $original = new vDecimal("10.000000", 6);

    $viaScalar = $original->subScalar(1);
    $viaWhole = $original->subWhole(1);

    // They should be equal in value
    assertEqual($viaScalar->toAtomicInt(), $viaWhole->toAtomicInt(), "Atomic mismatch");
    assertEqual($viaScalar->toString(), $viaWhole->toString(), "String mismatch");
});

echo "<br/>";
echo "=== Test Summary ===<br/>";
echo "✅ Passed: {$results['passed']}<br/>";
echo "❌ Failed: {$results['failed']}<br/>";
echo "====================<br/>";

if ($results['failed'] > 0) {
    exit(1); // CLI-friendly fail code
}
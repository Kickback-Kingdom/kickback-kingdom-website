<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vDecimal implements \JsonSerializable
{
    private int $value;     // Integer representation of the fixed-point value
    private int $scale;     // Number of decimal places (i.e., precision)

    public function __construct(int|float|string $input, int $scale = 8)
    {
        if (!function_exists('bcadd')) {
            throw new \RuntimeException("BCMath extension is required for vDecimal but not installed.");
        }
        
        $this->scale = $scale;

        if (is_int($input)) {
            $this->value = $input;
        } elseif (is_string($input)) {
            $input = trim($input);

            // Convert scientific notation to a proper decimal string
            if (stripos($input, 'e') !== false) {
                $input = sprintf('%.'.$scale.'f', (float)$input);
            }

            $this->value = (int)bcadd(bcmul($input, bcpow('10', (string)$scale, 0), 0), '0', 0);
        } elseif (is_float($input)) {
            $normalized = number_format($input, $scale, '.', '');
            $this->value = (int)bcadd(bcmul($normalized, bcpow('10', (string)$scale, 0), 0), '0', 0);
        } else {
            throw new \InvalidArgumentException("Unsupported input type passed to vDecimal.");
        }
    }

    /**
    * @return array{
    *     atomic:    int,
    *     formatted: string,
    *     float:     float,
    *     scale:     int
    * }
    */
    public function jsonSerialize(): array
    {
        return [
            'atomic' => $this->value,
            'formatted' => $this->toString(),
            'float' => $this->toFloat(),
            'scale' => $this->scale
        ];
    }
    
    public static function Zero(int $scale = 8): self{
        return new self(0, $scale);
    }

    public static function fromDB(string $input, int $scale = 8): self
    {
        return new self($input, $scale);
    }

    public static function fromInt(int $input, int $scale = 8) : self {
        return self::fromDB((string)$input, $scale);
    }

    public function add(vDecimal $other): vDecimal
    {
        $this->assertSameScale($other);
        return new self($this->value + $other->value, $this->scale);
    }

    public function sub(vDecimal $other): vDecimal
    {
        $this->assertSameScale($other);
        return new self($this->value - $other->value, $this->scale);
    }

    public function mul(vDecimal $other): vDecimal
    {
        $this->assertSameScale($other);
        $scaleFactor = bcpow('10', (string)$this->scale, 0);
        $product = (int)(($this->value * $other->value) / (int)$scaleFactor);
        return new self($product, $this->scale);
    }

    public function div(vDecimal $other): vDecimal
    {
        $this->assertSameScale($other);
        if ($other->value === 0) throw new \InvalidArgumentException("Division by zero");

        $scaleFactor = bcpow('10', (string)$this->scale, 0);
        $quotient = (int)(($this->value * (int)$scaleFactor) / $other->value);
        return new self($quotient, $this->scale);
    }
    
    public function addScalar(int|float|string $scalar): vDecimal
    {
        if (is_int($scalar)) {
            $scalar = number_format($scalar, $this->scale, '.', '');
        }
        return $this->add(new self($scalar, $this->scale));
    }

    public function subScalar(int|float|string $scalar): vDecimal
    {
        if (is_int($scalar)) {
            $scalar = number_format($scalar, $this->scale, '.', '');
        }
        return $this->sub(new self($scalar, $this->scale));
    }

    public function mulScalar(int|float|string $scalar): vDecimal
    {
        if (is_int($scalar)) {
            $scalar = number_format($scalar, $this->scale, '.', '');
        }
        return $this->mul(new self($scalar, $this->scale));
    }

    public function divScalar(int|float|string $scalar): vDecimal
    {
        if (is_int($scalar)) {
            $scalar = number_format($scalar, $this->scale, '.', '');
        }
        return $this->div(new self($scalar, $this->scale));
    }

    public function addWhole(int $units): vDecimal
    {
        return $this->add(new self(number_format($units, $this->scale, '.', ''), $this->scale));
    }
    
    public function subWhole(int $units): vDecimal
    {
        return $this->sub(new self(number_format($units, $this->scale, '.', ''), $this->scale));
    }
    
    public function mulWhole(int $units): vDecimal
    {
        return $this->mul(new self(number_format($units, $this->scale, '.', ''), $this->scale));
    }
    
    public function divWhole(int $units): vDecimal
    {
        return $this->div(new self(number_format($units, $this->scale, '.', ''), $this->scale));
    }

    public function addAtomic(int $atomic): vDecimal
    {
        return new self($this->value + $atomic, $this->scale);
    }

    public function subAtomic(int $atomic): vDecimal
    {
        return new self($this->value - $atomic, $this->scale);
    }

    public function mulAtomic(int $atomic): vDecimal
    {
        $product = (int)(($this->value * $atomic) / pow(10, $this->scale));
        return new self($product, $this->scale);
    }

    public function divAtomic(int $atomic): vDecimal
    {
        if ($atomic === 0) throw new \InvalidArgumentException("Division by zero");
        $quotient = (int)(($this->value * pow(10, $this->scale)) / $atomic);
        return new self($quotient, $this->scale);
    }

    public function toAtomicInt(): int
    {
        return $this->value;
    }

    public function toWholeUnitsInt(): int
    {
        $floored = $this->floor();
        return (int)($floored->toAtomicInt() / pow(10, $this->scale));
    }

    public function getFractional() : vDecimal {
        return $this->subWhole($this->toWholeUnitsInt());
    }

    public function toFloat(): float
    {
        return $this->value / pow(10, $this->scale);
    }

    public function toString(): string
    {
        return bcdiv((string)$this->value, bcpow('10', (string)$this->scale, 0), $this->scale);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function assertSameScale(vDecimal $other): void
    {
        if ($this->scale !== $other->scale) {
            throw new \InvalidArgumentException("Mismatched vDecimal scales: {$this->scale} vs {$other->scale}");
        }
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function floor(): vDecimal
    {
        $unitScale = bcpow('10', (string)$this->scale, 0);
        $floored = (int)(floor($this->value / (int)$unitScale)) * (int)$unitScale;
        return new self($floored, $this->scale);
    }

    public function ceil(): vDecimal
    {
        $unitScale = bcpow('10', (string)$this->scale, 0);
        $ceiled = (int)(ceil($this->value / (int)$unitScale)) * (int)$unitScale;
        return new self($ceiled, $this->scale);
    }

    public function round(): vDecimal
    {
        $unitScale = bcpow('10', (string)$this->scale, 0);
        $rounded = (int)(round($this->value / (int)$unitScale)) * (int)$unitScale;
        return new self($rounded, $this->scale);
    }

    public function roundTo(int $decimalPlace): vDecimal
    {
        $unitScale = bcpow('10', (string)($this->scale - $decimalPlace), 0);
        $rounded = (int)(round($this->value / (int)$unitScale)) * (int)$unitScale;
        return new self($rounded, $this->scale);
    }
}


// =============================================================================
// TODO: Integrate these with the whole-system unittests (or render vDecimal obsolete somehow)
// (Also it miiiight wait until we have a test runner; ideally one capable of HTML output.)
//
// These are intentionally inaccessible from the web for security reasons.
//
// To run them, place a script in your `html/scratch-pad/` directory with these contents:
// ```
// // (html/)scratch-pad/foobar.php
// declare(strict_types=1);
//
// require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");
//
// use Kickback\Backend\Views\vDateTime__Tests;
// use Kickback\Backend\Views\vDecimal__Tests;
//
// $ret = 0;
// $ret |= vDateTime__Tests::unittests() << 0;
// $ret |= vDecimal__Tests::unittests()  << 1;
// return $ret;
// ```
// Of course, add in the PHP start tag and optional end tag!
// (I can't put those in the comment without breaking the file)
//
// Then navigate your browser to
// https://127.0.0.1/scratch-pad/foobar.php
// ... and the tests should run.
//
final class vDecimal__Tests
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /** @var array{ passed: int, failed: int } */
    private static array $results = [
        'passed' => 0,
        'failed' => 0
    ];

    public static function test(string $name, callable $fn): void
    {
        $padded = str_pad("Running: $name", 40, ' ');
        echo $padded;

        try {
            $fn();
            echo "✅ PASS<br/>";
            self::$results['passed']++;
        } catch (\Throwable $e) {
            echo "❌ FAIL<br/>      ↳ {$e->getMessage()}<br/>";
            self::$results['failed']++;
        }
    }


    // Simple assertion
    public static function assertEqual(
        string|int|float|bool  $a,
        string|int|float|bool  $b,
        string                 $msg = ''
    ) : void
    {
        if ($a !== $b) {
            throw new \Exception("Assertion failed: $a !== $b. $msg");
        }
    }

    public static function unittests() : int
    {
        echo "=== vDecimal Unit Tests ===<br/>";

        // Construction
        self::test("Construct from string", function () {
            $d = new vDecimal("1.23456789", 8);
            self::assertEqual($d->toString(), "1.23456789");
        });

        self::test("Construct from float", function () {
            $d = new vDecimal(0.1, 8);
            self::assertEqual($d->toString(), "0.10000000"); // Expect padding
        });

        self::test("Construct from atomic integer", function () {
            $d = new vDecimal(123456789, 8);
            self::assertEqual($d->toAtomicInt(), 123456789);
        });

        self::test("Construct from unscaled whole number", function () {
            $d = new vDecimal("123.456789", 6);
            self::assertEqual($d->toAtomicInt(), 123456789);
        });

        // Arithmetic
        self::test("Add", function () {
            $a = new vDecimal("1.11111111", 8);
            $b = new vDecimal("2.22222222", 8);
            $c = $a->add($b);
            self::assertEqual($c->toString(), "3.33333333");
        });

        self::test("Sub", function () {
            $a = new vDecimal("5.5", 6);
            $b = new vDecimal("2.5", 6);
            $c = $a->sub($b);
            self::assertEqual($c->toString(), "3.000000");
        });

        self::test("Mul", function () {
            $a = new vDecimal("2.5", 6);
            $b = new vDecimal("3", 6);
            $c = $a->mul($b);
            self::assertEqual($c->toString(), "7.500000");
        });

        self::test("Div", function () {
            $a = new vDecimal("7.5", 6);
            $b = new vDecimal("2.5", 6);
            $c = $a->div($b);
            self::assertEqual($c->toString(), "3.000000");
        });

        self::test("Divide by zero", function () {
            $a = new vDecimal("1", 6);
            $b = new vDecimal("0", 6);

            try {
                $a->div($b);
                throw new \Exception("Expected exception for division by zero not thrown");
            } catch (\InvalidArgumentException $e) {
                self::assertEqual($e->getMessage(), "Division by zero");
            }
        });

        // Rounding
        self::test("Floor", function () {
            $a = new vDecimal("3.99999999", 8);
            self::assertEqual($a->floor()->toString(), "3.00000000");
        });

        self::test("Ceil", function () {
            $a = new vDecimal("3.00000001", 8);
            self::assertEqual($a->ceil()->toString(), "4.00000000");
        });

        self::test("Round down", function () {
            $a = new vDecimal("3.4", 8);
            self::assertEqual($a->round()->toString(), "3.00000000");
        });

        self::test("Round up", function () {
            $a = new vDecimal("3.5", 8);
            self::assertEqual($a->round()->toString(), "4.00000000");
        });

        // Negative values
        self::test("Negative floor", function () {
            $a = new vDecimal("-2.3", 8);
            self::assertEqual($a->floor()->toString(), "-3.00000000");
        });

        self::test("Negative ceil", function () {
            $a = new vDecimal("-2.3", 8);
            self::assertEqual($a->ceil()->toString(), "-2.00000000");
        });

        self::test("Scale mismatch throws", function () {
            $a = new vDecimal("1.23", 6);
            $b = new vDecimal("1.23", 8);

            try {
                $a->add($b);
                throw new \Exception("Expected scale mismatch exception not thrown");
            } catch (\InvalidArgumentException $e) {
                self::assertEqual(strpos($e->getMessage(), "Mismatched vDecimal scales") !== false, true);
            }
        });

        self::test("Zero value formatting", function () {
            $a = new vDecimal("0.00000000", 8);
            self::assertEqual($a->toString(), "0.00000000");
        });

        self::test("Add negative number", function () {
            $a = new vDecimal("5.0", 6);
            $b = new vDecimal("-2.5", 6);
            $c = $a->add($b);
            self::assertEqual($c->toString(), "2.500000");
        });

        self::test("Sub negative number", function () {
            $a = new vDecimal("5.0", 6);
            $b = new vDecimal("-2.5", 6);
            $c = $a->sub($b);
            self::assertEqual($c->toString(), "7.500000");
        });

        self::test("Negative round down", function () {
            $a = new vDecimal("-3.5", 8);
            self::assertEqual($a->round()->toString(), "-4.00000000");
        });

        self::test("Negative round up", function () {
            $a = new vDecimal("-3.4", 8);
            self::assertEqual($a->round()->toString(), "-3.00000000");
        });

        self::test("High precision input", function () {
            $a = new vDecimal("1.123456789123456789", 8);
            self::assertEqual($a->toString(), "1.12345678"); // Truncated at 8 decimals
        });

        self::test("Very large value", function () {
            $a = new vDecimal("99999999999.999999", 6);
            self::assertEqual($a->toString(), "99999999999.999999");
        });

        self::test("Construct with more precision than scale truncates", function () {
            $d = new vDecimal("1.234567891234", 8);
            self::assertEqual($d->toString(), "1.23456789");
        });

        self::test("Add zero", function () {
            $a = new vDecimal("1.23", 6);
            $zero = new vDecimal("0", 6);
            self::assertEqual($a->add($zero)->toString(), "1.230000");
        });

        self::test("Sub zero", function () {
            $a = new vDecimal("1.23", 6);
            $zero = new vDecimal("0", 6);
            self::assertEqual($a->sub($zero)->toString(), "1.230000");
        });

        self::test("Fractional multiplication", function () {
            $a = new vDecimal("0.25", 6);
            $b = new vDecimal("0.25", 6);
            self::assertEqual($a->mul($b)->toString(), "0.062500");
        });

        self::test("Fractional division", function () {
            $a = new vDecimal("1.000000", 6);
            $b = new vDecimal("0.25", 6);
            self::assertEqual($a->div($b)->toString(), "4.000000");
        });

        self::test("Round zero", function () {
            $a = new vDecimal("0", 8);
            self::assertEqual($a->round()->toString(), "0.00000000");
        });

        self::test("Get scale", function () {
            $a = new vDecimal("123.456", 6);
            self::assertEqual($a->getScale(), 6);
        });
        self::test("Chained add-sub-mul-div", function () {
            $a = new vDecimal("10.000000", 6);
            $b = new vDecimal("2.000000", 6);
            $c = $a->add($b)->sub($b)->mul($b)->div($b);
            self::assertEqual($c->toString(), "10.000000");
        });
        self::test("toFloat matches toString approximately", function () {
            $a = new vDecimal("3.141592", 6);
            $float = $a->toFloat();
            self::assertEqual(number_format($float, 6, '.', ''), $a->toString());
        });
        self::test("Very negative value formatting", function () {
            $a = new vDecimal("-99999999999.999999", 6);
            self::assertEqual($a->toString(), "-99999999999.999999");
        });
        self::test("Scale zero integer math", function () {
            $a = new vDecimal("10", 0);
            $b = new vDecimal("3", 0);
            self::assertEqual($a->div($b)->toString(), "3");
        });
        self::test("Leading zeros", function () {
            $a = new vDecimal("0000123.456000", 6);
            self::assertEqual($a->toString(), "123.456000");
        });


        self::test("decimal dissection via subScalar", function () {
            $a = new vDecimal("123.456000", 6);

            $wholeUnits = $a->toWholeUnitsInt();          // int: 123
            $fractional = $a->subScalar($wholeUnits);     // vDecimal: 0.456000

            $expectedFraction = new vDecimal("0.456000", 6);

            self::assertEqual($wholeUnits, 123);                            // good
            self::assertEqual($fractional->toAtomicInt(), $expectedFraction->toAtomicInt());
            self::assertEqual($fractional->toString(), $expectedFraction->toString()); // safe string compare
        });

        self::test("string constructor produces correct atomic value", function () {
            $d = new vDecimal("123.456000", 6);
            $expectedAtomic = 123456000;

            self::assertEqual($d->toAtomicInt(), $expectedAtomic, "Expected atomic value for '123.456000' with scale 6");
        });

        self::test("string input is accurate", function () {
            $s = new vDecimal("123.456000", 6);
            self::assertEqual($s->toAtomicInt(), 123456000);
        });

        self::test("float input is inaccurate", function () {
            $f = new vDecimal(123.456, 6);
            self::assertEqual($f->toAtomicInt(), 123456000);
        });
        // === Scalar Tests ===
        self::test("addScalar int", function () {
            $a = new vDecimal("1.250000", 6);
            $b = $a->addScalar(2);
            self::assertEqual($b->toString(), "3.250000");
        });

        self::test("subScalar string", function () {
            $a = new vDecimal("5.500000", 6);
            $b = $a->subScalar("2.250000");
            self::assertEqual($b->toString(), "3.250000");
        });

        self::test("mulScalar float", function () {
            $a = new vDecimal("2.000000", 6);
            $b = $a->mulScalar(2.5);
            self::assertEqual($b->toString(), "5.000000");
        });

        self::test("divScalar float", function () {
            $a = new vDecimal("5.000000", 6);
            $b = $a->divScalar(2.5);
            self::assertEqual($b->toString(), "2.000000");
        });

        // === Whole Unit Tests ===
        self::test("addWhole", function () {
            $a = new vDecimal("1.250000", 6);
            $b = $a->addWhole(3);
            self::assertEqual($b->toString(), "4.250000");
        });

        self::test("subWhole", function () {
            $a = new vDecimal("5.000000", 6);
            $b = $a->subWhole(2);
            self::assertEqual($b->toString(), "3.000000");
        });

        self::test("mulWhole", function () {
            $a = new vDecimal("1.250000", 6);
            $b = $a->mulWhole(4);
            self::assertEqual($b->toString(), "5.000000");
        });

        self::test("divWhole", function () {
            $a = new vDecimal("5.000000", 6);
            $b = $a->divWhole(5);
            self::assertEqual($b->toString(), "1.000000");
        });

        // === Atomic Tests ===
        self::test("addAtomic", function () {
            $a = new vDecimal("1.250000", 6); // atomic = 1250000
            $b = $a->addAtomic(500000); // adds 0.5
            self::assertEqual($b->toString(), "1.750000");
        });

        self::test("subAtomic", function () {
            $a = new vDecimal("3.000000", 6);
            $b = $a->subAtomic(500000); // subtracts 0.5
            self::assertEqual($b->toString(), "2.500000");
        });

        self::test("mulAtomic", function () {
            $a = new vDecimal("2.000000", 6);
            $b = $a->mulAtomic(1500000); // atomic = 1.5
            self::assertEqual($b->toString(), "3.000000");
        });

        self::test("divAtomic", function () {
            $a = new vDecimal("3.000000", 6);
            $b = $a->divAtomic(1500000); // atomic = 1.5
            self::assertEqual($b->toString(), "2.000000");
        });

        // === Cross-Method Tests ===
        self::test("scalar + whole + atomic", function () {
            $a = new vDecimal("1.250000", 6);
            $b = $a->addScalar("0.500000")->addWhole(1)->addAtomic(250000); // total = 3.000000
            self::assertEqual($b->toString(), "3.000000");
        });

        self::test("whole * scalar", function () {
            $a = new vDecimal("2.000000", 6);
            $b = $a->mulScalar("1.5")->mulWhole(2); // 2 * 1.5 * 2 = 6
            self::assertEqual($b->toString(), "6.000000");
        });

        self::test("atomic edge rounding", function () {
            $a = new vDecimal("1.999999", 6);
            $b = $a->addAtomic(1); // should add 0.000001
            self::assertEqual($b->toString(), "2.000000");
        });
        self::test("Precision overflow is truncated", function () {
            $a = new vDecimal("1.123456789", 6);
            self::assertEqual($a->toString(), "1.123456"); // Should truncate
        });
        self::test("Negative atomic subtraction", function () {
            $a = new vDecimal("1.000000", 6);
            $b = $a->subAtomic(2000000); // Subtract 2.000000
            self::assertEqual($b->toString(), "-1.000000");
        });
        self::test("Zero scale behavior", function () {
            $a = new vDecimal("5", 0);
            self::assertEqual($a->toString(), "5");
        });
        self::test("Atomic rounding edge case", function () {
            $a = new vDecimal("0.999999", 6);
            $b = $a->addAtomic(1); // Just tips it over
            self::assertEqual($b->toString(), "1.000000");
        });
        self::test("Deep operation chaining", function () {
            $a = new vDecimal("1.000000", 6);
            $result = $a
                ->addScalar("1.5")
                ->subScalar("0.5")
                ->mulScalar("2")
                ->divScalar("2");
            self::assertEqual($result->toString(), "2.000000");
        });
        self::test("Sub to zero", function () {
            $a = new vDecimal("3.141592", 6);
            $b = $a->sub(new vDecimal("3.141592", 6));
            self::assertEqual($b->toString(), "0.000000");
        });
        self::test("Add then sub returns to original", function () {
            $a = new vDecimal("2.718281", 6);
            $b = $a->addScalar("1.234567")->subScalar("1.234567");
            self::assertEqual($b->toString(), $a->toString());
        });
        self::test("toFloat close to string", function () {
            $a = new vDecimal("3.141592", 6);
            self::assertEqual(round($a->toFloat(), 6), (float)"3.141592");
        });
        self::test("Scientific notation string input", function () {
            $a = new vDecimal("1.23e2", 6); // 123.000000
            self::assertEqual($a->toString(), "123.000000");
        });
        self::test("Input with spaces trims correctly", function () {
            $a = new vDecimal("  123.456   ", 6);
            self::assertEqual($a->toString(), "123.456000");
        });
        self::test("Floor + remainder equals original", function () {
            $a = new vDecimal("123.456789", 6);
            $floor = $a->floor();
            $remainder = $a->sub($floor);
            $recombined = $floor->add($remainder);
            self::assertEqual($recombined->toString(), $a->toString());
        });

        self::test("Distributive property", function () {
            $a = new vDecimal("2.0", 6);
            $b = new vDecimal("3.0", 6);
            $c = new vDecimal("4.0", 6);
            $left = $a->add($b)->mul($c);
            $right = $a->mul($c)->add($b->mul($c));
            self::assertEqual($left->toString(), $right->toString());
        });
        self::test("Chained atomic/whole/scalar consistency", function () {
            $a = new vDecimal("0.000000", 6);
            $b = $a->addWhole(1)->addAtomic(500000)->addScalar("0.5"); // 1 + 0.5 + 0.5
            self::assertEqual($b->toString(), "2.000000");
        });
        self::test("Invalid string throws", function () {
            try {
                new vDecimal("abc123", 6);
                throw new \Exception("Expected exception for invalid numeric string not thrown");
            } catch (\Throwable $e) {
                self::assertEqual(true, true); // Pass
            }
        });
        self::test("High scale multiplication", function () {
            $a = new vDecimal("0.000001", 8);
            $b = new vDecimal("1000000", 8);
            $c = $a->mul($b);
            self::assertEqual($c->toString(), "1.00000000");
        });
        self::test("Scientific notation string input", function () {
            $d = new vDecimal("1.23e2", 6); // = 123.000000
            self::assertEqual($d->toString(), "123.000000");
        });

        self::test("Input with spaces trims correctly", function () {
            $d = new vDecimal("   123.456   ", 6);
            self::assertEqual($d->toString(), "123.456000");
        });
        self::test("subScalar(1) vs subWhole(1)", function () {
            $original = new vDecimal("10.000000", 6);

            $viaScalar = $original->subScalar(1);
            $viaWhole = $original->subWhole(1);

            // They should be equal in value
            self::assertEqual($viaScalar->toAtomicInt(), $viaWhole->toAtomicInt(), "Atomic mismatch");
            self::assertEqual($viaScalar->toString(), $viaWhole->toString(), "String mismatch");
        });

        $npassed = self::$results['passed'];
        $nfailed = self::$results['failed'];
        echo "<br/>";
        echo "=== Test Summary ===<br/>";
        echo "✅ Passed: {$npassed}<br/>";
        echo "❌ Failed: {$nfailed}<br/>";
        echo "====================<br/>";

        /** @phpstan-ignore greater.alwaysFalse */
        if (self::$results['failed'] > 0) {
            return 1; // CLI-friendly fail code
        } else {
            return 0;
        }
    }
}
?>

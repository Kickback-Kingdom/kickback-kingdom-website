<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vDecimal implements \JsonSerializable
{
    private int $value;     // Integer representation of the fixed-point value
    private int $scale;     // Number of decimal places (i.e., precision)

    public function __construct(int|float|string $input, int $scale = 8)
    {
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

}
?>
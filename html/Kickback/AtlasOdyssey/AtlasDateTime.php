<?php
declare(strict_types=1);

namespace Kickback\AtlasOdyssey;

class AtlasDateTime
{
    private $atcEpoch;  // Epoch start date for the Atlas Star System
    private $currentUtc;  // Tracks the current UTC time
    private $elapsedSinceEpoch;  // Stores the total seconds since epoch

    const SECONDS_PER_PULSE = 404;
    const PULSES_PER_SPIN = 50000;
    const SPINS_PER_CYCLE = 4;

    const SECONDS_PER_SPIN = self::PULSES_PER_SPIN * self::SECONDS_PER_PULSE;
    const SECONDS_PER_CYCLE = self::SPINS_PER_CYCLE * self::SECONDS_PER_SPIN;

    public function __construct()
    {
        // Start from a modern date (2024-01-01) and adjust to reach 356 BCE.
        $this->atcEpoch = new \DateTime('2024-01-01 00:00:00', new \DateTimeZone('UTC'));
        
        // Subtract the years and days to reach Alexander's birthdate (July 20, 356 BCE)
        $this->atcEpoch->modify('-2380 years');  // 2024 + 356 years
        $this->atcEpoch->modify('-5 months');    // From January 1 to July 20
        $this->atcEpoch->modify('-11 days');     // Final adjustment to reach July 20

        // Sync the current UTC time to track elapsed time
        $this->syncWithUTC();
    }

    // Syncs the current UTC time and calculates elapsed time since the epoch
    public function syncWithUTC()
    {
        $this->currentUtc = new \DateTime('now', new \DateTimeZone('UTC'));

        // Calculate the difference between the current time and the epoch in seconds
        $this->elapsedSinceEpoch = $this->currentUtc->getTimestamp() - $this->atcEpoch->getTimestamp();
    }

    // Get the ATC time in total seconds since epoch
    public function getATCTimeInSeconds(): int
    {
        return $this->elapsedSinceEpoch;
    }

    /**
     * Get ATC formatted as "Pulse/Spin/Cycle"
     */
    public function getFormattedATCDate(): string
    {
        $atc = $this->getATCData();
        return sprintf("%d/%d/%d", $atc['pulse'], $atc['spin'], $atc['cycle']);
    }

    /**
     * Get ATC formatted as "Pulse.Progress/Spin/Cycle"
     */
    public function getFormattedATCDateTime(): string
    {
        $atc = $this->getATCData();

        return sprintf("%.6f/%d/%d", 
            $atc['pulse'] + $atc['pulseProgress'], 
            $atc['spin'], 
            $atc['cycle']
        );
    }

    // Calculate the elapsed Stellar Cycles, Star Rotations (Spins), and Pulses
    private function getATCData(): array
    {
        // Elapsed Stellar Cycles
        $stellarCycleIndex = floor($this->elapsedSinceEpoch / self::SECONDS_PER_CYCLE);
        $stellarCycleNum = $stellarCycleIndex + 1;

        // Elapsed Star Rotations (Spins) within the current Stellar Cycle
        $remainingTimeInStellarCycle = $this->elapsedSinceEpoch % self::SECONDS_PER_CYCLE;
        $starRotationIndex = floor($remainingTimeInStellarCycle / self::SECONDS_PER_SPIN);
        $starRotationNum = $starRotationIndex + 1;
        // Elapsed Pulses within the current Star Rotation (Spin)
        $remainingTimeInStarRotation = $remainingTimeInStellarCycle % self::SECONDS_PER_SPIN;
        $pulseIndex = floor($remainingTimeInStarRotation / self::SECONDS_PER_PULSE);
        $pulseNum = $pulseIndex + 1;

        $pulseProgress = fmod($remainingTimeInStarRotation, self::SECONDS_PER_PULSE) / self::SECONDS_PER_PULSE;


        return [
            'pulse' => $pulseNum,
            'spin' => $starRotationNum,
            'cycle' => $stellarCycleNum,
            'pulseProgress' => $pulseProgress,
        ];
    }

    public function getATCTimestamp(): float
    {
        return $this->elapsedSinceEpoch / self::SECONDS_PER_PULSE;
    }


    /**
     * Convert an Earth UTC timestamp to ATC time.
     * 
     * @param string $earthUtcTimestamp A UTC datetime string (e.g., '2024-01-01 00:00:00')
     * @return float The ATC timestamp equivalent
     */
    public function convertUTCToATC(string $earthUtcTimestamp): float
    {
        $earthUtc = new \DateTime($earthUtcTimestamp, new \DateTimeZone('UTC'));
        
        // Prevent negative time errors
        if ($earthUtc < $this->atcEpoch) {
            throw new \Exception("Date must be after ATC Epoch (356 BCE)");
        }

        //$elapsedSinceEpoch = $earthUtc->getTimestamp() - $this->atcEpoch->getTimestamp();
        $elapsedSinceEpoch = (int)$earthUtc->format('U') - (int)$this->atcEpoch->format('U');

        return $elapsedSinceEpoch / self::SECONDS_PER_PULSE;
    }


    /**
     * Convert an ATC timestamp to an Earth UTC datetime.
     * 
     * @param float $atcTimestamp The ATC timestamp
     * @return string The corresponding Earth UTC datetime
     */
    public function convertATCToUTC(float $atcTimestamp, string $format = 'Y-m-d H:i:s'): string
    {
        $elapsedSeconds = $atcTimestamp * self::SECONDS_PER_PULSE;

        // ✅ Use a new UTC DateTime instance
        $earthUtc = new \DateTime('@' . ($this->atcEpoch->format('U') + $elapsedSeconds), new \DateTimeZone('UTC'));

        return $earthUtc->format($format);
    }


    /**
     * Run test conversions between Earth and ATC time.
     */
    public function runConversionTests()
    {
        $testDates = [
            '2024-01-01 00:00:00',
            '2000-01-01 00:00:00',
            '1990-06-15 12:00:00',
            '1969-07-20 20:17:40',
            '0356-07-20 00:00:00',
            (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
        ];
    
        echo "<h2>Atlas DateTime Tests</h2>";
        echo "<table border='1' cellspacing='0' cellpadding='5'>";
        echo "<tr><th>Earth UTC</th><th>ATC Timestamp</th><th>Converted Back to UTC</th></tr>";
    
        foreach ($testDates as $earthDate) {
            $atcTime = $this->convertUTCToATC($earthDate);
            $convertedBack = $this->convertATCToUTC($atcTime);
    
            echo "<tr>
                    <td>$earthDate</td>
                    <td>$atcTime</td>
                    <td>$convertedBack</td>
                  </tr>";
        }
        echo "</table>";
    }
    

}

?>

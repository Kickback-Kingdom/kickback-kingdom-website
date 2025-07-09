<?php

declare(strict_types=1);

namespace Kickback\AtlasOdyssey\Emberwood;

use Kickback\AtlasOdyssey\AtlasDateTime;

class EmberwoodTradingCargoship 
{
    private $deliveryIntervalInSeconds;  // Interval between deliveries (in seconds)
    private AtlasDateTime $atlasDateTime;              // AtlasDateTime object for time tracking
    private string $carrier = 'ETC';
    private string $startLocation = 'OSK';
    private string $endLocation = 'OSK';
    private string $customerCode = 'KBK';

    public function __construct(int $intervalInWeeks = 2)
    {
        // Set delivery interval (2 weeks in seconds)
        $this->deliveryIntervalInSeconds = $intervalInWeeks * 7 * 24 * 60 * 60; 
        
        // Initialize AtlasDateTime to manage ATC time
        $this->atlasDateTime = new AtlasDateTime();
    }

    // Get the time left until the next delivery in a simplified ATC format
    public function getTimeUntilNextDeliveryInATC(): string
    {
        $currentAtcTime = $this->atlasDateTime->getATCTimeInSeconds(); // Current ATC time in seconds
        $timeElapsedInCurrentShipment = $currentAtcTime % $this->deliveryIntervalInSeconds; // Time passed in the current shipment
        $timeRemaining = $this->deliveryIntervalInSeconds - $timeElapsedInCurrentShipment;

        // Calculate the largest time unit remaining
        if ($timeRemaining >= 24 * 3600) {
            $days = floor($timeRemaining / (24 * 3600));
            return "{$days} day" . ($days > 1 ? "s" : "");
        } elseif ($timeRemaining >= 3600) {
            $hours = floor($timeRemaining / 3600);
            return "{$hours} hour" . ($hours > 1 ? "s" : "");
        } elseif ($timeRemaining >= 60) {
            $minutes = floor($timeRemaining / 60);
            return "{$minutes} minute" . ($minutes > 1 ? "s" : "");
        } else {
            return "{$timeRemaining} second" . ($timeRemaining > 1 ? "s" : "");
        }
    }

    // Calculate the percentage of the journey completed for the current shipment
    public function getJourneyPercentage(): float
    {
        $currentAtcTime = $this->atlasDateTime->getATCTimeInSeconds(); // Current ATC time in seconds
        $timeElapsedInCurrentShipment = $currentAtcTime % $this->deliveryIntervalInSeconds; // Time passed in the current shipment
        $percentageComplete = ($timeElapsedInCurrentShipment / $this->deliveryIntervalInSeconds) * 100;

        return min(max($percentageComplete, 0.0), 100.0);  // Cap between 0% and 100%
    }

    // Determine the current shipment number based on the time elapsed
    public function getShipmentNumber(): int
    {
        $currentAtcTime = $this->atlasDateTime->getATCTimeInSeconds();
        return intdiv($currentAtcTime, $this->deliveryIntervalInSeconds) + 1 - 62125 + 54;  // Shipment number (starting from 1)
    }

    public function getTrackingNumber(): string
    {
        // Convert numeric customerId to a 3-character alphanumeric code
        $customerPrefix = $this->customerCode;

        // Shipment number padded to ensure fixed length
        $shipmentNumber = str_pad((string)$this->getShipmentNumber(), 4, '0', STR_PAD_LEFT);
        
        
        $baseNumber = sprintf("%s-%s%s%d", $this->carrier, $customerPrefix, $this->endLocation, $shipmentNumber);

        // Calculate check digit using mod 10
        $checkDigit = self::calculateCheckDigit($baseNumber);

        return $baseNumber . $checkDigit;
    }

    public static function parseTrackingNumber(string $trackingNumber): array
    {
        // New regex pattern based on provided structure
        $pattern = '/^([^\-]*)-([A-Z0-9]{3})([A-Z0-9]{3})([0-9]*)(\d)$/';

        if (preg_match($pattern, $trackingNumber, $matches)) {
            return [
                'carrier' => $matches[1],
                'customerPrefix' => $matches[2],
                'location' => $matches[3],
                'shipmentNumber' => (int)$matches[4],
                'checkDigit' => (int)$matches[5]
            ];
        } else {
            throw new \InvalidArgumentException("Invalid tracking number format.");
        }
    }

    public static function calculateCheckDigit(string $base): int
    {
        $sum = 0;
        foreach (str_split($base) as $char) {
            $sum += ord($char); // Sum ASCII values of each character
        }
        return $sum % 10; // Modulus 10 of the sum
    }
    
    public static function isValidTrackingNumber(string $trackingNumber): bool
    {
        $base = substr($trackingNumber, 0, -1); // All characters except last
        $providedCheckDigit = substr($trackingNumber, -1); // Last character
        return self::calculateCheckDigit($base) == $providedCheckDigit;
    }

    public function getCurrentATCDate(): string
    {
        return $this->atlasDateTime->getFormattedATCDate();
    }
    public function getCurrentATCDateTime(): string
    {
        return $this->atlasDateTime->getFormattedATCDateTime();
    }
    public function getCurrentATCTimestamp(): float
    {
        return $this->atlasDateTime->getATCTimestamp();
    }

    public function getLocation(): string
    {
        $currentPOI = $this->getCurrentPOI();
        return $currentPOI->value; // This will return the string value of the current POI
    }

    public function getShipStatusWithDetails(): ShipStatus
    {
        $currentPOI = $this->getCurrentPOI();
        $statuses = $currentPOI->getStatuses();

        // Seed based on shipment number for consistency per shipment
        srand(crc32((string)$this->getShipmentNumber()));
        shuffle($statuses);

        $statusChangeInterval = 60 * 30;
        $timeElapsed = $this->atlasDateTime->getATCTimeInSeconds() % $this->deliveryIntervalInSeconds;
        $timeWithinLocation = $timeElapsed % (int) round($this->deliveryIntervalInSeconds / count($statuses));
        $statusIndex = (int) round($timeWithinLocation / $statusChangeInterval) % count($statuses);

        $selectedStatus = $statuses[$statusIndex];

        // Define color and icon based on status type
        $typeColorsAndIcons = [
            'combat' => ['color' => 'danger', 'icon' => 'fas fa-shield-alt'],
            'science' => ['color' => 'warning', 'icon' => 'fas fa-flask'],
            'normal' => ['color' => 'success', 'icon' => 'fas fa-info-circle']
        ];

        $statusType = $selectedStatus['type'];
        $statusColor = $typeColorsAndIcons[$statusType]['color'] ?? 'gray';
        $statusIcon = $typeColorsAndIcons[$statusType]['icon'] ?? 'fas fa-info-circle';

        return new ShipStatus(
            $selectedStatus['status'],
            $statusIcon,
            $statusColor,
        );
    }

    



    // Helper to get current POI based on journey percentage
    private function getCurrentPOI(): EmberwoodPOI
    {
        $journeyPercentage = $this->getJourneyPercentage();
        $poiArray = EmberwoodPOI::cases();
        $locationIndex = (int) floor($journeyPercentage / (100 / count($poiArray)));

        return $poiArray[min($locationIndex, count($poiArray) - 1)];
    }
}

?>

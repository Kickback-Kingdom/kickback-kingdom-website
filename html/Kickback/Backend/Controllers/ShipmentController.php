<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Controllers\ItemController;
use Kickback\AtlasOdyssey\Emberwood\EmberwoodTradingCargoship;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\ShipmentManifestItem;
use Kickback\Services\Database;
use Exception;

class ShipmentController
{
    public static function getShipmentItemPoolOptions() : Response {
        
        $conn = Database::getConnection();
        
        $sql = "SELECT i.* FROM kickbackdb.v_item_info i 
                left join loot l on i.Id = l.item_id
                where (`type` in (3,5)
                or (`type` = 4 and l.Id is null)) and (i.Id not in (15, 16, 17, 18))
                group by i.Id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = ItemController::row_to_vItem($row);
        }

        $stmt->close();

        return new Response(true, "Shipment manifest retrieved successfully", $items);
    }
    
    public static function createShipmentManifest(string $trackingNumber): Response
    {
        try {
            $conn = Database::getConnection();
            $conn->begin_transaction();

            // Get items from ShipmentOrderPool with their probabilities and max count
            $sql = "SELECT item_id, probability, max_count FROM shipment_order_pool";
            $result = $conn->query($sql);

            $manifestItems = [];
            while ($row = $result->fetch_assoc()) {
                // Determine the number of each item to add to the manifest
                $count = self::calculateItemCount($row['probability'], $row['max_count']);
                if ($count > 0) {
                    $manifestItems[] = ['item_id' => $row['item_id'], 'count' => $count];
                }
            }

            $shipmentManifestItem = new ShipmentManifestItem($trackingNumber);

            // Insert items into the shipmentmanifest table
            $insertedRows = 0;
            foreach ($manifestItems as $item) {
                $stmt = $conn->prepare("
                    INSERT INTO shipment_manifest (ctime, crand, tracking_number, item_id, count)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sissi", $ctime, $crand, $trackingNumber, $item['item_id'], $item['count']);
                $stmt->execute();
                $insertedRows += $stmt->affected_rows;
                $stmt->close();
            }

            $conn->commit();
            return new Response(true, "Shipment manifest created successfully", [
                'tracking_number' => $trackingNumber,
                'items' => $manifestItems,
                'insertedRows' => $insertedRows
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            return new Response(false, "Failed to create shipment manifest: " . $e->getMessage());
        }
    }
    
    public static function getShipmentManifest(string $trackingNumber): Response
    {
        $conn = Database::getConnection();
        
        $sql = "SELECT item_id, count FROM shipment_manifest WHERE tracking_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $trackingNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = LootController::row_to_vItemStack($row);
        }

        $stmt->close();

        if (empty($items)) {
            return new Response(false, "No items found for tracking number $trackingNumber.");
        }

        return new Response(true, "Shipment manifest retrieved successfully", $items);
    }

    public static function validateTrackingNumber(string $trackingNumber): Response
    {
        try {
            $parsed = EmberwoodTradingCargoship::parseTrackingNumber($trackingNumber);
            $isValid = EmberwoodTradingCargoship::isValidTrackingNumber($trackingNumber);

            if ($isValid) {
                return new Response(true, "Tracking number is valid", $parsed);
            } else {
                return new Response(false, "Tracking number is invalid");
            }
        } catch (Exception $e) {
            return new Response(false, "Error validating tracking number: " . $e->getMessage());
        }
    }
    
    private static function calculateItemCount(float $probability, int $maxCount): int
    {
        $count = 0;
        for ($i = 0; $i < $maxCount; $i++) {
            if (mt_rand(0, 100) / 100 <= $probability) {
                $count++;
            }
        }
        return $count;
    }
}

<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vPrestigeReview;
use Kickback\Views\vRecordId;

class PrestigeController
{
    public static function getPrestigeReviewsByAccountTo(vRecordId $recordId) : Response
    {
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_prestige_info WHERE account_id_to = ?");
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            $prestigeReviews = [];
            while ($row = $result->fetch_assoc()) {
                $prestigeReview = self::row_to_vPrestigeReview($row);
                $prestigeReviews[] = $prestigeReview;
            }

            return (new Response(true, "Account Prestige Reviews", $prestigeReviews));
        } else {
            return (new Response(false, "An error occurred.", []));
        }
    }
    private static function row_to_vPrestigeReview($row) : vPrestigeReview
    {
        $prestigeReview = new vPrestigeReview();


        $account = new vAccount('', $row["account_id_from"]);
        $account->username = $row["Username"];
        if ($row['account_from_avatar_media'] != null)
        {
            $avatar = new vMedia();
            $avatar->setMediaPath($row['account_from_avatar_media']);
            $account->avatar = $avatar;
        }

        return $account;
    }
}
?>

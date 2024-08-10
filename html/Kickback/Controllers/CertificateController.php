<?php
declare(strict_types=1);

namespace Kickback\Controllers;


class CertificateController
{
    function GetAllCertifications() {
        $sql = "SELECT * FROM v_certifications ORDER BY `name` DESC";  // Adjust ordering as needed

        $result = mysqli_query($GLOBALS["conn"], $sql);

        $num_rows = mysqli_num_rows($result); // This line is redundant since you are not using $num_rows in this function
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new Kickback\Models\Response(true, "Available Certifications",  $rows));
    }

}
?>
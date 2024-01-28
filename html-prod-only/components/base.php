<?php 


$session = require("api/v1/engine/session/verifySession.php"); 

if (IsLoggedIn())
{
    $_SESSION["account"] = GetAccountById($_SESSION["account"]["Id"])->Data;
    $chestsResp = GetMyChests($_SESSION["account"]["Id"]);
    $chests = $chestsResp->Data;
    
    $chestsJSON = json_encode($chests);
}
else{
    $chestsJSON = "[]";
}

unset($thisQuest);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require("components/head.php");?>
</head>

<body class="box-layout rtl js-container-confetti" style="overflow-x: hidden;">
    <!-- tap on top starts-->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <!-- tap on tap ends-->
    <!-- page-wrapper Start-->
    <div class="page-wrapper horizontal-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <?php require("components/header.php"); ?>
        <!-- Page Header Ends                              -->
        <!-- Page Body Start-->
        <div class="page-body-wrapper horizontal-menu">
            <!-- Page Sidebar Start-->

            <?php require("components/sidebar.php"); ?>
            <!-- Page Sidebar Ends-->
            <div class="page-body">

                <div class="modal fade animated tada" id="myModal" tabindex="-1" role="dialog" onclick="ToggleChest();"
                    style="overflow: hidden;">
                    <div class="modal-dialog" role="document" style="padding-top:25vh;">
                        <div>
                            <div class="modal-body">
                                <div class="card" style="background: transparent;">
                                    <div class="animate-widget">
                                        <div><img id="imgShineBackground" class="img-fluid fa-spin"
                                                src=""
                                                style="    -khtml-user-select: none;    -o-user-select: none;    -moz-user-select: none;    -webkit-user-select: none;    user-select: none;    position: absolute;    left: 0;    right: 0;    top: 0;    bottom: 0;    z-index: -1;    " />
                                            <img id="imgChest" class="img-fluid" src=""
                                                style="-khtml-user-select: none;    -o-user-select: none;    -moz-user-select: none;    -webkit-user-select: none;    user-select: none;" />
                                            <img id="imgItem" class="img-fluid"
                                                src=""
                                                style="-khtml-user-select: none;-o-user-select: none;-moz-user-select: none;-webkit-user-select: none;user-select: none;position: absolute;margin: auto;top: 0;bottom: 0;left: 0;right: 0;left: 0;z-index: 1;width: 250px;height: 250px;">
                                            <img id="imgShineForeground" class="img-fluid fa-spin" src=""
                                                style="    -khtml-user-select: none;    -o-user-select: none;    -moz-user-select: none;    -webkit-user-select: none;    user-select: none;    position: absolute;    left: 0;    right: 0;    top: 0;    bottom: 0;    width: 400px;    height: 400px;    margin: auto;" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--<?php require("components/page-title.php"); ?>-->
                <!-- Container-fluid starts-->
                <?php require("components/page-router.php"); ?>

                <!-- Container-fluid Ends-->
            </div>
            <!-- footer start-->
            <?php require("components/footer.php"); ?>
        </div>
    </div>
    <?php require("components/javascript.php"); ?>


    <script>

        var chests = <?php echo $chestsJSON; ?>;

    </script>
</body>

</html>
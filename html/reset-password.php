<?php
//ini_set('display_errors', 0);
//ini_set('display_startup_errors', 0);
//error_reporting(E_ALL);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$redirectUrl = 'index.php';

if (isset($_GET["redirect"]))
{
    $redirectUrl = urldecode($_GET["redirect"]);
}

$hasError = false;
$errorMessage = "";



$accountResp = GetAccountById($_GET["i"]);
$code = $_GET["c"];

if ($accountResp->Success)
{
    $account = $accountResp->Data;
    if ($account["pass_reset"] != $code)
    {
        $hasError = true;
        $errorMessage = 'Link is invalid.';

    }
    else
    {

        if (isset($_POST["submit"]))
        {
            $resp = UpdateAccountPassword($account["Id"], $code, $_POST["password"]);
            if ($resp->Success)
            {

                header("Location: login.php");
            }
            else
            {

                $hasError = true;
                $errorMessage = $resp->Message;
            }
        }
    }
    
}
else
{
    $hasError = true;
    $errorMessage = $accountResp->Message;
}


?>
<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
<?php require("php-components/base-page-loading-overlay.php"); ?>
<!-- Modal -->
<div class="modal fade" id="modalLogin" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <img class="img-fluid mx-auto" src="/assets/images/logo-kk.png" style="width:50%;" />
                </div>
                <div class="modal-body">

                                    <?php if ($hasError) {?>
					<div class="alert alert-danger alert-dismissible fade show" role="alert">
										<strong>Oh snap!</strong> <?php echo $errorMessage; ?>
									</div>
									<?php } ?>

                                    
              <?php if ($hasSuccess) {?>
					<div class="alert alert-success alert-dismissible fade show" role="alert">
										<strong>Success!</strong> <?php echo $errorMessage; ?>
									</div>
									<?php } ?>
                    <div class="mb-3">
                        <label for="inputEmail" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" id="inputEmail">
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary" href="<?php echo $urlPrefixBeta."/".$redirectUrl; ?>">Back</a>
                    <input type="submit" name="submit" class="btn btn-primary" value="Change Password">
                </div>
            </div>
        </div>
    </div>
</div>

    
    <?php require("php-components/base-page-javascript.php"); ?>

    <script>
        
        $(document).ready(function () {

            $("#modalLogin").modal("show");
        });
    </script>

<?php require("php-components/base-page-loading-overlay-javascript.php"); ?>

</body>

</html>

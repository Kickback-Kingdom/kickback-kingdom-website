<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

\Kickback\Common\Version::$show_version_popup = false;

$hasError = false;
$resp = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/account/logout.php");
$redirectUrl = 'index.php';

if (isset($_GET["redirect"]))
{
    $redirectUrl = urldecode($_GET["redirect"]);
}
if (isset($_POST["submit"]))
{
    $_POST["serviceKey"] = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");
    $resp = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/account/login.php");
    $hasError = !$resp->success;
    if (!$hasError)
    {
        
        header("Location: ".$redirectUrl);
    }
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
										<strong>Oh snap!</strong> <?php echo $resp->message; ?>
									</div>
									<?php } ?>
                    <div class="mb-3">
                        <label for="inputEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" name="email" id="inputEmail">
                    </div>
                    <div class="mb-3">
                        <label for="inputPwd" class="form-label" style="width:100%;">Password<a class="float-end" href="<?php echo Version::urlBetaPrefix(); ?>/forgot-password.php">Forgot password?</a></label>
                        <input type="password" class="form-control" name="pwd" id="inputPwd">
                    </div>
                    
                
                    <p>Don't have account? <a href="register.php<?php if (isset($_GET["redirect"])) 
                    { 
                    echo '?redirect='.urlencode($_GET["redirect"]);
                    if (isset($_GET['wq']))
                    {
                        echo "&wq=".urlencode($_GET['wq']);
                    }
                    }?>">Create Account</a>
                    </p>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary" href="<?php echo Version::urlBetaPrefix()."/".$redirectUrl; ?>">Back</a>
                    <input type="submit" name="submit" class="btn btn-primary" value="Login">
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

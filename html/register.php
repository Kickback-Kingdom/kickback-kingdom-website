<?php

$session = require($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$hasError = false;
$errorMessage = '';
$firstName = '';
$lastName = '';
$password = '';
$password2 = '';
$username = '';
$email = '';
$i_agree = false;

if (isset($_POST['firstName']))
$firstName = $_POST['firstName'];

if (isset($_POST['lastName']))
$lastName = $_POST['lastName'];

if (isset($_POST['pwd']))
$password = $_POST['pwd'];

if (isset($_POST['pwd_confirm']))
$password2 = $_POST['pwd_confirm'];

if (isset($_POST['username']))
$username = $_POST['username'];

if (isset($_POST['email']))
$email = $_POST['email'];

$i_agree = isset($_POST['i_agree_to_the_terms']);

if (isset($_POST["submit"]))
{

  //$resp = RegisterAccount($firstName,$lastName ,$password,$password2,$username, $refUsername ,$email,$i_agree);
  $resp = require_once($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/account/register.php");
  $hasError = !$resp->Success;
  if ($hasError)
  {
    $errorMessage = $resp->Message." (Data: ".$resp->Data.")";
  }
  else{
    require_once($_SERVER['DOCUMENT_ROOT']."/service-credentials-ini.php");
    $kk_credentials = LoadServiceCredentialsOnce();
    $kk_service_key = $kk_credentials["kk_service_key"];
    unset($kk_credentials);

    $_POST["serviceKey"] = $kk_service_key;
    $resp = require_once($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/account/login.php");
    $hasError = !$resp->Success;
    if (!$hasError)
    {
        $url = 'index.php';

        if (isset($_GET["redirect"]))
        {
          $url = urldecode($_GET["redirect"]);
        }
        header("Location: ".$url);
    }
    else
    {
      
    $errorMessage = $resp->Message;
    }
  }
}
$showGuard = false;
$writProvided =  false;
$guardImg = 'halt';

if (isset($_GET['wi']))
{
    $writ_of_passage_id = ($_GET['wi']);
    $writProvided = true;
}

if (isset($_GET['wq']))
{
    require_once($_SERVER['DOCUMENT_ROOT']."/service-credentials-ini.php");
    $kk_credentials = LoadServiceCredentialsOnce();
    $kk_crypt_key_quest_id = $kk_credentials["crypt_key_quest_id"];
    unset($kk_credentials);

    $writ_of_passage_quest = ($_GET['wq']);
    $writProvided = true;
    require_once($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/engine.php");
    $crypt = new IDCrypt($kk_crypt_key_quest_id);
    $wq = $crypt->decrypt($writ_of_passage_quest);
    $questResp = GetQuestById($wq);
    if ($questResp->Success)
    {
        $quest = $questResp->Data;
        /*$writResp = GetWritOfPassageByAccountId($quest['host_id']);
        if ($writResp->Success)
        {
            $writ = $writResp->Data;
            $writId = $writ['next_item_id'];
            $encrypted = $crypt->encrypt($writId);
            //$decrypted = $crypt->decrypt($encrypted); // 123
            $writ_of_passage = $encrypted;
        }
        else
        {
            $hasError = true;
            //$errorMessage = json_encode($writResp);
            $errorMessage = $writResp->Message;
            $guardImg = 'halt-writ';
            $showGuard = true;
        }*/
    }
    else
    {
        $hasError = true;
        $errorMessage = "Something isn't right, we couldn't find the quest associated with your Writ of Passage. Please try again.";
        $guardImg = 'halt-writ';
        $showGuard = true;

    }
}

if (!$writProvided)
{

    $showGuard = true;
    $hasError = true;
    $errorMessage = "Where is your Writ of Passage?";
    $guardImg = 'halt';
}
?>


<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
<?php require("php-components/base-page-loading-overlay.php"); ?>
<!-- Modal -->
<?php
if ($showGuard)
{
?>

<div class="modal fade" id="modalRegister" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <img class="img-fluid mx-auto" src="/assets/images/logo-kk.png" style="width:50%;" />
                </div>
                <div class="modal-body">
                    <img src="/assets/media/context/halt.jpeg" class="img-fluid"/>
                    <p style="padding: 32px;font-size: 1.3em;text-align: left;">Halt! <?php echo $errorMessage; ?></p>
                    <small class="float-end" style="font-size: 1em;"> - Gate Gaurd</small>
                </div>
                <div class="modal-footer">
                <?php if (isset($_GET["redirect"])) { ?>
                    <a class="btn btn-secondary" href="<?php echo urldecode($_GET["redirect"]);?>">Go Back</a>
                    <?php } else { ?>
                    <a type="button" class="btn btn-secondary" href="<?php echo $urlPrefixBeta; ?>/">Back</a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
}
else
{

?>
<form method="POST">
    <div class="modal fade" id="modalRegister" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <input type="hidden" name="passage_quest" value="<?php echo $writ_of_passage_quest; ?>">
            <input type="hidden" name="passage_id" value="<?php echo $writ_of_passage_id; ?>">
            <input type="hidden" name="i_agree_to_the_terms" value="checked">
                            
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
                    <div class="row">
                        <div class="col-12 col-lg-4">
                                    <img src="/assets/media/<?php echo htmlspecialchars($quest['imagePath']);?>"  class="img-fluid img-thumbnail">
                                    <h6><?php echo $quest['name']; ?></h6>
                                    <small class="text-body-secondary float-end">Hosted by <a href="<?php echo $urlPrefixBeta; ?>/u/<?php echo urlencode(htmlspecialchars($quest["host_name"])); ?>" class="username"><?php echo htmlspecialchars($quest["host_name"]); ?></a>
                                    <?php if ($quest['host_name_2'] != null) { ?> and <a href="<?php echo $urlPrefixBeta; ?>/u/<?php echo urlencode(htmlspecialchars($quest['host_name_2'])); ?>" class="username"><?php echo htmlspecialchars($quest['host_name_2']);?></a><?php } ?>
                                    </small>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="inputFirstName" class="form-label">First Name</label>
                                    <input class="form-control" id="inputFirstName" name="firstName" type="text" required=""  value="<?php echo $firstName; ?>" />
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="inputLastName" class="form-label">Last Name</label>
                                    <input class="form-control" id="inputLastName" name="lastName" type="text" required=""  value="<?php echo $lastName; ?>" />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="inputUsername" class="form-label" style="width:100%;">Username</label>
                                    <input type="text" class="form-control" name="username" id="inputUsername" required="" value="<?php echo $username; ?>" >
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="inputEmail" class="form-label">Email address</label>
                                    <input type="email" class="form-control" name="email" id="inputEmail" required="" placeholder="name@example.com" value="<?php echo $email; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="inputPwd" class="form-label" style="width:100%;">Password</label>
                                    <input type="password" class="form-control" name="pwd" id="inputPwd" required=""  value="<?php echo $password; ?>" >
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="inputPwdConfirm" class="form-label" style="width:100%;">Confirm Password</label>
                                    <input type="password" class="form-control" name="pwd_confirm" id="inputPwdConfirm" required="" value="<?php echo $password2; ?>">
                                </div>
                            </div>
                            
                            <p>Already have account? <a href="<?php echo $urlPrefixBeta; ?>/login.php<?php if (isset($_GET["redirect"])) 
                            { 
                            echo '?redirect='.urlencode($_GET["redirect"]);
                            if (isset($_GET['wq']))
                            {
                                echo "&wq=".urlencode($_GET['wq']);
                            }
                            }?>">Login</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary" href="<?php echo $urlPrefixBeta; ?>/">Back</a>
                    <a href="#" class="btn btn-primary"  data-bs-target="#exampleModalToggle2" data-bs-toggle="modal">Register</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="exampleModalToggle2" aria-hidden="true" aria-labelledby="exampleModalToggleLabel2" tabindex="-1"  data-bs-backdrop="static" data-bs-keyboard="false" >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalToggleLabel2">Terms of Service</h1>
                    <button type="button" class="btn-close" data-bs-target="#modalRegister" data-bs-toggle="modal" ></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y:scroll;">
                    <?php 
                        require("php-components/tos.php");
                    ?>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-secondary" data-bs-target="#modalRegister" data-bs-toggle="modal" href="#">Back</a>
                    <input type="submit" name="submit" class="btn btn-primary" value="I Accept, Register my Account" />
                </div>
            </div>
        </div>
    </div>
</form>
<?php
}
?>
    
    <?php require("php-components/base-page-javascript.php"); ?>

    <script>
        
        $(document).ready(function () {

            $("#modalRegister").modal("show");
        });
    </script>
<?php require("php-components/base-page-loading-overlay-javascript.php"); ?>

</body>

</html>

<?php
//ini_set('display_errors', 0);
//ini_set('display_startup_errors', 0);
//error_reporting(E_ALL);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Kickback\Backend\Controllers\AccountController;

\Kickback\Common\Version::$show_version_popup = false;

$redirectUrl = 'index.php';

if (isset($_GET["redirect"]))
{
    $redirectUrl = urldecode($_GET["redirect"]);
}

?>
<?php
  
  $hasError = false;
  $errorMessage = "";
  if (isset($_POST["submit"]))
  {
      $email = $_POST["email"];
      $emailResp = AccountController::getAccountByEmail($email);
      if ($emailResp->success)
      {
         try {
          $account = $emailResp->data;
          $codeResp = AccountController::prepareAccountPasswordResetCode($account);
          if ($codeResp->success)
          {
              $code = $codeResp->data;
              //Create an instance; passing `true` enables exceptions
              $mail = new PHPMailer(true);
              try {
                  $kk_credentials = \Kickback\Backend\Config\ServiceCredentials::instance();

                  $mail->IsSMTP(); // telling the class to use SMTP
                  //$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
                                     // 1 = errors and messages
                                     // 2 = messages only

                  $mail->SMTPAuth   = filter_var($kk_credentials["smtp_auth"], FILTER_VALIDATE_BOOLEAN);
                  $mail->SMTPSecure = $kk_credentials["smtp_secure"];
                  $mail->Host       = $kk_credentials["smtp_host"];
                  $mail->Port       = intval($kk_credentials["smtp_port"]);
                  $mail->Username   = $kk_credentials["smtp_username"];
                  $mail->Password   = $kk_credentials["smtp_password"];

                  //Recipients
                  $mail->setFrom($kk_credentials["smtp_from_email"],$kk_credentials["smtp_from_name"]);
                  $mail->addAddress($email,$account->firstName." ".$account->lastName); 
                  $mail->addReplyTo($kk_credentials["smtp_replyto_email"],$kk_credentials["smtp_replyto_name"]);

                  unset($kk_credentials);

                  //Content
                  $mail->isHTML(true);                                  //Set email format to HTML
                  $mail->Subject = 'Kickback Kingdom Account Recovery';

                  $html = '<!doctype html>
                  <html lang="en-UK">
                  
                  <head>
                      <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
                      <title>Reset Password</title>
                      <meta name="description" content="Reset Password Email">
                      <style type="text/css">
                          a:hover {text-decoration: underline !important;}
                      </style>
                  </head>
                  
                  <body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
                      <!--100% body table-->
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8" style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: \'Open Sans\', sans-serif;">
                          <tr>
                              <td>
                                  <table style="background-color: #f2f3f8; max-width:670px;  margin:0 auto;" width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                                      <tr>
                                          <td style="height:80px;">&nbsp;</td>
                                      </tr>
                                      <tr>
                                          <td style="text-align:center; padding:0 50px;">
                                              <a href="https://kickback-kingdom.com" title="logo" target="_blank">
                                                  <img src="https://kickback-kingdom.com/assets/images/logo-kk.png"  title="logo" alt="logo">
                                              </a>
                                          </td>
                                      </tr>
                                      <tr>
                                          <td style="height:20px;">&nbsp;</td>
                                      </tr>
                                      <tr>
                                          <td>
                                              <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0" style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06); margin-bottom: 100px;">
                                                  <tr>
                                                      <td style="height:40px;">&nbsp;</td>
                                                  </tr>
                                                  <tr>
                                                      <td style="padding:0 35px;">
                                                          <h1 style="color:#1e1e2d; font-weight:500; margin:0;font-size:32px;font-family:\'Rubik\',sans-serif; text-align:center">You have requested to reset your password</h1>
                                                          <span style="display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100%;"></span>
                                                          <p style="color:#455056; font-size:15px;line-height:24px; margin:0; text-align:left">
                                                              A unique link to reset your password has been generated for you. To reset your password, click the following link and follow the instructions.
                                                          </p>
                                                          <a href="https://kickback-kingdom.com'. Version::urlBetaPrefix() . '/reset-password.php?c='.$code.'&i='.$account->crand.'" style="background:#08B9ED;text-decoration:none !important; font-weight:500; margin-top:35px; color:#fff;text-transform:uppercase; font-size:14px;padding:10px 24px;display:inline-block;border-radius:50px;">Reset
                                                                                          Password</a>
                                                      </td>
                                                  </tr>
                                                  <tr>
                                                      <td style="height:40px;">&nbsp;</td>
                                                  </tr>
                                              </table>
                                          </td>
                                      </tr>
                                      
                                  </table>
                              </td>
                              </tr>
                  
                      </table>
                      <!--/100% body table-->
                  </body>
                  
                  </html>';
                  
                  $mail->Body = $html;
                  $mail->AltBody = 'A unique link to reset your password has been generated for you. To reset your password, click the following link and follow the instructions. https://kickback-kingdom.com/reset-password.php?c='.$code.'&i='.$account->crand;

                  $mail->send();
                  $hasError = false;
                  $hasSuccess = true;
                  $errorMessage = "Check your email for a link to reset your password!";
              } 
              catch (Exception $e1) 
              {
                  
              $hasError = true;
              $errorMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo} {$e1}";
              }
          }
          else
          {
              // TODO: Is this supposed to be \Exception or PHPMailer\PHPMailer\Exception?
              // Because right now it's PHPMailer\PHPMailer\Exception...
              throw new Exception($codeResp->message);
          }
          
          } 
          catch (Exception $e2)
          {
              
              $hasError = true;
              $errorMessage = "Message could not be sent. Mailer Error: {$e2}";
          }
          
      }
      else
      {
          $hasError = true;
          $errorMessage = $emailResp->message;
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
										<strong>Oh snap!</strong> <?php echo $errorMessage; ?>
									</div>
									<?php } ?>

                                    
              <?php if ($hasSuccess) {?>
					<div class="alert alert-success alert-dismissible fade show" role="alert">
										<strong>Success!</strong> <?php echo $errorMessage; ?>
									</div>
									<?php } ?>
                    <div class="mb-3">
                        <label for="inputEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" name="email" id="inputEmail">
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary" href="<?php echo Version::urlBetaPrefix()."/".$redirectUrl; ?>">Back</a>
                    <input type="submit" name="submit" class="btn btn-primary" value="Send Recovery Email">
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

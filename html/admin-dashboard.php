<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\FeedController;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Backend\Controllers\MerchantGuildController;

if (!Kickback\Services\Session::isAdmin())
{
    header('Location: index.php');
    exit();
}


$targetDate = date("Y-m", strtotime("+1 month")) . "-01";
$merchantGuildTasks = MerchantGuildController::PullMerchantGuildProcessingTasks()->data;

$merchantGuildTasksToProcess = null;
$currentStatementTiedToTask = null;

foreach ($merchantGuildTasks as $merchantGuildTask) {
    if (!$merchantGuildTask->processed) {
        $merchantGuildTasksToProcess = $merchantGuildTask;
        break;
    }
}

if ($merchantGuildTasksToProcess != null)
{
    if ($merchantGuildTasksToProcess->TaskType == 0)
    {
            $currentStatementTiedToTask = MerchantGuildController::BuildStatement($merchantGuildTasksToProcess->sharePurchase->account, $merchantGuildTasksToProcess->sharePurchase->PurchaseDate, false);



        $preProcessData = MerchantGuildController::PreProcessPurchase($merchantGuildTasksToProcess, $currentStatementTiedToTask);
    }
}

$reviewFeedResp = FeedController::getNeedsReviewedFeed();
$reviewFeed = $reviewFeedResp->data;
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    <style>
        
        </style>
        <?php if (isset($preProcessData)) { ?>
<!--taskProcessingModal MODAL-->
<div class="modal fade" id="taskProcessingModal"  tabindex="-1"  aria-labelledby="taskProcessingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center flex-fill">
                    <h5 class="modal-title" id="taskProcessingModalLabel">Process Task</h5>        
                </div>
            </div>
            <div class="modal-body">
        
                <div style="font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e0e0e0; max-width: 500px; margin: 0 auto; background-color: #f9f9f9;">
    
                    <!-- Owned Shares -->
                    <div style="margin-bottom: 20px;">
                        <strong style="display: block; margin-bottom: 10px;">Last Statement:</strong>
                        <ul style="list-style-type: none; padding-left: 0;">
                            <li style="margin-bottom: 5px;">Date: <span id="fullSharesOwned"><?= $preProcessData->lastStatementDate->formattedDetailed; ?></span></li>
                        </ul>
                    </div>

                    <!-- Owned Shares -->
                    <div style="margin-bottom: 20px;">
                        <strong style="display: block; margin-bottom: 10px;">Current Statement:</strong>
                        <ul style="list-style-type: none; padding-left: 0;">
                            <li style="margin-bottom: 5px;">JSON: <span id="fullSharesOwned"><?= $preProcessData->currentStatementJSON; ?></span></li>
                        </ul>
                    </div>

                    <!-- Owned Shares -->
                    <div style="margin-bottom: 20px;">
                        <strong style="display: block; margin-bottom: 10px;">Pre-Owned Shares:</strong>
                        <ul style="list-style-type: none; padding-left: 0;">
                            <li style="margin-bottom: 5px;">Full: <span id="fullSharesOwned"><?= $preProcessData->preOwnedFullShares; ?></span></li>
                            <li>Partial: <span id="partialSharesOwned"><?= $preProcessData->preOwnedPartialShares; ?></span></li>
                        </ul>
                    </div>
                    
                    <!-- Purchased Shares -->
                    <div style="margin-bottom: 20px;">
                        <strong style="display: block; margin-bottom: 10px;">Newly Purchased Shares:</strong>
                        <ul style="list-style-type: none; padding-left: 0;">
                            <li style="margin-bottom: 5px;">Full: <span id="fullSharesPurchased"><?= $preProcessData->fullSharesPurchased; ?></span></li>
                            <li>Partial: <span id="partialSharesPurchased"><?= $preProcessData->partialSharesPurchased; ?></span></li>
                        </ul>
                    </div>

                    <!-- Financial Summary -->
                    <div style="margin-bottom: 20px;">
                        <strong>Amount Spent:</strong> <span id="amountSpent"><?= $merchantGuildTasksToProcess->sharePurchase->Amount; ?></span> <span id="currencyUsed"><?=  $merchantGuildTasksToProcess->sharePurchase->Currency; ?></span>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <strong>ADA Value:</strong> <span id="adaConversionValue"><?= $merchantGuildTasksToProcess->sharePurchase->ADAValue; ?> ADA</span>
                    </div>

                    <!-- Share Summary -->
                    <div style="margin-bottom: 20px;">
                        <strong style="display: block; margin-bottom: 10px;">Share Summary:</strong>
                        <ul style="list-style-type: none; padding-left: 0;">
                            <li style="margin-bottom: 5px;">Newly Completed Full Shares: <span id="fullSharesCompleted"><?= $preProcessData->completedShares; ?></span></li>
                            <li style="margin-bottom: 5px;">Remaining Partial Shares: <span id="remainingPartialShares"><?= $preProcessData->remainingPartialShares; ?></span></li>
                            <li>Certificates To Be Issued: <span id="shareCertificatesToBeGiven"><?= $preProcessData->shareCertificatesToBeGiven; ?></span></li>
                        </ul>
                    </div>
                </div>

            </div> 
            <div class="modal-footer">
                <button type="button" class="btn btn-primary"  data-bs-dismiss="modal" >Cancel</button>
                <form method="POST">
                    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                    <input type="hidden" name="purchase_id" value="<?= $merchantGuildTasksToProcess->sharePurchase->crand; ?>"/>
                    <input type="submit" name="process-purchase" class="btn bg-ranked-1" id="processButton" value="Process">
                </form>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!--statementProcessingModal-->
<div class="modal fade" id="statementProcessingModal"  tabindex="-1"  aria-labelledby="statementProcessingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center flex-fill">
                    <h5 class="modal-title" id="statementProcessingModalLabel">Process Monthly Statement</h5>        
                </div>
            </div>
            <?php
                // Assuming $merchantGuildTasksToProcess["statement_date"] is in the format 'YYYY-MM-DD'
                $statement_date = $merchantGuildTasksToProcess->statement_date->value;
                $period_start_date = clone $statement_date;
                $period_start_date->modify('-1 month');
            ?>

            <div class="modal-body">
                Would you like to process the statement for the period of 
                <strong><?php echo $period_start_date->format('F, Y'); ?></strong>
                and statement date of 
                <strong><?php echo $statement_date->format('Y-m-d'); ?></strong>?
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary"  data-bs-dismiss="modal" >Cancel</button>
                <form method="POST">
                    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
                    <input type="hidden" name="statement-date" value="<?=  $merchantGuildTasksToProcess->statement_date->dbValue; ?>"/>
                    <input type="submit" name="process-statements" class="btn bg-ranked-1"  value="Process">
                </form>
            </div>
        </div>
    </div>
</div>

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
    
    <?php if ($hasError || $hasSuccess) {?>
        <div class="row">
            <div class="col-12">
                <?php if ($hasError) {?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Oh snap!</strong> <?php echo $errorMessage; ?>
                </div>
                <?php } ?>
                <?php if ($hasSuccess) {?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Congrats!</strong> <?php echo $successMessage; ?>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "Admin Dashboard";
                require("php-components/base-page-breadcrumbs.php"); 
                ?>
                <div class="row">
                    <div class="col-12">
                        
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="nav-link active" id="nav-review-tab" data-bs-toggle="tab" data-bs-target="#nav-review" type="button" role="tab" aria-controls="nav-review" aria-selected="true"><i class="fa-solid fa-magnifying-glass"></i></button>
                                <button class="nav-link" id="nav-merchant-processing-tab" data-bs-toggle="tab" data-bs-target="#nav-merchant-processing" type="button" role="tab" aria-controls="nav-merchant-processing" aria-selected="true"><i class="fa-solid fa-globe"></i></button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade active show" id="nav-review" role="tabpanel" aria-labelledby="nav-review-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Community Review</div>

                                <?php 

                                    for ($i=0; $i < count($reviewFeed); $i++) 
                                    { 
                                        $feedCard = $reviewFeed[$i];
                                        
                                        $_vFeedCard = FeedCardController::vFeedRecord_to_vFeedCard($feedCard);
                                        require("php-components/vFeedCardRenderer.php");
                                    }
                                ?>
                            </div>
                            <div class="tab-pane fade" id="nav-merchant-processing" role="tabpanel" aria-labelledby="nav-merchant-processing-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Merchant Guild Processing</div>

                                <table class="table">
                                    <thead>
                                        <tr>
                                            <!-- Add table headers based on the columns you're expecting -->
                                            <th>Account</th>
                                            <th>Shares</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>ADA Value</th>
                                            <th>P-Id</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $firstItem = true;
                                        foreach ($merchantGuildTasks as $merchantGuildTask): 
                                            if ($merchantGuildTask->TaskType == 0) {
                                        ?>
                                            <tr>
                                                <td><img class="img-fluid img-thumbnail" style="width:64px;margin-right: 10px;" src="<?= $merchantGuildTask->sharePurchase->account->getProfilePictureURL(); ?>"/><?= $merchantGuildTask->sharePurchase->account->getAccountElement(); ?></td>
                                                <td><?= $merchantGuildTask->sharePurchase->SharesPurchased; ?></td>
                                                <td><?= $merchantGuildTask->sharePurchase->PurchaseDate->formattedYmd; ?></td>
                                                <td><?= $merchantGuildTask->sharePurchase->Amount." ".$merchantGuildTask->sharePurchase->Currency; ?></td>
                                                <td><?= $merchantGuildTask->sharePurchase->ADAValue; ?></td>
                                                <td><?= $merchantGuildTask->sharePurchase->crand; ?></td>
                                                <td><?php 
                                                
                                                if ($firstItem)
                                                {
                                                    ?>
                                                        <button type="button" class="btn bg-ranked-1" onclick="OpenMerchantGuildTaskProcessingModal(<?= $merchantGuildTask->sharePurchase->crand; ?>,'<?= $merchantGuildTask->sharePurchase->PurchaseDate->dbValue; ?>',0)">Process</button>
                                                    <?php
                                                }
                                                else{
                                                    echo "Waiting...";
                                                }

                                                ?></td>
                                            </tr>
                                        <?php 
                                            } else {
                                                ?>
                                            <tr>
                                                <td class="text-bg-primary">Monthly Statement</td>
                                                <td class="text-bg-primary"></td>
                                                <td class="text-bg-primary"><?= $merchantGuildTask->statement_date->dbValue; ?></td>
                                                <td class="text-bg-primary"></td>
                                                <td class="text-bg-primary"></td>
                                                <td class="text-bg-primary"></td>
                                                <td class="text-bg-primary"><?php 
                                                
                                                if ($firstItem)
                                                {
                                                    ?>
                                                        <button type="button" class="btn bg-ranked-1" onclick="OpenMerchantGuildStatementProcessingModal()">Process</button>
                                                    <?php
                                                }
                                                else{
                                                    echo "Waiting...";
                                                }

                                                ?></td>
                                            </tr>

                                                <?php
                                            }
                                    $firstItem = false;
                                    endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>
    
    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
        function OpenMerchantGuildTaskProcessingModal(pid, exec_date, type) {
            $("#taskProcessingModal").modal("show");
        }
        function OpenMerchantGuildStatementProcessingModal() {
            $("#statementProcessingModal").modal("show");
        }
    </script>
</body>

</html>

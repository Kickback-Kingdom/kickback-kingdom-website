<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$totalCombinedShares = 0;
$totalMembers = 0;
$minRequiredShares = 20;
if (Kickback\Services\Session::isLoggedIn())
{
    $accountId = Kickback\Services\Session::getCurrentAccount()->crand;
    $targetDate = date("Y-m", strtotime("+1 month")) . "-01";
    $interestDate = date("Y-m", strtotime("-1 month")) . "-01";
    $currentStatement = BuildStatement($accountId, $targetDate);
    $totalCombinedShares = $currentStatement["fractional_shares"]+$currentStatement["total_shares"];
    $totalSharesOwned = 0;
    if (IsMerchant())
    {
        $purchases = PullPurchasesUntilForAll($targetDate);
        $shareholders = PullMerchantGuildShareHolders();

        
        foreach ($shareholders as $shareholder):
            $totalSharesOwned += $shareholder["shares"];
            if ($shareholder["shares"] >= $minRequiredShares)
            {
                $totalMembers++;
            }
        endforeach;
    }
    else
    {
        $purchases = PullPurchasesUntil($accountId, $targetDate);
    }
}

$percentUntilGuildMember = min(($totalCombinedShares/$minRequiredShares)*100, 100);


$milestones = [
    ['shares' => 20, 'reward' => 'Basic Reward', 'icon' => 'fa-trophy'],
    ['shares' => 40, 'reward' => 'Intermediate Reward', 'icon' => 'fa-gift'],
    ['shares' => 2000, 'reward' => 'Ultimate Reward', 'icon' => 'fa-star']
];



// Sample owned shares for demonstration
$ownedShares = $currentStatement["total_shares"];

// Total shares based on the milestones
$totalShares = end($milestones)['shares'];

// Calculate the fill height based on owned shares
$fillHeight = ($ownedShares / $totalShares) * 300; // assuming 300px is the full height of the progress bar

$progressBarHeight = 500; // adjust this to match your design if needed

// Calculate the position for each milestone
foreach ($milestones as &$milestone) {
    $milestone['position'] = (1 - ($milestone['shares'] / $totalShares)) * $progressBarHeight;
}
unset($milestone); // Unset the reference
$descriptionHeight = 300 / count($milestones); // Calculate the height of each description
$padlockOffset = $descriptionHeight / 2; 

?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>
<style>
    .merchant-reward-milestone {
    height: calc(300px / <?php echo count($milestones); ?>); /* Distribute the height evenly based on the number of milestones */
    /* ... other styles ... */
}

.merchant-reward-padlock {
    position: absolute;
    left: 50%;
    transform: translateX(-50%) translateY(-50%); /* Adjust the vertical position of padlocks for center alignment */
    /* ... other styles ... */
}

    </style>
<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "Merchant's Guild";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                
                <?php if (IsMerchant()) { ?>
                <div class="row">
                    <div class="col-12">
                        
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="nav-link active" id="nav-guild-overview-tab" data-bs-toggle="tab" data-bs-target="#nav-guild-overview" type="button" role="tab" aria-controls="nav-guild-overview" aria-selected="true"><i class="fa-solid fa-globe"></i></button>
                                <button class="nav-link" id="nav-purchase-history-tab" data-bs-toggle="tab" data-bs-target="#nav-purchase-history" type="button" role="tab" aria-controls="nav-purchase-history" aria-selected="true"><i class="fa-solid fa-receipt"></i></button>
                                <button class="nav-link" id="nav-cash-flow-tab" data-bs-toggle="tab" data-bs-target="#nav-cash-flow" type="button" role="tab" aria-controls="nav-cash-flow" aria-selected="true"><i class="fa-solid fa-money-bill-transfer"></i></button>
                                <button class="nav-link" id="nav-rewards-tab" data-bs-toggle="tab" data-bs-target="#nav-rewards" type="button" role="tab" aria-controls="nav-rewards" aria-selected="true"><i class="fa-solid fa-gift"></i></button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade active show" id="nav-guild-overview" role="tabpanel" aria-labelledby="nav-guild-overview-tab" tabindex="0">
                                

                                <div class="display-6 tab-pane-title">Kingdom Financial Overview</div>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">

                                            <!-- Monthly Expenses -->
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-money-bill-wave fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Monthly Expenses</div>
                                                        <div class="h5 mb-0">~$200</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Monthly Income -->
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-money-bill-wave fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Monthly Income</div>
                                                        <div class="h5 mb-0">$0</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            

                                            <!-- Monthly NET -->
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-wallet fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Monthly Net</div>
                                                        <div class="h5 mb-0">~-$200</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                                <div class="display-6 tab-pane-title mt-4">Kingdom Treasuries Overview</div>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">

                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-crown fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Kingdom Treasury</div>
                                                        <div class="h5 mb-0">~$500</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-store fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Merchants' Guild Treasury</div>
                                                        <div class="h5 mb-0">$0</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-hiking fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Adventurers' Guild Treasury</div>
                                                        <div class="h5 mb-0">$0</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-book fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Apprentices' Guild Treasury</div>
                                                        <div class="h5 mb-0">$0</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-tools fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Craftsmens Guild Treasury</div>
                                                        <div class="h5 mb-0">$0</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-chess-rook fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Stewards' Guild Treasury</div>
                                                        <div class="h5 mb-0">$0</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div class="display-6 tab-pane-title mt-4">Merchants' Guild Overview</div>
                                
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-handshake fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Total Shares Owned</div>
                                                        <div class="h5 mb-0"><?php echo $totalSharesOwned; ?> Shares</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-users fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Guild Members</div>
                                                        <div class="h5 mb-0"><?php echo $totalMembers; ?></div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-users fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Shareholders</div>
                                                        <div class="h5 mb-0"><?php echo count($shareholders); ?></div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-coins fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Last Months Payout</div>
                                                        <div class="h5 mb-0">0 ADA</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-coins fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">This Months Payout (Estimated)</div>
                                                        <div class="h5 mb-0">0 ADA</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-percentage fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Shares Interest Rate</div>
                                                        <div class="h5 mb-0">0.5% Monthly</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-ticket fa-2x me-3"></i>
                                                    <div>
                                                        <div class="text-muted">Min Shares for Membership</div>
                                                        <div class="h5 mb-0"><?php echo $minRequiredShares; ?> Shares</div> <!-- Replace with dynamic value -->
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="display-6 tab-pane-title mt-4">Shareholders</div>
                                <div class="card">
                                    <div class="card-body">
                                        <table id="datatable-members" class="display">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th>Shares</th>
                                                    <th>Guild Member</th>
                                                    <th>Ownership %</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($shareholders as $shareholder): ?>
                                                    <tr>
                                                        <td><img class="img-fluid img-thumbnail" style="width:64px;margin-right: 10px;" src="/assets/media/<?php echo GetAccountProfilePicture($shareholder); ?>"/><a href="<?php echo Version::urlBetaPrefix(); ?>/u/<?php echo $shareholder['Username']; ?>" class="username"><?php echo $shareholder['Username']; ?></a></td>
                                                        <td><?php echo $shareholder['shares']; ?></td>
                                                        <td><?php echo ($shareholder['shares'] >= $minRequiredShares ? "YES":"NO") ?></td>
                                                        <td><?php echo round(($shareholder['shares']/$totalSharesOwned)*10000)/100; ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="nav-purchase-history" role="tabpanel" aria-labelledby="nav-purchase-history-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Purchase History</div>
                                
                                <table id="datatable-purchases" class="display">
                                    <thead>
                                        <tr>
                                            <!-- Add table headers based on the columns you're expecting -->
                                            <th>Account</th>
                                            <th>Shares</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>ADA Value</th>
                                            <th>P-Id</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($purchases as $purchase): ?>
                                            <tr>
                                                <td><img class="img-fluid img-thumbnail" style="width:64px;margin-right: 10px;" src="/assets/media/<?php echo GetAccountProfilePicture($purchase); ?>"/><a href="<?php echo Version::urlBetaPrefix(); ?>/u/<?php echo $purchase['Username']; ?>" class="username"><?php echo $purchase['Username']; ?></a></td>
                                                <td><?php echo $purchase['SharesPurchased']; ?></td>
                                                <td><?php echo $purchase['PurchaseDate']; ?></td>
                                                <td><?php echo $purchase['Amount']." ".$purchase['Currency']; ?></td>
                                                <td><?php echo $purchase['ADAValue']; ?></td>
                                                <td><?php echo $purchase['purchase_id']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="nav-cash-flow" role="tabpanel" aria-labelledby="nav-cash-flow-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Cash Flow In</div>
                                
                                <table id="datatable-cash-flow-in" class="display">
                                    <thead>
                                        <tr>
                                            <!-- Add table headers based on the columns you're expecting -->
                                            <th>Category</th>
                                            <th>Name</th>
                                            <th>Amount</th>
                                            <th>Frequency</th>
                                            <th>ADA Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>

                                <div class="display-6 tab-pane-title mt-4">Cash Flow Out</div>
                                <table id="datatable-cash-flow-out" class="display">
                                    <thead>
                                        <tr>
                                            <!-- Add table headers based on the columns you're expecting -->
                                            <th>Category</th>
                                            <th>Provider</th>
                                            <th>Name</th>
                                            <th>Amount</th>
                                            <th>Frequency</th>
                                            <th>ADA Value</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Server</td>
                                            <td>AWS</td>
                                            <td>kickback-kingdom.com</td>
                                            <td>$20 USD</td>
                                            <td>Monthly</td>
                                            <td>77 ADA</td>
                                            <td>Charges on the 2nd or 3rd of every month</td>
                                        </tr>
                                        <tr>
                                            <td>Server</td>
                                            <td>AWS</td>
                                            <td>apprentices-guild.com</td>
                                            <td>$10 USD</td>
                                            <td>Monthly</td>
                                            <td>39 ADA</td>
                                            <td>Charges on the 2nd or 3rd of every month</td>
                                        </tr>
                                        <tr>
                                            <td>SSL</td>
                                            <td>namecheap.com</td>
                                            <td>kickback-kingdom.com</td>
                                            <td></td>
                                            <td>Yearly</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>SSL</td>
                                            <td>namecheap.com</td>
                                            <td>apprentices-guild.com</td>
                                            <td></td>
                                            <td>Yearly</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>Server</td>
                                            <td>GPORTAL</td>
                                            <td>Project Zomboid</td>
                                            <td>$8 USD</td>
                                            <td>Monthly</td>
                                            <td>31 ADA</td>
                                            <td>Charges on the 4th of every month</td>
                                        </tr>
                                        <tr>
                                            <td>Email</td>
                                            <td>Google</td>
                                            <td>horsemen@kickback-kingdom.com</td>
                                            <td>$20 USD</td>
                                            <td>Monthly</td>
                                            <td>77 ADA</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="nav-rewards" role="tabpanel" aria-labelledby="nav-rewards-tab" tabindex="0">
                                <div class="merchant-reward-stretch-goals">
                                    <div class="merchant-reward-header text-center bg-success text-white p-3">
                                        Merchant Rewards
                                    </div>
                                    <div class="row">
                                        <div class="col-2 d-flex flex-column align-items-center position-relative">
                                            <div class="merchant-reward-progress-container">
                                                <div class="merchant-reward-progress-fill" style="height: <?php echo 300 - $fillHeight; ?>px;"></div>
                                            </div>
                                            <?php foreach ($milestones as $index => $milestone): ?>
                                                <div class="merchant-reward-padlock" style="--padlock-position: <?php echo ($index * $descriptionHeight) + $padlockOffset; ?>px;">
                                                    <i class="fa <?php echo $milestone['icon']; ?>"></i>
                                                    <div><?php echo $milestone['shares']; ?> Shares</div>
                                                </div>
                                            <?php endforeach; ?>

                                        </div>
                                        <div class="col-10">
                                        <?php foreach ($milestones as $milestone): ?>

                                            <div class="merchant-reward-milestone bg-light p-3 mb-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div><?php echo $milestone['reward']; ?></div>
                                                    <div class="merchant-reward-exclusive-tag bg-warning text-white">KICKSTARTER EXCLUSIVE</div>
                                                </div>
                                                <div class="merchant-reward-character-details mt-2">
                                                    <!-- Placeholder for image -->
                                                    <img src="character-image-url" alt="Character Image" class="float-left mr-3">
                                                    <div class="merchant-reward-character-description">
                                                        <strong><?php echo $milestone['reward']; ?></strong>
                                                        <p>Description for <?php echo $milestone['reward']; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>




                            </div>
                        </div>
                    </div>
                </div>
                <?php } else { ?>
                    <div class="card mt-4 shadow-sm rounded">
                        <div class="card-body text-center py-4">
                            <!-- Centered and bigger Image of Merchant Guild Share -->
                            <div class="mb-4">
                                <img src="/assets/media/items/30.png" alt="Merchant Guild Share" style="width: 150px; height: 150px;">
                            </div>

                            <!-- Access Restricted Title with the Warning Icon -->
                            <h5><i class="fa-solid fa-exclamation-triangle fa-lg me-2 text-muted"></i> Access Restricted</h5>
                            <?php if (Kickback\Services\Session::isLoggedIn()) { ?>
                            <p class="mb-4">You need to own at least <?php echo $minRequiredShares; ?> full merchant shares to gain access to the Merchants' Guild.</p>

                            <!-- Display Partial Shares -->
                            <div class="my-3 font-weight-bold">
                                Shares Owned: <span><?php echo $totalCombinedShares; ?> / <?php echo $minRequiredShares; ?></span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress mb-2 rounded" role="progressbar" aria-label="Animated striped example" aria-valuenow="<?php echo floor($percentUntilGuildMember); ?>" aria-valuemin="0" aria-valuemax="100" style="height: 24px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" style="width: <?php echo floor($percentUntilGuildMember); ?>%;"><?php echo floor($percentUntilGuildMember); ?>% to Full Share</div>
                            </div>

                                                                
                            <section class="mt-5">
                                <h3>Purchase History</h3>
                                <table id="datatable-purchases" class="display">
                                <thead>
                                        <tr>
                                            <!-- Add table headers based on the columns you're expecting -->
                                            <th>Purchase Id</th>
                                            <th>Shares Purchased</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>ADA Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($purchases as $purchase): ?>
                                            <tr>
                                                <td><?php echo $purchase['purchase_id']; ?></td>
                                                <td><?php echo $purchase['SharesPurchased']; ?></td>
                                                <td><?php echo $purchase['PurchaseDate']; ?></td>
                                                <td><?php echo $purchase['Amount']." ".$purchase['Currency']; ?></td>
                                                <td><?php echo $purchase['ADAValue']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </section>
                            
                            <?php } else { ?>
                                <p class="mb-4">You must be logged in to gain access to the Merchants' Guild.</p>

                                <!-- Login Button -->
                                <a href="<?php echo Version::urlBetaPrefix(); ?>/login.php?redirect=merchants-guild.php" class="btn btn-primary">Log In</a>

                            <?php } ?>
                        </div>
                    </div>

                <?php } ?>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
        $(document).ready( function () {
            $('#datatable-members').DataTable({
                "order": [[1, 'desc']]  // Sort by the 5th column (0-indexed) in ascending order
            });
            $('#datatable-purchases').DataTable({
                "order": [[2, 'desc']]  // Sort by the 5th column (0-indexed) in ascending order
            });
            $('#datatable-cash-flow-out').DataTable({
                "order": [[5, 'desc']]  // Sort by the 5th column (0-indexed) in ascending order
            });
            $('#datatable-cash-flow-in').DataTable({
                "order": [[2, 'desc']]  // Sort by the 5th column (0-indexed) in ascending order
            });
        } );
    </script>
    
</body>

</html>

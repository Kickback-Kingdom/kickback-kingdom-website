<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");


$analyticsMonthlyResp = GetMonthlyGrowthStats();

$analyticsMonthly = $analyticsMonthlyResp->Data;

$analyticsJSON = json_encode($analyticsMonthly);
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

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
                
                
                $activePageName = "Analytics";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
                            <h5 class="card-header">Monthly Growth & Retention Analytics</h5>
                            <div class="card-body">
                                <table id="datatable-analtyics" class="table display">
                                    <thead>
                                        <tr>
                                        <th scope="col">Month</th>
                                        <th scope="col">New Accounts</th>
                                        <th scope="col">Total Accounts</th>
                                        <th scope="col">Growth %</th>
                                        <th scope="col">Active Accounts</th>
                                        <th scope="col">Retention %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($analyticsMonthly as $data) : 
                                            // Determine classes for each condition
                                            $growthClass = (float) number_format($data['growth_percentage'], 1, '.', '') >= 5.0 ? 'table-success' : 'table-danger';
                                            $retentionClass = (float) number_format($data['retention_rate'], 1, '.', '') >= 20 ? 'table-success' : 'table-danger';

                                            // Determine the row class based on the cell classes
                                            $rowClass = ($growthClass === 'table-danger' || $retentionClass === 'table-danger') ? 'table-danger' : 'table-success';
                                        ?>
                                            <tr class="<?= $rowClass; ?>">
                                                <td><?= htmlspecialchars($data['month']); ?></td>
                                                <td><?= htmlspecialchars($data['new_accounts']); ?></td>
                                                <td><?= htmlspecialchars($data['total_accounts']); ?></td>
                                                <td class="<?= $growthClass; ?>">
                                                    <?= htmlspecialchars(number_format($data['growth_percentage'], 1, '.', '')); ?>%
                                                </td>
                                                <td><?= htmlspecialchars($data['active_accounts']); ?></td>
                                                <td class="<?= $retentionClass; ?>">
                                                    <?= htmlspecialchars(number_format($data['retention_rate'], 1, '.', '')); ?>%
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="accordion" id="accordionPanelsStayOpenExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                                        Total Monthly Accounts
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        
                                        <canvas id="totalAccountsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                                        New Accounts Monthly
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        
                                        <canvas id="newAccountsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                        Monthly Growth Percentage
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        
                                        <canvas id="growthPercentageChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFour" aria-expanded="false" aria-controls="panelsStayOpen-collapseFour">
                                        Monthly Retention Rate
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseFour" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        
                                        <canvas id="retentionRateChart"></canvas>
                                    </div>
                                </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <script>
        $(document).ready( function () {
            $('#datatable-analtyics').DataTable({
                "order": [[0, 'desc']]  // Sort by the 5th column (0-indexed) in ascending order
            });
        } );

        const analyticsData = <?= $analyticsJSON; ?>;
        
        const labels = analyticsData.map(data => data.month);
        const totalAccounts = analyticsData.map(data => parseInt(data.total_accounts));
        const newAccounts = analyticsData.map(data => parseInt(data.new_accounts));
        // Mapping growth percentages and rounding to the nearest tenth
        const growthPercentages = analyticsData.map(data => parseFloat(data.growth_percentage).toFixed(1));

        // Mapping retention rates and rounding to the nearest tenth
        const retentionRates = analyticsData.map(data => parseFloat(data.retention_rate).toFixed(1));

        const totalAccountsChart = new Chart('totalAccountsChart', {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Accounts',
                    data: totalAccounts,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const newAccountsChart = new Chart('newAccountsChart', {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'New Accounts',
                    data: newAccounts,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const growthPercentageChart = new Chart('growthPercentageChart', {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Growth Percentage',
                    data: growthPercentages,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const retentionRateChart = new Chart('retentionRateChart', {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Retention Rate',
                    data: retentionRates,
                    borderColor: 'rgb(153, 102, 255)',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

</body>

</html>

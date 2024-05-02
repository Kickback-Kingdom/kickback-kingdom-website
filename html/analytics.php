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
                                                <td class="<?= $growthClass; ?>"><?= htmlspecialchars($data['new_accounts']); ?></td>
                                                <td class="<?= $growthClass; ?>"><?= htmlspecialchars($data['total_accounts']); ?></td>
                                                <td class="<?= $growthClass; ?>"><?= htmlspecialchars(number_format($data['growth_percentage'], 1, '.', '')); ?>%</td>
                                                <td class="<?= $retentionClass; ?>"><?= htmlspecialchars($data['active_accounts']); ?></td>
                                                <td class="<?= $retentionClass; ?>"><?= htmlspecialchars(number_format($data['retention_rate'], 1, '.', '')); ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="row mb-3 align-items-center">
                    <div class="col-md-4">
                        <label for="startDate">Start Date:</label>
                        <input type="month" id="startDate" name="startDate" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="endDate">End Date:</label>
                        <input type="month" id="endDate" name="endDate" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex justify-content-end">
                        <button class="btn btn-primary" onclick="updateCharts()">Update Graphs</button>
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
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFive" aria-expanded="false" aria-controls="panelsStayOpen-collapseFive">
                                        Monthly Active Accounts
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseFive" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        
                                        <canvas id="activeAccountsChart"></canvas>
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
        function updateCharts() {
            var startDate = document.getElementById('startDate').value;
            var endDate = document.getElementById('endDate').value;

        }

        $(document).ready( function () {
            $('#datatable-analtyics').DataTable({
                "order": [[0, 'desc']]  // Sort by the 5th column (0-indexed) in ascending order
            });
        } );

        document.addEventListener('DOMContentLoaded', function() {
            if (analyticsData.length > 0) {
                // Extract the month strings and find the minimum and maximum
                const months = analyticsData.map(data => data.month); // Assuming 'month' is already in 'YYYY-MM' format
                const minMonth = months.reduce((a, b) => a < b ? a : b); // Find the earliest month
                const maxMonth = months.reduce((a, b) => a > b ? a : b); // Optionally find the latest month, useful if data may not include the current month

                // Set the values of the month inputs
                document.getElementById('startDate').value = minMonth;
                document.getElementById('endDate').value = new Date().toISOString().substring(0, 7); // Current year and month
            }
        });



        const analyticsData = <?= $analyticsJSON; ?>;
        let fullData = analyticsData;

        function filterData(startDate, endDate) {
            return fullData.filter(item => {
                const itemDate = new Date(item.month); // Assuming 'month' is in a format that can be parsed by Date, e.g., "2021-03"
                return itemDate >= new Date(startDate) && itemDate <= new Date(endDate);
            });
        }

        function updateCharts() {
            var startDate = document.getElementById('startDate').value;
            var endDate = document.getElementById('endDate').value;

            // Filter the data
            let filteredData = filterData(startDate, endDate);

            // Now map the filtered data
            const labels = filteredData.map(data => data.month);
            const totalAccounts = filteredData.map(data => parseInt(data.total_accounts));
            const newAccounts = filteredData.map(data => parseInt(data.new_accounts));
            const growthPercentages = filteredData.map(data => parseFloat(data.growth_percentage).toFixed(1));
            const retentionRates = filteredData.map(data => parseFloat(data.retention_rate).toFixed(1));
            const activeAccounts = filteredData.map(data => parseInt(data.active_accounts));

            // Update the charts
            updateChartData(labels, totalAccounts, newAccounts, growthPercentages, retentionRates, activeAccounts);
        }

        function updateChartData(labels, totalAccounts, newAccounts, growthPercentages, retentionRates, activeAccounts) {
            totalAccountsChart.data.labels = labels;
            totalAccountsChart.data.datasets[0].data = totalAccounts;
            totalAccountsChart.update();

            newAccountsChart.data.labels = labels;
            newAccountsChart.data.datasets[0].data = newAccounts;
            newAccountsChart.update();

            growthPercentageChart.data.labels = labels;
            growthPercentageChart.data.datasets[0].data = growthPercentages;
            growthPercentageChart.update();

            retentionRateChart.data.labels = labels;
            retentionRateChart.data.datasets[0].data = retentionRates;
            retentionRateChart.update();

            activeAccountsChart.data.labels = labels;
            activeAccountsChart.data.datasets[0].data = activeAccounts;
            activeAccountsChart.update();
        }


        const labels = analyticsData.map(data => data.month);
        const totalAccounts = analyticsData.map(data => parseInt(data.total_accounts));
        const newAccounts = analyticsData.map(data => parseInt(data.new_accounts));
        const growthPercentages = analyticsData.map(data => parseFloat(data.growth_percentage).toFixed(1));
        const retentionRates = analyticsData.map(data => parseFloat(data.retention_rate).toFixed(1));
        const activeAccounts = analyticsData.map(data => parseInt(data.active_accounts));
        
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


        const activeAccountsChart = new Chart('activeAccountsChart', {
            type: 'line', // Line chart type
            data: {
                labels: labels, // Use the same labels array as other charts
                datasets: [{
                    label: 'Active Accounts',
                    data: activeAccounts, // Data array for active accounts
                    borderColor: 'rgb(255, 159, 64)', // Color of the line
                    tension: 0.1 // Smooths the line
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true // Ensures the y-axis starts at 0
                    }
                }
            }
        });

    </script>

</body>

</html>

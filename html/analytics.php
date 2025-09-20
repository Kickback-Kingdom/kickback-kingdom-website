<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\AnalyticController;

$analyticsMonthlyResp = AnalyticController::getMonthlyGrowthStats();

$analyticsMonthly = $analyticsMonthlyResp->data;

$analyticsJSON = json_encode($analyticsMonthly);

$mapDataResp = AnalyticController::getMapData();
$mapData = $mapDataResp->data;
$mapDataJSON = json_encode($mapData);

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
                                <div class="table-responsive">
                                    <table id="datatable-analtyics" class="dataTable no-footer nowrap table table-striped">
                                        <thead>
                                            <tr>
                                            <th scope="col">Month</th>
                                            <th scope="col">New Accounts</th>
                                            <th scope="col">Total Accounts</th>
                                            <th scope="col">Growth %</th>
                                            <th scope="col">Active Accounts</th>
                                            <th scope="col">Retention %</th>
                                            <th scope="col">Website Hits</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($analyticsMonthly as $data) : 
                                                // Determine classes for each condition
                                                $growthClass = (float) number_format($data['growth_percentage'], 1, '.', '') >= 5.0 ? 'table-success' : 'table-danger';
                                                $retentionClass = (float) number_format($data['retention_rate'], 1, '.', '') >= 60 ? 'table-success' : 'table-danger';

                                                // Determine the row class based on the cell classes
                                                $rowClass = ($growthClass === 'table-danger' || $retentionClass === 'table-danger') ? 'table-danger' : 'table-success';
                                            ?>
                                                <tr class="<?= $rowClass; ?>">
                                                    <td><?= htmlspecialchars($data['month']); ?></td>
                                                    <td class="<?= $growthClass; ?>"><?= htmlspecialchars($data['new_accounts']); ?></td>
                                                    <td class="<?= $growthClass; ?>"><?= htmlspecialchars($data['total_accounts']); ?></td>
                                                    <td class="<?= $growthClass; ?>"><?= htmlspecialchars(number_format($data['growth_percentage'], 1, '.', ',')); ?>%</td>
                                                    <td class="<?= $retentionClass; ?>"><?= htmlspecialchars($data['active_accounts']); ?></td>
                                                    <td class="<?= $retentionClass; ?>"><?= htmlspecialchars(number_format($data['retention_rate'], 1, '.', ',')); ?>%</td>
                                                    <td class="<?= $rowClass; ?>"><?= htmlspecialchars(number_format($data['website_hits'], 0, '.', ',')); ?></td>
                                                </tr>
                                            <?php endforeach; ?>

                                        </tbody>
                                    </table>
                                </div>
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
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSix" aria-expanded="false" aria-controls="panelsStayOpen-collapseSix">
                                        Monthly Website Hits
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseSix" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        
                                        <canvas id="websiteHitsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSeven" aria-expanded="false" aria-controls="panelsStayOpen-collapseSeven">
                                        Projected Growth
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseSeven" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        
                                        <canvas id="projectedGrowthChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row mt-5">
                    <div class="col-12">
                        <div id="container" style="height:500px;"></div>
                    </div>
                </div>

                <div class="row mt-5">
                    <div class="col-12">
                        <h5 class="text-center">Guildsmen Distribution by Country</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="countryTable">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Country</th>
                                        <th>Guildsmen</th>
                                        <th>Guildsmen %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>

    <script src="https://code.highcharts.com/maps/highmaps.js"></script>
    <script src="https://code.highcharts.com/maps/modules/data.js"></script>
    <script src="https://code.highcharts.com/maps/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/mapdata/custom/world-highres.js"></script>




    <script>

        $(document).ready( function () {
            $('#datatable-analtyics').DataTable({
                "order": [[0, 'desc']],
                //"responsive": true,
                //"scrollX": true,
            });
        } );

        document.addEventListener('DOMContentLoaded', function() {
            if (analyticsData.length > 0) {
                // Get today's date and calculate one year ago
                const today = new Date();
                const oneYearAgo = new Date();
                oneYearAgo.setFullYear(today.getFullYear() - 1);

                // Format the dates as 'YYYY-MM'
                const todayFormatted = today.toISOString().substring(0, 7);
                const oneYearAgoFormatted = oneYearAgo.toISOString().substring(0, 7);

                // Extract the month strings and find the minimum month in the dataset
                const months = analyticsData.map(data => data.month); // Assuming 'month' is in 'YYYY-MM' format
                const minMonth = months.reduce((a, b) => a < b ? a : b); // Find the earliest month

                // Set the startDate to one year ago or the minMonth, whichever is later
                const startDate = oneYearAgoFormatted > minMonth ? oneYearAgoFormatted : minMonth;

                // Set the values of the date inputs
                document.getElementById('startDate').value = startDate;
                document.getElementById('endDate').value = todayFormatted; // Current year and month

                updateCharts();
            }


            const mapData = <?= $mapDataJSON; ?>; // Map data from PHP


             // Load the countries.json file
    fetch('/assets/js/countries.json')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to load countries.json: ${response.status}`);
            }
            return response.json();
        })
        .then(countriesData => {
            const countries = countriesData.countries;
            const totalUserCount = mapData.reduce((sum, [, userCount]) => sum + userCount, 0);

            // Populate Bootstrap table
            const tableBody = document.querySelector('#countryTable tbody');
            const sortedData = mapData.sort((a, b) => b[1] - a[1]); // Sort by user count in descending order

            sortedData.forEach((item, index) => {
                const [countryCode, userCount] = item;

                // Skip countries with no users
                if (userCount === 0) return;

                const normalizedCountryCode = countryCode.toUpperCase();
                // Get the country name from the JSON data
                const countryName = Array.isArray(countries[normalizedCountryCode])
                    ? countries[normalizedCountryCode][0] // Use the first name if it's an array
                    : countries[normalizedCountryCode] || normalizedCountryCode;

                const percentage = ((userCount / totalUserCount) * 100).toFixed(2);

                // Create table row
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${countryName}</td>
                    <td>${userCount}</td>
                    <td>${percentage}%</td>
                `;
                tableBody.appendChild(row);
            });
        });

            Highcharts.mapChart('container', {
                chart: {
                    map: 'custom/world-highres'
                },
                title: {
                    text: 'Kickback Kingdom Guildsmen Locations'
                },
                subtitle: {
                    text: 'Discover the global reach of Kickback Kingdom'
                },
                mapNavigation: {
                    enabled: true,
                    buttonOptions: {
                        verticalAlign: 'bottom'
                    }
                },
                colorAxis: {
                    min: 0,
                    stops: [
                        [0, '#EFEFFF'],
                        [0.5, '#4444FF'],
                        [1, '#000022']
                    ]
                },
                series: [{
                    data: mapData,
                    name: 'User Count',
                    states: {
                        hover: {
                            color: '#FF9933'
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        format: '{point.name}'
                    }
                }]
            });
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
            const startDate = document.getElementById('startDate').value || analyticsData[0].month;
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
            const websiteHits = filteredData.map(data => parseInt(data.website_hits));


            // Projection logic
            const projectionMonths = 12;
            const minGrowthRate = 5; // Minimum monthly growth rate (percentage)

            // Calculate average growth rate (percentage) based on filtered data
            let totalGrowthRate = 0;
            for (let i = 1; i < filteredData.length; i++) {
                const previous = parseInt(filteredData[i - 1].total_accounts);
                const current = parseInt(filteredData[i].total_accounts);
                const growthRate = ((current - previous) / previous) * 100; // Calculate percentage growth
                totalGrowthRate += growthRate;
            }
            const averageGrowthRate = totalGrowthRate / (filteredData.length - 1); // Use filtered data length

            // Generate projection data starting from the selected `startDate`
            const historicalData = analyticsData.filter(data => data.month >= startDate).slice(0, -1);

            const projectedDataMin = [];
            const projectedDataMax = [];
    
            // Create projections
            let lastTotalAccountsMin = parseInt(historicalData[historicalData.length - 1].total_accounts); // For min growth
            let lastTotalAccountsMax = parseInt(historicalData[historicalData.length - 1].total_accounts); // For max growth

            for (let i = 0; i < projectionMonths; i++) {
                // Calculate growth for the current month
                const minGrowth = Math.ceil(lastTotalAccountsMin * (minGrowthRate / 100)); // 5% growth
                const maxGrowth = Math.round(lastTotalAccountsMax * (averageGrowthRate / 100)); // Average growth

                // Update separate variables for min and max growth projections
                lastTotalAccountsMin += minGrowth;
                lastTotalAccountsMax += maxGrowth;

                // Calculate projection month
                const projectionDate = new Date(historicalData[historicalData.length - 1].month);
                projectionDate.setMonth(projectionDate.getMonth() + i + 1);
                const projectionMonth = projectionDate.toISOString().substring(0, 7); // Format as 'YYYY-MM'

                // Add projections
                projectedDataMin.push(Math.round(lastTotalAccountsMin)); // Add min projection
                projectedDataMax.push(Math.round(lastTotalAccountsMax)); // Add max projection
            }


            const labelsProjection = historicalData.map(data => data.month).concat(
                [...Array(projectionMonths).keys()].map(i => {
                    const projectionDate = new Date(historicalData[historicalData.length - 1].month);
                    projectionDate.setMonth(projectionDate.getMonth() + i + 1);
                    return projectionDate.toISOString().substring(0, 7);
                })
            );
            


            const userCountsHistorical = historicalData.map(data => parseInt(data.total_accounts));
            const userCountsMinProjection = projectedDataMin;
            const userCountsMaxProjection = projectedDataMax;

            // Update the charts
            updateChartData(labels, totalAccounts, newAccounts, growthPercentages, retentionRates, activeAccounts, websiteHits, labelsProjection, userCountsHistorical, userCountsMinProjection, userCountsMaxProjection, averageGrowthRate);
        }

        function updateChartData(labels, totalAccounts, newAccounts, growthPercentages, retentionRates, activeAccounts, websiteHits, labelsProjection, userCountsHistorical, userCountsMinProjection, userCountsMaxProjection, averageGrowthRate) {
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

            websiteHitsChart.data.labels = labels;
            websiteHitsChart.data.datasets[0].data = websiteHits;
            websiteHitsChart.update();

            userGrowthProjectionChart.data.labels = labelsProjection;
            userGrowthProjectionChart.data.datasets[0].data = userCountsHistorical;
            userGrowthProjectionChart.data.datasets[1].data = [...userCountsHistorical, ...userCountsMinProjection];
            userGrowthProjectionChart.data.datasets[2].data = [...userCountsHistorical, ...userCountsMaxProjection];
            userGrowthProjectionChart.data.datasets[2].label = `Max Projection (${averageGrowthRate.toFixed(2)}% Average Growth)`; // Update subtitle  Max Projection (Average Growth)

            userGrowthProjectionChart.update();

        }
        

        const totalAccountsChart = new Chart('totalAccountsChart', {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Total Accounts',
                    data: [],
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
                labels: [],
                datasets: [{
                    label: 'New Accounts',
                    data: [],
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
                labels: [],
                datasets: [{
                    label: 'Growth Percentage',
                    data: [],
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
                labels: [],
                datasets: [{
                    label: 'Retention Rate',
                    data: [],
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
                labels: [], // Use the same labels array as other charts
                datasets: [{
                    label: 'Active Accounts',
                    data: [], // Data array for active accounts
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

        const websiteHitsChart = new Chart('websiteHitsChart', {
            type: 'line', // Line chart type
            data: {
                labels: [], // Use the same labels array as other charts
                datasets: [{
                    label: 'Website Hits',
                    data: [], // Data array for active accounts
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
        
        // Create Line Chart
        const userGrowthProjectionChart = new Chart('projectedGrowthChart', {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Historical Total Accounts',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        fill: false,
                    },
                    {
                        label: 'Min Projection (5% Monthly Growth)',
                        data: [],
                        borderColor: 'rgb(255, 99, 132)',
                        borderDash: [5, 5], // Dashed line for the projection
                        tension: 0.1,
                        fill: false,
                    },
                    {
                        label: 'Max Projection (Average Growth)',
                        data: [],
                        borderColor: 'rgb(54, 162, 235)',
                        borderDash: [5, 5], // Dashed line for the projection
                        tension: 0.1,
                        fill: '-1', // Fill the area between min and max
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    },
                ],
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Accounts',
                        },
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                        },
                    },
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Projected User Growth (Starting from Selected Month)',
                    },
                    subtitle: {
                        display: true,
                        text: 'Includes a range from minimum (5%) to average growth rates',
                    },
                },
            },
        });


    </script>

</body>

</html>

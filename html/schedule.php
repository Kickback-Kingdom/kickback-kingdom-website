<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\ScheduleController;

$month = date('m');  // current month
$year = date('Y');   // current year
$events = ScheduleController::getCalendarEvents($month, $year);
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
                $activePageName = "Schedule";
                require("php-components/base-page-breadcrumbs.php"); 
                ?>
                <div class="card card-body bg-primary table-responsive px-0">
                  <h2 class="text-white"><span id="calendarPage"></span><button id="next" class="btn btn-secondary float-end bg-ranked-1" onclick="NextMonth();">Next</button><button id="prev" class="btn btn-secondary float-end me-2 bg-ranked-1" onclick="PreviousMonth();">Previous</button></h2>
                  <table id="calendar" class="calendar table table-sm table-bordered">
                      <!-- Calendar will be generated here -->
                  </table>
                </div>

                <div class="card mt-3">
                  <div class="card-header">Suggested Dates</div>
                  <div class="card-body" id="suggested-dates">
                    Loading...
                  </div>
                </div>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    
    <script>
        // Convert the PHP array to JSON so JavaScript can use it
        var events = <?php echo json_encode($events); ?>;
    </script>
<script>
    var month = <?php echo $month; ?>; // July
    var year = <?php echo $year; ?>;

    function generateCalendar(month, year) {
      var date = new Date(year, month, 1);
      var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

      var header = '<thead><tr>';
      for (var i = 0; i < days.length; i++) {
        header += '<th>' + days[i] + '</th>';
      }
      header += '</tr></thead>';

      var body = '<tbody>';
      while (date.getMonth() === month) {
        body += '<tr>';
        for (var i = 0; i < 7; i++) {
          if (date.getDay() === i && date.getMonth() === month) {
            body += '<td><span>' + date.getDate() + '</span><h6 style="" class="calendar-event text-bg-danger text-sm-center">RedCaps</h6></td>';
            date.setDate(date.getDate() + 1);
          } else {
            body += '<td></td>';
          }
        }
        body += '</tr>';
      }
      body += '</tbody>';

      document.getElementById('calendarPage').innerHTML = date.toLocaleString('default', { month: 'long' }) + ' ' + year;
      document.getElementById('calendar').innerHTML = header + body;
    }

    function NextMonth()
    {
        if (month === 11) {
        month = 0;
        year++;
      } else {
        month++;
      }
      generateCalendar(month, year);
      loadSuggestedDates();
    }

    function PreviousMonth()
    {
        if (month === 0) {
        month = 11;
        year--;
      } else {
        month--;
      }
      generateCalendar(month, year);
      loadSuggestedDates();
    }

    function loadSuggestedDates() {
      fetch(`/api/v1/schedule/suggestedDates.php?month=${month+1}&year=${year}`)
        .then(response => response.json())
        .then(data => {
          var container = document.getElementById('suggested-dates');
          if (data.success && data.data.length > 0) {
            container.innerHTML = '';
            data.data.forEach(item => {
              var p = document.createElement('p');
              p.textContent = `${item.date} - ${item.reason}`;
              container.appendChild(p);
            });
          } else {
            container.textContent = 'No suggestions available';
          }
        })
        .catch(() => {
          var container = document.getElementById('suggested-dates');
          container.textContent = 'Failed to load suggestions';
        });
    }

    generateCalendar(month, year);
    loadSuggestedDates();
  </script>
</body>

</html>

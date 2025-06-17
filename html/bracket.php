<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\TournamentController;
use Kickback\Common\Version;

$hasError = false;
$errorMessage = "";
if (isset($_GET['id']))
{

    $id = $_GET['id'];
    $questResp = QuestController::getQuestById($id);
}

if (isset($_GET['locator'])){
        
    $name = $_GET['locator'];
    $questResp = QuestController::getQuestByLocator($name);
}

if (!$questResp->success)
{
    unset($questResp);
}

if (!isset($questResp))
{
    $hasError = true;
    $errorMessage = "Failed to load bracket";
}

$thisQuest = $questResp->data;

$userCanEditQuest = false;

if ($thisQuest->canEdit())
{
    $userCanEditQuest = true;
}

$thisQuest->populateTournament();
$questApplicants = $thisQuest->queryQuestApplicants();

$bracketRenderData = $thisQuest->tournament->calculateBracketRenderData($questApplicants);
$teams = $bracketRenderData[0];
$matchArray = $bracketRenderData[1];
$startPlacementArray = $bracketRenderData[2];
$pairs = $bracketRenderData[3];

$seedsAreTBD = false;

if ($questApplicants[0]->seed==null)
{
    $seedsAreTBD = true;
    $hasError = true;
    $errorMessage = "Seeds are to be determined.";
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Bracket</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, minimum-scale=0.5, user-scalable=yes">


        <link href="<?= Version::urlBetaPrefix(); ?>/assets/vendors/bootstrap/bootstrap.min.css" rel="stylesheet">
        <link href="<?= Version::urlBetaPrefix(); ?>/assets/vendors/bracket/jquery.bracket.min.css" rel="stylesheet">
        <link href="<?= Version::urlBetaPrefix(); ?>/assets/vendors/animate/animate.min.css" rel="stylesheet" />
        
    <?php
        $cssFile = Version::urlBetaPrefix().'/assets/css/kickback-kingdom.css';
        $cssVersion = Version::current()->number();
    ?>

    <link rel="stylesheet" type="text/css" href="<?= $cssFile.'?v='.$cssVersion ?>">

        <script src="<?= Version::urlBetaPrefix(); ?>/assets/vendors/jquery/jquery-3.7.0.min.js"></script>
        <script src="<?= Version::urlBetaPrefix(); ?>/assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
        <script src="<?= Version::urlBetaPrefix(); ?>/assets/vendors/bracket/jquery.bracket.min.js"></script>
        <script src="<?= Version::urlBetaPrefix(); ?>/assets/vendors/fittext/jquery.fittext.js"></script>
    </head>
    <body class="body-bracket">
        <div class="modal fade" id="matchModal" tabindex="-1" role="dialog" aria-labelledby="matchModal" aria-hidden="true" style="background-color: #5e6e30c7; transition: 1s;">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content" style="background-color: transparent;border: none;" onclick="$('#matchModal').modal('hide');">
                    <form method="post" action="/q/Don-Frios-Civ-6-Ranked-Match">
                        
                        <!--<div class="modal-header">
                            <h4 class="modal-title">Match Results</h4>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close" data-bs-original-title="" title=""></button>
                        </div>-->
                        <div class="modal-body" style="color: white;">
            
                            <!-- Add more content here -->
                            <div>

                                <div class="row">
                                    <div class="col-3 slideInLeft animated">            
                                        <img id="matchResultPicture-1"  src="https://kickback-kingdom.com/assets/media/profiles/young-9.jpg" style="width: 100%; border-radius: 5%;">
                                    </div>
                                    <div class="col-6" style="display: flex;flex-direction: column;justify-content: center;/* align-items: center; */">
                                        <div class="slideInLeft animated">

                                            <h2 id="matchResultName-1" class="fitText" style="/* display: flex; *//* flex-wrap: wrap; *//* align-content: start; */">LandoTheBarbarian</h2>
                                            <h6 id="matchResultRank-1">Rank #1</h6>
                                        </div>
                                        <div class="flip animated">
                                            <img src="/assets/media/context/Versus-Transparent.png" style="width: 60px;display: flex;align-items: normal;margin-left: auto;margin-right: auto;">
                                        </div>
                                        <div class="slideInRight animated">
                                            <h2 id="matchResultName-2" class="fitText" style="text-align: right;">simonsays</h2>
                                            <h6 id="matchResultRank-2" style="text-align: right;">Rank #5</h6>
                                        </div>
                                    </div>
                                    <div class="col-3 align-self-end slideInRight animated">
                                        <img id="matchResultPicture-2" src="https://kickback-kingdom.com/assets/media/profiles/young-9.jpg" style="width: 100%; border-radius: 5%;">
                                    </div>
                                </div>
                                <?php 
                                if ($thisQuest->CanEdit())
                                {
                                    ?>
                                
                                <div id="matchDeclare" class="fadeInUp" style="padding-top: 50px;">
                                    <div class="row">
                                        <button class="btn bg-ranked-1" type="button" onclick="OpenDeclareMatch()">Declare Results</button>
                                    </div>
                                </div>
                                <?php
                                }
                                ?>
                                <div id="matchResultSets" class="fadeInUp" style="padding-top: 50px;">
                                    <div class="row">
                                        <div class="col-6">
                                            <h6 style="text-align:right">Soviet <span class="badge bg-danger">L</span></h6>
                                        </div>
                                        <div class="col-6">
                                            <h6 style="/* text-align:right; */"><span class="badge bg-success pull-left">W</span><span class="pull-right"> Wehrmacht</span></h6>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <h6 style="text-align:right">Soviet <span class="badge bg-danger">L</span></h6>
                                        </div>
                                        <div class="col-6">
                                            <h6 style="/* text-align:right; */"><span class="badge bg-success pull-left">W</span><span class="pull-right"> Wehrmacht</span></h6>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <h6 style="text-align:right">Soviet <span class="badge bg-danger">L</span></h6>
                                        </div>
                                        <div class="col-6">
                                            <h6 style="/* text-align:right; */"><span class="badge bg-success pull-left">W</span><span class="pull-right"> Wehrmacht</span></h6>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <!--<div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" data-bs-original-title="" title="">Back</button>                
                        </div>-->
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="declareModal" tabindex="-1" role="dialog" aria-labelledby="declareModal" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form method="post">
                        
                        <div class="modal-header">
                            <h4 class="modal-title">Declare Match</h4>
                            <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close" data-bs-original-title="" title=""></button>
                        </div>
                        <div class="modal-body" style="color: white;">
            
                        
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" data-bs-original-title="" title="">Back</button>                
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php 
        if ($hasError)
        {
?>
<h1><?php echo $errorMessage; ?></h1>

<?php
        }
        else{
?>
        <div id="bracket" class="align-items-center align-self-center d-flex flex-column flex-wrap justify-content-evenly" style="height:100vh;">
            <!-- Bracket will be rendered here -->
        </div>
        <script>
            const colors = ["#4581b57a", "#4eb5457a", "#b545457a", "#b545b37a"];
    let i = 0;

    setInterval(function() {
        $('#matchModal').css("background-color", colors[i]);
        i++;
        if(i == colors.length) {
            i = 0;
        }
    }, 1000); // Changes color every 1 second

                var teams = <?php echo json_encode($teams);?>;
                var matchResultsArray = <?php echo json_encode($matchArray);?>;
                var previousResults = <?php echo json_encode($startPlacementArray); ?>;
                function getTeamNameFromLocation(bracketIndex, roundIndex, matchIndex, teamMatchIndex) {
                    return getTeamNamesFromLocation(bracketIndex, roundIndex, matchIndex)[teamMatchIndex];
                }

                function getTeamNamesFromLocation(bracketIndex, roundIndex, matchIndex)
                {
                    var bracket = document.getElementsByClassName('bracket')[0];
                    if (bracketIndex == 1)
                    {
                        bracket = document.getElementsByClassName('loserBracket')[0];
                    }
                    if (bracketIndex == 2)
                    {
                        bracket = document.getElementsByClassName('finals')[0];
                    }
                    const round = bracket.getElementsByClassName('round')[roundIndex];
                    const match = round.getElementsByClassName('match')[matchIndex];
                    const teams = match.getElementsByClassName('team');

                    var teamNames = [];

                    for (let index = 0; index < teams.length; index++) {
                        var teamElement = teams[index];
                        var teamName = teamElement.querySelector('.label').innerText;
                        teamNames.push(teamName);
                    }

                    return teamNames;
                }

                function GetTeamsByDisplayNames(displayNames) {
                    var filteredTeams = [];
                    for (let index = 0; index < teams.length; index++) {
                        var team = teams[index];
                        if (displayNames.includes(team.displayName)) {
                            filteredTeams.push(team);
                        }                        
                    }
                    return filteredTeams;
                }

                function saveFn(data) {
                    console.log("Previous");
                    console.log(JSON.stringify(previousResults));
                    console.log("New");
                    console.log(JSON.stringify(data.results));
                    
                    var diff = findDiff(previousResults, data.results);  // function to find the difference
                    var teamName = getTeamNameFromLocation(diff.bracket, diff.round, diff.match, diff.changeIndex);

                    console.log(diff);
                    console.log(JSON.stringify(diff));
                    console.log(teamName);
                    previousResults = JSON.parse(JSON.stringify(data.results));  // copy the current state to the previous state
                }

                function findDiff(prevArray, currArray, path = []) {
                    if (Array.isArray(prevArray) && Array.isArray(currArray)) {
                        for (let i = 0; i < prevArray.length; i++) {
                        let result = findDiff(prevArray[i], currArray[i], [...path, i]);
                        if (result) return result;
                        }
                    } else if (Array.isArray(prevArray) || Array.isArray(currArray) || prevArray !== currArray) {
                        return {
                        bracket: path[0],
                        round: path[1],
                        match: path[2],
                        changeIndex: path[3],
                        previousValue: prevArray,
                        newValue: currArray
                        };
                    }
                    return null;
                }

                function onclick(matchResult) {
                    if (matchResult != null)
                    {

                        console.log(matchResult);

                        var displayNames = getTeamNamesFromLocation(matchResult.bracketNum-1, matchResult.roundNum-1, matchResult.matchNum-1);
                        var matchTeams = GetTeamsByDisplayNames(displayNames);

                        console.log(matchTeams);
                        UpdateMatchResultsModal(matchResult, matchTeams);
                            $("#matchModal").modal("show");
                            $('#matchResultSets').hide();
                        setInterval(function() {
                            $(".fitText").fitText(1.1, {
                            });
                            $('#matchResultSets').show();
                            //$('#matchResultSets').removeClass("animated");
                            //$('#matchResultSets').addClass("animated");
                            
                        }, 1000); // Changes color every 1 second
                    }
                }

                function OpenDeclareMatch()
                {
                    
                    $("#declareModal").modal("show");
                }

                function UpdateMatchResultsModal(matchResult, matchTeams)
                {
                    $("#matchResultPicture-1").attr("src", matchTeams[0].icon.url);
                    $("#matchResultPicture-2").attr("src", matchTeams[1].icon.url);

                    $("#matchResultName-1").text(matchTeams[0].teamName);
                    $("#matchResultName-2").text(matchTeams[1].teamName);

                    if (matchTeams[0].rank != null && matchTeams[0].rank > 0)
                    {

                        $("#matchResultRank-1").text("Rank #"+matchTeams[0].rank);
                    }
                    else
                    {

                        $("#matchResultRank-1").text("Unranked");
                    }
                    
                    if (matchTeams[1].rank != null && matchTeams[1].rank > 0)
                    {

                        $("#matchResultRank-2").text("Rank #"+matchTeams[1].rank);
                    }
                    else
                    {

                        $("#matchResultRank-2").text("Unranked");
                    }
                    $("#matchResultSets").html("");
                    var setId = 0;
                    var slot1 = 0;
                    var slot2 = 1;
                    if (matchTeams[0].teamName != matchResult.teams[0])
                    {
                        slot1 = 1;
                        slot2 = 0;
                    }
                    matchResult.sets.forEach(set => {
                        if (set[slot1] != null && set[slot2] != null)
                        {

                        
                        console.log(set);
                        var color1 = (set[slot1][0]==0?"danger":"success");
                        var color2 = (set[slot2][0]==0?"danger":"success");
                        var text1 = (set[slot1][0]==0?"L":"W");
                        var text2 = (set[slot2][0]==0?"L":"W");
                        var team1 = set[slot1][1];
                        var team2 = set[slot2][1];
                        $("#matchResultSets").append(`
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="fitText" id="teamText-1-`+setId+`" style="text-align:right; transition: all 1s ease 0s;">`+team1+` <span class="badge bg-`+color1+`">`+text1+`</span></h6>
                                </div>
                                <div class="col-6">
                                    <h6  class="fitText" id="teamText-2-`+setId+`" style="transition: all 1s ease 0s;"><span class="badge bg-`+color2+` pull-left">`+text2+`</span><span class="pull-right"> `+team2+`</span></h6>
                                </div>
                            </div>
                        `);
                        }
                        setId++;
                    });
                }
                
                var minimalData = {
                    teams : <?php echo json_encode($pairs); ?>,
                    results :  previousResults
                };

            function initBracket(data)
            {
                $('#bracket').bracket({
                    skipConsolationRound: false,
                    teamWidth:150,
                    //save: saveFn,
                    onMatchClick: onclick,
                    //disableToolbar: true,
                    //disableTeamEdit: true,
                    init: data,
                    /* Your bracket configuration here */
                });
            }
            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            function GetOrderedMatchScores(result, displayNameOrder)
            {
                var scores = [];
                for (let index = 0; index < displayNameOrder.length; index++) {
                    var displayName = displayNameOrder[index];
                    var displayNameIndex = -1;
                    //console.log(displayName);
                    for (let j = 0; j < result.displayNames.length; j++) {
                        var element = result.displayNames[j];
                        if (element == displayName)
                        {
                            displayNameIndex = j;
                            break;
                        }
                    }
                    //console.log(displayNameIndex);
                    scores.push(result.scores[displayNameIndex]);
                }
                if (scores.length != 2)
                {
                    scores = [null, null];
                }
                scores.push(result);
                return scores;
            }

            function SetMatchScore(result, displayNameOrder)
            {
                var bracketIndex = result.bracketNum -1;
                var roundIndex = result.roundNum - 1;
                var matchIndex = result.matchNum - 1;
                //check if bracket exists
                if (minimalData.results.length <= bracketIndex)
                    minimalData.results.push([]);
                    
                while (minimalData.results[bracketIndex].length <= roundIndex)
                {
                    minimalData.results[bracketIndex].push([]);
                }
                
                while (minimalData.results[bracketIndex][roundIndex].length <= matchIndex)
                {
                    minimalData.results[bracketIndex][roundIndex].push([]);
                }

                var scores = GetOrderedMatchScores(result, displayNameOrder);
                minimalData.results[bracketIndex][roundIndex][matchIndex] = scores;
                initBracket(minimalData);
            }

            async function PopulateResultsIntoBracket(results)
            {
                await sleep(1);
                for (let index = 0; index < results.length; index++) {
                    var result = results[index];
                    
                    console.log("Populating - "+result.teams[0] + " v "+result.teams[1]+" = "+result.scores[0]+" to "+result.scores[1]);
                    var bracketIndex = result.bracketNum -1;
                    var roundIndex = result.roundNum - 1;
                    var matchIndex = result.matchNum - 1;
                    var teamNames = getTeamNamesFromLocation(bracketIndex, roundIndex, matchIndex);
                    //console.log(teamNames);
                    //console.log(result);
                    SetMatchScore(result,teamNames);
                    await sleep(1);
                }
            }

            initBracket(minimalData);
            PopulateResultsIntoBracket(matchResultsArray);
        </script>
<?php
        }
        ?>
        
    </body>
</html>

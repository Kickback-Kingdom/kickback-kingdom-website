<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$hasError = false;
$errorMessage = "";
if (isset($_GET['id']))
{

    $id = $_GET['id'];
    $questResp = GetQuestById($id);
}

if (isset($_GET['locator'])){
        
    $name = $_GET['locator'];
    $questResp = GetQuestByLocator($name);
}

if (!$questResp->Success)
{
    unset($questResp);
}

if (!isset($questResp))
{
    $hasError = true;
    $errorMessage = "Failed to load bracket";
}

$thisQuest = $questResp->Data;


$userCanEditQuest = false;

if (CanEditQuest($thisQuest))
{
    $userCanEditQuest = true;
}



$questApplicantsResp = GetQuestApplicants($thisQuest["Id"]);
$questApplicants = $questApplicantsResp->Data;

$bracketInfoResp = GetTournamentBracketInfo($thisQuest["tournament_id"]);
$bracketInfo = $bracketInfoResp->Data;

function GetTeamsArrayFixedSize($teamObjects)
{
    $usernames = array();
    for ($i = 0; $i < count($teamObjects); $i++) {
        $team = $teamObjects[$i];
        $name = "Unknown";
        $seed = ($i+1);
        $displayName = $seed.". ".$name;
        if (isset($teamObjects[$i]->DisplayName))
        {
            $displayName = $teamObjects[$i]->DisplayName;
        }
        array_push($usernames, $displayName);
    }

    // Add null to usernames array to make its size a power of 2
    while (log(count($usernames), 2) != floor(log(count($usernames), 2))) {
        
        array_push($usernames, null);
    }

    return $usernames;
}


function pairSeeds($teamNames) {
    // Split the array into two halves
    $upperHalf = array_slice($teamNames, 0, count($teamNames) / 2);
    $lowerHalf = array_reverse(array_slice($teamNames, count($teamNames) / 2));

    // Pair the users from the upper and lower halves
    $matchups = array();
    for ($x = 0; $x < count($upperHalf); $x++) {
        $betterPlayer = $upperHalf[$x];
        $worsePlayer = $lowerHalf[$x];
        $matchup = array($betterPlayer,$worsePlayer);
        array_push($matchups,$matchup);
      }

    return $matchups;
}

function teeter_totter($matchups)
{
    $firstBracket = array();
    $secondBracket = array();

    for ($i = 0; $i < count($matchups); $i++) {
        if ($i % 2 == 0) {
            //first bracket
            array_push($firstBracket, $matchups[$i]);
        } else {
            //second bracket
            array_push($secondBracket, $matchups[$i]);
        }
    }

    return array_merge($firstBracket, $secondBracket);
}

class Team {
    public $TeamName;
    public $Seed;
    public $DisplayName;
}

class BracketMatch {
    //public $TeamA;
    //public $TeamB;
    public $Teams;
    public $Scores;
    public $DisplayNames;
    //public $ScoreA;
    //public $ScoreB;

    public $BracketNum;
    public $RoundNum;
    public $MatchNum;
    public $Sets;
}


function BuildTeamsArray($questApplicants)
{
    $teams = array();
    for ($i = 0; $i < count($questApplicants); $i++) {
        $name = "Unknown";
        $seed = ($i+1);
        $rank = -1;
        if (isset($questApplicants[$i]['Username']))
        {
            $name = $questApplicants[$i]['Username'];
        }

        if (isset($questApplicants[$i]['seed']))
        {
            $seed = $questApplicants[$i]['seed'];
        }
        if (isset($questApplicants[$i]['rank']))
        {
            $rank = $questApplicants[$i]['rank'];
        }
        $team = new Team();
        $team->TeamName = $name;
        $team->Seed = $seed;
        $team->DisplayName = $seed.". ".$name;
        $team->Rank = $rank;
        $team->Picture = '/assets/media/'.GetAccountProfilePicture($questApplicants[$i]);
        array_push($teams, $team);
    }

    return $teams;
}

function GetSetMatchScore($bracketData, $bracket, $round, $match, $displayName)
{

}

function GetMatchScore($bracketData, $bracket, $round, $match)
{
    $matchScore = [0,0];
    for ($i = 0; $i < count($bracketData); $i++) {
        $game_record = $bracketData[$i];
        
    }

    return $matchScore;
}

function GetMatch($matchArray, $bracketNum, $roundNum, $matchNum)
{
    
    for ($i = 0; $i < count($matchArray); $i++) {
        $match = $matchArray[$i];

        if ($match->BracketNum == $bracketNum && $match->RoundNum == $roundNum && $match->MatchNum == $matchNum)
        {
            return $match;
        }
    }


    return null;
}

function GetDisplayName($teamName, $teams)
{
    
    for ($i = 0; $i < count($teams); $i++) {
        $team = $teams[$i];

        if ($team->TeamName == $teamName)
        {
            return $team->DisplayName;
        }
    }

    return null;
}

function sortMatches($matches) {
    usort($matches, function ($a, $b) {
        
        if ($a->BracketNum !== $b->BracketNum) {
            return $a->BracketNum - $b->BracketNum;
        }

        if ($a->RoundNum !== $b->RoundNum) {
            return $a->RoundNum - $b->RoundNum;
        }


        if ($a->MatchNum !== $b->MatchNum) {
            return $a->MatchNum - $b->MatchNum;
        }

        return 0;
    });

    return $matches;
}


function BuildMatchArray($bracketData, $teams){

    $matchArray = array();
    for ($i = 0; $i < count($bracketData); $i++) {
        $game_record = $bracketData[$i];

        $bracketNum = $game_record['bracket'];
        $roundNum = $game_record['round'];
        $matchNum = $game_record['match'];
        $teamName = $game_record['Username'];
        $match = GetMatch($matchArray, $bracketNum, $roundNum, $matchNum);
        
        $setIndex = $game_record["set"]-1;
        if ($match == null)
        {
            
            $match = new BracketMatch();
            $match->BracketNum = $bracketNum;
            $match->RoundNum = $roundNum;
            $match->MatchNum = $matchNum;
            //$match->TeamA = $teamName;
            //$match->ScoreA = $game_record['win'];
            //$match->ScoreB = 0;
            $match->SetsCount = 0.5;
            $match->Teams = [];
            $match->DisplayNames = [];
            $match->Scores = [];
            $match->Teams[0] = $teamName;
            $match->Scores[0] = $game_record['win'];
            $match->DisplayNames[0] = GetDisplayName($teamName,$teams);
            $match->Sets = [];
            $match->Sets[0] = [];
            if (!isset($match->Sets[$setIndex])) {
                $match->Sets[$setIndex] = [];
            }
            $match->Sets[$setIndex][0] = [$game_record['win'],$game_record['character']];
            array_push($matchArray, $match);
        }
        else
        {
            if ($match->Teams[0] == $teamName)
            {
                //$match->ScoreA = $match->ScoreA + $game_record['win'];
                $match->Scores[0] = $match->Scores[0] + $game_record['win'];
                $match->Sets[$setIndex][0] = [$game_record['win'],$game_record['character']];
            }
            else
            {
                //$match->TeamB = $teamName;
                if (!isset($match->Teams[1])) {
                    $match->Teams[1] = $teamName;
                    $match->DisplayNames[1] = GetDisplayName($teamName, $teams);
                    $match->Scores[1] = 0; // Initialize the score for team B
                }
                
                $match->Scores[1] += $game_record['win'];
                if (!isset($match->Sets[$setIndex])) {
                    $match->Sets[$setIndex] = [[], []]; // Initialize with two elements
                }
                $match->Sets[$setIndex][1] = [$game_record['win'],$game_record['character']];
                //$match->ScoreB = $match->ScoreB + $game_record['win'];
            }
            $match->SetsCount = $match->SetsCount+0.5;
        }
    }

    return sortMatches($matchArray);
    //return $matchArray;
}


function BuildResultsArray($startPlacement, $matchArray)
{
    $doubleElim = true;
    $results = array_merge($startPlacement);
    
    if ($doubleElim)
    {
        $results[] = [];
        
        for ($i = 0; $i < count($matchArray); $i++) {
            $match = $matchArray[$i];
            
            $bracketIndex = $match->BracketNum-1;
            $roundIndex = $match->RoundNum-1;
            $matchIndex = $match->MatchNum-1;
            $results[$bracketIndex][$roundIndex][$matchIndex] = [$match->Scores[0], $match->Scores[1], $match];
        }
    }

    return $results;
}

function BuildStartPlacementArray($pairs)
{
    $doubleElim = true;
    $results = array();
    $results[] = [];
    //set team placement (round 1 placement)
    for ($i = 0; $i < count($pairs); $i++) {
        $pair = $pairs[$i];

        // bracket 1, round 1, match $i
        $results[0][0][$i] = [];
    }

    if ($doubleElim)
    {
        $results[] = [];
    }

    return $results;
}



$teams = BuildTeamsArray($questApplicants);
$matchArray = BuildMatchArray($bracketInfo, $teams);
$teamsFixedSize = GetTeamsArrayFixedSize($teams);
$pairs = pairSeeds($teamsFixedSize);
$pairs = teeter_totter($pairs);
$startPlacementArray = BuildStartPlacementArray($pairs);
$results = BuildResultsArray($startPlacementArray, $matchArray);
//$bracketSize = calculateBracketSize(count($questApplicants));
//$bracketArraySize = $bracketSize/2;

$seedsAreTBD = false;

if ($questApplicants[0]['seed']==null)
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


        <link href="<?php echo $urlPrefixBeta; ?>/assets/vendors/bootstrap/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $urlPrefixBeta; ?>/assets/vendors/bracket/jquery.bracket.min.css" rel="stylesheet">
        <link href="<?php echo $urlPrefixBeta; ?>/assets/vendors/animate/animate.min.css" rel="stylesheet" />
        
    <?php
        $cssFile = $urlPrefixBeta.'/assets/css/kickback-kingdom.css';
        $cssVersion = filemtime($_SERVER['DOCUMENT_ROOT'].$cssFile);
    ?>

    <link rel="stylesheet" type="text/css" href="<?= $cssFile.'?v='.$cssVersion ?>">

        <script src="<?php echo $urlPrefixBeta; ?>/assets/vendors/jquery/jquery-3.7.0.min.js"></script>
        <script src="<?php echo $urlPrefixBeta; ?>/assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
        <script src="<?php echo $urlPrefixBeta; ?>/assets/vendors/bracket/jquery.bracket.min.js"></script>
        <script src="<?php echo $urlPrefixBeta; ?>/assets/vendors/fittext/jquery.fittext.js"></script>
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
                                if (CanEditQuest($quest))
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
<!--<p>Quest Applicants: <?php echo json_encode($questApplicants);?></p>-->
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
                        if (displayNames.includes(team.DisplayName)) {
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

                        var displayNames = getTeamNamesFromLocation(matchResult.BracketNum-1, matchResult.RoundNum-1, matchResult.MatchNum-1);
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
                    $("#matchResultPicture-1").attr("src", matchTeams[0].Picture);
                    $("#matchResultPicture-2").attr("src", matchTeams[1].Picture);

                    $("#matchResultName-1").text(matchTeams[0].TeamName);
                    $("#matchResultName-2").text(matchTeams[1].TeamName);

                    if (matchTeams[0].Rank != null && matchTeams[0].Rank > 0)
                    {

                        $("#matchResultRank-1").text("Rank #"+matchTeams[0].Rank);
                    }
                    else
                    {

                        $("#matchResultRank-1").text("Unranked");
                    }
                    
                    if (matchTeams[1].Rank != null && matchTeams[1].Rank > 0)
                    {

                        $("#matchResultRank-2").text("Rank #"+matchTeams[1].Rank);
                    }
                    else
                    {

                        $("#matchResultRank-2").text("Unranked");
                    }
                    $("#matchResultSets").html("");
                    var setId = 0;
                    var slot1 = 0;
                    var slot2 = 1;
                    if (matchTeams[0].TeamName != matchResult.Teams[0])
                    {
                        slot1 = 1;
                        slot2 = 0;
                    }
                    matchResult.Sets.forEach(set => {
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
                    for (let j = 0; j < result.DisplayNames.length; j++) {
                        var element = result.DisplayNames[j];
                        if (element == displayName)
                        {
                            displayNameIndex = j;
                            break;
                        }
                    }
                    //console.log(displayNameIndex);
                    scores.push(result.Scores[displayNameIndex]);
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
                var bracketIndex = result.BracketNum -1;
                var roundIndex = result.RoundNum - 1;
                var matchIndex = result.MatchNum - 1;
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
                    
                    console.log("Populating - "+result.Teams[0] + " v "+result.Teams[1]+" = "+result.Scores[0]+" to "+result.Scores[1]);
                    var bracketIndex = result.BracketNum -1;
                    var roundIndex = result.RoundNum - 1;
                    var matchIndex = result.MatchNum - 1;
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

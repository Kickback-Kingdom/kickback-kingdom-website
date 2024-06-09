<?php

declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vBracketTeam;
use Kickback\Views\vBracketMatch;

class BracketController
{

    private static function getTeamsArrayFixedSize($teamObjects)
    {
        $usernames = array();
        for ($i = 0; $i < count($teamObjects); $i++) {
            $team = $teamObjects[$i];
            $name = "Unknown";
            $seed = ($i+1);
            $displayName = $seed.". ".$name;
            if (isset($teamObjects[$i]->displayName))
            {
                $displayName = $teamObjects[$i]->displayName;
            }
            array_push($usernames, $displayName);
        }
    
        // Add null to usernames array to make its size a power of 2
        while (log(count($usernames), 2) != floor(log(count($usernames), 2))) {
            
            array_push($usernames, null);
        }
    
        return $usernames;
    }
    
    
    private static function pairSeeds($teamNames) {
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
    
    private static function teeter_totter($matchups)
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
    
    private static function buildTeamsArray($questApplicants)
    {
        $teams = array();
        for ($i = 0; $i < count($questApplicants); $i++) {
            $name = "Unknown";
            $seed = ($i+1);
            $rank = -1;
            if (isset($questApplicants[$i]->account->username))
            {
                $name = $questApplicants[$i]->account->username;
            }
    
            if (isset($questApplicants[$i]->seed))
            {
                $seed = $questApplicants[$i]->seed;
            }
            if (isset($questApplicants[$i]->rank))
            {
                $rank = $questApplicants[$i]->rank;
            }
            $team = new vBracketTeam();
            $team->teamName = $name;
            $team->seed = $seed;
            $team->displayName = $seed.". ".$name;
            $team->rank = $rank;
            $team->icon = $questApplicants[$i]->account->avatar;
            array_push($teams, $team);
        }
    
        return $teams;
    }
    
    private static function getMatch($matchArray, $bracketNum, $roundNum, $matchNum)
    {
        
        for ($i = 0; $i < count($matchArray); $i++) {
            $match = $matchArray[$i];
    
            if ($match->bracketNum == $bracketNum && $match->roundNum == $roundNum && $match->matchNum == $matchNum)
            {
                return $match;
            }
        }
    
    
        return null;
    }
    
    private static function getDisplayName($teamName, $teams)
    {
        
        for ($i = 0; $i < count($teams); $i++) {
            $team = $teams[$i];
    
            if ($team->teamName == $teamName)
            {
                return $team->displayName;
            }
        }
    
        return null;
    }
    
    private static function sortMatches($matches) {
        usort($matches, function ($a, $b) {
            
            if ($a->bracketNum !== $b->bracketNum) {
                return $a->bracketNum - $b->bracketNum;
            }
    
            if ($a->roundNum !== $b->roundNum) {
                return $a->roundNum - $b->roundNum;
            }
    
    
            if ($a->matchNum !== $b->matchNum) {
                return $a->matchNum - $b->matchNum;
            }
    
            return 0;
        });
    
        return $matches;
    }
    
    
    private static function buildMatchArray($bracketInfoArray, $teams){
    
        $matchArray = array();
        for ($i = 0; $i < count($bracketInfoArray); $i++) {
            $bracketInfo = $bracketInfoArray[$i];
    
            $bracketNum = $bracketInfo->gameMatch->bracket;
            $roundNum = $bracketInfo->gameMatch->round;
            $matchNum = $bracketInfo->gameMatch->match;
            $teamName = $bracketInfo->account->username;
            $match = self::getMatch($matchArray, $bracketNum, $roundNum, $matchNum);
            
            $setIndex = $bracketInfo->gameMatch->set-1;
            if ($match == null)
            {
                
                $match = new vBracketMatch();
                $match->bracketNum = $bracketNum;
                $match->roundNum = $roundNum;
                $match->matchNum = $matchNum;
                //$match->TeamA = $teamName;
                //$match->ScoreA = $game_record['win'];
                //$match->ScoreB = 0;
                $match->setsCount = 0.5;
                $match->teams = [];
                $match->displayNames = [];
                $match->scores = [];
                $match->teams[0] = $teamName;
                $match->scores[0] = $bracketInfo->gameRecord->getScore();
                $match->displayNames[0] = self::getDisplayName($teamName,$teams);
                $match->sets = [];
                $match->sets[0] = [];
                if (!isset($match->sets[$setIndex])) {
                    $match->sets[$setIndex] = [];
                }
                $match->sets[$setIndex][0] = [$bracketInfo->gameRecord->getScore(), $bracketInfo->gameMatch->characterHint];
                array_push($matchArray, $match);
            }
            else
            {
                if ($match->teams[0] == $teamName)
                {
                    //$match->ScoreA = $match->ScoreA + $game_record['win'];
                    $match->scores[0] = $match->scores[0] + $bracketInfo->gameRecord->getScore();
                    $match->sets[$setIndex][0] = [$bracketInfo->gameRecord->getScore(), $bracketInfo->gameMatch->characterHint];
                }
                else
                {
                    //$match->TeamB = $teamName;
                    if (!isset($match->teams[1])) {
                        $match->teams[1] = $teamName;
                        $match->displayNames[1] = self::getDisplayName($teamName, $teams);
                        $match->scores[1] = 0; // Initialize the score for team B
                    }
                    
                    $match->scores[1] += $bracketInfo->gameRecord->getScore();
                    if (!isset($match->sets[$setIndex])) {
                        $match->sets[$setIndex] = [[], []]; // Initialize with two elements
                    }
                    $match->sets[$setIndex][1] = [$bracketInfo->gameRecord->getScore(), $bracketInfo->gameMatch->characterHint];
                    //$match->ScoreB = $match->ScoreB + $game_record['win'];
                }
                $match->setsCount = $match->setsCount+0.5;
            }
        }
    
        return self::sortMatches($matchArray);
    }
    
    
    private static function buildResultsArray($startPlacement, $matchArray)
    {
        $doubleElim = true;
        $results = array_merge($startPlacement);
        
        if ($doubleElim)
        {
            $results[] = [];
            
            for ($i = 0; $i < count($matchArray); $i++) {
                $match = $matchArray[$i];
                
                $bracketIndex = $match->bracketNum-1;
                $roundIndex = $match->roundNum-1;
                $matchIndex = $match->matchNum-1;
                $results[$bracketIndex][$roundIndex][$matchIndex] = [$match->scores[0], $match->scores[1], $match];
            }
        }
    
        return $results;
    }
    
    private static function buildStartPlacementArray($pairs)
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

    public static function getBracketRenderData($questApplicants, $bracketInfoArray) : array {
        
        $teams = self::buildTeamsArray($questApplicants);
        $matchArray = self::buildMatchArray($bracketInfoArray, $teams);
        $teamsFixedSize = self::getTeamsArrayFixedSize($teams);
        $pairs = self::pairSeeds($teamsFixedSize);
        $pairs = self::teeter_totter($pairs);
        $startPlacementArray = self::buildStartPlacementArray($pairs);
        $results = self::buildResultsArray($startPlacementArray, $matchArray);

        return [$teams, $matchArray, $startPlacementArray, $pairs];
    }
}


?>
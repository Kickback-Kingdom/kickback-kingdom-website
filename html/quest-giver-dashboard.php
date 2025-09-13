<?php
$pageTitle = "Quest Giver Dashboard";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Manage your quests and reviews";

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Backend\Views\vDateTime;
use Kickback\Common\Version;

const LOYAL_PARTICIPANT_THRESHOLD = 3;
const WEIGHT_RATING = 0.7;
const WEIGHT_LOYALTY = 0.3;
const CO_HOST_SUGGESTION_COUNT = 5;

function describeRating(float $rating): string
{
    if ($rating >= 4.5) {
        $phrases = ['excellent', 'outstanding', 'stellar'];
    } elseif ($rating >= 4.0) {
        $phrases = ['strong', 'solid', 'favorable'];
    } elseif ($rating >= 3.0) {
        $phrases = ['moderate', 'mixed', 'average'];
    } else {
        $phrases = ['low', 'weak', 'subpar'];
    }
    return $phrases[array_rand($phrases)];
}

function followupPrompt(
    float $questRating,
    float $hostRating,
    int $registered,
    int $unique,
    int $loyal
): string {
    $messages = [];

    // Overall rating blend
    if ($questRating >= 4.5 && $hostRating >= 4.5) {
        $options = [
            'Players raved about every aspect—this adventure could become a flagship event.',
            'Quest and hosting alike earned top marks; a celebratory sequel might be in order.'
        ];
    } elseif ($questRating >= 4.5) {
        $options = [
            'The quest design dazzled adventurers; polish your hosting to match the concept.',
            'Players loved the adventure itself—refining delivery could push it into legend status.'
        ];
    } elseif ($hostRating >= 4.5) {
        $options = [
            'Your hosting carried the run even as the quest mechanics drew mixed reactions.',
            'Guiding players is your strength; revisit the quest structure to align with your talent.'
        ];
    } elseif ($questRating >= 4.0 && $hostRating >= 4.0) {
        $options = [
            'Feedback is favorable across the board—consider scheduling a follow-up while interest is high.',
            'Both design and hosting resonated; a seasonal return could keep momentum going.'
        ];
    } elseif ($questRating >= 3.0 && $hostRating >= 3.0) {
        $options = [
            'Reviews were mixed, suggesting targeted tweaks could boost future runs.',
            'Some players were engaged while others hesitated—use detailed comments to refine pacing.'
        ];
    } else {
        $options = [
            'Results highlight pain points in design or delivery—consider a significant overhaul before revisiting.',
            'Ratings were low overall; a fresh approach may serve better than a direct sequel.'
        ];
    }
    $messages[] = $options[array_rand($options)];

    // Participation rate commentary
    if ($registered > 0) {
        $rate = $unique / $registered;
        if ($rate >= 0.9) {
            $options = [
                'Turnout was exceptional, showing strong commitment from registrants.',
                'Nearly everyone who signed up joined the adventure—engagement is high.'
            ];
        } elseif ($rate >= 0.5) {
            $options = [
                'Participation was solid; consider nudging undecided registrants next time.',
                'About half of sign-ups became participants—there\'s room to convert more.'
            ];
        } else {
            $options = [
                'Few registrants took part—review timing or messaging to boost attendance.',
                'A low conversion from sign-ups to players suggests barriers to entry.'
            ];
        }
        $messages[] = $options[array_rand($options)];
    }

    // Loyalty commentary
    if ($loyal > 0) {
        $options = [
            "{$loyal} repeat players returned, so rewarding that loyalty could pay off.",
            "With {$loyal} familiar faces coming back, a sequel might deepen community ties.",
            "{$loyal} loyal adventurers showed up again—recognize them to keep them engaged."
        ];
    } else {
        $options = [
            'No repeat players joined—consider incentives to build long-term interest.',
            'This run drew only first-timers; think about ways to encourage returns.'
        ];
    }
    $messages[] = $options[array_rand($options)];

    // Closing remark
    $options = [
        'Let these insights guide your next quest.',
        'Use these details to shape future adventures.',
        'Carry these takeaways into your upcoming runs.'
    ];
    $messages[] = $options[array_rand($options)];

    return implode(' ', $messages);
}

function generateBringBackSuggestion(array $quest): string
{
    $parts = [];
    $parts[] = "Players gave {$quest['questRatingDesc']} marks of " . number_format($quest['avgQuestRating'], 1) . "/5 for the quest and {$quest['hostRatingDesc']} feedback of " . number_format($quest['avgHostRating'], 1) . "/5 for your hosting";
    if (($quest['participants'] ?? 0) > 0) {
        $parts[] = "with {$quest['participants']} adventurer" . ($quest['participants'] === 1 ? '' : 's') . " taking part";
    }
    if (isset($quest['loyal']) && $quest['loyal'] > 0) {
        $parts[] = "including {$quest['loyal']} returning player" . ($quest['loyal'] === 1 ? '' : 's');
    }
    $parts[] = "but it hasn't been offered since " . htmlspecialchars($quest['endDate']->formattedBasic) . ".";
    $parts[] = "Reviving it could re-engage fans—consider adding new twists or rewards to keep it fresh.";
    return implode(' ', $parts);
}

function generateSequelSuggestion(array $quest): string
{
    $parts = [];
    $parts[] = "Loyal adventurers gave {$quest['questRatingDesc']} marks of " . number_format($quest['avgQuestRating'], 1) . "/5 for the quest and {$quest['hostRatingDesc']} ratings of " . number_format($quest['avgHostRating'], 1) . "/5 for your hosting.";
    if (isset($quest['loyal'])) {
        $parts[] = "{$quest['loyal']} returning player" . ($quest['loyal'] === 1 ? '' : 's') . " joined its last run on " . htmlspecialchars($quest['endDate']->formattedBasic) . ".";
    }
    $parts[] = "A sequel would be well received—build on its strengths and address any feedback to keep the saga fresh.";
    return implode(' ', $parts);
}

function generateSimilarQuestSuggestion(array $quest): string
{
    $parts = [];
    $parts[] = "Out of {$quest['registered']} sign-ups, {$quest['unique']} adventurer" . ($quest['unique'] === 1 ? '' : 's') . " joined";
    if ($quest['loyal'] > 0) {
        $parts[] = "including {$quest['loyal']} loyal player" . ($quest['loyal'] === 1 ? '' : 's');
    }
    $parts[] = "The quest earned {$quest['questRatingDesc']} feedback at " . number_format($quest['avgQuestRating'], 1) . "/5 and your hosting received {$quest['hostRatingDesc']} marks at " . number_format($quest['avgHostRating'], 1) . "/5.";
    $parts[] = $quest['followupPrompt'];
    return implode(' ', $parts);
}

function generateImproveQuestSuggestion(array $quest): string
{
    $parts = [];
    $parts[] = "Despite {$quest['participants']} of {$quest['registered']} registered adventurer" . ($quest['registered'] === 1 ? '' : 's') . " taking part, players gave {$quest['questRatingDesc']} ratings of " . number_format($quest['avgQuestRating'], 1) . "/5 and your hosting received {$quest['hostRatingDesc']} marks of " . number_format($quest['avgHostRating'], 1) . "/5.";
    $parts[] = "Review the feedback to adjust balance, narrative, or rewards before offering a refined version.";
    return implode(' ', $parts);
}

function generatePromoteQuestSuggestion(array $quest): string
{
    $parts = [];
    $parts[] = "Players gave {$quest['questRatingDesc']} marks of " . number_format($quest['avgQuestRating'], 1) . "/5 for the quest and {$quest['hostRatingDesc']} ratings of " . number_format($quest['avgHostRating'], 1) . "/5 for your hosting, yet only {$quest['participants']} adventurer" . ($quest['participants'] === 1 ? '' : 's') . " joined.";
    $parts[] = "Highlight those strong reviews and spread the word across guild halls and socials to draw more participants.";
    return implode(' ', $parts);
}

function generateCoHostSuggestion(array $candidate): string
{
    $parts = [];
    $parts[] = $candidate['username'] . ' has joined you on ' . $candidate['loyalty'] . ' quest' . ($candidate['loyalty'] === 1 ? '' : 's') . ',';
    $parts[] = 'showing up for ' . number_format($candidate['reliability'] * 100, 0) . '% of the quests they register for.';
    $parts[] = 'They\'ve adventured alongside ' . $candidate['network'] . ' other player' . ($candidate['network'] === 1 ? '' : 's') . ', expanding your reach.';
    if (($candidate['questsHosted'] ?? 0) > 0) {
        $parts[] = 'They\'ve hosted ' . $candidate['questsHosted'] . ' quest' . ($candidate['questsHosted'] === 1 ? '' : 's') . ' of their own with average ratings of ' . number_format($candidate['avgHostedQuestRating'], 1) . '/5.';
    }
    if (isset($candidate['daysSinceLastQuest'])) {
        $days = $candidate['daysSinceLastQuest'];
        $parts[] = 'Their last quest with you was ' . $days . ' day' . ($days === 1 ? '' : 's') . ' ago.';
    }
    $parts[] = 'Consider inviting them to co-host your next quest.';
    return implode(' ', $parts);
}

if (!Session::isQuestGiver()) {
    Session::redirect("index.php");
}

$account = Session::getCurrentAccount();

$futureResp = QuestController::queryHostedFutureQuests($account);
$futureQuests = $futureResp->success ? $futureResp->data : [];

$pastResp = QuestController::queryHostedPastQuests($account);
$pastQuests = $pastResp->success ? $pastResp->data : [];

$reviewsResp = QuestController::queryQuestReviewsByHostAsResponse($account);
$questReviewAverages = $reviewsResp->success ? $reviewsResp->data : [];
usort($questReviewAverages, fn($a, $b) => strtotime($a->questEndDate) <=> strtotime($b->questEndDate));

$totalHostedQuests = count($futureQuests) + count($pastQuests);
$questTitles = array_map(fn($qr) => $qr->questTitle, $questReviewAverages);
$avgHostRatings = array_map(fn($qr) => (float)$qr->avgHostRating, $questReviewAverages);
$avgQuestRatings = array_map(fn($qr) => (float)$qr->avgQuestRating, $questReviewAverages);

$questRatingsMap = [];
$hostRatingsMap = [];
foreach ($questReviewAverages as $qr) {
    $questRatingsMap[$qr->questTitle] = (float)$qr->avgQuestRating;
    $hostRatingsMap[$qr->questTitle] = (float)$qr->avgHostRating;
}

$participantCounts = [];
$participantQuestTitles = [];
$uniqueParticipants = [];
$participantTotals = [];
$participantRegisteredTotals = [];
$participantDetails = [];
$participantHostRatingSums = [];
$participantHostRatingCounts = [];
$participantQuestRatingSums = [];
$participantQuestRatingCounts = [];
$perQuestParticipantCounts = [];
$perQuestParticipantIds = [];
$perQuestRegisteredCounts = [];
$participantLastQuestTimes = [];
$coHostStats = [];
$now = time();
$allQuests = array_merge($futureQuests, $pastQuests);
usort($allQuests, fn($a, $b) => strcmp(
    $a->hasEndDate() ? $a->endDate()->formattedYmd : '',
    $b->hasEndDate() ? $b->endDate()->formattedYmd : ''
));
$questIds = array_map(fn($q) => $q->crand, $allQuests);
$applicantsByQuest = QuestController::queryQuestApplicantsForQuests($questIds);
foreach ($allQuests as $quest) {
    $qid = $quest->crand;
    $registrations = $applicantsByQuest[$qid] ?? [];
    foreach ($registrations as $reg) {
        $rid = $reg->account->crand;
        if ($rid === $account->crand) {
            continue;
        }
        $participantRegisteredTotals[$rid] = ($participantRegisteredTotals[$rid] ?? 0) + 1;
    }
    $participants = array_filter(
        $registrations,
        fn($app) => $app->participated
    );
    $count = count($participants);
    $perQuestRegisteredCounts[$quest->title] = count($registrations);
    $participantCounts[] = $count;
    $participantQuestTitles[] = $quest->title;
    $perQuestParticipantCounts[$quest->title] = $count;
    $perQuestParticipantIds[$qid] = [];
    foreach ($participants as $participant) {
        $crand = $participant->account->crand;
        $perQuestParticipantIds[$qid][] = $crand;
        $uniqueParticipants[$crand] = true;
        if ($quest->hasEndDate()) {
            $endTime = $quest->endDate()->value->getTimestamp();
            if ($endTime <= $now) {
                $participantLastQuestTimes[$crand] = $endTime;
            }
        }
        if ($crand === $account->crand) {
            continue;
        }
        $participantTotals[$crand] = ($participantTotals[$crand] ?? 0) + 1;
        if (!isset($participantDetails[$crand])) {
            $participantDetails[$crand] = [
                'username' => $participant->account->username,
                'avatar' => $participant->account->avatar->getFullPath(),
                'url' => $participant->account->url(),
            ];
        }
    }
}
$totalUniqueParticipants = count($uniqueParticipants);

$perPastQuestParticipantStats = [];
foreach ($pastQuests as $quest) {
    $qid = $quest->crand;
    $ids = $perQuestParticipantIds[$qid] ?? [];
    $unique = count($ids);
    $loyal = count(array_filter($ids, fn($id) => ($participantTotals[$id] ?? 0) > 1));
    $perPastQuestParticipantStats[] = [
        'quest' => $quest,
        'unique' => $unique,
        'loyal' => $loyal,
        'score' => $unique * $loyal,
    ];
}
usort($perPastQuestParticipantStats, fn($a, $b) => $b['score'] <=> $a['score']);
$recommendedQuest = null;
if (!empty($perPastQuestParticipantStats)) {
    $top = $perPastQuestParticipantStats[0];
    $recommendedQuest = [
        'title' => $top['quest']->title,
        'locator' => $top['quest']->locator,
        'icon' => $top['quest']->icon ? $top['quest']->icon->getFullPath() : '',
        'unique' => $top['unique'],
        'loyal' => $top['loyal'],
        'registered' => $perQuestRegisteredCounts[$top['quest']->title] ?? 0,
        'avgQuestRating' => $questRatingsMap[$top['quest']->title] ?? 0,
        'avgHostRating' => $hostRatingsMap[$top['quest']->title] ?? 0,
        'endDate' => $top['quest']->hasEndDate() ? $top['quest']->endDate() : null,
        'id' => $top['quest']->crand,
        'banner' => $top['quest']->banner ? $top['quest']->banner->getFullPath() : '',
    ];
    $recommendedQuest['questRatingDesc'] = describeRating($recommendedQuest['avgQuestRating']);
    $recommendedQuest['hostRatingDesc'] = describeRating($recommendedQuest['avgHostRating']);
    $recommendedQuest['followupPrompt'] = followupPrompt(
        $recommendedQuest['avgQuestRating'],
        $recommendedQuest['avgHostRating'],
        $recommendedQuest['registered'],
        $recommendedQuest['unique'],
        $recommendedQuest['loyal']
    );
}

$pastQuestIds = array_map(fn($q) => $q->crand, $pastQuests);
$reviewDetailsByQuest = QuestController::queryQuestReviewDetailsForQuests($pastQuestIds);

foreach ($pastQuests as $quest) {
    foreach ($reviewDetailsByQuest[$quest->crand] ?? [] as $detail) {
        $id = $detail->accountId;
        if ($id === $account->crand) {
            continue;
        }
        if (!is_null($detail->hostRating)) {
            $participantHostRatingSums[$id] = ($participantHostRatingSums[$id] ?? 0) + $detail->hostRating;
            $participantHostRatingCounts[$id] = ($participantHostRatingCounts[$id] ?? 0) + 1;
        }
        if (!is_null($detail->questRating)) {
            $participantQuestRatingSums[$id] = ($participantQuestRatingSums[$id] ?? 0) + $detail->questRating;
            $participantQuestRatingCounts[$id] = ($participantQuestRatingCounts[$id] ?? 0) + 1;
        }
    }
}
$participantAvgHostRatings = [];
foreach ($participantHostRatingSums as $id => $sum) {
    $participantAvgHostRatings[$id] = $sum / $participantHostRatingCounts[$id];
}
$participantAvgQuestRatings = [];
foreach ($participantQuestRatingSums as $id => $sum) {
    $participantAvgQuestRatings[$id] = $sum / $participantQuestRatingCounts[$id];
}

arsort($participantTotals);
$topParticipants = [];
foreach ($participantTotals as $crand => $count) {
    if ($crand === $account->crand) {
        continue;
    }
    $info = $participantDetails[$crand];
    $topParticipants[] = [
        'id' => $crand,
        'username' => $info['username'],
        'avatar' => $info['avatar'],
        'url' => $info['url'],
        'loyalty' => $count,
        'avgHostRating' => $participantAvgHostRatings[$crand] ?? 0,
        'avgQuestRating' => $participantAvgQuestRatings[$crand] ?? 0,
    ];
    if (count($topParticipants) >= 10) {
        break;
    }
}
$topParticipantIds = array_map(fn($p) => $p['id'], $topParticipants);
$hostingStats = QuestController::queryHostStatsForAccounts($topParticipantIds);
foreach ($topParticipants as &$p) {
    $friendSet = [];
    foreach ($perQuestParticipantIds as $ids) {
        if (in_array($p['id'], $ids, true)) {
            foreach ($ids as $id) {
                if ($id !== $p['id']) {
                    $friendSet[$id] = true;
                }
            }
        }
    }
    $p['network'] = count($friendSet);
    $p['loyalty'] = $p['loyalty'] ?? 0;
    $daysSince = isset($participantLastQuestTimes[$p['id']])
        ? (int) floor(($now - $participantLastQuestTimes[$p['id']]) / 86400)
        : null;
    $p['daysSinceLastQuest'] = $daysSince;
    $p['recentActivity'] = isset($daysSince) ? max(0, 30 - $daysSince) : 0;
    $registered = $participantRegisteredTotals[$p['id']] ?? 0;
    $attended = $participantTotals[$p['id']] ?? 0;
    $p['registered'] = $registered;
    $p['attended'] = $attended;
    $p['reliability'] = $registered > 0 ? $attended / $registered : 0;
    $hostStat = $hostingStats[$p['id']] ?? ['questsHosted' => 0, 'avgHostRating' => 0, 'avgQuestRating' => 0];
    $p['questsHosted'] = $hostStat['questsHosted'];
    $p['avgHostedHostRating'] = $hostStat['avgHostRating'];
    $p['avgHostedQuestRating'] = $hostStat['avgQuestRating'];
    $p['score'] = (
        ($p['loyalty'] * 2) +
        ($p['network'] * 1) +
        ($p['recentActivity'] * 0.5) +
        ($p['reliability'] * 3) +
        ($p['questsHosted'] * 0.2) +
        ($p['avgHostedHostRating'] * 0.5) +
        ($p['avgHostedQuestRating'] * 0.5)
    );
}
unset($p);

$sortedCandidates = $topParticipants;
usort($sortedCandidates, fn($a, $b) => $b['score'] <=> $a['score']);
$coHostCandidates = array_slice($sortedCandidates, 0, CO_HOST_SUGGESTION_COUNT);

// Aggregate co-host performance metrics
foreach ($pastQuests as $quest) {
    if (!isset($quest->host2) || $quest->host2->crand === $account->crand) {
        continue;
    }

    $id = $quest->host2->crand;
    if (!isset($coHostStats[$id])) {
        $coHostStats[$id] = [
            'id' => $id,
            'username' => $quest->host2->username,
            'avatar' => $quest->host2->avatar ? $quest->host2->avatar->getFullPath() : '',
            'url' => $quest->host2->url(),
            'questCount' => 0,
            'participantSum' => 0,
            'uniqueParticipantIds' => [],
            'hostRatingSum' => 0,
            'hostRatingCount' => 0,
            'questRatingSum' => 0,
            'questRatingCount' => 0,
        ];
    }

    $stats =& $coHostStats[$id];
    $stats['questCount']++;
    $stats['participantSum'] += $perQuestParticipantCounts[$quest->title] ?? 0;
    foreach ($perQuestParticipantIds[$quest->crand] ?? [] as $pid) {
        $stats['uniqueParticipantIds'][$pid] = true;
    }
    foreach ($reviewDetailsByQuest[$quest->crand] ?? [] as $detail) {
        if ($detail->hostRating !== null) {
            $stats['hostRatingSum'] += $detail->hostRating;
            $stats['hostRatingCount']++;
        }
        if ($detail->questRating !== null) {
            $stats['questRatingSum'] += $detail->questRating;
            $stats['questRatingCount']++;
        }
    }
}
unset($stats);

$topCoHosts = [];
foreach ($coHostStats as $stats) {
    $avgParticipants = $stats['questCount'] > 0 ? $stats['participantSum'] / $stats['questCount'] : 0;
    $uniqueCount = count($stats['uniqueParticipantIds']);
    $avgHostRating = $stats['hostRatingCount'] > 0 ? $stats['hostRatingSum'] / $stats['hostRatingCount'] : 0;
    $avgQuestRating = $stats['questRatingCount'] > 0 ? $stats['questRatingSum'] / $stats['questRatingCount'] : 0;
    $topCoHosts[] = [
        'id' => $stats['id'],
        'username' => $stats['username'],
        'avatar' => $stats['avatar'],
        'url' => $stats['url'],
        'questCount' => $stats['questCount'],
        'avgParticipants' => $avgParticipants,
        'uniqueParticipants' => $uniqueCount,
        'avgHostRating' => $avgHostRating,
        'avgQuestRating' => $avgQuestRating,
        'score' => $avgParticipants + $uniqueCount + $avgHostRating + $avgQuestRating,
    ];
}
usort($topCoHosts, fn($a, $b) => $b['score'] <=> $a['score']);
$topCoHosts = array_slice($topCoHosts, 0, 10);

$topParticipantIds = array_map(fn($p) => $p['id'], $topParticipants);
$fanFavoriteQuest = null;
$questRatingsByTop = [];
foreach ($pastQuests as $quest) {
    $qid = $quest->crand;
    $details = $reviewDetailsByQuest[$qid] ?? [];
    $sum = 0;
    $count = 0;
    $seen = [];
    foreach ($details as $detail) {
        $id = $detail->accountId;
        if (!in_array($id, $topParticipantIds, true)) {
            continue;
        }
        if ($detail->questRating !== null) {
            $sum += $detail->questRating;
            $count++;
        }
        $seen[$id] = true;
    }
    if ($count > 0) {
        $loyal = count(array_filter(array_keys($seen), fn($id) => ($participantTotals[$id] ?? 0) > 1));
        $questRatingsByTop[] = [
            'quest' => $quest,
            'avgRating' => $sum / $count,
            'loyal' => $loyal,
        ];
    }
}
$qualifiedTop = array_filter($questRatingsByTop, fn($q) => $q['loyal'] >= LOYAL_PARTICIPANT_THRESHOLD);
if (!empty($qualifiedTop)) {
    usort($qualifiedTop, function ($a, $b) {
        $scoreA = WEIGHT_RATING * $a['avgRating'] + WEIGHT_LOYALTY * $a['loyal'];
        $scoreB = WEIGHT_RATING * $b['avgRating'] + WEIGHT_LOYALTY * $b['loyal'];
        return $scoreB <=> $scoreA;
    });
    $top = $qualifiedTop[0];
    $fanFavoriteQuest = [
        'title' => $top['quest']->title,
        'locator' => $top['quest']->locator,
        'icon' => $top['quest']->icon ? $top['quest']->icon->getFullPath() : '',
        'avgQuestRating' => $top['avgRating'],
        'avgHostRating' => $hostRatingsMap[$top['quest']->title] ?? 0,
        'endDate' => $top['quest']->endDate(),
        'id' => $top['quest']->crand,
        'banner' => $top['quest']->banner ? $top['quest']->banner->getFullPath() : '',
        'loyal' => $top['loyal'],
        'participants' => $perQuestParticipantCounts[$top['quest']->title] ?? 0,
        'registered' => $perQuestRegisteredCounts[$top['quest']->title] ?? 0,
    ];
    $fanFavoriteQuest['questRatingDesc'] = describeRating($fanFavoriteQuest['avgQuestRating']);
    $fanFavoriteQuest['hostRatingDesc'] = describeRating($fanFavoriteQuest['avgHostRating']);
}

$dormantQuest = null;
$dormantWindowMonths = 6;
$dormantCutoff = (new DateTime())->modify("-{$dormantWindowMonths} months");
$dormantCandidates = [];
foreach ($pastQuests as $quest) {
    if (!$quest->hasEndDate()) {
        continue;
    }
    $avgRating = $questRatingsMap[$quest->title] ?? 0;
    $hostRating = $hostRatingsMap[$quest->title] ?? 0;
    if ($avgRating < 4) {
        continue;
    }
    $endDateObj = $quest->endDate();
    if ($endDateObj->value < $dormantCutoff) {
        $ids = $perQuestParticipantIds[$quest->crand] ?? [];
        $participants = count($ids);
        $loyal = count(array_filter($ids, fn($id) => ($participantTotals[$id] ?? 0) > 1));
        $dormantCandidates[] = [
            'title' => $quest->title,
            'locator' => $quest->locator,
            'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
            'avgQuestRating' => $avgRating,
            'avgHostRating' => $hostRating,
            'endDate' => $endDateObj,
            'id' => $quest->crand,
            'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
            'participants' => $participants,
            'registered' => $perQuestRegisteredCounts[$quest->title] ?? 0,
            'loyal' => $loyal,
        ];
    }
}
if (!empty($dormantCandidates)) {
    usort($dormantCandidates, fn($a, $b) => $b['avgQuestRating'] <=> $a['avgQuestRating']);
    $dormantQuest = $dormantCandidates[0];
    $dormantQuest['questRatingDesc'] = describeRating($dormantQuest['avgQuestRating']);
    $dormantQuest['hostRatingDesc'] = describeRating($dormantQuest['avgHostRating']);
}

$bestQuestCandidates = [];
foreach ($pastQuests as $quest) {
    $title = $quest->title;
    $participants = $perQuestParticipantCounts[$title] ?? 0;
    $avgQuestRating = $questRatingsMap[$title] ?? 0;
    $avgHostRating = $hostRatingsMap[$title] ?? 0;
    $bestQuestCandidates[] = [
        'title' => $title,
        'locator' => $quest->locator,
        'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
        'id' => $quest->crand,
        'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
        'participants' => $participants,
        'avgQuestRating' => $avgQuestRating,
        'avgHostRating' => $avgHostRating,
        'score' => $participants * $avgQuestRating,
    ];
}
usort($bestQuestCandidates, fn($a, $b) => $b['score'] <=> $a['score']);
$topBestQuests = array_slice($bestQuestCandidates, 0, 10);

// Identify quests with high participation but low ratings
$underperformingQuest = null;
$underperformingCandidates = [];
$minParticipants = 10;
$maxAvgRating = 3;
foreach ($pastQuests as $quest) {
    $title = $quest->title;
    $participants = $perQuestParticipantCounts[$title] ?? 0;
    $avgRating = $questRatingsMap[$title] ?? 0;
    $hostRating = $hostRatingsMap[$title] ?? 0;
    if ($participants >= $minParticipants && $avgRating <= $maxAvgRating) {
        $underperformingCandidates[] = [
            'title' => $title,
            'locator' => $quest->locator,
            'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
            'participants' => $participants,
            'registered' => $perQuestRegisteredCounts[$title] ?? 0,
            'avgQuestRating' => $avgRating,
            'avgHostRating' => $hostRating,
            'endDate' => $quest->hasEndDate() ? $quest->endDate() : null,
            'id' => $quest->crand,
            'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
        ];
    }
}
usort($underperformingCandidates, fn($a, $b) => $b['participants'] <=> $a['participants']);
if (!empty($underperformingCandidates)) {
    $underperformingQuest = $underperformingCandidates[0];
    $underperformingQuest['questRatingDesc'] = describeRating($underperformingQuest['avgQuestRating']);
    $underperformingQuest['hostRatingDesc'] = describeRating($underperformingQuest['avgHostRating']);
}

// Identify well-rated quests that had low participation
$hiddenGemQuest = null;
$hiddenGemCandidates = [];
foreach ($pastQuests as $quest) {
    $title = $quest->title;
    $participants = $perQuestParticipantCounts[$title] ?? 0;
    $avgRating = $questRatingsMap[$title] ?? 0;
    $hostRating = $hostRatingsMap[$title] ?? 0;
    if ($avgRating >= 4 && $participants < $minParticipants) {
        $hiddenGemCandidates[] = [
            'title' => $title,
            'locator' => $quest->locator,
            'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
            'participants' => $participants,
            'registered' => $perQuestRegisteredCounts[$title] ?? 0,
            'avgQuestRating' => $avgRating,
            'avgHostRating' => $hostRating,
            'endDate' => $quest->hasEndDate() ? $quest->endDate() : null,
            'id' => $quest->crand,
            'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
        ];
    }
}
usort($hiddenGemCandidates, function ($a, $b) {
    if ($b['avgQuestRating'] === $a['avgQuestRating']) {
        return $a['participants'] <=> $b['participants'];
    }
    return $b['avgQuestRating'] <=> $a['avgQuestRating'];
});
if (!empty($hiddenGemCandidates)) {
    $hiddenGemQuest = $hiddenGemCandidates[0];
    $hiddenGemQuest['questRatingDesc'] = describeRating($hiddenGemQuest['avgQuestRating']);
    $hiddenGemQuest['hostRatingDesc'] = describeRating($hiddenGemQuest['avgHostRating']);
}

$ratingData = [];
foreach ($pastQuests as $quest) {
    $title = $quest->title;
    if (isset($questRatingsMap[$title]) && $quest->hasEndDate()) {
        $ratingData[] = [
            'date' => $quest->endDate()->formattedYmd,
            'rating' => $questRatingsMap[$title],
        ];
    }
}
usort($ratingData, fn($a, $b) => strcmp($a['date'], $b['date']));
$ratingDates = array_column($ratingData, 'date');
$avgRatingsOverTime = array_column($ratingData, 'rating');

$recentReviews = $questReviewAverages;
usort($recentReviews, fn($a, $b) => strtotime($b->questEndDate) <=> strtotime($a->questEndDate));
$recentReviews = array_slice($recentReviews, 0, 10);
$recentCount = count($recentReviews);
$avgHostRatingRecent = $recentCount > 0 ? array_sum(array_map(fn($qr) => (float)$qr->avgHostRating, $recentReviews)) / $recentCount : 0;
$avgQuestRatingRecent = $recentCount > 0 ? array_sum(array_map(fn($qr) => (float)$qr->avgQuestRating, $recentReviews)) / $recentCount : 0;

function renderStarRating(float $rating): string
{
    $rounded = round($rating * 2) / 2;
    $stars = '<span class="star-rating" style="pointer-events: none; display: inline-block;">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rounded >= $i) {
            $class = 'fa-solid fa-star selected';
        } elseif ($rounded >= $i - 0.5) {
            $class = 'fa-solid fa-star-half-stroke selected';
        } else {
            $class = 'fa-regular fa-star';
        }
        $stars .= "<i class=\"{$class}\"></i>";
    }
    return $stars . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require("php-components/base-page-head.php"); ?>
<body class="bg-body-secondary container p-0">
<?php
    require("php-components/base-page-components.php");
    require("php-components/ad-carousel.php");
?>
<main class="container pt-3 bg-body" style="margin-bottom: 56px;">
    <h2>Quest Giver Dashboard</h2>
    <div class="row text-center g-3 mt-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Total Quests Hosted</small>
                    <h3 class="mb-0"><?= $totalHostedQuests; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Total Unique Participants</small>
                    <h3 class="mb-0"><?= $totalUniqueParticipants; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Average Host Rating (Last 10)</small>
                    <div><?= renderStarRating($avgHostRatingRecent); ?><span class="ms-1"><?= number_format($avgHostRatingRecent, 2); ?>/5</span></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small>Average Quest Rating (Last 10)</small>
                    <div><?= renderStarRating($avgQuestRatingRecent); ?><span class="ms-1"><?= number_format($avgQuestRatingRecent, 2); ?>/5</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-upcoming-tab" data-bs-toggle="tab" data-bs-target="#nav-upcoming" type="button" role="tab" aria-controls="nav-upcoming" aria-selected="true"><i class="fa-regular fa-calendar"></i></button>
                    <button class="nav-link" id="nav-past-tab" data-bs-toggle="tab" data-bs-target="#nav-past" type="button" role="tab" aria-controls="nav-past" aria-selected="false"><i class="fa-solid fa-clock-rotate-left"></i></button>
                    <button class="nav-link" id="nav-reviews-tab" data-bs-toggle="tab" data-bs-target="#nav-reviews" type="button" role="tab" aria-controls="nav-reviews" aria-selected="false"><i class="fa-solid fa-star"></i></button>
                    <button class="nav-link" id="nav-graphs-tab" data-bs-toggle="tab" data-bs-target="#nav-graphs" type="button" role="tab" aria-controls="nav-graphs" aria-selected="false"><i class="fa-solid fa-chart-line"></i></button>
                    <button class="nav-link" id="nav-suggestions-tab" data-bs-toggle="tab" data-bs-target="#nav-suggestions" type="button" role="tab" aria-controls="nav-suggestions" aria-selected="false"><i class="fa-solid fa-lightbulb"></i></button>
                    <button class="nav-link" id="nav-schedule-tab" data-bs-toggle="tab" data-bs-target="#nav-schedule" type="button" role="tab" aria-controls="nav-schedule" aria-selected="false"><i class="fa-solid fa-calendar-days"></i></button>
                    <button class="nav-link" id="nav-top-tab" data-bs-toggle="tab" data-bs-target="#nav-top" type="button" role="tab" aria-controls="nav-top" aria-selected="false"><i class="fa-solid fa-trophy"></i></button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-upcoming" role="tabpanel" aria-labelledby="nav-upcoming-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Upcoming Quests</div>
                    <?php if (count($futureQuests) === 0) { ?>
                        <p>No upcoming quests.</p>
                    <?php } else { foreach ($futureQuests as $quest) {
                        $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($quest);
                        require("php-components/vFeedCardRenderer.php");
                    }} ?>
                </div>
                <div class="tab-pane fade" id="nav-past" role="tabpanel" aria-labelledby="nav-past-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Past Quests</div>
                    <?php if (count($pastQuests) === 0) { ?>
                        <p>No past quests.</p>
                    <?php } else { foreach ($pastQuests as $quest) {
                        $_vFeedCard = FeedCardController::vQuest_to_vFeedCard($quest);
                        require("php-components/vFeedCardRenderer.php");
                    }} ?>
                </div>
                <div class="tab-pane fade" id="nav-reviews" role="tabpanel" aria-labelledby="nav-reviews-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Quest Reviews</div>
                    <?php if (count($questReviewAverages) === 0) { ?>
                        <p>No quest reviews yet.</p>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table id="datatable-reviews" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Quest</th>
                                        <th>Date</th>
                                        <th>Average Host Rating</th>
                                        <th>Average Quest Rating</th>
                                        <th>Reviews</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questReviewAverages as $qr) { ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($qr->questIcon); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                    <a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $qr->questLocator)); ?>" target="_blank"><?= htmlspecialchars($qr->questTitle); ?></a>
                                                </div>
                                            </td>
                                            <?php $qd = new vDateTime($qr->questEndDate); ?>
                                            <td data-order="<?= htmlspecialchars($qd->dbValue); ?>">
                                                <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?= htmlspecialchars($qd->formattedDetailed); ?> UTC" data-datetime-utc="<?= htmlspecialchars($qd->valueString); ?>" data-db-value="<?= htmlspecialchars($qd->dbValue); ?>"><?= htmlspecialchars($qd->formattedBasic); ?></span>
                                            </td>
                                            <td data-order="<?= $qr->avgHostRating; ?>" class="align-middle">
                                                <?= renderStarRating($qr->avgHostRating); ?><span class="ms-1"><?= number_format($qr->avgHostRating, 2); ?></span>
                                            </td>
                                            <td data-order="<?= $qr->avgQuestRating; ?>" class="align-middle">
                                                <?= renderStarRating($qr->avgQuestRating); ?><span class="ms-1"><?= number_format($qr->avgQuestRating, 2); ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <?php $btnClass = !empty($qr->hasComments) ? 'btn-primary' : 'btn-outline-secondary'; ?>
                                                <?php $iconClass = !empty($qr->hasComments) ? 'fa-solid' : 'fa-regular'; ?>
                                                <button class="btn btn-sm <?= $btnClass ?> view-reviews-btn" data-quest-id="<?= $qr->questId; ?>" data-quest-title="<?= htmlspecialchars($qr->questTitle); ?>" data-quest-banner="<?= htmlspecialchars($qr->questBanner); ?>"><i class="<?= $iconClass ?> fa-comments me-1"></i>View</button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
                <div class="tab-pane fade" id="nav-graphs" role="tabpanel" aria-labelledby="nav-graphs-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Quest Analytics</div>
                    <div class="accordion" id="graphAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingRatings">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRatings" aria-expanded="true" aria-controls="collapseRatings">
                                    Average Ratings
                                </button>
                            </h2>
                            <div id="collapseRatings" class="accordion-collapse collapse show" aria-labelledby="headingRatings">
                                <div class="accordion-body">
                                    <canvas id="reviewChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingRatingOverTime">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRatingOverTime" aria-expanded="false" aria-controls="collapseRatingOverTime">
                                    Average Quest Rating Over Time
                                </button>
                            </h2>
                            <div id="collapseRatingOverTime" class="accordion-collapse collapse" aria-labelledby="headingRatingOverTime">
                                <div class="accordion-body">
                                    <canvas id="ratingOverTimeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPerQuest">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePerQuest" aria-expanded="false" aria-controls="collapsePerQuest">
                                    Participants per Quest
                                </button>
                            </h2>
                            <div id="collapsePerQuest" class="accordion-collapse collapse" aria-labelledby="headingPerQuest">
                                <div class="accordion-body">
                                    <canvas id="participantPerQuestChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-suggestions" role="tabpanel" aria-labelledby="nav-suggestions-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Suggestions</div>
                    <?php if ($recommendedQuest || $underperformingQuest || $dormantQuest || $fanFavoriteQuest || $hiddenGemQuest || !empty($coHostCandidates)) { ?>
                        <?php if ($dormantQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($dormantQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($dormantQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Bring this quest back</h5>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $dormantQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($dormantQuest['title']); ?></a> last ran <?= htmlspecialchars($dormantQuest['endDate']->formattedBasic); ?></p>
                        <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($dormantQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($dormantQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($dormantQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($dormantQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= generateBringBackSuggestion($dormantQuest); ?></p>
                                    <button class="btn btn-sm btn-outline-primary view-reviews-btn mt-2" data-quest-id="<?= $dormantQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($dormantQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($dormantQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                </div>
                            </div>
                        <?php } else { ?>
                            <p>No dormant fan favorites found.</p>
                        <?php } ?>
                        <?php if ($fanFavoriteQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($fanFavoriteQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($fanFavoriteQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Create a sequel</h5>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $fanFavoriteQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($fanFavoriteQuest['title']); ?></a> last ran <?= htmlspecialchars($fanFavoriteQuest['endDate']->formattedBasic); ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($fanFavoriteQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($fanFavoriteQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($fanFavoriteQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($fanFavoriteQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= generateSequelSuggestion($fanFavoriteQuest); ?></p>
                                    <button class="btn btn-sm btn-outline-primary view-reviews-btn mt-2" data-quest-id="<?= $fanFavoriteQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($fanFavoriteQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($fanFavoriteQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($recommendedQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($recommendedQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($recommendedQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Create a similar quest</h5>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $recommendedQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($recommendedQuest['title']); ?></a> last ran <?= htmlspecialchars($recommendedQuest['endDate']->formattedBasic); ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($recommendedQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($recommendedQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($recommendedQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($recommendedQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= generateSimilarQuestSuggestion($recommendedQuest); ?></p>
                                    <button class="btn btn-sm btn-outline-primary view-reviews-btn mt-2" data-quest-id="<?= $recommendedQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($recommendedQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($recommendedQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($hiddenGemQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($hiddenGemQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($hiddenGemQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Retry this quest with more promotion</h5>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $hiddenGemQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($hiddenGemQuest['title']); ?></a> last ran <?= htmlspecialchars($hiddenGemQuest['endDate']->formattedBasic); ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($hiddenGemQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($hiddenGemQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($hiddenGemQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($hiddenGemQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= generatePromoteQuestSuggestion($hiddenGemQuest); ?></p>
                                    <button class="btn btn-sm btn-outline-primary view-reviews-btn mt-2" data-quest-id="<?= $hiddenGemQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($hiddenGemQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($hiddenGemQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if (!empty($coHostCandidates)) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Invite a co-host</h5>
                                    <p class="card-text mb-3">Partner with reliable players to manage larger quests and reach new audiences.</p>
                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Player</th>
                                                    <th>Joined</th>
                                                    <th>Reliability</th>
                                                    <th>Hosted</th>
                                                    <th>Network</th>
                                                    <th>Last Quest</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($coHostCandidates as $coHostCandidate) { ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($coHostCandidate['avatar'])) { ?>
                                                                    <img src="<?= htmlspecialchars($coHostCandidate['avatar']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                <?php } ?>
                                                                <a href="<?= htmlspecialchars($coHostCandidate['url']); ?>" target="_blank" class="username"><?= htmlspecialchars($coHostCandidate['username']); ?></a>
                                                            </div>
                                                        </td>
                                                        <td class="align-middle"><?= $coHostCandidate['loyalty']; ?></td>
                                                        <td class="align-middle"><?= number_format($coHostCandidate['reliability'] * 100, 0); ?>%</td>
                                                        <td class="align-middle"><?= $coHostCandidate['questsHosted']; ?></td>
                                                        <td class="align-middle"><?= $coHostCandidate['network']; ?></td>
                                                        <td class="align-middle">
                                                            <?php if (isset($coHostCandidate['daysSinceLastQuest'])) { ?>
                                                                <?= $coHostCandidate['daysSinceLastQuest']; ?> day<?= $coHostCandidate['daysSinceLastQuest'] === 1 ? '' : 's'; ?> ago
                                                            <?php } else { ?>
                                                                &ndash;
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($underperformingQuest) { ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (!empty($underperformingQuest['icon'])) { ?>
                                            <img src="<?= htmlspecialchars($underperformingQuest['icon']); ?>" class="rounded me-3" style="width:60px;height:60px;" alt="">
                                        <?php } ?>
                                        <div>
                                            <h5 class="card-title mb-1">Improve this quest</h5>
                                            <p class="card-text mb-1"><a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $underperformingQuest['locator'])); ?>" target="_blank"><?= htmlspecialchars($underperformingQuest['title']); ?></a> last ran <?= htmlspecialchars($underperformingQuest['endDate']->formattedBasic); ?></p>
                                            <p class="card-text mb-0">
                                                Quest Rating: <?= renderStarRating($underperformingQuest['avgQuestRating']); ?><span class="ms-1"><?= number_format($underperformingQuest['avgQuestRating'], 1); ?></span>
                                                &middot; Host Rating: <?= renderStarRating($underperformingQuest['avgHostRating']); ?><span class="ms-1"><?= number_format($underperformingQuest['avgHostRating'], 1); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="card-text mb-2"><?= generateImproveQuestSuggestion($underperformingQuest); ?></p>
                                    <button class="btn btn-sm btn-outline-primary view-reviews-btn mt-2" data-quest-id="<?= $underperformingQuest['id']; ?>" data-quest-title="<?= htmlspecialchars($underperformingQuest['title']); ?>" data-quest-banner="<?= htmlspecialchars($underperformingQuest['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <p>No suggestions found. Keep hosting adventures!</p>
                    <?php } ?>
                </div>
                <div class="tab-pane fade" id="nav-schedule" role="tabpanel" aria-labelledby="nav-schedule-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Schedule Planning</div>
                    <p>Review past quest performance to discover the best days and times for hosting. Plan future quests when participation has historically been highest.</p>
                    <div class="card card-body">
                        <h5>
                            <span id="scheduleCalendarMonth"></span>
                            <button class="btn btn-sm btn-outline-secondary float-end ms-2" id="scheduleNext">Next</button>
                            <button class="btn btn-sm btn-outline-secondary float-end" id="schedulePrev">Previous</button>
                        </h5>
                        <table id="scheduleCalendar" class="table table-sm table-bordered mb-0"></table>
                    </div>
                    <small class="text-muted">Cell colors indicate relative participation.</small>
                    <div class="card card-body mt-3">
                        <h5 class="mb-3">Average Participation by Weekday</h5>
                        <canvas id="weekdayAveragesChart"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-top" role="tabpanel" aria-labelledby="nav-top-tab" tabindex="0">
                    <div class="display-6 tab-pane-title">Top Quests & Participants</div>
                    <div class="accordion" id="topAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTopQuests">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopQuests" aria-expanded="true" aria-controls="collapseTopQuests">
                                    Top 10 Quests
                                </button>
                            </h2>
                            <div id="collapseTopQuests" class="accordion-collapse collapse show" aria-labelledby="headingTopQuests">
                                <div class="accordion-body">
                                    <?php if (count($topBestQuests) === 0) { ?>
                                        <p>No completed quests.</p>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Quest</th>
                                                        <th>Participants</th>
                                                        <th>Avg Quest Rating</th>
                                                        <th>Avg Host Rating</th>
                                                        <th>Reviews</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topBestQuests as $q) { ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($q['icon'])) { ?>
                                                                        <img src="<?= htmlspecialchars($q['icon']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                    <?php } ?>
                                                                    <a href="<?= htmlspecialchars(Version::formatUrl('/q/' . $q['locator'])); ?>" target="_blank"><?= htmlspecialchars($q['title']); ?></a>
                                                                </div>
                                                            </td>
                                                            <td class="align-middle"><?= $q['participants']; ?></td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($q['avgQuestRating']); ?><span class="ms-1"><?= number_format($q['avgQuestRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($q['avgHostRating']); ?><span class="ms-1"><?= number_format($q['avgHostRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <button class="btn btn-sm btn-outline-primary view-reviews-btn" data-quest-id="<?= $q['id']; ?>" data-quest-title="<?= htmlspecialchars($q['title']); ?>" data-quest-banner="<?= htmlspecialchars($q['banner']); ?>"><i class="fa-regular fa-comments me-1"></i>Reviews</button>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTopParticipants">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopParticipants" aria-expanded="false" aria-controls="collapseTopParticipants">
                                    Top 10 Loyal Participants
                                </button>
                            </h2>
                            <div id="collapseTopParticipants" class="accordion-collapse collapse" aria-labelledby="headingTopParticipants">
                                <div class="accordion-body">
                                    <?php if (count($topParticipants) === 0) { ?>
                                        <p>No participants yet.</p>
                                    <?php } else { ?>
                                        <div class="mb-3">
                                            <label for="participantSort" class="form-label">Sort by:</label>
                                            <select id="participantSort" class="form-select form-select-sm" style="max-width:200px;">
                                                <option value="loyalty">Quests Joined</option>
                                                <option value="reliability">Reliability</option>
                                                <option value="questshosted">Hosted Quests</option>
                                                <option value="network">Network Reach</option>
                                            </select>
                                        </div>
                                        <div class="row mb-3 g-2">
                                            <div class="col">
                                                <input type="number" id="reliabilityFilter" class="form-control form-control-sm" placeholder="Min reliability %">
                                            </div>
                                            <div class="col">
                                                <input type="number" id="hostedFilter" class="form-control form-control-sm" placeholder="Min hosted quests">
                                            </div>
                                            <div class="col">
                                                <input type="number" id="networkFilter" class="form-control form-control-sm" placeholder="Min network reach">
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped" id="topParticipantsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Participant</th>
                                                        <th>Quests Joined</th>
                                                        <th>Reliability</th>
                                                        <th>Hosted Quests</th>
                                                        <th>Network</th>
                                                        <th>Avg Quest Rating</th>
                                                        <th>Avg Host Rating</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topParticipants as $p) { ?>
                                                        <tr data-loyalty="<?= $p['loyalty']; ?>" data-reliability="<?= $p['reliability']; ?>" data-questshosted="<?= $p['questsHosted']; ?>" data-network="<?= $p['network']; ?>">
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <img src="<?= htmlspecialchars($p['avatar']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                    <div><a href="<?= htmlspecialchars($p['url']); ?>" target="_blank" class="username"><?= htmlspecialchars($p['username']); ?></a></div>
                                                                </div>
                                                            </td>
                                                            <td class="align-middle"><?= $p['loyalty']; ?></td>
                                                            <td class="align-middle"><?= number_format($p['reliability'] * 100, 0); ?>%</td>
                                                            <td class="align-middle"><?= $p['questsHosted']; ?></td>
                                                            <td class="align-middle"><?= $p['network']; ?></td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($p['avgQuestRating']); ?>
                                                                <span class="ms-1"><?= number_format($p['avgQuestRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($p['avgHostRating']); ?>
                                                                <span class="ms-1"><?= number_format($p['avgHostRating'], 2); ?></span>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTopCoHosts">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopCoHosts" aria-expanded="false" aria-controls="collapseTopCoHosts">
                                    Top 10 Co-Hosts
                                </button>
                            </h2>
                            <div id="collapseTopCoHosts" class="accordion-collapse collapse" aria-labelledby="headingTopCoHosts">
                                <div class="accordion-body">
                                    <?php if (count($topCoHosts) === 0) { ?>
                                        <p>No co-hosts yet.</p>
                                    <?php } else { ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Co-Host</th>
                                                        <th>Quests Co-Hosted</th>
                                                        <th>Avg Participants</th>
                                                        <th>Unique Participants</th>
                                                        <th>Avg Host Rating</th>
                                                        <th>Avg Quest Rating</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topCoHosts as $h) { ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($h['avatar'])) { ?>
                                                                        <img src="<?= htmlspecialchars($h['avatar']); ?>" class="rounded me-2" style="width:40px;height:40px;" alt="">
                                                                    <?php } ?>
                                                                    <div><a href="<?= htmlspecialchars($h['url']); ?>" target="_blank" class="username"><?= htmlspecialchars($h['username']); ?></a></div>
                                                                </div>
                                                            </td>
                                                            <td class="align-middle"><?= $h['questCount']; ?></td>
                                                            <td class="align-middle"><?= number_format($h['avgParticipants'], 1); ?></td>
                                                            <td class="align-middle"><?= $h['uniqueParticipants']; ?></td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($h['avgHostRating']); ?>
                                                                <span class="ms-1"><?= number_format($h['avgHostRating'], 2); ?></span>
                                                            </td>
                                                            <td class="align-middle">
                                                                <?= renderStarRating($h['avgQuestRating']); ?>
                                                                <span class="ms-1"><?= number_format($h['avgQuestRating'], 2); ?></span>
                                                            </td>
                                                        </tr>
                                                   <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Quest Reviews</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="reviewModalBanner" class="img-fluid mb-3" src="" alt="">
                    <div id="reviewModalBody"></div>
                </div>
            </div>
        </div>
    </div>

    <?php require("php-components/base-page-footer.php"); ?>
</main>
<?php require("php-components/base-page-javascript.php"); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
<script>
const questTitles = <?= json_encode($questTitles); ?>;
const avgHostRatings = <?= json_encode($avgHostRatings); ?>;
const avgQuestRatings = <?= json_encode($avgQuestRatings); ?>;
const participantCounts = <?= json_encode($participantCounts); ?>;
const participantQuestTitles = <?= json_encode($participantQuestTitles); ?>;
const ratingDates = <?= json_encode($ratingDates); ?>;
const avgRatingsOverTime = <?= json_encode($avgRatingsOverTime); ?>;

function renderStarRatingJs(rating) {
    const rounded = Math.round(rating * 2) / 2;
    let stars = '<span class="star-rating" style="pointer-events: none; display: inline-block;">';
    for (let i = 1; i <= 5; i++) {
        let cls;
        if (rounded >= i) {
            cls = 'fa-solid fa-star selected';
        } else if (rounded >= i - 0.5) {
            cls = 'fa-solid fa-star-half-stroke selected';
        } else {
            cls = 'fa-regular fa-star';
        }
        stars += `<i class="${cls}"></i>`;
    }
    return stars + '</span>';
}

$(document).ready(function () {
    const sessionToken = "<?= $_SESSION['sessionToken']; ?>";

    let participationCounts = {};
    let eventConflicts = {};
    let weekdayChart;
    let calMonth = (new Date()).getMonth();
    let calYear = (new Date()).getFullYear();

    function renderScheduleCalendar() {
        const first = new Date(calYear, calMonth, 1);
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        let header = '<thead><tr>' + days.map(d => `<th>${d}</th>`).join('') + '</tr></thead>';
        const values = Object.values(participationCounts);
        const max = values.length ? Math.max(...values) : 0;
        let body = '<tbody><tr>';
        for (let i = 0; i < first.getDay(); i++) { body += '<td></td>'; }
        let date = new Date(first);
        while (date.getMonth() === calMonth) {
            const dStr = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
            const count = participationCounts[dStr] || 0;
            const conflicts = eventConflicts[dStr] || [];
            let cls = '';
            if (count > 0 && max > 0) {
                const ratio = count / max;
                if (ratio > 0.66) { cls = 'bg-success text-white'; }
                else if (ratio > 0.33) { cls = 'bg-warning'; }
                else { cls = 'bg-danger text-white'; }
            }
            let tooltip = '';
            if (conflicts.length > 0) {
                cls = 'bg-secondary text-muted';
                const title = conflicts.map(c => c.title || c).join('<br>');
                tooltip = `data-bs-toggle="tooltip" data-bs-html="true" title="${title.replace(/"/g, '&quot;')}"`;
            } else {
                cls += ' border border-success border-2';
            }
            body += `<td class="${cls}" ${tooltip}><div>${date.getDate()}</div>${count ? `<small>${count}</small>` : ''}</td>`;
            if (date.getDay() === 6) { body += '</tr><tr>'; }
            date.setDate(date.getDate()+1);
        }
        const lastDay = new Date(calYear, calMonth + 1, 0);
        for (let i = lastDay.getDay(); i < 6; i++) { body += '<td></td>'; }
        body += '</tr></tbody>';
        $('#scheduleCalendarMonth').text(first.toLocaleString('default', { month: 'long', year: 'numeric' }));
        $('#scheduleCalendar').html(header + body);
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
    }

    function loadParticipationByDate() {
        $.post('/api/v1/quest/participationByDate.php', { sessionToken: sessionToken }, function(resp) {
            if (resp.success) {
                participationCounts = {};
                resp.data.forEach(function(r) { participationCounts[r.date] = r.participants; });
                renderScheduleCalendar();
            }
        });
    }

    function loadWeekdayAverages() {
        $.post('/api/v1/quest/participationAveragesByWeekday.php', { sessionToken: sessionToken }, function(resp) {
            if (resp.success) {
                const labels = resp.data.map(function(r) { return r.weekday; });
                const data = resp.data.map(function(r) { return r.avgParticipants; });
                const ctx = document.getElementById('weekdayAveragesChart').getContext('2d');
                if (weekdayChart) { weekdayChart.destroy(); }
                weekdayChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Avg Participants',
                            data: data,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)'
                        }]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: { beginAtZero: true, precision: 0 }
                            }]
                        }
                    }
                });
            }
        });
    }

    function loadCalendarEvents() {
        $.post('/api/v1/schedule/events.php', { sessionToken: sessionToken, month: calMonth + 1, year: calYear }, function(resp) {
            if (resp.success) {
                eventConflicts = {};
                resp.data.forEach(function(e) {
                    const d = e.start_date.substring(0,10);
                    if (!eventConflicts[d]) { eventConflicts[d] = []; }
                    eventConflicts[d].push(e);
                });
                renderScheduleCalendar();
            }
        });
    }

    $('#scheduleNext').on('click', function() {
        if (calMonth === 11) { calMonth = 0; calYear++; } else { calMonth++; }
        renderScheduleCalendar();
        loadCalendarEvents();
    });
    $('#schedulePrev').on('click', function() {
        if (calMonth === 0) { calMonth = 11; calYear--; } else { calMonth--; }
        renderScheduleCalendar();
        loadCalendarEvents();
    });

    loadParticipationByDate();
    loadCalendarEvents();
    loadWeekdayAverages();

    $('#datatable-reviews').DataTable({
        pageLength: 10,
        lengthChange: true,
        columnDefs: [{ targets: [4], orderable: false }],
        order: [[1, 'desc']]
    });

    function updateParticipantTable() {
        const sortKey = $('#participantSort').val();
        const relMin = parseFloat($('#reliabilityFilter').val()) || 0;
        const hostedMin = parseFloat($('#hostedFilter').val()) || 0;
        const networkMin = parseFloat($('#networkFilter').val()) || 0;
        const rows = $('#topParticipantsTable tbody tr').get();

        rows.forEach(row => {
            const rel = parseFloat($(row).data('reliability')) * 100;
            const hosted = parseFloat($(row).data('questshosted'));
            const network = parseFloat($(row).data('network'));
            if (rel >= relMin && hosted >= hostedMin && network >= networkMin) {
                $(row).show();
            } else {
                $(row).hide();
            }
        });

        rows.sort((a, b) => {
            const aVal = parseFloat($(a).data(sortKey));
            const bVal = parseFloat($(b).data(sortKey));
            return bVal - aVal;
        });
        $.each(rows, (idx, row) => $('#topParticipantsTable tbody').append(row));
    }

    $('#participantSort, #reliabilityFilter, #hostedFilter, #networkFilter').on('input change', updateParticipantTable);
    updateParticipantTable();

    $(document).on('click', '.view-reviews-btn', function () {
        const questId = $(this).data('quest-id');
        const title = $(this).data('quest-title');
        const banner = $(this).data('quest-banner');
        $('#reviewModalLabel').text(title + ' Reviews');
        $('#reviewModalBanner').attr('src', banner);
        $('#reviewModalBody').html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i></div>');
        $('#reviewModal').modal('show');

        $.post('/api/v1/quest/reviews.php', { questId: questId, sessionToken: sessionToken }, function (resp) {
            if (resp.success) {
                const list = $('<div class="list-group"></div>');
                resp.data.forEach(function (r) {
                    const item = $('<div class="list-group-item d-flex align-items-start"></div>');
                    const img = $('<img class="rounded me-3" style="width:40px;height:40px;">').attr('src', r.avatar);
                    const body = $('<div class="flex-grow-1"></div>');
                    body.append(`<div><a href="/u/${r.username}" class="username" target="_blank">${r.username}</a></div>`);
                    if (r.hostRating !== null) {
                        body.append('Host Rating: ' + renderStarRatingJs(r.hostRating) + '<br>');
                        body.append('Quest Rating: ' + renderStarRatingJs(r.questRating) + '<br>');
                        body.append(`<small>${r.message}</small>`);
                    } else {
                        body.append('<em>Review pending</em>');
                    }
                    item.append(img).append(body);
                    list.append(item);
                });
                $('#reviewModalBody').html(list);
            } else {
                $('#reviewModalBody').html('<div class="text-danger">' + resp.message + '</div>');
            }
        }, 'json');
    });

    var reviewCtx = document.getElementById('reviewChart').getContext('2d');
    new Chart(reviewCtx, {
        type: 'bar',
        data: {
            labels: questTitles,
            datasets: [
                {
                    label: 'Avg Host Rating',
                    data: avgHostRatings,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)'
                },
                {
                    label: 'Avg Quest Rating',
                    data: avgQuestRatings,
                    backgroundColor: 'rgba(153, 102, 255, 0.6)'
                }
            ]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        max: 5
                    }
                }]
            }
        }
    });

    var ratingOverTimeCtx = document.getElementById('ratingOverTimeChart').getContext('2d');
    new Chart(ratingOverTimeCtx, {
        type: 'line',
        data: {
            labels: ratingDates,
            datasets: [{
                label: 'Avg Quest Rating',
                data: avgRatingsOverTime,
                fill: false,
                borderColor: 'rgba(255, 206, 86, 1)',
                tension: 0.1
            }]
        },
        options: {
            scales: {
                xAxes: [{
                    type: 'time',
                    time: {
                        parser: 'YYYY-MM-DD',
                        unit: 'day',
                        displayFormats: {
                            day: 'MMM D'
                        }
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        max: 5
                    }
                }]
            }
        }
    });

    var perQuestCtx = document.getElementById('participantPerQuestChart').getContext('2d');
    new Chart(perQuestCtx, {
        type: 'bar',
        data: {
            labels: participantQuestTitles,
            datasets: [{
                label: 'Participants',
                data: participantCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        precision: 0
                    }
                }]
            }
        }
    });
});
</script>
</body>
</html>

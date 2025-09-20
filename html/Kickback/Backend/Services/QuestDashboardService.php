<?php
declare(strict_types=1);

namespace Kickback\Backend\Services;

use Kickback\Backend\Controllers\FeedCardController;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\QuestLineController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vFeedCard;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Views\vQuestReviewSummary;

/**
 * Helper responsible for collecting all data needed to render the quest giver dashboard.
 */
class QuestDashboardService
{
    public const LOYAL_PARTICIPANT_THRESHOLD = 3;
    public const WEIGHT_RATING = 0.7;
    public const WEIGHT_LOYALTY = 0.3;
    public const CO_HOST_SUGGESTION_COUNT = 5;

    /**
     * Builds the full dashboard dataset for the provided quest giver.
     *
     * The returned array contains both the raw objects used by the legacy PHP templates and
     * structured aggregates that can be serialized for API consumers. Keys include:
     *
     * - `overview`: quest counts, participation totals and recent ratings.
     * - `reviews`: average review summaries and chart series.
     * - `suggestions`: highlighted quests and co-host recommendations.
     * - `questLines`: quest line rollups with status counters and scheduling hints.
     * - `top`: top participant/co-host leaderboards.
     * - `raw`: low-level collections (future/past quests, review summaries, chart labels) for templates.
     *
     * @return array{
     *     overview: array{
     *         totals: array{hosted:int, future:int, past:int},
     *         participants: array{unique:int},
     *         ratings: array{recentHost:float,recentQuest:float}
     *     },
     *     reviews: array{
     *         summaries:list<vQuestReviewSummary>,
     *         chart: array{
     *             questTitles:list<string>,
     *             avgHostRatings:list<float>,
     *             avgQuestRatings:list<float>,
     *             participantQuestTitles:list<string>,
     *             participantCounts:list<int>,
     *             ratingDates:list<string>,
     *             avgRatingsOverTime:list<float>
     *         }
     *     },
     *     suggestions: array{
     *         recommendedQuest:?array<string,mixed>,
     *         dormantQuest:?array<string,mixed>,
     *         fanFavoriteQuest:?array<string,mixed>,
     *         hiddenGemQuest:?array<string,mixed>,
     *         underperformingQuest:?array<string,mixed>,
     *         coHostCandidates:list<array<string,mixed>>,
     *         coHostStats:list<array<string,mixed>>
     *     },
     *     questLines: array{
     *         statusCounts:array<string,int>,
     *         lines:list<array<string,mixed>>,
     *         error:?string
     *     },
     *     top: array{
     *         quests:list<array<string,mixed>>,
     *         participants:list<array<string,mixed>>,
     *         coHosts:list<array<string,mixed>>
     *     },
     *     raw: array{
     *         futureQuests:list<vQuest>,
     *         pastQuests:list<vQuest>,
     *         reviewSummaries:list<vQuestReviewSummary>
     *     }
     * }
     */
    public function buildDashboard(vAccount $account): array
    {
        $questLinesResp = QuestLineController::getMyQuestLines($account, false);
        $questLines = $questLinesResp->success && is_array($questLinesResp->data)
            ? $questLinesResp->data
            : [];
        $questLinesError = $questLinesResp->success ? null : $questLinesResp->message;

        $questLineStatusCounts = [
            'total' => count($questLines),
            'published' => 0,
            'inReview' => 0,
            'draft' => 0,
            'withUpcoming' => 0,
            'needingScheduling' => 0,
            'withoutQuests' => 0,
        ];

        $questLineStats = [];
        foreach ($questLines as $questLine) {
            if (!$questLine instanceof vQuestLine) {
                continue;
            }

            if ($questLine->reviewStatus->published) {
                $questLineStatusCounts['published']++;
            } elseif ($questLine->reviewStatus->beingReviewed) {
                $questLineStatusCounts['inReview']++;
            } else {
                $questLineStatusCounts['draft']++;
            }

            $questLineStats[$questLine->crand] = [
                'questLine' => $questLine,
                'questCount' => 0,
                'futureCount' => 0,
                'pastCount' => 0,
                'publishedQuests' => 0,
                'inReviewQuests' => 0,
                'draftQuests' => 0,
                'avgQuestRatingSum' => 0.0,
                'avgQuestRatingCount' => 0,
                'avgHostRatingSum' => 0.0,
                'avgHostRatingCount' => 0,
                'participantsTotal' => 0,
                'registeredTotal' => 0,
                'nextRun' => null,
                'nextRunTimestamp' => null,
                'lastRun' => null,
                'lastRunTimestamp' => null,
            ];
        }

        $futureResp = QuestController::queryHostedFutureQuests($account);
        $futureQuests = $futureResp->success && is_array($futureResp->data) ? $futureResp->data : [];

        $pastResp = QuestController::queryHostedPastQuests($account);
        $pastQuests = $pastResp->success && is_array($pastResp->data) ? $pastResp->data : [];

        $reviewsResp = QuestController::queryQuestReviewsByHostAsResponse($account);
        $questReviewAverages = $reviewsResp->success && is_array($reviewsResp->data)
            ? $reviewsResp->data
            : [];
        usort($questReviewAverages, static fn($a, $b) => strtotime($a->questEndDate) <=> strtotime($b->questEndDate));

        $totalHostedQuests = count($futureQuests) + count($pastQuests);
        $questTitles = array_map(static fn($qr) => $qr->questTitle, $questReviewAverages);
        $avgHostRatings = array_map(static fn($qr) => (float)$qr->avgHostRating, $questReviewAverages);
        $avgQuestRatings = array_map(static fn($qr) => (float)$qr->avgQuestRating, $questReviewAverages);

        $questRatingsMap = [];
        $hostRatingsMap = [];
        foreach ($questReviewAverages as $qr) {
            if (!$qr instanceof vQuestReviewSummary) {
                continue;
            }
            $qid = $qr->questId;
            $questRatingsMap[$qid] = (float)$qr->avgQuestRating;
            $hostRatingsMap[$qid] = (float)$qr->avgHostRating;
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
        usort(
            $allQuests,
            static fn($a, $b) => strcmp(
                $a->hasEndDate() ? $a->endDate()->formattedYmd : '',
                $b->hasEndDate() ? $b->endDate()->formattedYmd : ''
            )
        );
        $questIds = array_map(static fn($q) => $q->crand, $allQuests);
        $applicantsByQuest = QuestController::queryQuestApplicantsForQuests($questIds);
        if (!is_array($applicantsByQuest)) {
            $applicantsByQuest = [];
        }

        foreach ($allQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
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
                static fn($app) => $app->participated
            );
            $count = count($participants);
            $perQuestRegisteredCounts[$qid] = count($registrations);
            $participantCounts[] = $count;
            $participantQuestTitles[] = $quest->title;
            $perQuestParticipantCounts[$qid] = $count;
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
            if (!$quest instanceof vQuest) {
                continue;
            }
            $qid = $quest->crand;
            $ids = $perQuestParticipantIds[$qid] ?? [];
            $unique = count($ids);
            $loyal = count(array_filter($ids, static fn($id) => ($participantTotals[$id] ?? 0) > 1));
            $perPastQuestParticipantStats[] = [
                'quest' => $quest,
                'unique' => $unique,
                'loyal' => $loyal,
                'score' => $unique * $loyal,
            ];
        }
        usort($perPastQuestParticipantStats, static fn($a, $b) => $b['score'] <=> $a['score']);
        $recommendedQuest = null;
        if (!empty($perPastQuestParticipantStats)) {
            $top = $perPastQuestParticipantStats[0];
            $topQuest = $top['quest'];
            if ($topQuest instanceof vQuest) {
                $topQuestId = $topQuest->crand;
                $recommendedEndDate = $topQuest->hasEndDate() ? $topQuest->endDate() : null;
                $recommendedQuest = [
                    'title' => $topQuest->title,
                    'locator' => $topQuest->locator,
                    'icon' => $topQuest->icon ? $topQuest->icon->getFullPath() : '',
                    'unique' => $top['unique'],
                    'loyal' => $top['loyal'],
                    'registered' => $perQuestRegisteredCounts[$topQuestId] ?? 0,
                    'avgQuestRating' => $questRatingsMap[$topQuestId] ?? 0.0,
                    'avgHostRating' => $hostRatingsMap[$topQuestId] ?? 0.0,
                    'endDate' => $recommendedEndDate,
                    'endDateFormatted' => $recommendedEndDate ? $recommendedEndDate->formattedBasic : null,
                    'id' => $topQuestId,
                    'banner' => $topQuest->banner ? $topQuest->banner->getFullPath() : '',
                ];
                $recommendedQuest['questRatingDesc'] = self::describeRating((float)$recommendedQuest['avgQuestRating']);
                $recommendedQuest['hostRatingDesc'] = self::describeRating((float)$recommendedQuest['avgHostRating']);
                $recommendedQuest['followupPrompt'] = self::followupPrompt(
                    (float)$recommendedQuest['avgQuestRating'],
                    (float)$recommendedQuest['avgHostRating'],
                    (int)$recommendedQuest['registered'],
                    (int)$recommendedQuest['unique'],
                    (int)$recommendedQuest['loyal']
                );
            }
        }

        $pastQuestIds = array_map(static fn($q) => $q->crand, $pastQuests);
        $reviewDetailsByQuest = QuestController::queryQuestReviewDetailsForQuests($pastQuestIds);
        if (!is_array($reviewDetailsByQuest)) {
            $reviewDetailsByQuest = [];
        }

        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            foreach ($reviewDetailsByQuest[$quest->crand] ?? [] as $detail) {
                $id = $detail->accountId;
                if ($id === $account->crand) {
                    continue;
                }
                if ($detail->hostRating !== null) {
                    $participantHostRatingSums[$id] = ($participantHostRatingSums[$id] ?? 0) + $detail->hostRating;
                    $participantHostRatingCounts[$id] = ($participantHostRatingCounts[$id] ?? 0) + 1;
                }
                if ($detail->questRating !== null) {
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
                'avgHostRating' => $participantAvgHostRatings[$crand] ?? 0.0,
                'avgQuestRating' => $participantAvgQuestRatings[$crand] ?? 0.0,
            ];
            if (count($topParticipants) >= 10) {
                break;
            }
        }
        $topParticipantIds = array_map(static fn($p) => $p['id'], $topParticipants);
        $hostingStats = QuestController::queryHostStatsForAccounts($topParticipantIds);
        if (!is_array($hostingStats)) {
            $hostingStats = [];
        }

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
            $p['reliability'] = $registered > 0 ? $attended / $registered : 0.0;
            $hostStat = $hostingStats[$p['id']] ?? ['questsHosted' => 0, 'avgHostRating' => 0.0, 'avgQuestRating' => 0.0];
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
        usort($sortedCandidates, static fn($a, $b) => $b['score'] <=> $a['score']);
        $coHostCandidates = array_slice($sortedCandidates, 0, self::CO_HOST_SUGGESTION_COUNT);

        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            $qid = $quest->crand;
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
                    'hostRatingSum' => 0.0,
                    'hostRatingCount' => 0,
                    'questRatingSum' => 0.0,
                    'questRatingCount' => 0,
                ];
            }

            $stats =& $coHostStats[$id];
            $stats['questCount']++;
            $stats['participantSum'] += $perQuestParticipantCounts[$qid] ?? 0;
            foreach ($perQuestParticipantIds[$qid] ?? [] as $pid) {
                $stats['uniqueParticipantIds'][$pid] = true;
            }
            foreach ($reviewDetailsByQuest[$qid] ?? [] as $detail) {
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
        foreach ($coHostStats as &$stats) {
            $participantsTotal = max(1, $stats['questCount']);
            $stats['avgParticipants'] = $stats['questCount'] > 0
                ? $stats['participantSum'] / $stats['questCount']
                : 0.0;
            $stats['uniqueParticipants'] = count($stats['uniqueParticipantIds']);
            unset($stats['uniqueParticipantIds']);
            $stats['avgHostRating'] = $stats['hostRatingCount'] > 0
                ? $stats['hostRatingSum'] / $stats['hostRatingCount']
                : 0.0;
            $stats['avgQuestRating'] = $stats['questRatingCount'] > 0
                ? $stats['questRatingSum'] / $stats['questRatingCount']
                : 0.0;
            $stats['hostRatingCount'] = $participantsTotal;
        }
        unset($stats);
        usort($coHostCandidates, static fn($a, $b) => $b['score'] <=> $a['score']);

        $questLinesWithUpcoming = 0;
        $questLinesNeedingScheduling = 0;
        $questLinesWithoutQuests = 0;

        foreach ($futureQuests as $quest) {
            if (!$quest instanceof vQuest || !$quest->hasQuestLine()) {
                continue;
            }
            $lineId = $quest->questLine->crand;
            if (!isset($questLineStats[$lineId])) {
                continue;
            }
            $stats =& $questLineStats[$lineId];
            $stats['questCount']++;
            $stats['futureCount']++;
            if ($quest->reviewStatus->published) {
                $stats['publishedQuests']++;
            } elseif ($quest->reviewStatus->beingReviewed) {
                $stats['inReviewQuests']++;
            } else {
                $stats['draftQuests']++;
            }
            $qid = $quest->crand;
            $stats['participantsTotal'] += $perQuestParticipantCounts[$qid] ?? 0;
            $stats['registeredTotal'] += $perQuestRegisteredCounts[$qid] ?? 0;
            if ($quest->hasEndDate()) {
                $endDateObj = $quest->endDate();
                $timestamp = $endDateObj->value->getTimestamp();
                if ($stats['nextRunTimestamp'] === null || $timestamp < $stats['nextRunTimestamp']) {
                    $stats['nextRunTimestamp'] = $timestamp;
                    $stats['nextRun'] = $endDateObj;
                }
            }
        }

        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest || !$quest->hasQuestLine()) {
                continue;
            }
            $lineId = $quest->questLine->crand;
            if (!isset($questLineStats[$lineId])) {
                continue;
            }
            $stats =& $questLineStats[$lineId];
            $stats['questCount']++;
            $stats['pastCount']++;
            if ($quest->reviewStatus->published) {
                $stats['publishedQuests']++;
            } elseif ($quest->reviewStatus->beingReviewed) {
                $stats['inReviewQuests']++;
            } else {
                $stats['draftQuests']++;
            }
            $qid = $quest->crand;
            $stats['participantsTotal'] += $perQuestParticipantCounts[$qid] ?? 0;
            $stats['registeredTotal'] += $perQuestRegisteredCounts[$qid] ?? 0;
            if (isset($questRatingsMap[$qid])) {
                $stats['avgQuestRatingSum'] += $questRatingsMap[$qid];
                $stats['avgQuestRatingCount']++;
            }
            if (isset($hostRatingsMap[$qid])) {
                $stats['avgHostRatingSum'] += $hostRatingsMap[$qid];
                $stats['avgHostRatingCount']++;
            }
            if ($quest->hasEndDate()) {
                $endDateObj = $quest->endDate();
                $timestamp = $endDateObj->value->getTimestamp();
                if ($stats['lastRunTimestamp'] === null || $timestamp > $stats['lastRunTimestamp']) {
                    $stats['lastRunTimestamp'] = $timestamp;
                    $stats['lastRun'] = $endDateObj;
                }
            }
        }
        unset($stats);

        foreach ($questLineStats as &$lineStats) {
            $lineStats['avgQuestRating'] = $lineStats['avgQuestRatingCount'] > 0
                ? $lineStats['avgQuestRatingSum'] / $lineStats['avgQuestRatingCount']
                : null;
            $lineStats['avgHostRating'] = $lineStats['avgHostRatingCount'] > 0
                ? $lineStats['avgHostRatingSum'] / $lineStats['avgHostRatingCount']
                : null;
            $lineStats['attendanceRate'] = $lineStats['registeredTotal'] > 0
                ? $lineStats['participantsTotal'] / $lineStats['registeredTotal']
                : null;

            if ($lineStats['futureCount'] > 0) {
                $questLinesWithUpcoming++;
            }
            if ($lineStats['questCount'] === 0) {
                $questLinesWithoutQuests++;
            }
            if (
                $lineStats['questLine']->reviewStatus->published &&
                $lineStats['futureCount'] === 0 &&
                $lineStats['questCount'] > 0
            ) {
                $questLinesNeedingScheduling++;
            }
        }
        unset($lineStats);

        $questLineStatusCounts['withUpcoming'] = $questLinesWithUpcoming;
        $questLineStatusCounts['needingScheduling'] = $questLinesNeedingScheduling;
        $questLineStatusCounts['withoutQuests'] = $questLinesWithoutQuests;

        $questLineStatsList = array_values($questLineStats);
        usort($questLineStatsList, static function (array $a, array $b): int {
            $aHasNext = isset($a['nextRunTimestamp']) && $a['nextRunTimestamp'] !== null;
            $bHasNext = isset($b['nextRunTimestamp']) && $b['nextRunTimestamp'] !== null;
            if ($aHasNext && $bHasNext) {
                if ($a['nextRunTimestamp'] === $b['nextRunTimestamp']) {
                    return strcasecmp($a['questLine']->title, $b['questLine']->title);
                }
                return $a['nextRunTimestamp'] <=> $b['nextRunTimestamp'];
            }
            if ($aHasNext !== $bHasNext) {
                return $aHasNext ? -1 : 1;
            }
            $aHasLast = isset($a['lastRunTimestamp']) && $a['lastRunTimestamp'] !== null;
            $bHasLast = isset($b['lastRunTimestamp']) && $b['lastRunTimestamp'] !== null;
            if ($aHasLast && $bHasLast) {
                if ($a['lastRunTimestamp'] === $b['lastRunTimestamp']) {
                    return strcasecmp($a['questLine']->title, $b['questLine']->title);
                }
                return $b['lastRunTimestamp'] <=> $a['lastRunTimestamp'];
            }
            if ($aHasLast !== $bHasLast) {
                return $aHasLast ? -1 : 1;
            }
            return strcasecmp($a['questLine']->title, $b['questLine']->title);
        });

        $underperformingQuest = null;
        $underperformingCandidates = [];
        $minParticipants = 10;
        $maxAvgRating = 3;
        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            $title = $quest->title;
            $qid = $quest->crand;
            $participants = $perQuestParticipantCounts[$qid] ?? 0;
            $avgRating = $questRatingsMap[$qid] ?? 0.0;
            $hostRating = $hostRatingsMap[$qid] ?? 0.0;
            if ($participants >= $minParticipants && $avgRating <= $maxAvgRating) {
                $endDateObj = $quest->hasEndDate() ? $quest->endDate() : null;
                $underperformingCandidates[] = [
                    'title' => $title,
                    'locator' => $quest->locator,
                    'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
                    'participants' => $participants,
                    'registered' => $perQuestRegisteredCounts[$qid] ?? 0,
                    'avgQuestRating' => $avgRating,
                    'avgHostRating' => $hostRating,
                    'endDate' => $endDateObj,
                    'endDateFormatted' => $endDateObj ? $endDateObj->formattedBasic : null,
                    'id' => $qid,
                    'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
                ];
            }
        }
        usort($underperformingCandidates, static fn($a, $b) => $b['participants'] <=> $a['participants']);
        if (!empty($underperformingCandidates)) {
            $underperformingQuest = $underperformingCandidates[0];
            $underperformingQuest['questRatingDesc'] = self::describeRating((float)$underperformingQuest['avgQuestRating']);
            $underperformingQuest['hostRatingDesc'] = self::describeRating((float)$underperformingQuest['avgHostRating']);
        }

        $hiddenGemQuest = null;
        $hiddenGemCandidates = [];
        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            $title = $quest->title;
            $qid = $quest->crand;
            $participants = $perQuestParticipantCounts[$qid] ?? 0;
            $avgRating = $questRatingsMap[$qid] ?? 0.0;
            $hostRating = $hostRatingsMap[$qid] ?? 0.0;
            if ($avgRating >= 4 && $participants < $minParticipants) {
                $endDateObj = $quest->hasEndDate() ? $quest->endDate() : null;
                $hiddenGemCandidates[] = [
                    'title' => $title,
                    'locator' => $quest->locator,
                    'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
                    'participants' => $participants,
                    'registered' => $perQuestRegisteredCounts[$qid] ?? 0,
                    'avgQuestRating' => $avgRating,
                    'avgHostRating' => $hostRating,
                    'endDate' => $endDateObj,
                    'endDateFormatted' => $endDateObj ? $endDateObj->formattedBasic : null,
                    'id' => $qid,
                    'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
                ];
            }
        }
        usort($hiddenGemCandidates, static function ($a, $b) {
            if ($b['avgQuestRating'] === $a['avgQuestRating']) {
                return $a['participants'] <=> $b['participants'];
            }
            return $b['avgQuestRating'] <=> $a['avgQuestRating'];
        });
        if (!empty($hiddenGemCandidates)) {
            $hiddenGemQuest = $hiddenGemCandidates[0];
            $hiddenGemQuest['questRatingDesc'] = self::describeRating((float)$hiddenGemQuest['avgQuestRating']);
            $hiddenGemQuest['hostRatingDesc'] = self::describeRating((float)$hiddenGemQuest['avgHostRating']);
        }

        $bestQuestCandidates = [];
        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            $qid = $quest->crand;
            $participants = $perQuestParticipantCounts[$qid] ?? 0;
            $avgQuestRating = $questRatingsMap[$qid] ?? 0.0;
            $avgHostRating = $hostRatingsMap[$qid] ?? 0.0;
            $bestQuestCandidates[] = [
                'title' => $quest->title,
                'locator' => $quest->locator,
                'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
                'id' => $qid,
                'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
                'participants' => $participants,
                'avgQuestRating' => $avgQuestRating,
                'avgHostRating' => $avgHostRating,
                'score' => $participants * max($avgQuestRating, 1.0),
            ];
        }
        usort($bestQuestCandidates, static fn($a, $b) => $b['score'] <=> $a['score']);
        $topBestQuests = array_slice($bestQuestCandidates, 0, 10);

        $ratingData = [];
        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            $qid = $quest->crand;
            if (isset($questRatingsMap[$qid]) && $quest->hasEndDate()) {
                $ratingData[] = [
                    'date' => $quest->endDate()->formattedYmd,
                    'rating' => $questRatingsMap[$qid],
                ];
            }
        }
        usort($ratingData, static fn($a, $b) => strcmp($a['date'], $b['date']));
        $ratingDates = array_column($ratingData, 'date');
        $avgRatingsOverTime = array_column($ratingData, 'rating');

        $recentReviews = $questReviewAverages;
        usort($recentReviews, static fn($a, $b) => strtotime($b->questEndDate) <=> strtotime($a->questEndDate));
        $recentReviews = array_slice($recentReviews, 0, 10);
        $recentCount = count($recentReviews);
        $avgHostRatingRecent = $recentCount > 0
            ? array_sum(array_map(static fn($qr) => (float)$qr->avgHostRating, $recentReviews)) / $recentCount
            : 0.0;
        $avgQuestRatingRecent = $recentCount > 0
            ? array_sum(array_map(static fn($qr) => (float)$qr->avgQuestRating, $recentReviews)) / $recentCount
            : 0.0;

        $dormantQuest = self::findDormantQuest($pastQuests, $perQuestParticipantCounts, $perQuestRegisteredCounts, $questRatingsMap, $hostRatingsMap);
        $fanFavoriteQuest = self::findFanFavoriteQuest($pastQuests, $perQuestParticipantCounts, $perQuestRegisteredCounts, $questRatingsMap, $hostRatingsMap);

        return [
            'overview' => [
                'totals' => [
                    'hosted' => $totalHostedQuests,
                    'future' => count($futureQuests),
                    'past' => count($pastQuests),
                ],
                'participants' => [
                    'unique' => $totalUniqueParticipants,
                ],
                'ratings' => [
                    'recentHost' => $avgHostRatingRecent,
                    'recentQuest' => $avgQuestRatingRecent,
                ],
            ],
            'reviews' => [
                'summaries' => $questReviewAverages,
                'chart' => [
                    'questTitles' => $questTitles,
                    'avgHostRatings' => $avgHostRatings,
                    'avgQuestRatings' => $avgQuestRatings,
                    'participantQuestTitles' => $participantQuestTitles,
                    'participantCounts' => $participantCounts,
                    'ratingDates' => $ratingDates,
                    'avgRatingsOverTime' => $avgRatingsOverTime,
                ],
            ],
            'suggestions' => [
                'recommendedQuest' => $recommendedQuest,
                'dormantQuest' => $dormantQuest,
                'fanFavoriteQuest' => $fanFavoriteQuest,
                'hiddenGemQuest' => $hiddenGemQuest,
                'underperformingQuest' => $underperformingQuest,
                'coHostCandidates' => $coHostCandidates,
                'coHostStats' => array_values($coHostStats),
            ],
            'questLines' => [
                'statusCounts' => $questLineStatusCounts,
                'lines' => $questLineStatsList,
                'error' => $questLinesError,
            ],
            'top' => [
                'quests' => $topBestQuests,
                'participants' => $topParticipants,
                'coHosts' => array_values($coHostStats),
            ],
            'raw' => [
                'futureQuests' => $futureQuests,
                'pastQuests' => $pastQuests,
                'reviewSummaries' => $questReviewAverages,
            ],
        ];
    }

    /**
     * Builds a JSON-ready payload for the API based on the same dataset used by the dashboard page.
     *
     * The payload mirrors the `buildDashboard` structure but only contains scalars/arrays that can be
     * safely encoded for API clients.
     *
     * @return array{
     *     overview: array{
     *         totals: array{hosted:int,future:int,past:int},
     *         participants: array{unique:int},
     *         ratings: array{recentHost:float,recentQuest:float}
     *     },
     *     upcoming: list<array<string,mixed>>,
     *     reviews: array{
     *         summaries:list<array<string,mixed>>,
     *         chart: array<string,list<mixed>>
     *     },
     *     suggestions: array{
     *         recommendedQuest:?array<string,mixed>,
     *         dormantQuest:?array<string,mixed>,
     *         fanFavoriteQuest:?array<string,mixed>,
     *         hiddenGemQuest:?array<string,mixed>,
     *         underperformingQuest:?array<string,mixed>,
     *         coHostCandidates:list<array<string,mixed>>,
     *         coHostStats:list<array<string,mixed>>
     *     },
     *     questLines: array{
     *         statusCounts:array<string,int>,
     *         lines:list<array<string,mixed>>,
     *         error:?string
     *     },
     *     top: array{
     *         quests:list<array<string,mixed>>,
     *         participants:list<array<string,mixed>>,
     *         coHosts:list<array<string,mixed>>
     *     }
     * }
     */
    public function buildApiPayload(vAccount $account): array
    {
        $data = $this->buildDashboard($account);
        $raw = $data['raw'];

        $upcoming = array_map(function (vQuest $quest): array {
            $card = FeedCardController::vQuest_to_vFeedCard($quest);
            $cardData = self::feedCardToArray($card);
            $cardData['questId'] = $quest->crand;
            $cardData['questLocator'] = $quest->locator;
            return $cardData;
        }, $raw['futureQuests']);

        $reviewSummaries = array_map(
            static function (vQuestReviewSummary $summary): array {
                $endDate = new vDateTime($summary->questEndDate);
                return [
                    'questId' => $summary->questId,
                    'questLocator' => $summary->questLocator,
                    'questTitle' => $summary->questTitle,
                    'questEndDate' => self::formatDateTime($endDate),
                    'questIcon' => $summary->questIcon,
                    'questBanner' => $summary->questBanner,
                    'avgHostRating' => $summary->avgHostRating,
                    'avgQuestRating' => $summary->avgQuestRating,
                    'hasComments' => $summary->hasComments,
                ];
            },
            $data['reviews']['summaries']
        );

        $questLines = array_map(
            static function (array $stats): array {
                /** @var vQuestLine $line */
                $line = $stats['questLine'];
                return [
                    'id' => $line->crand,
                    'title' => $line->title,
                    'summary' => $line->summary,
                    'reviewStatus' => [
                        'published' => $line->reviewStatus->published,
                        'beingReviewed' => $line->reviewStatus->beingReviewed,
                        'draft' => $line->reviewStatus->isDraft(),
                    ],
                    'counts' => [
                        'quests' => $stats['questCount'],
                        'future' => $stats['futureCount'],
                        'past' => $stats['pastCount'],
                        'published' => $stats['publishedQuests'],
                        'inReview' => $stats['inReviewQuests'],
                        'draft' => $stats['draftQuests'],
                    ],
                    'nextRun' => self::formatDateTime($stats['nextRun'] ?? null),
                    'lastRun' => self::formatDateTime($stats['lastRun'] ?? null),
                    'avgQuestRating' => $stats['avgQuestRating'],
                    'avgHostRating' => $stats['avgHostRating'],
                    'attendanceRate' => $stats['attendanceRate'],
                ];
            },
            $data['questLines']['lines']
        );

        return [
            'overview' => $data['overview'],
            'upcoming' => $upcoming,
            'reviews' => [
                'summaries' => $reviewSummaries,
                'chart' => $data['reviews']['chart'],
            ],
            'suggestions' => $data['suggestions'],
            'questLines' => [
                'statusCounts' => $data['questLines']['statusCounts'],
                'lines' => $questLines,
                'error' => $data['questLines']['error'],
            ],
            'top' => $data['top'],
        ];
    }

    public static function generateBringBackSuggestion(array $quest): string
    {
        $parts = [];
        $parts[] = "Players gave {$quest['questRatingDesc']} marks of " . number_format((float)$quest['avgQuestRating'], 1) . "/5 for the quest and {$quest['hostRatingDesc']} feedback of " . number_format((float)$quest['avgHostRating'], 1) . "/5 for your hosting";
        if (($quest['participants'] ?? 0) > 0) {
            $parts[] = "with {$quest['participants']} adventurer" . ($quest['participants'] === 1 ? '' : 's') . " taking part";
        }
        if (isset($quest['loyal']) && $quest['loyal'] > 0) {
            $parts[] = "including {$quest['loyal']} returning player" . ($quest['loyal'] === 1 ? '' : 's');
        }
        $lastRun = $quest['endDateFormatted'] ?? null;
        if (!empty($lastRun)) {
            $parts[] = "but it hasn't been offered since " . htmlspecialchars((string)$lastRun) . ".";
        } else {
            $parts[] = "and its last run date is still TBD—putting it back on the calendar could re-engage fans.";
        }
        $parts[] = "Reviving it could re-engage fans—consider adding new twists or rewards to keep it fresh.";
        return implode(' ', $parts);
    }

    public static function generateSequelSuggestion(array $quest): string
    {
        $parts = [];
        $parts[] = "Loyal adventurers gave {$quest['questRatingDesc']} marks of " . number_format((float)$quest['avgQuestRating'], 1) . "/5 for the quest and {$quest['hostRatingDesc']} ratings of " . number_format((float)$quest['avgHostRating'], 1) . "/5 for your hosting.";
        if (isset($quest['loyal'])) {
            $lastRun = $quest['endDateFormatted'] ?? null;
            $playerLabel = "{$quest['loyal']} returning player" . ($quest['loyal'] === 1 ? '' : 's');
            if (!empty($lastRun)) {
                $parts[] = $playerLabel . " joined its last run on " . htmlspecialchars((string)$lastRun) . ".";
            } else {
                $verb = $quest['loyal'] === 1 ? 'is' : 'are';
                $parts[] = $playerLabel . " {$verb} ready for the next run once it's scheduled.";
            }
        }
        $parts[] = "A sequel would be well received—build on its strengths and address any feedback to keep the saga fresh.";
        return implode(' ', $parts);
    }

    public static function generateSimilarQuestSuggestion(array $quest): string
    {
        $parts = [];
        $parts[] = "Out of {$quest['registered']} sign-ups, {$quest['unique']} adventurer" . ($quest['unique'] === 1 ? '' : 's') . " joined";
        if (($quest['loyal'] ?? 0) > 0) {
            $parts[] = "including {$quest['loyal']} loyal player" . ($quest['loyal'] === 1 ? '' : 's');
        }
        $parts[] = "The quest earned {$quest['questRatingDesc']} feedback at " . number_format((float)$quest['avgQuestRating'], 1) . "/5 and your hosting received {$quest['hostRatingDesc']} marks at " . number_format((float)$quest['avgHostRating'], 1) . "/5.";
        if (isset($quest['followupPrompt'])) {
            $parts[] = $quest['followupPrompt'];
        }
        return implode(' ', $parts);
    }

    public static function generateImproveQuestSuggestion(array $quest): string
    {
        $parts = [];
        $parts[] = "Despite {$quest['participants']} of {$quest['registered']} registered adventurer" . ($quest['registered'] === 1 ? '' : 's') . " taking part, players gave {$quest['questRatingDesc']} ratings of " . number_format((float)$quest['avgQuestRating'], 1) . "/5 and your hosting received {$quest['hostRatingDesc']} marks of " . number_format((float)$quest['avgHostRating'], 1) . "/5.";
        $parts[] = "Review the feedback to adjust balance, narrative, or rewards before offering a refined version.";
        return implode(' ', $parts);
    }

    public static function generatePromoteQuestSuggestion(array $quest): string
    {
        $parts = [];
        $parts[] = "Players gave {$quest['questRatingDesc']} marks of " . number_format((float)$quest['avgQuestRating'], 1) . "/5 for the quest and {$quest['hostRatingDesc']} ratings of " . number_format((float)$quest['avgHostRating'], 1) . "/5 for your hosting, yet only {$quest['participants']} adventurer" . ($quest['participants'] === 1 ? '' : 's') . " joined.";
        $parts[] = "Highlight those strong reviews and spread the word across guild halls and socials to draw more participants.";
        return implode(' ', $parts);
    }

    public static function generateCoHostSuggestion(array $candidate): string
    {
        $parts = [];
        $parts[] = $candidate['username'] . ' has joined you on ' . $candidate['loyalty'] . ' quest' . ($candidate['loyalty'] === 1 ? '' : 's') . ',';
        $parts[] = 'showing up for ' . number_format($candidate['reliability'] * 100, 0) . '% of the quests they register for.';
        $parts[] = 'They\'ve adventured alongside ' . $candidate['network'] . ' other player' . ($candidate['network'] === 1 ? '' : 's') . ', expanding your reach.';
        if (($candidate['questsHosted'] ?? 0) > 0) {
            $parts[] = 'They\'ve hosted ' . $candidate['questsHosted'] . ' quest' . ($candidate['questsHosted'] === 1 ? '' : 's') . ' of their own with average ratings of ' . number_format((float)$candidate['avgHostedQuestRating'], 1) . '/5.';
        }
        if (isset($candidate['daysSinceLastQuest'])) {
            $days = $candidate['daysSinceLastQuest'];
            $parts[] = 'Their last quest with you was ' . $days . ' day' . ($days === 1 ? '' : 's') . ' ago.';
        }
        $parts[] = 'Consider inviting them to co-host your next quest.';
        return implode(' ', $parts);
    }

    private static function describeRating(float $rating): string
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

    private static function followupPrompt(
        float $questRating,
        float $hostRating,
        int $registered,
        int $unique,
        int $loyal
    ): string {
        $messages = [];

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

        $options = [
            'Let these insights guide your next quest.',
            'Use these details to shape future adventures.',
            'Carry these takeaways into your upcoming runs.'
        ];
        $messages[] = $options[array_rand($options)];

        return implode(' ', $messages);
    }

    private static function feedCardToArray(vFeedCard $card): array
    {
        return [
            'type' => $card->type,
            'title' => $card->title(),
            'description' => $card->description,
            'url' => $card->url(),
            'icon' => $card->icon ? $card->icon->getFullPath() : null,
            'dateTime' => self::formatDateTime($card->dateTime),
            'reviewStatus' => $card->reviewStatus ? [
                'published' => $card->reviewStatus->published,
                'beingReviewed' => $card->reviewStatus->beingReviewed,
                'draft' => $card->reviewStatus->isDraft(),
            ] : null,
        ];
    }

    private static function formatDateTime(?vDateTime $dateTime): ?array
    {
        if ($dateTime === null) {
            return null;
        }
        return [
            'formattedBasic' => $dateTime->formattedBasic,
            'formattedDetailed' => $dateTime->formattedDetailed,
            'timestamp' => $dateTime->value->getTimestamp(),
        ];
    }

    private static function findDormantQuest(array $pastQuests, array $perQuestParticipantCounts, array $perQuestRegisteredCounts, array $questRatingsMap, array $hostRatingsMap): ?array
    {
        $dormantQuest = null;
        $dormantCandidates = [];
        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            if (!$quest->hasEndDate()) {
                continue;
            }
            $daysSince = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->getTimestamp() - $quest->endDate()->value->getTimestamp();
            $dayCount = (int) floor($daysSince / 86400);
            if ($dayCount < 60) {
                continue;
            }
            $qid = $quest->crand;
            $dormantCandidates[] = [
                'title' => $quest->title,
                'locator' => $quest->locator,
                'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
                'participants' => $perQuestParticipantCounts[$qid] ?? 0,
                'registered' => $perQuestRegisteredCounts[$qid] ?? 0,
                'avgQuestRating' => $questRatingsMap[$qid] ?? 0.0,
                'avgHostRating' => $hostRatingsMap[$qid] ?? 0.0,
                'endDate' => $quest->endDate(),
                'endDateFormatted' => $quest->endDate()->formattedBasic,
                'id' => $qid,
                'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
                'questRatingDesc' => self::describeRating($questRatingsMap[$qid] ?? 0.0),
                'hostRatingDesc' => self::describeRating($hostRatingsMap[$qid] ?? 0.0),
            ];
        }
        usort($dormantCandidates, static function ($a, $b) {
            $aDate = $a['endDate'] instanceof vDateTime ? $a['endDate']->value->getTimestamp() : 0;
            $bDate = $b['endDate'] instanceof vDateTime ? $b['endDate']->value->getTimestamp() : 0;
            return $aDate <=> $bDate;
        });
        if (!empty($dormantCandidates)) {
            $dormantQuest = $dormantCandidates[0];
        }
        return $dormantQuest;
    }

    private static function findFanFavoriteQuest(array $pastQuests, array $perQuestParticipantCounts, array $perQuestRegisteredCounts, array $questRatingsMap, array $hostRatingsMap): ?array
    {
        $fanFavoriteQuest = null;
        $fanFavoriteCandidates = [];
        foreach ($pastQuests as $quest) {
            if (!$quest instanceof vQuest) {
                continue;
            }
            $qid = $quest->crand;
            $participants = $perQuestParticipantCounts[$qid] ?? 0;
            $avgRating = $questRatingsMap[$qid] ?? 0.0;
            $hostRating = $hostRatingsMap[$qid] ?? 0.0;
            if ($participants >= self::LOYAL_PARTICIPANT_THRESHOLD && $avgRating >= 4.2 && $hostRating >= 4.0) {
                $endDateObj = $quest->hasEndDate() ? $quest->endDate() : null;
                $fanFavoriteCandidates[] = [
                    'title' => $quest->title,
                    'locator' => $quest->locator,
                    'icon' => $quest->icon ? $quest->icon->getFullPath() : '',
                    'participants' => $participants,
                    'registered' => $perQuestRegisteredCounts[$qid] ?? 0,
                    'avgQuestRating' => $avgRating,
                    'avgHostRating' => $hostRating,
                    'endDate' => $endDateObj,
                    'endDateFormatted' => $endDateObj ? $endDateObj->formattedBasic : null,
                    'id' => $qid,
                    'banner' => $quest->banner ? $quest->banner->getFullPath() : '',
                    'loyal' => $participants >= self::LOYAL_PARTICIPANT_THRESHOLD ? $participants - 1 : 0,
                    'questRatingDesc' => self::describeRating($avgRating),
                    'hostRatingDesc' => self::describeRating($hostRating),
                ];
            }
        }
        usort($fanFavoriteCandidates, static function ($a, $b) {
            if ($b['avgQuestRating'] === $a['avgQuestRating']) {
                return $b['participants'] <=> $a['participants'];
            }
            return $b['avgQuestRating'] <=> $a['avgQuestRating'];
        });
        if (!empty($fanFavoriteCandidates)) {
            $fanFavoriteQuest = $fanFavoriteCandidates[0];
        }
        return $fanFavoriteQuest;
    }
}


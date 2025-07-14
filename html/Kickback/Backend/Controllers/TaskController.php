<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Services\Database;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Models\TaskType;
use Kickback\Backend\Models\RecordId;
use Kickback\Backend\Models\TaskDefinitionCode;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Views\vTask;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vRecordId;

class TaskController
{
    /**
     * Converts a DB row into a vTask object.
     */
    public static function row_to_vTask(array $row, vAccount $account, \mysqli $conn): vTask
    {
        $atCtime = $row["ctime"] ?? '';
        $atCrand = (int)($row["crand"] ?? - 1);
        $task = new vTask($atCtime, $atCrand);

        
        $tdCtime = $row["task_ctime"] ?? '';
        $tdCrand = (int)($row["task_crand"] ?? - 1);

        $task->taskDefinitionId = new ForeignRecordId($tdCtime, $tdCrand);
        $task->type        = TaskType::from($row['type']);
        $task->code        = TaskDefinitionCode::from($row['code']);
        $task->title       = $row['title'];
        $task->description = $row['description'];
        $task->goalCount   = isset($row['goal_count']) ? (int)$row['goal_count'] : 1;

        $task->rewardItem  = new vItem('', (int)$row['reward_item_id']);


        // Set the item properties if available
        if (isset($row['item_name'])) {
            $task->rewardItem->name = $row['item_name'];
        }
        if (isset($row['item_description'])) {
            $task->rewardItem->description = $row['item_description'];
        }
        if (isset($row['item_image'])) {
            $task->rewardItem->iconSmall->setMediaPath($row['item_image']);
        }
        if (isset($row['item_large_image'])) {
            $task->rewardItem->iconBig->setMediaPath($row['item_large_image']);
        }

        $task->rewardCount = isset($row['reward_count']) ? (int)$row['reward_count'] : 1;

        $task->calculateExpiration();

        $existingCompleted = (bool)($row['completed'] ?? false);
        $existingClaimed   = (bool)($row['claimed'] ?? false);
        $existingTaskExists = $task->crand > -1;


        $task->isCompleted = $existingCompleted;
        $task->isClaimed   = $existingClaimed;

        if (!$task->isExpired() && !$existingCompleted) {
            $task->progress = self::calculateTaskProgress($task, $account, $conn);

            if ($task->progress >= $task->goalCount) {
                $task->isCompleted = true;
            }

            if (
                $task->progress > 0 &&
                ($task->isCompleted !== $existingCompleted)
            ) {
                self::upsertAccountTaskProgress($task, $account, $conn, $existingTaskExists);
            }
        }

        if ($task->isCompleted)
        {
            $task->progress = $task->goalCount;
        }
        if ($tdCrand < 0)
        {
            throw new \Exception(json_encode($task));
        }
        

        return $task;
    }


    public static function upsertAccountTaskProgress(vTask $task, vAccount $account, \mysqli $conn, bool $existsAlready): void
    {
        $completed = $task->isCompleted ? 1 : 0;

        if ($existsAlready) {
            $stmt = $conn->prepare("
                UPDATE account_tasks
                SET completed = ?, claimed = claimed
                WHERE account_id = ? AND ctime = ? AND crand = ?
            ");
            $stmt->bind_param('iisi', $completed, $account->crand, $task->ctime, $task->crand);
            $stmt->execute();
        } else {
            $insertStmt = $conn->prepare("
                INSERT INTO account_tasks (ctime, crand, account_id, task_ctime, task_crand, completed, claimed)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $ctime = RecordId::getCTime();
            $crand = RecordId::generateCRand();

            $insertStmt->bind_param(
                'siisii',
                $ctime,
                $crand,
                $account->crand,
                $task->taskDefinitionId->ctime,
                $task->taskDefinitionId->crand,
                $completed
            );
            $insertStmt->execute();

            
            $task->ctime = $ctime;
            $task->crand = $crand;
        }
    }



    public static function calculateTaskProgress(vTask $task, vAccount $account, \mysqli $conn): int
    {
        switch ($task->code) {
            case TaskDefinitionCode::VIEW_BLOG_POST:
            case TaskDefinitionCode::VIEW_RAFFLE:
            case TaskDefinitionCode::GO_TO_TOWN_SQUARE:
            case TaskDefinitionCode::VIEW_PROFILE:
            case TaskDefinitionCode::VIEW_QUEST:
            case TaskDefinitionCode::VISIT_STORE:
            case TaskDefinitionCode::VIEW_LICH_CARD_WIKI:
            case TaskDefinitionCode::VISIT_ANALYTICS_PAGE:
            case TaskDefinitionCode::VISIT_GAMES_PAGE:
            case TaskDefinitionCode::VISIT_BLOGS_PAGE:
            case TaskDefinitionCode::VISIT_GUILD_HALLS_PAGE:
            case TaskDefinitionCode::VISIT_ADVENTURERS_GUILD_PAGE:
            case TaskDefinitionCode::VISIT_LICH_PAGE:
            case TaskDefinitionCode::SEARCH_LICH_CARD:
                return self::countPageVisitsForTask($task, $account);

            case TaskDefinitionCode::SPEND_PRESTIGE_TOKEN:
                return self::countPrestigeGivenForTask($task, $account);

            case TaskDefinitionCode::PLAY_RANKED_LICH:
                return self::countRankedMatchesForTask($task, $account, 18);

            case TaskDefinitionCode::WIN_RANKED_LICH:
                return self::countRankedMatchesForTask($task, $account, 18);

            case TaskDefinitionCode::WIN_TOURNAMENT:
                return self::countTournamentsWonForTask($task, $account);
                
            case TaskDefinitionCode::PLAY_RANKED_MATCH:
                return self::countRankedMatchesForTask($task, $account);

            case TaskDefinitionCode::WIN_RANKED_MATCH:
                return self::countRankedWinsForTask($task, $account);

            case TaskDefinitionCode::PARTICIPATE_QUEST:
                return self::countQuestParticipationsForTask($task, $account);
                
            case TaskDefinitionCode::HAVE_WROP_USED:
                return self::countWropUsedForTask($task, $account);

            case TaskDefinitionCode::PARTICIPATE_RAFFLE:
                return self::countRaffleEntriesForTask($task, $account);
                
        default:
            return 0;
        }
    }

    public static function countRaffleEntriesForTask(vTask $task, vAccount $account): int
    {
        if ($task->type === TaskType::ACHIEVEMENT) {
            return QuestController::countRaffleEntries($account->crand);
        }

        $since = $task->ctime;
        $till  = $task->expiresAt?->dbValue ?? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        return QuestController::countRaffleEntriesBetween($account->crand, $since, $till);
    }

    public static function countWropUsedForTask(vTask $task, vAccount $account): int
    {
        if ($task->type === TaskType::ACHIEVEMENT) {
            return LootController::countWropUsedByNewAccounts($account->crand);
        }
    
        $since = $task->ctime;
        $till = $task->expiresAt?->dbValue ?? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    
        return LootController::countWropUsedByNewAccountsBetween($account->crand, $since, $till);
    }

    public static function countQuestParticipationsForTask(vTask $task, vAccount $account): int
    {
        if ($task->type === TaskType::ACHIEVEMENT) {
            return QuestController::countQuestParticipations($account->crand);
        }

        $since = $task->ctime;
        $till = $task->expiresAt?->dbValue ?? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        return QuestController::countQuestParticipationsBetween(
            $account->crand,
            $since,
            $till
        );
    }

    public static function countRankedWinsForTask(vTask $task, vAccount $account, ?int $gameId = null): int
    {
        if ($task->type === TaskType::ACHIEVEMENT) {
            return GameController::countRankedWins($account->crand, $gameId);
        }
    
        $since = $task->ctime;
        $till  = $task->expiresAt?->dbValue ?? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    
        return GameController::countRankedWinsBetween(
            $account->crand,
            $since,
            $till,
            $gameId
        );
    }
    
    public static function countRankedMatchesForTask(vTask $task, vAccount $account, ?int $gameId = null): int
    {
        if ($task->type === TaskType::ACHIEVEMENT) {
            return GameController::countRankedMatches($account->crand, $gameId);
        }
    
        $since = $task->ctime;
        $till  = $task->expiresAt?->dbValue ?? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    
        return GameController::countRankedMatchesBetween(
            $account->crand,
            $since,
            $till,
            $gameId
        );
    }
    

    public static function countPageVisitsForTask(vTask $task, vAccount $account): int
    {
        $pattern = $task->code->getPageIdPattern();
    
        if ($pattern === null) {
            return 0;
        }
    
        // Use non-date-bounded count for achievements
        if ($task->type === TaskType::ACHIEVEMENT) {
            return AnalyticController::countPageVisits(
                $account->crand,
                $pattern
            );
        }
    
        // Use date-bounded count for recurring tasks
        $since = $task->ctime;
        $till = $task->expiresAt?->dbValue ?? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    
        return AnalyticController::countPageVisitsBetween(
            $account->crand,
            $pattern,
            $since,
            $till
        );
    }

    public static function countPrestigeGivenForTask(vTask $task, vAccount $account): int
    {
        if ($task->type === TaskType::ACHIEVEMENT) {
            return PrestigeController::countPrestigeGiven($account->crand);
        }

        $since = $task->ctime;
        $till = $task->expiresAt?->dbValue ?? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        return PrestigeController::countPrestigeGivenBetween(
            $account->crand,
            $since,
            $till
        );
    }

    public static function countTournamentsWonForTask(vTask $task, vAccount $account): int
    {
        if ($task->type === TaskType::ACHIEVEMENT) {
            return TournamentController::countTournamentsWon($account->crand);
        }
        
        return 0;
    }


    /**
     * Retrieves all tasks assigned to a given account.
     */
    public static function getAccountTasks(vAccount $account): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT  at.ctime, at.crand, td.ctime as task_ctime, td.crand as task_crand,
                td.type, td.code, td.title, td.description, td.goal_count, td.goal_count, td.reward_item_id, td.reward_count, at.completed, at.claimed, 
                   vi.name AS item_name, 
                   vi.desc AS item_description, 
                   vi.small_image AS item_image, 
                   vi.large_image AS item_large_image
            FROM account_tasks at
            JOIN task_definitions td ON td.ctime = at.task_ctime AND td.crand = at.task_crand
            LEFT JOIN kickbackdb.v_item_info vi ON vi.Id = td.reward_item_id
            WHERE at.account_id = ? AND td.type IN ('daily', 'weekly', 'monthly')
            AND ((
                    (td.type = 'daily'   AND at.ctime >= DATE_SUB(?, INTERVAL 1 DAY)) OR
                    (td.type = 'weekly'  AND at.ctime >= DATE_SUB(?, INTERVAL 7 DAY)) OR
                    (td.type = 'monthly' AND at.ctime >= DATE_SUB(?, INTERVAL 30 DAY))
                ) or (at.completed = 1 and at.claimed = 0))
            ORDER BY FIELD(td.type, 'daily', 'weekly', 'monthly')";


        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare statement.");
        }

        
        $now = RecordId::getCTime();

        $stmt->bind_param('isss', $account->crand,$now,$now,$now);
        $stmt->execute();

        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to fetch account tasks.");
        }

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = self::row_to_vTask($row,$account,$conn);
        }

        return new Response(true, "Account tasks retrieved", $tasks);
    }
    public static function getAchievementTasks(vAccount $account): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT at.ctime, at.crand, td.ctime as task_ctime, td.crand as task_crand,
                td.type, td.code, td.title, td.description, td.goal_count, td.goal_count, td.reward_item_id, td.reward_count, 
                at.completed, 
                at.claimed,
                vi.name AS item_name, 
                vi.desc AS item_description, 
                vi.small_image AS item_image, 
                vi.large_image AS item_large_image
            FROM task_definitions td
            LEFT JOIN account_tasks at 
                ON td.ctime = at.task_ctime 
                AND td.crand = at.task_crand 
                AND at.account_id = ?
            LEFT JOIN kickbackdb.v_item_info vi 
                ON vi.Id = td.reward_item_id
            WHERE td.type = 'achievement' 
                AND (at.claimed IS NULL OR at.claimed = 0)
            ORDER BY at.completed DESC, td.goal_count, td.ctime
            LIMIT 3";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare achievement query.");
        }

        $stmt->bind_param('i', $account->crand);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve achievement tasks.");
        }

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            
            $tasks[] = self::row_to_vTask($row,$account,$conn);
        }

        return new Response(true, "Achievement tasks retrieved.", $tasks);
    }


    public static function assignTaskToAccount(vAccount $account, string $taskCTime, int $taskCRand, \mysqli $conn): ?vTask
    {
        $insertSql = "INSERT INTO account_tasks 
            (ctime, crand, account_id, task_ctime, task_crand, completed, claimed)
            VALUES (?, ?, ?, ?, ?, 0, 0)";
    
        $stmt = $conn->prepare($insertSql);
        if (!$stmt) {
            throw new \Exception("Failed to prepare insert statement for task assignment: " . $conn->error);
        }
    
        $accountTaskCTime = RecordId::getCTime();
        $accountTaskCRand = RecordId::generateCRand();
    
        while (true) {
            $stmt->bind_param(
                'siisi',
                $accountTaskCTime,
                $accountTaskCRand,
                $account->crand,
                $taskCTime,
                $taskCRand
            );
    
            if ($stmt->execute()) {
                break; // Insert succeeded
            }
    
            if ($stmt->errno === 1062) {
                // Regenerate crand and retry
                $accountTaskCRand = RecordId::generateCRand();
            } else {
                throw new \Exception("Failed to insert task assignment: " . $stmt->error);
            }
        }
    
        return self::getTaskById($account, new vRecordId($accountTaskCTime, $accountTaskCRand));
    }
    
    public static function getOrAssignRecurringTask(vAccount $account, TaskType $type): ?vTask
    {
        $conn = Database::getConnection();
    
        // Check if an active task exists
        $sql = "SELECT at.ctime, at.crand, td.ctime as task_ctime, td.crand as task_crand,
                   td.type, td.code, td.title, td.description, td.goal_count, td.reward_item_id, td.reward_count, 
                   at.completed, at.claimed,
                   vi.name AS item_name,
                   vi.desc AS item_description,
                   vi.small_image AS item_image,
                   vi.large_image AS item_large_image
            FROM account_tasks at
            JOIN task_definitions td ON td.ctime = at.task_ctime AND td.crand = at.task_crand
            LEFT JOIN kickbackdb.v_item_info vi ON vi.Id = td.reward_item_id
            WHERE at.account_id = ? AND td.type = ?
            ORDER BY at.ctime DESC
            LIMIT 1";
    
        $stmt = $conn->prepare($sql);
        $typeStr = $type->value;
        $stmt->bind_param('is', $account->crand, $typeStr);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($row = $result->fetch_assoc()) {
            $task = self::row_to_vTask($row,$account,$conn);
    
            if (!$task->isExpired()) {
                return $task; // return existing if still valid
            }
        }
    
        // If expired or not found, assign a new one
        return self::assignNewRandomRecurringTask($account, $type, $conn);
    }

    public static function assignNewRandomRecurringTask(vAccount $account, TaskType $type, \mysqli $conn): ?vTask
    {
        // Select random definition of correct type
        $sql = "SELECT ctime, crand FROM task_definitions 
                WHERE type = ? 
                ORDER BY RAND() 
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $typeStr = $type->value;
        $stmt->bind_param('s', $typeStr);
        $stmt->execute();

        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        return self::assignTaskToAccount($account, $row['ctime'], $row['crand'], $conn);
    }

    public static function ensureRecurringTasks(vAccount $account): array
    {
        return [
            TaskType::DAILY->value => self::getOrAssignRecurringTask($account, TaskType::DAILY),
            TaskType::WEEKLY->value => self::getOrAssignRecurringTask($account, TaskType::WEEKLY),
            TaskType::MONTHLY->value => self::getOrAssignRecurringTask($account, TaskType::MONTHLY),
        ];
    }

    public static function getTaskById(vAccount $account, vRecordId $taskId): ?vTask
    {
        $conn = Database::getConnection();

        $sql = "SELECT 
                    at.ctime, at.crand, td.ctime as task_ctime, td.crand as task_crand,
                    td.type, td.code, td.title, td.description, td.goal_count, 
                    td.reward_item_id, td.reward_count, 
                    at.completed, at.claimed,
                    vi.name AS item_name, 
                    vi.desc AS item_description, 
                    vi.small_image AS item_image, 
                    vi.large_image AS item_large_image
                FROM account_tasks at
                JOIN task_definitions td ON td.ctime = at.task_ctime AND td.crand = at.task_crand
                LEFT JOIN kickbackdb.v_item_info vi ON vi.Id = td.reward_item_id
                WHERE at.account_id = ? AND at.ctime = ? AND at.crand = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('isi', $account->crand, $taskId->ctime, $taskId->crand);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return self::row_to_vTask($row, $account, $conn);
        }

        return null;
    }

    public static function ClaimTaskReward(vAccount $account, vRecordId $taskId): Response
    {
        $conn = Database::getConnection();
        $conn->begin_transaction();
    
        try {
            $task = self::getTaskById($account, $taskId, $conn);
    
            if (!$task) {
                $conn->rollback();
                return new Response(false, "Task not found.");
            }
    
            if (!$task->isCompleted) {
                $conn->rollback();
                return new Response(false, "Task is not yet completed.");
            }
    
            if ($task->isClaimed) {
                $conn->rollback();
                return new Response(false, "Reward already claimed.");
            }
    
            // Mark task as claimed
            $stmt = $conn->prepare("UPDATE account_tasks SET claimed = 1 WHERE account_id = ? AND ctime = ? AND crand = ?");
            if (!$stmt) {
                $conn->rollback();
                return new Response(false, "Failed to prepare claim update statement.");
            }
    
            $stmt->bind_param('isi', $account->crand, $task->ctime, $task->crand);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $conn->rollback();
                return new Response(false, "Failed to mark task as claimed.");
            }
    
            // Grant rewards
            $successCount = 0;
            for ($i = 0; $i < $task->rewardCount; $i++) {
                $lootResp = LootController::giveLoot($account, $task->rewardItem, null, $conn);
                if (!$lootResp->success) {
                    $conn->rollback();
                    return new Response(false, "Failed to deliver reward: {$lootResp->message}");
                }
                $successCount++;
            }
    
            $conn->commit();
    
            return new Response(
                true,
                "You received {$successCount}x {$task->rewardItem->name}!",
                $task
            );
        } catch (\Throwable $e) {
            $conn->rollback();
            return new Response(false, "Unexpected error: " . $e->getMessage());
        }
    }
    


}

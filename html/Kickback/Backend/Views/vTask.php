<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

use Kickback\Backend\Models\TaskDefinitionCode;
use Kickback\Backend\Models\TaskType;
use Kickback\Backend\Models\ForeignRecordId;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vItem;
use Kickback\Backend\Views\vDateTime;

class vTask extends vRecordId
{
    public TaskType $type;
    public TaskDefinitionCode $code;

    public string $title;
    public string $description;
    public int $goalCount = 1;

    public vItem $rewardItem;
    public int $rewardCount = 1;

    public ?vDateTime $expiresAt = null;

    public bool $isCompleted = false;
    public bool $isClaimed = false;
    public int $progress = 0;

    public ForeignRecordId $taskDefinitionId;

    public function __construct(string $ctime, int $crand)
    {
        parent::__construct($ctime, $crand);
    }

    public function getFaIcon(): string
    {
        return $this->code->getFaIcon();
    }
    /**
     * Sets the expiration date based on the task type.
     */
    public function calculateExpiration(): void
    {
        if ($this->type === TaskType::ACHIEVEMENT) {
            $this->expiresAt = null;
            return;
        }

        $createdAt = new vDateTime($this->ctime);

        $this->expiresAt = match($this->type) {
            TaskType::DAILY     => $createdAt->addDays(1),
            TaskType::WEEKLY    => $createdAt->addDays(7),
            TaskType::MONTHLY   => $createdAt->addMonths(1),
            default             => null,
        };
    }



    /**
     * Returns a user-friendly progress string.
     */
    public function getProgressText(): string
    {
        return $this->goalCount > 1
            ? "{$this->progress} / {$this->goalCount}"
            : ($this->isCompleted ? "1 / 1" : "0 / 1");
    }

    /**
     * Determines if the task is expired at a given point in time.
     */
    public function isExpired(vDateTime $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt->isBefore($now ?? new vDateTime());
    }
}


?>
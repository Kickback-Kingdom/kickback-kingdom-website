<?php
use Kickback\Services\Session;
use Kickback\Backend\Controllers\TaskController;
use Kickback\Backend\Models\TaskType;
use Kickback\Backend\Views\vDateTime;

function renderTaskToast(\Kickback\Backend\Views\vTask $task): void {
    $taskClaimedWaiting = $task->isClaimed && !$task->isExpired();
    $toastClasses = 'toast show mb-3';
    if ($taskClaimedWaiting) {
        $toastClasses .= ' bg-light border border-info-subtle opacity-50 text-muted'; // Lighter and more intuitive color for waiting tasks
    }

    // Distinct colors for each task type
    $typeColor = match($task->type) {
        \Kickback\Backend\Models\TaskType::DAILY => 'bg-primary text-bg-primary', // Daily Tasks - Blue
        \Kickback\Backend\Models\TaskType::WEEKLY => 'bg-success text-bg-success', // Weekly Tasks - Green
        \Kickback\Backend\Models\TaskType::MONTHLY => 'bg-danger text-bg-danger', // Monthly Tasks - Red
        default => 'bg-ranked-1 text-bg-primary' // Achievements - Grey
    };


    ?>
    <div class="<?= $toastClasses ?>" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header <?= $typeColor ?>">
            <i class="fa-solid <?= htmlspecialchars($task->code->getFaIcon()) ?> me-2"></i>
            <strong class="me-auto"><?= htmlspecialchars($task->title) ?></strong>
            <small class=""><?= ucfirst($task->type->value) ?></small>
        </div>
        <div class="toast-body">
            <div class="mb-2">
                <small class="text-muted"><?= htmlspecialchars($task->description) ?>
</small>
            </div>

            <?php if ($task->goalCount > 1): ?>
                <div class="progress mb-2" style="height: 14px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated <?= explode(' ', $typeColor)[1] ?>"
                        style="width: <?= min(100, (int)(($task->progress / max(1, $task->goalCount)) * 100)) ?>%;"></div>
                </div>
                <div class="text-end mb-2">
                    <small class="text-muted fw-semibold"><?= $task->getProgressText() ?></small>
                </div>
            <?php endif; ?>


            <div class="d-flex justify-content-between align-items-center mt-2">
                <!-- Reward (left) -->
                <div class="d-flex align-items-center">
                    <span tabindex="0"
                        data-bs-toggle="popover"
                        data-bs-trigger="focus"
                        data-bs-placement="top"
                        data-bs-custom-class="custom-popover"
                        data-bs-title="<?= htmlspecialchars($task->rewardItem->name) ?>"
                        data-bs-content="<?= htmlspecialchars($task->rewardItem->description) ?>">
                        <img src="<?= $task->rewardItem->iconSmall->getFullPath() ?>"
                            class="loot-badge me-2" />
                    </span>
                    <span class="fw-semibold small text-muted"><?= $task->rewardCount ?>x</span>
                </div>

                <!-- Action (right) -->
                <div>
                    <?php if ($task->isCompleted && !$task->isClaimed): ?>
                        <form method="POST" class="m-0">
                            <input type="hidden" name="claim_task_crand" value="<?= $task->crand ?>">
                            <input type="hidden" name="claim_task_ctime" value="<?= $task->ctime ?>">
                            <button type="submit" name="submit_claim_task" class="bg-ranked-1 btn btn-sm">
                                <i class="fa-solid fa-gift"></i> Collect Rewards
                            </button>
                        </form>

                    <?php elseif ($task->isClaimed && !$task->isExpired()): ?>
                        <span class="badge bg-success-subtle text-success-emphasis small">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Waiting (<?= vDateTime::timeIntervalToString((new vDateTime())->value->diff($task->expiresAt->value), 2) ?>)
                        </span>

                    <?php elseif (!$task->isCompleted && !$task->isExpired() && $task->expiresAt !== null): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis small">
                            <i class="fa-solid fa-hourglass-half me-1"></i>
                            <?= vDateTime::timeIntervalToString((new vDateTime())->value->diff($task->expiresAt->value), 2) ?> left
                        </span>

                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis small">
                            <i class="fa-solid fa-spin fa-spinner me-1"></i>
                            Waiting
                        </span>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <?php
}
?>


<div class="container px-3">
    <!-- RECURRING TASKS -->
    <div class="text-center text-muted my-4">
        <i class="fa-solid fa-calendar-day mx-2"></i>
        <span>Daily / Weekly / Monthly Tasks</span>
        <i class="fa-solid fa-calendar-day mx-2"></i>
    </div>
    <?php foreach ($recurringTasks as $task): ?>
        <?php renderTaskToast($task); ?>
    <?php endforeach; ?>

    <!-- ACHIEVEMENTS -->
    <div class="text-center text-muted my-4">
        <i class="fa-solid fa-star mx-2"></i>
        <span>Achievements</span>
        <i class="fa-solid fa-star mx-2"></i>
    </div>
    <?php foreach ($achievements as $task): ?>
        <?php renderTaskToast($task); ?>
    <?php endforeach; ?>


</div>

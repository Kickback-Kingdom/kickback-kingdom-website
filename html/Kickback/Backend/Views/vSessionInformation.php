<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vSessionInformation
{
    public ?vAccount $account;
    public string $chestsJSON;

    /** @var array<mixed> */
    public array $chests;

    /** @var array<vNotification> */
    public array $notifications;
    public string $notificationsJSON;

    public bool $delayUpdateAfterChests;
    
}

?>

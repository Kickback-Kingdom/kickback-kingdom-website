<?php 
declare(strict_types=1);

namespace Kickback\Backend\Views;

class vSessionInformation
{
    public ?vAccount $account;
    public string $chestsJSON;
    public array $chests;

    public array $notifications;
    public string $notificationsJSON;

    public bool $delayUpdateAfterChests;
    
}

?>
<?php
declare(strict_types=1);

namespace Kickback\Backend\Models;

class Account extends RecordId
{
    public string $email;
    public string $firstName;
    public string $lastName;
    public string $username;
    public bool $banned;
    public ForeignRecordId $passageId;
    public ?string $discordUserId = null;
    public ?string $discordUsername = null;
    public ?string $steamUserId = null;
    public ?string $steamUsername = null;
}
?>

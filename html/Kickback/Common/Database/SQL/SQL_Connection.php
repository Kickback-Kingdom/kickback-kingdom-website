<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL;

interface SQL_Connection
{
    //public function set_column_access_type_strictness(int $strictness_level) : void;
    public function close() : bool;

    //public function mysqli_autocommit(bool $enable): bool;
    //public function mysqli_begin_transaction(int $flags = 0, ?string $name = null): bool;
    //public function mysqli_change_user(string $username, #[\SensitiveParameter] string $password, ?string $database): bool;
    //public function mysqli_character_set_name(): string;
    //public function mysqli_close() : bool;
    //public function mysqli_commit(int $flags = 0, ?string $name = null): bool;
    //public function mysqli_rollback(int $flags = 0, ?string $name = null): bool;
}

?>

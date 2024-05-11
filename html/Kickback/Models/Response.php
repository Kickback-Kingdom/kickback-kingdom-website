<?php

declare(strict_types=1);

namespace Kickback\Models;

class Response {
    public bool $Success;
    public string $Message;
    public mixed $Data;

    function __construct(bool $success, string $message, mixed $data = null)
    {
        $this->Success = $success;
        $this->Message = $message;
        $this->Data = $data;
    }
    
    function Return() : void
    {
        echo json_encode($this);
    }

    function ToString() : string|false
    {
        return json_encode($this);
    }

    function Exit() : never
    {
        exit($this->ToString());
    }
}
?>

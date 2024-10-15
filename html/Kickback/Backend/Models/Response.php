<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

class Response {
    public bool $success;
    public string $message;
    public mixed $data;

    function __construct(bool $success, string $message, mixed $data = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
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
    
    function ThrowIfFailed() : void {
        if (!$this->success)
            throw new \Exception($this->message);
    }
}
?>

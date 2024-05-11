<?php

require("account.php");

class APIResponse {

    public bool    $Success;
    public string  $Message;
    public mixed   $Data;

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

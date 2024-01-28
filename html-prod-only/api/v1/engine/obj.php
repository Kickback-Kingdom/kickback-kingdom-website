<?php

require("account.php");

  class APIResponse {

    public $Success;
    public $Message;
    public $Data;

    function __construct($success, $message, $data)
    {
      $this->Success = $success;
      $this->Message = $message;
      $this->Data = $data;
      
    }

    function Return()
    {
      echo json_encode($this);
    }

    function ToString()
    {
      return json_encode($this);
    }

    function Exit()
    {
      exit($this->ToString());
    }
  }


?>
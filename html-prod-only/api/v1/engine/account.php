<?php
class account {
    // Properties
    public $Id;
    public $Email;
    public $FirstName;
    public $LastName;
    public $Username;
    public $LastLogin;
    public $DateCreated;
    public $Banned;

    	/* Constructor */
      public function __construct()
      {
        /* Initialize the $id and $name variables to NULL */
        $this->$Id=-1;
        $this->$Email="";
        $this->$FirstName="";
        $this->$LastName="";
        $this->$Username="";
        $this->$LastLogin=NULL;
        $this->$DateCreated=NULL;
        $this->$Banned=FALSE;

      }
  }

?>
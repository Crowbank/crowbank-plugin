<?php

interface Database {
  public function execute($sql);
}

class MySqlDatabase implements Database {
  private $mysqli;
  private $host;
  private $database;
  private $user;
  private $password;

  public function __construct($host, $database, $user, $password) {
  	$this->host = $host;
  	$this->database = $database;
  	$this->user = $user;
  	$this->password = $password;
}
  
  private function reset() {
  	$this->mysqli = new mysqli($this->host, $this->user, $this->password, $this->database);
  }

  public function execute($sql) {
	$this->reset();
  	$result = $this->mysqli->query($sql);
  	if (gettype($result) <> 'boolean')
  		$result = $result->fetch_all(MYSQLI_ASSOC);
  	
  	return $result;
  }
}

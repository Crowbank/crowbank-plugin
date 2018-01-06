<?php

interface Database {
  public function execute($sql);
}

class MySqlDatabase implements Database {
  private $mysqli;

  public function __construct($host, $database, $user, $password) {
  	$this->mysqli = new mysqli($host, $user, $password, $database);
  }

  public function execute($sql) {
//  	echo "Executing $sql<br>";
  	$result = $this->mysqli->query($sql);
  	if (gettype($result) <> 'boolean')
  		$result = $result->fetch_all(MYSQLI_ASSOC);
  	
  	return $result;
  }
}

<?php
class Vet {
	public $no;
	public $name;
	public $telno;
	public $email;
	
	public function __construct($row) {
		$this->no = (int) $row['vet_no'];
		$this->name = $row['vet_practice_name'];
		$this->telno = $row['vet_telno_1'];
		$this->email = $row['vet_email'];
	}
}

class Vets {
	private $by_no = array();
	private $by_name = array();
	public $count = 0;
	
	private $isLoaded = FALSE;
	
	public function __construct() {
	}
	
	public function get_list() {
		$this->load();
		return $this->by_no;
	}
	
	public function load($force = FALSE) {
		global $petadmin_db;
		
		$sql = "Select vet_no, vet_practice_name, vet_telno_1, vet_email from my_vet order by vet_practice_name";
		
		if ($this->isLoaded and ! $force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->by_no = array();
			$this->by_name = array();
			$this->count = 0;
		}
		
		crowbank_log('Loading vets');
		$result = $petadmin_db->execute($sql);
		
		foreach($result as $row) {
			$vet = new Vet($row);
			$this->count++;
			$this->by_no[$vet->no] = $vet;
			$this->by_name[$vet->name] = $vet;
		}
		
		$this->isLoaded = TRUE;
	}
	
	public function get_by_no($no) {
		$this->load();
		return array_key_exists($no, $this->by_no) ? $this->by_no[$no] : NULL;
	}
	
	public function get_by_name($name) {
		$this->load();
		return array_key_exists($name, $this->by_name) ? $this->by_name[$name] : NULL;
	}
}
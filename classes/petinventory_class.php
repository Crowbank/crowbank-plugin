<?php
class PetInventory {
	public $date;
	public $spec;
	public $pets = array();
	public $run_type;
	public $run;
	public $booking;
	public $in_out;

	public function __construct($row){
		global $petadmin;

		$this->date = new DateTime($row['pi_date']);
		$this->spec = $row['pi_spec'];
		$this->run = $row['pi_run_code'];
		$this->run_type = $row['pi_run_type'];
		$this->in_out = $row['pi_in_out'];

		$bk_no = $row['pi_bk_no'];
		$this->booking = $petadmin->bookings->get_by_no($bk_no);

		$pet_no = $row['pi_pet_no_1'];
		$pet = $petadmin->pets->get_by_no($pet_no);

		$this->pets[] = $pet;

		$pet_no = $row['pi_pet_no_2'];
		if (!$pet_no)
			return;

		$pet = $petadmin->pets->get_by_no($pet_no);
		$this->pets[] = $pet;

		$pet_no = $row['pi_pet_no_3'];
		if (!$pet_no)
			return;

		$pet = $petadmin->pets->get_by_no($pet_no);
		$this->pets[] = $pet;
	}
}

class PetInventories {
	public $by_date = array();
	public $isLoaded = FALSE;
	public $count = 0;
	
	public function __construct() {
	}
	
	public function load($force = FALSE) {
		global $petadmin_db;
		$sql = 'select pi_bk_no, pi_run_code, pi_run_type, pi_date, pi_spec, pi_pet_no_1, pi_pet_no_2, pi_pet_no_3, pi_in_out from my_petinventory where pi_date > \'2017-08-25\'';
		
		if ($this->isLoaded and !$force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->by_date = array();
			$this->count = 0;
		}
		
		petadmin_log('Loading petinventories');
		
		$result = $petadmin_db->execute($sql);
		foreach ($result as $row) {
			$inventory = new PetInventory($row);
			$this->count++;
			$ts = $inventory->date->getTimestamp();
			if (!isset($this->by_date[$ts])) {
				$this->by_date[$ts] = array();
			}

			if (!isset($this->by_date[$ts][$inventory->spec])) {
				$this->by_date[$ts][$inventory->spec] = array();
			}

			if (!isset($this->by_date[$ts][$inventory->spec][$inventory->run_type])) {
				$this->by_date[$ts][$inventory->spec][$inventory->run_type] = array();
			}
			$this->by_date[$ts][$inventory->spec][$inventory->run_type][] = $inventory;
		}
		
		$this->isLoaded = TRUE;
	}
	
	public function get_by_date_and_spec($date, $spec) {
		$this->load();	

		$ts = $date->getTimestamp();
		if (!isset($this->by_date[$ts])) {
			return NULL;
		}

		if (!isset($this->by_date[$ts][$spec]))	{
			return NULL;
		}
		
		return $this->by_date[$ts][$spec];
	}
}
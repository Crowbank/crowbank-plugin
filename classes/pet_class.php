<?php
class Pet {
	public $no;
	public $name;
	public $customer;
	public $species;
	public $breed;
	public $dob;
	public $warning;
	public $sex;
	public $neutered;
	public $vet;
	public $vacc_status;
	public $vacc_date;
	public $deceased;

	public $bookings = array();
	
	public function __construct($row) {
		global $petadmin;
		
		$this->no = (int) $row['pet_no'];
		$this->name = $row['pet_name'];
		$cust_no = $row['pet_cust_no'];
		$this->customer = $petadmin->customers->get_by_no($cust_no);
		$this->species = $row['pet_spec'];
		$breed_no = $row['pet_breed_no'];
		$this->breed = $petadmin->breeds->get_by_no($breed_no);
		$this->dob = new DateTime($row['pet_dob']);
		$this->warning = $row['pet_warning'];
		$this->sex = $row['pet_sex'];
		$this->neutered = $row['pet_neutered'];
		$vet_no = $row['pet_vet_no'];
		$this->vet = $petadmin->vets->get_by_no($vet_no);
		$this->vacc_status = $row['pet_vacc_status'];
		$this->vacc_date = new DateTime($row['pet_vacc_date']);
		$this->deceased = $row['pet_deceased'];
		
		if (!$this->customer) {
			echo "Could not find customer #$cust_no for pet #$this->no<br>";
		} else {
			$this->customer->add_pet($this);
		}
	}
	
	public function add_booking($booking) {
		$this->bookings[$booking->no] = $booking;
	}
	
	public function description() {
		return $this->name . ' (' . $this->breed->short_desc . ')';
	}
}

class Pets {
	private $by_no = array();
	public $count = 0;
	
	public $isLoaded = FALSE;
	
	public function __construct() {
	}
	
	public function load($force = FALSE) {
		global $petadmin_db, $petadmin;
		$sql = "Select pet_no, pet_name, pet_cust_no, pet_spec, pet_breed_no, pet_dob, pet_warning,
pet_sex, pet_neutered, pet_vet_no, pet_vacc_status, pet_vacc_date, pet_deceased from vw_pet";
		
		if ($this->isLoaded and ! $force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->by_no = array();
			$this->count = 0;
		}
		
		crowbank_log('Loading pets');
		
		$result = $petadmin_db->execute($sql);
		
		foreach($result as $row) {
			$pet = new Pet($row);
			$this->count++;
			$this->by_no[$pet->no] = $pet;
		}
		
		$this->isLoaded = TRUE;
	}
	
	public function get_by_no($no) {
		$this->load();
		return array_key_exists($no, $this->by_no) ? $this->by_no[$no] : NULL;
	}
	
}
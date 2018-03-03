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
	
	public function update($pet_name, $pet_species, $pet_breed_no, $pet_sex,
			$pet_neutered, $pet_dob, $pet_vet_no, $pet_vacc_date, $pet_kc_date, $pet_vacc_img, $pet_comments ) {
		global $petadmin, $petadmin_db;
		
		if ($pet_species == 'Dog' and $pet_kc_date and $pet_kc_date < $pet_vacc_date) {
			$pet_vacc_date = $pet_kc_date;
		}
		
		$pet_vacc_status = 'None';
		if ($pet_vacc_date) {
			$diff = date_diff($pet_vacc_date, new DateTime())->format('%a');
			if ($diff < 365) {
				$pet_vacc_status = 'Valid';
			} else {
				$pet_vacc_status = 'Expired';
			}
		}
		
		$sql = "update my_pet set pet_name = '" .  $pet_name . "', pet_spec_no = " . ($pet_species == 'Dog' ? 1 : 2);
		$sql .= ", pet_breed_no = " . $pet_breed_no . ", pet_sex = '" . $pet_sex . "', pet_neutered = '" . $pet_neutered;
		$sql .= "', pet_dob = '" . $pet_dob->format('Y-m-d') . "', pet_vet_no = " . $pet_vet_no . ", pet_vacc_status = '";
		$sql .= $pet_vacc_status . "', pet_vacc_date = '" . $pet_vacc_date->format('Y-m-d');
		$sql .= "', pet_notes = '" . $pet_comments . "' where pet_no = " . $pet_no;
		
		$petadmin_db->execute($sql);
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
	
	public function create_pet( $cust_no, $pet_no, $pet_name, $pet_species, $pet_breed, $pet_sex,
			$pet_neutered, $pet_dob, $pet_vet, $pet_vacc_date, $pet_kc_date, $pet_vacc_img, $pet_comments ) {
		global $petadmin_db;
		
		$pet = new Pet();
		$customer = $petadmin->customers->get_by_no($cust_no);
		
		$pet->no = $pet_no;
		$pet->name = $pet_name;
		$pet->customer = $customer;
		$pet->species = $pet_species;
		$pet->breed = $petadmin->breeds->get_by_no($breed_no);
		$pet->dob = $pet_dob;
		$pet->warning = '';
		$pet->sex = $pet_sex;
		$pet->neutered = $pet_neutered;
		$vet_no = $pet_vet_no;
		$pet->vet = $petadmin->vets->get_by_no($vet_no);
		$pet->vacc_status = $pet_vacc_status;
		$pet->vacc_date = $pet_vacc_date;
		$pet->deceased = 'N';

		$this->count++;
		$this->by_no[$pet_no] = $pet;
	}
}

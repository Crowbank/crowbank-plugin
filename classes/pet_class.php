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
	public $kc_date;
	public $notes;
	public $deceased;

	public $bookings = array();
	
	public function __construct($row = null) {
		global $petadmin;
		
		if ($row) {
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
			$this->kc_date = new DateTime($row['pet_kc_date']);
			$this->deceased = $row['pet_deceased'];
			$this->notes = $row['pet_notes'];
			
			if (!$this->customer) {
				echo "Could not find customer #$cust_no for pet #$this->no<br>";
			} else {
				$this->customer->add_pet($this);
			}
		}
	}
	
	public function add_booking($booking) {
		$this->bookings[$booking->no] = $booking;
	}
	
	public function description() {
		return $this->name . ' (' . $this->breed->short_desc . ')';
	}
	
	public function home_url() {
		return home_url("pet/?pet_no=$this->no&cust=$this->customer->no");
	}
	
	public function update($pet_name, $pet_species, $pet_breed_no, $pet_sex,
			$pet_neutered, $pet_dob, $pet_vet_no, $vc_img, $pet_notes ) {
		global $petadmin, $petadmin_db;
		
		if ( $this->vacc_status <> 'None' and $vc_img ) {
			$this->vacc_status = 'Unconfirmed';
		}
		
		$sql = "update my_pet set pet_name = '" .  $pet_name . "', pet_spec_no = " . ($pet_species == 'Dog' ? 1 : 2);
		$sql .= ", pet_breed_no = " . $pet_breed_no . ", pet_sex = '" . $pet_sex . "', pet_neutered = '" . $pet_neutered;
		$sql .= "', pet_dob = '" . $pet_dob . "', pet_vet_no = " . $pet_vet_no . ", pet_vacc_status = '";
		$sql .= $pet_vacc_status . "', pet_notes = '" . $pet_comments . "' where pet_no = " . $this->no;
		
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
		$sql = "Select pet_no, pet_name, pet_cust_no, pet_spec, pet_breed_no, pet_dob, 
pet_warning, pet_sex, pet_neutered, pet_vet_no, pet_vacc_status, pet_kc_date, 
pet_vacc_date, pet_deceased, pet_notes from vw_pet";
		
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
	
	public function create_pet( $cust_no, $msg_no, $pet_name, $pet_species, $pet_breed_no, $pet_sex,
			$pet_neutered, $pet_dob, $pet_vet_no, $vc_img, $pet_comments ) {
		global $petadmin_db, $petadmin;

		if ( $vc_img ) {
			$vacc_status = 'Unconfirmed';
		} else {
			$vacc_status = 'None';
		}
		
		$pet = new Pet();
		$customer = $petadmin->customers->get_by_no($cust_no);
		
		$pet->no = -$msg_no;
		$pet->name = $pet_name;
		$pet->customer = $customer;
		$pet->species = $pet_species;
		$pet->breed = $petadmin->breeds->get_by_no($pet_breed_no);
		$pet->dob = $pet_dob;
		$pet->warning = '';
		$pet->sex = $pet_sex;
		$pet->neutered = $pet_neutered;
		$pet->vet = $petadmin->vets->get_by_no($pet_vet_no);
		$pet->vacc_status = $vacc_status;
		$pet->deceased = 'N';

		$this->count++;
		$this->by_no[$pet->no] = $pet;
				
		$sql = "insert into my_pet (pet_no, pet_cust_no, pet_name, pet_spec_no, pet_breed_no, pet_dob, ";
		$sql .= "pet_warning, pet_sex, pet_neutered, pet_vet_no, pet_vacc_status, pet_deceased)";
		$sql .= " values (" . -$msg_no . ", " . $cust_no . ", '" . $pet_name . "', ";
		$sql .= ($pet_species == 'Dog' ? 1 : 2) . ", " . $pet_breed_no . ", '" . $pet_sex . "', '";
		$sql .= $pet_neutered . "', '" . $pet_dob . "', " . $pet_vet_no . ", ";
		$sql .= $vacc_status .  "', '" . $pet_comments . "')";
		
		$petadmin_db->execute( $sql );
	}
}

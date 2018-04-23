<?php
require_once "config.php";
require_once "database_class.php";
require_once "customer_class.php";
require_once "breed_class.php";
require_once "vet_class.php";
require_once "pet_class.php";
require_once "booking_class.php";
require_once "availability_class.php";
require_once "inventory_class.php";
require_once "employee_class.php";
require_once "timesheet_class.php";
require_once "task_class.php";
require_once "weather_class.php";
require_once "petinventory_class.php";
require_once "message_class.php";

function fetch_or_load_petadmin() {
	global $petadmin, $database;
	
	$petadmin->load();
	return $petadmin;
	
	if (apcu_exists('petadmin')) {
		$petadmin = apcu_fetch('petadmin');
		$database = new MySqlDatabase(HOST, DATABASE, USERNAME, PASSWORD);
		$petadmin->database = $database;
		$lt = $petadmin->get_lasttransfer();
		
		if ($lt > $petadmin->last_transfer) {
			apcu_delete('petadmin');
			$petadmin->load(TRUE);
			apcu_store('petadmin', $petadmin);
		}
	} else {
		$database = new MySqlDatabase(HOST, DATABASE, USERNAME, PASSWORD);
		$petadmin = Petadmin::getPetadmin($database);
		$petadmin->load();
		apcu_store('petadmin', $petadmin);
	}
		
	return $petadmin;
}

$GLOBALS['petadmin_db'] = new MySqlDatabase(HOST, DATABASE, USERNAME, PASSWORD);
if (!isset($GLOBALS['petadmin'])) {
	$GLOBALS['petadmin'] = new Petadmin();
}

/* $GLOBALS['petadmin']->load(); */
/* Changed to lazy loading */

class Petadmin {
  public $isLoaded = FALSE;
  public $customers = NULL;
  public $breeds = NULL;
  public $bookings = NULL;
  public $pets = NULL;
  public $vets = NULL;
  public $employees = NULL;
  public $inventory = NULL;
  public $timesheets = NULL;
  public $tasks = NULL;
  public $weather = NULL;
  public $petinventory = NULL;
  public $counts = array();
  public $last_transfer;

  public function __construct() {
   	$this->customers = new Customers();
    $this->breeds = new Breeds();
   	$this->bookings = new Bookings();
    $this->pets = new Pets();
    $this->vets = new Vets();
    $this->employees = new Employees();
    $this->inventory = new Inventories();
    $this->timesheets = new Timesheets();
    $this->tasks = new Tasks();
    $this->weather = new Weather();
    $this->petinventory = new PetInventories();
  }

	public function get_status() {
		$minimum_levels = ['customers'=> 1000, 'breeds' => 20, 'bookings' => 1000, 'pets' => 2000,
				'bookings' => 4000, 'vets' => 50, 'employees' => 10, 'inventory' => 500,
				'timesheets' => 100, 'tasks' => 100, 'petinventory' => 3000];
		
		$status = '';
		
		if (!$this->isLoaded)
			return 'Unloaded';
		
		$now = new DateTime();
		$last_transfer = $this->get_lasttransfer();
		
		if ($now->getTimestamp() - $last_transfer->getTimestamp() > 3600) {
			$status = 'Stale'; 
		} else {
			$status = 'Loaded';
		}
		
		foreach ($minimum_levels as $key=>$level) {
			if (!array_key_exists($key, $this->counts)) {
				$status .= ", $key missing";
			} elseif ($this->counts[$key] < $minimum_levels[$key]) {
				$status .= ", $key short";
			}
		}
		
		return $status;
	}
  
  public function get_lasttransfer() {
  	global $petadmin_db;
  	$sql = "Select lt_lasttransfer from my_lasttransfer";
  	$result = $petadmin_db->execute($sql);
  	
  	foreach($result as $row) {
  		$last_transfer = new DateTime($row['lt_lasttransfer']);
  		break;
  	}
  	
  	return $last_transfer;
  }
  
  public function load($force = FALSE) {
  	global $database;
  	
    if ($this->isLoaded and !$force) {
      return;
    }
    
    $this->last_transfer = $this->get_lasttransfer();
	  $this->isLoaded = TRUE;
    
    crowbank_log('Loading petadmin (last transfer = ' . $this->last_transfer->format('d/m H:i:s') . ')');

    if ($this->customers == NULL) {
    	$this->customers = new Customers();
    }
    $this->customers->load($force);
    $this->counts['customers'] = $this->customers->count;   
    
    if ($this->breeds == NULL) {
      $this->breeds = new Breeds();
    }
    $this->breeds->load($force);
    $this->counts['breeds'] = $this->breeds->count;
    
    if ($this->vets == NULL) {
    	$this->vets = new Vets();
    }
    $this->vets->load($force);
    $this->counts['vets'] = $this->vets->count;
    
    
    if ($this->pets == NULL) {
    	$this->pets = new Pets();
    }
    $this->pets->load($force);
    $this->counts['pets'] = $this->pets->count;
    
    if ($this->bookings == NULL) {
      $this->bookings = new Bookings();
    }
    $this->bookings->load($force);
    $this->counts['bookings'] = $this->bookings->count;
    
    if ($this->inventory == NULL) {
    	$this->inventory = new Inventory();
    }
    $this->inventory->load($force);
    $this->counts['inventory'] = $this->inventory->count;

    if ($this->employees == NULL) {
    	$this->employees = new Employees();
    }
    $this->employees->load($force);
    $this->counts['employees'] = $this->employees->count;

    if ($this->timesheets == NULL) {
    	$this->timesheets = new Timesheets();
    }
    $this->timesheets->load($force);
    $this->counts['timesheets'] = $this->timesheets->count;

    if ($this->tasks == NULL) {
    	$this->tasks = new Tasks();
    }
    $this->tasks->load($force);
    $this->counts['tasks'] = $this->tasks->count;
    
    if ($this->petinventory == NULL) {
      $this->petinventory = new PetInventories();
    }
    $this->petinventory->load($force);
    $this->counts['petinventory'] = $this->petinventory->count;

    $this->weather->refresh();
}
}
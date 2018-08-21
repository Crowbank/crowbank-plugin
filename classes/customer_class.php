<?php

class Customer {
  public $no;
  public $title;
  public $surname;
  public $forename;
  public $addr1;
  public $addr3;
  public $postcode;
  public $telno_home;
  public $telno_mobile;
  public $telno_mobile2;
  public $email;
  public $nodeposit;

  private $pets = array();
  private $bookings = array();
  private $past_bookings = array();
  private $current_bookings = array();
  private $future_bookings = array();
  private $max_bk_no = 0;
  private $deluxe = 0;

  public function __construct($row) {
    $this->no = (int) $row['cust_no'];
    $this->title = trim($row['cust_title']);
    $this->surname = trim($row['cust_surname']);
    $this->forename = trim($row['cust_forename']);
    $this->addr1 = trim($row['cust_addr1']);
    $this->addr3 = trim($row['cust_addr3']);
    $this->postcode = trim($row['cust_postcode']);
    $this->telno_home = trim($row['cust_telno_home']);
    $this->telno_mobile = trim($row['cust_telno_mobile']);
    $this->telno_mobile2 = trim($row['cust_telno_mobile2']);
    $this->email = strtolower(trim($row['cust_email']));
    $this->nodeposit = (int) $row['cust_nodeposit'];
  }
  
  public function return_field($field_name) {
  	switch ( $field_name ) {
  		case 'cust_no':
  			return $this->no;
  		case 'cust_title':
  			return $this->title;
  		case 'cust_surname':
  			return $this->surname;
  		case 'cust_forename':
  			return $this->forename;
  		case 'cust_addr1':
 			return $this->addr1;
  		case 'cust_addr3':
  			return $this->addr3;
  		case 'cust_postcode':
  			return $this->postcode;
  		case 'cust_telno_home':
  			return $this->telno_home;
  		case 'cust_telno_mobile':
  			return $this->telno_mobile;
  		case 'cust_telno_mobile2':
  			return $this->telno_mobile2;
  		case 'cust_email':
  			return $this->email;
  		case 'cust_nodeposit':
  			return $this->nodeposit;
  		default:
  			return null;
  			
  	}
  }

  public function update_changes( $changes ) {
  	global $petadmin_db;
  	
  	$sql = "update my_customer set ";
  	$first = true;
  	
  	foreach ( $changes as $k => $v ) {
  		if (!$first) {
  			$sql .= ', ';
  		}
  		$sql .= $k . " = '" . $v . "'";
  		$first = false;
  	}
  	$sql .= ' where cust_no = ' . $this->no;
  	$petadmin_db->execute($sql);
  }
  
  public function update( $cust_forename, $cust_surname, $cust_addr1, $cust_addr3, $cust_postcode,
  		$cust_telno_home, $cust_telno_mobile, $cust_telno_mobile2 ) {
  	global $petadmin, $petadmin_db;
  	
  	$sql = "update my_customer set cust_forename = '" .  $cust_forename . "', cust_surname = '";
  	$sql .= $cust_surname . "', cust_addr1 = '" . $cust_addr1 . "', cust_addr3 = '" . $cust_addr3;
  	$sql .= "', cust_postcode = '" . $cust_postcode . "', cust_telno_home = '" . $cust_telno_home;
  	$sql .= "', cust_telno_mobile = '" . $cust_telno_mobile . "', cust_telno_mobile2 = '" . $cust_telno_mobile2;
  	$sql .= "' where cust_no = " . $this->no;
  	
  	$petadmin_db->execute($sql);
  }
  
  public function get_pets() {
  	global $petadmin;
  	
  	$petadmin->pets->load();
  	
  	return $this->pets;
  }
  
  public function get_bookings() {
  	global $petadmin;
  	
  	$petadmin->bookings->load();
  	
  	return $this->bookings;
  }
  
  public function is_deluxe() {
  	global $petadmin;
  	
  	$petadmin->bookings->load();
  	
  	return $this->deluxe;
  }
  
  public function get_current_bookings() {
  	global $petadmin;
  	
  	$petadmin->bookings->load();
  	
  	return $this->current_bookings;
  }
  
  public function get_past_bookings() {
  	global $petadmin;
  	
  	$petadmin->bookings->load();
  	
  	return $this->past_bookings;
  }
  
  public function get_future_bookings() {
  	global $petadmin;
  	
  	$petadmin->bookings->load();
  	
  	return $this->future_bookings;
  }
  
  public function add_pet($pet) {
  	$this->pets[$pet->no] = $pet;
  }
  
  public function add_booking($booking) {
  	$no = $this->no;
  	$this->bookings[$booking->no] = $booking;
  	$today = new DateTime();
  	if ($booking->start_date > $today) {
  		$this->future_bookings[$booking->no] = $booking;
  	} 
  	
  	if ($booking->end_date < $today) {
  		$this->past_bookings[$booking->no] = $booking;
  	}
  	
  	if ($booking->start_date <= $today and $booking->end_date >= $today) {
  		$this->current_bookings[$booking->no] = $booking;
  	}
  	
  	if ($booking->no > $this->max_bk_no) {
  		$this->max_bk_no = $booking->no;
  		$this->deluxe = $booking->deluxe;
  	}
  }
  
  public function home_url() {
  	return home_url("customer/?cust=$this->no");
  }
  
  public function display_name() {
  	if ($this->title == '') {
  		$display_name = '';
  	} else {
  		$display_name = $this->title .  ' ';
  	}
  	
  	if ($this->forename != '') {
  		$display_name .= ' ' . $this->forename;
  	}
  	
  	if ($display_name != '') {
  		$display_name .= ' ';
  	}
  	
  	$display_name .= $this->surname;
  	
  	return $display_name;
  }
}

class Customers {
  public $by_no = array();
  private $by_email = array();
  private $by_telno = array();
  private $by_any = array();
  public $isLoaded = FALSE;
  public $count = 0;

  public function __construct() {
  }

  public function load($force = FALSE) {
  	global $petadmin_db;
  	
  	$sql = "Select cust_no, cust_title, cust_surname, cust_forename, cust_addr1,
cust_addr3, cust_postcode, cust_telno_home, cust_telno_mobile,
cust_telno_mobile2, cust_email, cust_nodeposit from my_customer";
  	
    if ($this->isLoaded and ! $force) {
      return;
    }

    if ($this->isLoaded) {
      $this->by_no = array();
      $this->by_email = array();
      $this->by_telno = array();
      $this->count = 0;
    }

    petadmin_log('Loading customers');
    $result = $petadmin_db->execute($sql);

    foreach($result as $row) {
      $customer = new Customer($row);
      $this->count++;
      $this->by_no[$customer->no] = $customer;
      $this->add_by_any($customer->no, $customer);
      $this->add_by_any(strtolower($customer->surname), $customer);
      if ($customer->email != '') {
        $this->by_email[$customer->email] = $customer;
        $this->add_by_any(strtolower($customer->email), $customer);
      }
      if ($customer->telno_home != '') {
      	$this->by_telno[$customer->telno_home] = $customer;
      	$this->add_by_any($customer->telno_home, $customer);
      }
      if ($customer->telno_mobile != '') {
      	$this->by_telno[$customer->telno_mobile] = $customer;
      	$this->add_by_any($customer->telno_mobile, $customer);
      }
      if ($customer->telno_mobile2 != '') {
      	$this->by_telno[$customer->telno_mobile2] = $customer;
      	$this->add_by_any($customer->telno_mobile2, $customer);
      }
    }
    $this->isLoaded = TRUE;
  }

  private function add_by_any($key, $customer) {
  	if (!array_key_exists($key, $this->by_any)) {
  		$this->by_any[$key] = array();
  	}  	
  	$this->by_any[$key][] = $customer;
  }
  
  	public function get_by_no($no) {
	  	$this->load();
    	return array_key_exists($no, $this->by_no) ? $this->by_no[$no] : NULL;
  	}

	public function get_by_telno($telno) {
	  	$this->load();
	  	return array_key_exists($telno, $this->by_telno) ? $this->by_telno[$telno] : NULL;
  	}

  	public function get_by_email($email) {
	  	$this->load();
	  	return array_key_exists($email, $this->by_email) ? $this->by_email[$email] : NULL;
  	}
  
	public function get_by_any($any) {
		$this->load();
		$lc = strtolower($any);
		return array_key_exists($lc, $this->by_any) ? $this->by_any[$lc] : NULL;
	}
	
	public function get_list() {
		$this->load();
		return $this->by_no;
	}
}

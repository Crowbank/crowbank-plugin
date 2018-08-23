<?php
class Booking {
	public $no;
	public $customer;
	public $pets = array();
	public $pets_in = array();
	public $pets_out = array();
	public $checked_in;
	public $checked_out;
	public $start_date;
	public $end_date;
	public $start_time;
	public $end_time;
	public $gross_amt;
	public $paid_amt;
	public $notes;
	public $memo;
	public $status;
	public $create_date;
	public $deluxe;
	public $has_dogs = FALSE;
	public $has_cats = FALSE;
	public $outstanding_amt;
	public $pet_names;
	public $original_booking;
	
	public function __construct($row) {
		global $petadmin;
		$this->no = (int) $row['bk_no'];
		$cust_no = $row['bk_cust_no'];
		$customer = $petadmin->customers->get_by_no($cust_no);
		$this->customer = $customer;
		$this->start_date = new DateTime($row['bk_start_date']);
		$this->end_date = new DateTime($row['bk_end_date']);
		$this->start_time = $row['bk_start_time'];
		$this->end_time = $row['bk_end_time'];
		$this->gross_amt = $row['bk_gross_amt'];
		$this->paid_amt = $row['bk_paid_amt'];
		$this->outstanding_amt = $this->gross_amt - $this->paid_amt;
		$this->notes = $row['bk_notes'];
		$this->memo = $row['bk_memo'];
		$this->status = $row['bk_status'];
		$this->deluxe = $row['bk_deluxe'];
		$this->original_booking = null;
		if ($this->status == '') {
			$this->status = ' ';
		}
		$this->create_date = new DateTime($row['bk_create_date']);
		
		$this->customer->add_booking($this);
	}

	public function add_pet($pet, $checkin, $checkout) {
		if (!$pet) {
			echo crowbank_error('NULL pet');
			return;
		}
		
		$this->pets[] = $pet;
		if ($checkin) {
			$this->pets_in[$pet->no] = TRUE;
			$this->checked_in = TRUE;
		} else {
			$this->pets_in[$pet->no] = FALSE;
			$this->checked_in = FALSE;
		}
		
		if ($checkout) {
			$this->pets_out[$pet->no] = TRUE;
			$this->checked_out = TRUE;
		} else {
			$this->pets_out[$pet->no] = FALSE;
			$this->checked_out = FALSE;
		}
		
		
		if ($pet->species == 'Dog') {
			$this->has_dogs = TRUE;
		}
		if ($pet->species == 'Cat') {
			$this->has_cats = TRUE;
		}
	}
	
	public function check_pet($pet) {
		foreach ($this->pets as $p) {
			if ($p->no == $pet->no) {
				return true;
			}
		}
		return false;
	}
	
	public function pet_names() {
		$names = '';
		for($i = 0; $i < count($this->pets); $i++) {
			$name = $this->pets[$i]->name;
			if ($i == 0) {
				$names = $name;
			} else if ($i == count($this->pets) - 1) {
				$names .= " and " . $name;
			} else {
				$names .= ", " . $name;
			}
		}
		return $names;
	}
	
	public function is_deluxe() {
		/* return 1 if booking has dogs, and at least one goes to deluxe
		 * return 0 if booking has dogs, and none goes to deluxe
		 * return -1 if booking has no dogs
		 */
	}
	
	public function deposit() {
		if ($this->customer->nodeposit) {
			return 0;
		}

		if ($this->status != ' ') {
			return 0;
		}

		if ($this->paid_amt > 0.0) {
			return 0;
		}

		$has_dogs = FALSE;
		foreach ($this->pets as $pet) {
			if ($pet->species = 'dog') {
				$has_dogs = TRUE;
				break;
			}
		}
		
		$deposit = $has_dogs ? 50.0 : 30.0;
		
		if ($deposit > $this->gross_amt / 2.0) {
			$deposit = $this->gross_amt / 2.0;
		}
		
		return $deposit;
	}
	
	public function deposit_url() {
		$deposit = $this->deposit();
		
		if ($deposit) {
			$url = "https://secure.worldpay.com/wcc/purchase?instId=1094566&cartId=PBL-" . $this->no . "&amount=" . number_format($deposit + 1.20, 2) . "&currency=GBP&";
			$url .= "desc=Deposit+for+Crowbank+booking+" . $this->no . "+for+" . htmlspecialchars($this->pet_names()) . "&accId1=CROWBANKPETBM1&testMode=0";
			$url .= '&name=' . htmlspecialchars($this->customer->display_name());
			
			if ($this->customer->email != '') {
				$url .= '&email=' . htmlspecialchars($this->customer->email);
			}
			
			if ($this->customer->addr1 != '') {
				$url .= '&address1=' . htmlspecialchars($this->customer->addr1);
			}
			
			if ($this->customer->addr3 != '') {
				$url .= '&town=' . htmlspecialchars($this->customer->addr3);
			}
			
			if ($this->customer->postcode != '') {
				$url .= '&postcode=' . htmlspecialchars($this->customer->postcode);
			}
			
			$url .= '&country=UK';
	
			if ($this->customer->telno_home != '') {
				$phone = $this->customer->telno_home;
				if (strlen($phone) == 6) {
					$phone = '01236 ' . $phone;
				}
				$url .= '&tel=' . htmlspecialchars($phone);
			}
	        return $url;
		} else {
			return '';
		}
	}
	
	public function confirmation_url() {
		return home_url('confirmation/?bk_no=' . $this->no . '&cust=' . $this->customer->no);
	}
	
	public function start_time_slot() {
		if ($this->start_time < '12:30') {
			return 'am';
		}
		
		return 'pm';
	}

	public function end_time_slot() {
		if ($this->end_time < '12:30') {
			return 'am';
		}
		
		return 'pm';
	}
	
	public function cancel_booking() {
		global $petadmin_db;
		
		$this->status = 'C';
		$sql = "update my_booking set bk_status = 'C' where bk_no = " . $this->no;
		
		$petadmin_db->execute($sql);
	}
	
	public function update($pets, $start_date, $start_time, $end_date, $end_time, $is_deluxe, $comment, $status, $cost_estimate) {
		global $petadmin_db;
		
		$this->pets = array();
		$this->has_dogs = false;
		$this->has_cats = false;
		
		foreach ($pets as $pet) {
			$this->add_pet($pet, false, false);
		}
		
		$this->start_date = $start_date;
		$this->start_time = time_slot_to_time($start_time, 'in');
		$this->end_date = $end_date;
		$this->end_time = time_slot_to_time($end_time, 'out');
		
		$this->deluxe = $is_deluxe;
		$this->memo = $comment;
		$this->status = $status;
		$this->gross_amt = $cost_estimate;
		
		$sql = "update my_booking set bk_start_date = '" . $start_time->format('Y-m-d') . "', bk_start_time = '" . $this->start_time;
		$sql .= "', bk_end_date = '" . $end_date->format('Y-m-d') . "', bk_end_time = '" . $this->end_time . "', bk_memo = '";
		$sql .= $comment . "', bk_gross_amt = " . $cost_estimate . ", bk_deluxe = " . $is_deluxe;
		$sql .= ' where bk_no = ' . $this->no;
		
		$petadmin_db->execute($sql);
	}
}

class Bookings {
	private $by_no = array();
	private $by_start_date = array();
	private $by_end_date = array();
	public $count = 0;
	
	private $sql;
	private $sql2;
	
	public $isLoaded = FALSE;
	
	public function __construct() {
	}
	
	public function load($force = FALSE) {
		global $petadmin_db, $petadmin;
		
		$sql = "Select bk_no, bk_cust_no, bk_start_date, bk_end_date, bk_start_time,
bk_end_time, bk_gross_amt, bk_paid_amt, bk_notes, bk_memo, bk_status, bk_create_date, bk_deluxe
from my_booking";
		
		$sql2 = "Select bi_bk_no, bi_pet_no, bi_checkin_time, bi_checkout_time from my_bookingitem";
		
		if ($this->isLoaded and ! $force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->by_no = array();
			$this->count = 0;
		}
		
		$petadmin->customers->load();
		$petadmin->pets->load();
		
		petadmin_log('Loading bookings');
		$result = $petadmin_db->execute($sql);
		
		foreach($result as $row) {
			$booking = new Booking($row);
			$this->count++;
			$this->by_no[$booking->no] = $booking;
			
			if ($booking->status != ' ' and $booking->status != 'V' and $booking->status != '') {
				continue;
			}
			$start_ts = $booking->start_date->getTimestamp();
			$end_ts = $booking->end_date->getTimestamp();
			
			if (!array_key_exists($start_ts, $this->by_start_date)) {
				$this->by_start_date[$start_ts] = array();
			}
			
			if (!array_key_exists($end_ts, $this->by_end_date)) {
				$this->by_end_date[$end_ts] = array();
			}
			
			$this->by_start_date[$start_ts][]  = $booking;
			$this->by_end_date[$end_ts][]  = $booking;
			
		}
		
		$result = $petadmin_db->execute($sql2);
		
		foreach($result as $row) {
			$bk_no = $row['bi_bk_no'];
			$pet_no = $row['bi_pet_no'];
			$checkin = $row['bi_checkin_time'];
			$checkout = $row['bi_checkout_time'];
			$booking = array_key_exists($bk_no, $this->by_no) ? $this->by_no[$bk_no] : NULL;
			try {
				$pet = $petadmin->pets->get_by_no($pet_no);
				if ($booking) {
					$booking->add_pet($pet, $checkin, $checkout);
					$pet->add_booking($booking);
				}
			} catch(Exception $e) {
				crowbank_error($e->getMessage());
			}
			
		}
		
		$this->isLoaded = TRUE;
	}
	
	public function get_by_no($no) {
		$this->load();
		return array_key_exists($no, $this->by_no) ? $this->by_no[$no] : NULL;
	}
	
	public function inouts($date, $direction) {
		$this->load();
		
		if ($direction == 'in') {
			return isset($this->by_start_date[$date]) ? $this->by_start_date[$date] : null;
		} else {
			return isset($this->by_end_date[$date]) ? $this->by_end_date[$date] : null;
		}
	}
	
	public function create_booking($customer, $pets, $start_date, $start_time, $end_date, $end_time, $is_deluxe, $comments, $status, $msg_no, $cost_estimate) {
		global $petadmin;
		global $petadmin_db;
		/* 
		 * Create a new temporary booking on mysql. All relevant tables are populated, with a separate mechanism maintaining the records
		 * across database refreshes until the original message is marked as processed.
		 * 
		 * Only my_booking and my_bookingitem need to be updated.
		 */
		
		$sql = 'insert into crowbank_petadmin.my_booking (bk_no, bk_cust_no, bk_start_date, bk_end_date, bk_start_time, bk_end_time, ';
		$sql .= 'bk_gross_amt, bk_paid_amt, bk_memo, bk_notes, bk_status, bk_create_date, bk_deluxe) values (';
		$sql .= -$msg_no . ', ' . $customer->no . ", '" . $start_date->format('Y-m-d') . "', '"  . $end_date->format('Y-m-d') . "', '";
		$sql .= time_slot_to_time($start_time, 'in') . "', '" . time_slot_to_time($end_time, 'out') . "', " . number_format($cost_estimate, 2);
		$sql .= ", 0.0, '" . esc_sql($comments) . "', '', $status, '" . date('Y-m-d') . "', " . $is_deluxe . ')';
		
		$petadmin_db->execute($sql);
		
		foreach ($pets as $pet) {
			$sql = 'insert into crowbank_petadmin.my_bookingitem (bi_bk_no, bi_pet_no, bi_checkin_date, bi_checkin_time, bi_checkout_date, bi_checkout_time)';
			$sql .= ' values (' . -$msg_no . ', ' . $pet->no . ", '', '', '', '')";
			
			$petadmin_db->execute($sql);
		}
		
		$sql = "Select bk_no, bk_cust_no, bk_start_date, bk_end_date, bk_start_time,
bk_end_time, bk_gross_amt, bk_paid_amt, bk_notes, bk_memo, bk_status, bk_create_date, bk_deluxe
from my_booking where bk_no = " . -$msg_no;
		
		$sql2 = "Select bi_bk_no, bi_pet_no, bi_checkin_time, bi_checkout_time from my_bookingitem where bi_bk_no = " . -$msg_no;

		$result = $petadmin_db->execute($sql);
		
		foreach($result as $row) {
			$booking = new Booking($row);
			$this->count++;
			$this->by_no[$booking->no] = $booking;
			
			if ($booking->status != ' ' and $booking->status != 'V' and $booking->status != '') {
				continue;
			}
			$start_ts = $booking->start_date->getTimestamp();
			$end_ts = $booking->end_date->getTimestamp();
			
			if (!array_key_exists($start_ts, $this->by_start_date)) {
				$this->by_start_date[$start_ts] = array();
			}
			
			if (!array_key_exists($end_ts, $this->by_end_date)) {
				$this->by_end_date[$end_ts] = array();
			}
			
			$this->by_start_date[$start_ts][]  = $booking;
			$this->by_end_date[$end_ts][]  = $booking;	
		}
		
		$result = $petadmin_db->execute($sql2);
		
		foreach($result as $row) {
			$bk_no = $row['bi_bk_no'];
			$pet_no = $row['bi_pet_no'];
			$checkin = $row['bi_checkin_time'];
			$checkout = $row['bi_checkout_time'];
			try {
				$pet = $petadmin->pets->get_by_no($pet_no);
				if ($booking) {
					$booking->add_pet($pet, $checkin, $checkout);
					$pet->add_booking($booking);
				}
			} catch(Exception $e) {
				crowbank_error($e->getMessage());
			}	
		}
		return $booking;
	}
	
	public function find_overlapping($customer, $start_date, $end_date) {
		$customer_bookings = $customer->get_bookings();
		
		foreach ( $customer_bookings as $booking ) {
			if ( $booking->start_date <= $end_date and $booking->end_date >= $start_date )
				return true;
		}
		
		return false;
	}
}
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
	public $dog_count = 0;
	public $cat_count = 0;
	public $dog_weight = 0;
	public $outstanding_amt;
	public $pet_names;
	public $original_booking;
	
	const STATUS_ARRAY = array(
			'B' => ['bookingbooking', 'Unconfirmed'],
			' ' => ['bookingbooking', 'Unconfirmed'],
			'V' => ['confirmedbooking', 'Confirmed'],
			'C' => ['cancelledbooking', 'Cancelled'],
			'N' => ['cancelledbooking', 'No Show'],
			'-' => ['pastbooking', ''],
			'A' => ['pastbooking', ''],
			'0' => ['currentbooking', ''],
			'P' => ['standbybooking', 'Provisional'],
			'S' => ['standbybooking', 'Standby'],
			'O' => ['standbybooking', 'Online'],
			'R' => ['requestedbooking', 'Requested'],
			'D' => ['draftbooking', 'Draft']
	);
	
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
			if ($pet->breed->billcat == 'Dog large')
				$this->dog_weight += 3;
			else if ($pet->breed->billcat == 'Dog medium')
				$this->dog_weight += 2;
			else if ($pet->breed->billcat == 'Dog small')
				$this->dog_weight += 1;
			else {
				echo crowbank_error('Bad billing category for ' . $pet->name . '(' . $pet->breed->desc . ')');
				return -1;
			}
			$this->dog_count += 1;

		}
		if ($pet->species == 'Cat') {
			$this->has_cats = TRUE;
			$this->cat_count += 1;
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
		return $this.deluxe;
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
	
	public function deposit_url($callback = '') {
		$deposit = $this->deposit();
		
		if ($deposit) {
			$url = "https://secure.worldpay.com/wcc/purchase?instId=1094566&cartId=PBL-";
			if ($this->status == 'D') {
				$url .= 'D';
			}
			$url .= abs($this->no) . "&amount=" . number_format($deposit + 1.20, 2) . "&currency=GBP&";
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
			
			if ($callback != '') {
				$url .= '&MC_callback=' . $callback;
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
	
	public function get_cost_estimate() {
		global $petadmin_db;

		if ($this->dog_count < 3 and $this->cat_count < 3) {
			$sql = "select rate_start_date, rate_category, rate_service, rate_amount from my_rate order by rate_start_date desc";
			$result = $petadmin_db->execute($sql);
			
			foreach($result as $row) {
				$rate_start_date = new DateTime($row['rate_start_date']);
				if ($rate_start_date > $this->start_date) {
					continue;
				}
				
				$rate_category = $row['rate_category'] . $row['rate_service'];
				if (!isset($rates[$rate_category])) {
					$rates[$rate_category] = (float) $row['rate_amount'];
				}
			}
					
			$night_rate = 0.0;
			$first_dog = true;
			$first_cat = true;
			foreach ($this->pets as $pet) {
				$rate_category = $pet->breed->billcat;
				if ($pet->species == 'Dog' and $this->deluxe == 1) {
					$rate_category = 'Deluxe';
				}
				
				if (($pet->species == 'Dog' and $first_dog) or (($pet->species == 'Cat' and $first_cat))) {
					$rate_service = 'BOARD';
					if ($pet->species == 'Dog') {
						$first_dog = FALSE;
					}
					if ($pet->species == 'Cat') {
						$first_cat = FALSE;
					}
				} else {
					$rate_service = 'BOARD2';
				}
				
				$rate = $rates[$rate_category . $rate_service];
				$night_rate += $rate;
			}
			
			$interval = $this->start_date->diff($this->end_date)->format("%a");
			if ($this->end_time_slot() == 'pm') {
				$interval += 1;
			}
				
			$cost_estimate = $night_rate * $interval;
		} else {
			$cost_estimate = 0.0;
		}
		return $cost_estimate;
	}

	public function get_cost_comment() {
		if ($this->dog_count == 2 and $this->cat_count < 2) {
			$cost_comment = ' (with both dogs sharing a kennel)';
		} else if ($this->dog_count == 2 and $this->cat_count == 2) {
			$cost_comment = ' (with both dogs sharing a kennel and both cats sharing a pen)';
		} else if ($this->dog_count < 2 and $this->cat_count == 2) {
			$cost_comment = ' (with both cats sharing a pen)';
		} else {
			$cost_comment = 'We will return to you with a total charge';
		}

		return $cost_comment;
	}

	private function check_availability($dog_weight) {
		global $petadmin_db;
		
		$sql = "call pa_booking_availability('" . $this->start_date->format('Y-m-d') . "', '" . $this->end_date->format('Y-m-d');
		$sql .= "', '" . $this->end_time_slot() . "', " . $this->deluxe . ", " . $this->dog_count . ", " . $this->cat_count . ", " . $dog_weight . ");";
		
		$result = $petadmin_db->execute($sql);
		
		if ($result) {
			$availability = $result[0]['availability'];
		}
		else {
			$msg = 'Failed running ' . $sql;
			echo crowbank_error($msg);
			return 1;
		}

		return $availability;
	}

	public function get_availability() {
		$availability = $this->check_availability($this->dog_weight);

		if ($availability == 2 and $this->dog_weight > 3 and $this->deluxe == 0) {
			$standard_availability = $this->check_availability(2);
			if ($standard_availability < 2) {
				$availability = 1;
			}
		}
	
		return $availability;
	}
	
	
	public function cancel_booking() {
		global $petadmin_db;
		
		$this->status = 'C';
		$sql = "update my_booking set bk_status = 'C' where bk_no = " . $this->no;
		
		$petadmin_db->execute($sql);
	}
	
	public function update_from_draft($draft_booking) {
		$this->update($draft_booking->pets, $draft_booking->start_date, $draft_booking->start_time, $draft_booking->end_date,
				$draft_booking->end_time, $draft_booking->is_deluxe, $draft_booking->comments, $draft_booking->status,
				$draft_booking->cost_estimate, $draft_booking->bk_paid_amt);	
	}
	
	public function update($pets, $start_date, $start_time, $end_date, $end_time, $is_deluxe, $comment, $status, $cost_estimate, $paid_amt = 0.0) {
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
		$sql .= $comment . "', bk_gross_amt = " . $cost_estimate . ", bk_deluxe = " . $is_deluxe . ", bk_paid_amt = " . $paid_amt;
		$sql .= ' where bk_no = ' . $this->no;
		
		$petadmin_db->execute($sql);
	}

	public function html($style, $time, $buttons = NULL) {
		$status = $this->status;
		if ($status == '') {
			$status = 'B';
		}
		if ($time == 'present') {
			$status = '0';
		} else if ($time == 'past' and $status != 'C' and $status != 'N') {
			$status = '-';
			$status_desc = '';
		}
		
		$status_desc = STATUS_ARRAY[$status][1];
		$status_class = STATUS_ARRAY[$status][0];
		$start = $this->start_date->format('d/m/y') . ' ' . $this->start_time;;
		$end = $this->end_date->format('d/m/y') . ' ' . $this->end_time;
		
		if ($style == 'card') {
			return $this->html_card($status_class, $status_desc, $start, $end, $buttons);
		} else if ($style == 'row') {
			return $this->html_row($status_class, $status_desc, $start, $end, $buttons);
		}
		return '';
	}
	
	private function html_card($status_class, $status_desc, $start, $end, $buttons = NULL) {
		$r = '<div class="booking ' . $status_class . '">';
		$r .= '<div class="label">Bk #</div><div class="content">';
		if ($this->no > 0) {
			$r .= $this->no;
		} else {
			$r .= 'N/A';
		}
		$r .= '</div>';
		if ($status_desc) {
			$r .= '<div class="label">Status</div><div class="content">' . $status_desc . '</div>';
		} else {
			$r .= '<div class="label"></div><div class="content"></div>';
		}
		$r .= '<div class="label">Start</div><div class="content">' . $start . '</div>';
		$r .= '<div class="label">End</div><div class="content">' . $end . '</div>';
		$r .= '<div class="label">Pets</div><div class="span3 content">' . $this->pet_names() . '</div>';
		$r .= '<div style="padding-top: 0px"></div><div class="buttonRow span4"><div class="label">Gross</div>';
		$r .= '<div class="content">£' . number_format($this->gross_amt, 2) . '</div>';
		$r .= '<div class="label">Paid</div><div class="content">£' . number_format($this->paid_amt, 2). '</div>';
		$r .= '<div class="label">Balance</div><div class="content">£' . number_format($this->gross_amt-$this->paid_amt, 2). '</div>';
		$r .= '</div><div style="padding-top: 0px"></div>';
		if ($buttons) {
			$r .= '<div class="buttonRow span4">';
			foreach ($buttons as $b) {
				$r .= '<div><a href="' . $b['link'] . '" class="crowbank_button button' . $b['type'] . '">' . $b['title'] . '</a></div>'; 
			}
			$r .= '</div>';
		}
		$r .= '</div>';
		
		return $r;
	}
	
	private function html_row($status_class, $status_desc, $start, $end, $buttons = NULL) {
		$r = '<tr class="' . $status_class . '"><td>';
		if ($this->no > 0) {
			$r .= $this->no;
		} else {
			$r .= 'N/A';
		}
		$r .= '</td><td>' . $start . '</td><td>' . $end . '</td><td>';
		foreach ($this->pets as $pet) {
			$r .= "$pet->name<br>";
		}
		$r .= "</td><td align=right>" . number_format($this->gross_amt, 2) . "</td><td align=right>" . number_format($this->paid_amt, 2) .
		"</td><td align=right>" . number_format($this->gross_amt-$this->paid_amt, 2) . "</td><td>$status_desc</td><td>";
		
		foreach($buttons as $b) {
			$r .= '<a class="table_button booking_edit_button" href="' . $update_url . '">Modify <span class="fa fa-fw fa-edit"></span></a>';
			$r .= "</td>";
		}

		$r .= '</tr>';
		return $r;
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
	
	public function create_booking($customer, $pets, $start_date, $start_time, $end_date, $end_time, $is_deluxe, $comments, $status, $cost_estimate,
			$bk_no) {
		global $petadmin;
		global $petadmin_db;
		/* 
		 * Create a new temporary booking on mysql. All relevant tables are populated, with a separate mechanism maintaining the records
		 * across database refreshes until the original message is marked as processed.
		 * 
		 * Only my_booking and my_bookingitem need to be updated.
		 */
		
		$id = crowbank_localid(['type' => 'booking']);
		
		$sql = 'insert into crowbank_petadmin.my_booking (bk_no, bk_cust_no, bk_start_date, bk_end_date, bk_start_time, bk_end_time, ';
		$sql .= 'bk_paid_amt, bk_memo, bk_notes, bk_status, bk_create_date, bk_deluxe) values (';
		$sql .= -$id . ', ' . $customer->no . ", '" . $start_date->format('Y-m-d') . "', '"  . $end_date->format('Y-m-d') . "', '";
		$sql .= time_slot_to_time($start_time, 'in') . "', '" . time_slot_to_time($end_time, 'out') . "'";
		$sql .= ", 0.0, '" . esc_sql($comments) . "', '";
		if ($bk_no != 0) {
			$sql .= $bk_no;
		}
		$sql .= "', '" . $status . "', '" . date('Y-m-d') . "', " . $is_deluxe . ')';
		
		$petadmin_db->execute($sql);
		
		foreach ($pets as $pet) {
			$sql = 'insert into crowbank_petadmin.my_bookingitem (bi_bk_no, bi_pet_no, bi_checkin_date, bi_checkin_time, bi_checkout_date, bi_checkout_time)';
			$sql .= ' values (' . -$id . ', ' . $pet->no . ", '', '', '', '')";
			
			$petadmin_db->execute($sql);
		}
		
		$row = array('bk_no' => -$id, 'bk_cust_no' => $customer->no, 'bk_start_date' => $start_date->format('Y-m-d'),
				'bk_end_date' => $end_date->format('Y-m-d'), 'bk_start_time' => time_slot_to_time($start_time, 'in'),
				'bk_end_time' => time_slot_to_time($end_time, 'out'), 
				'bk_paid_amt' => 0.0, 'bk_notes' => '', 'bk_memo' => $comments, 'bk_status' => $status, 'bk_deluxe' => $is_deluxe,
				'bk_create_date' => (new DateTime())->format('Y-m-d'));
		$booking = new Booking($row);
		
		foreach ($pets as $pet) {
			$booking->add_pet($pet, '', '');
		}
		
		if (!$cost_estimate) {
			$cost_estimate = $booking->get_cost_estimate();
		}

		$sql = 'update crowbank_petadmin.my_booking set bk_gross_amt = ' . number_format($cost_estimate, 2) . ' where bk_no = ' . $booking->no;
		$petadmin_db->execute($sql);

		$this->count++;
		$this->by_no[$booking->no] = $booking;
			
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
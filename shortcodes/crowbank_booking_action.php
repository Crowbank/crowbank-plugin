<?php
function crowbank_booking_confirmation($attr = [], $content = null, $tag = '') {
	global $petadmin, $petadmin_db, $wp;
	
	
	/*
	 * http://192.168.0.200/wordpress/booking-request-confirmation/?
	 * bk_no=
	 * pets=162,%2013704
	 * start_date=02%2F28%2F2018
	 * start_time=am
	 * end_date=03%2F07%2F2018
	 * end_time=pm
	 * kennel=Deluxe
	 * comments=Take+good+care+of+them%21
	 * action=true
	 */
	$current_url = home_url(add_query_arg(array(), $wp->request));
	
	$customer = get_customer();
	
	$booking = null;
	$bk_no = 0;
	
	if (isset($_REQUEST['bk_no'])) {
		$bk_no = $_REQUEST['bk_no'];
		if ($bk_no) {
			$booking = $petadmin->bookings->by_no($bk_no);
			if (!$booking or $booking->customer->no <> $customer->no) {
				return crowbank_error('Invalid Booking');
			}
		}
	}

	if (isset($_REQUEST['pets'])) {
		$pets = htmlspecialchars_decode($_REQUEST['pets']);
	} else { 
		return crowbank_error('Must select at least one pet');
	}

	$pets_array = explode(', ', $pets);
	$pet_names = '';
	$dog_count = 0;
	$cat_count = 0;
	
	foreach ($pets_array as $pet_no) {
		$pet = $petadmin->pets->get_by_no($pet_no);
		if (!$pet)
			return crowbank_error('Unable to find pet #' . $pet_no);
		
		if ($pet->species == 'Dog')
			$dog_count += 1;
		else
			$cat_count += 1;
	}
	
	if (isset($_REQUEST['start_date'])) {
		$start_date = new DateTime(htmlspecialchars_decode($_REQUEST['start_date']));
	} else {
		return crowbank_error('Invalid or missing start date');
	}
	
	if (isset($_REQUEST['start_time'])) {
		$start_time = $_REQUEST['start_time'];
	} else {
		return crowbank_error('Invalid or missing start time');
	}
	
	if (isset($_REQUEST['end_date'])) {
		$end_date = new DateTime(htmlspecialchars_decode($_REQUEST['end_date']));
	} else {
		return crowbank_error('Invalid or missing end date');
	}
	
	if (isset($_REQUEST['end_time'])) {
		$end_time = $_REQUEST['end_time'];
	} else {
		return crowbank_error('Invalid or missing end time');
	}
	
	if (isset($_REQUEST['kennel'])) {
		$kennel = $_REQUEST['kennel'];
	} else {
		$kennel = 'Standard';
	}
	
	$comments = '';
	if (isset($_REQUEST['comments'])) {
		$comments = htmlspecialchars_decode($_REQUEST['comments']);
	}
	
	if (isset($_REQUEST['action'])) {
		$action = $_REQUEST['action'];
	} else {
		crowbank_error('Must select choice of action');
	}
	
	if ($bk_no) {
		$msg_type = 'booking-update';
	} else {
		$msg_type = 'booking-request';
	}
	
	if ($deluxe == 'Deluxe')
		$is_deluxe = 1;
	else 
		$is_deluxe = 0;

	$sql = "select rate_start_date, rate_category, rate_service, rate_amount from my_rate order by rate_start_date desc";
	$result = $petadmin_db->execute($sql);
	
	foreach($result as $row) {
		$rate_start_date = new DateTime($row['rate_start_date']);
		if ($rate_start_date > $start_date) {
			continue;
		}
		
		$rate_category = $row['rate_category'] . $row['rate_service'];
		if (!isset($rates[$rate_category])) {
			$rates[$rate_category] = (float) $row['rate_amount'];
		}
	}
	
		
	$sql = "call pa_booking_availability('" . $start_date->format('Y-m-d') . "', '" . $end_date->format('Y-m-d');
	$sql .= "', '" . $end_time . "', " . $is_deluxe . ", " . $dog_count . ", " . $cat_count . ");";
	
	if ($dog_count < 3 and $cat_count < 3) {
		$night_rate = 0.0;
		$first_dog = true;
		$first_cat = true;
		foreach ($pets_array as $pet) {
			$rate_category = $pet->breed->billcat;
			if ($pet->species == 'Dog' and $is_deluxe == 1) {
				$rate_category = 'Deluxe';
			}
			
			if (($pet->species == 'Dog' and $first_dog) or (($pet->species == 'Dog' and $first_dog))) {
				$rate_service = 'BOARD';
			} else {
				$rate_service = 'BOARD2';
			}
			
			$rate = $rates[$rate_category . $rate_service];
			$night_rate += $rate;
		}
		
		
	}
	
	$msg = new Message($msg_type, ['cust_no' => $customer->no, 'bk_no' => $bk_no, 'pets' => $pets, 'start_date' => $start_date->format('Ymd'),
			'start_time' => $start_time, 'end_date' => $end_date->format('Ymd'), 'end_time' => $end_time, 'kennel' => $kennel,
			'comments' => $comments, 'action' => $action]);
	$msg->flush();
	
	if ($bk_no) {
		$r = 'Your booking amendment request has been processed - you should get an email confirmation shortly';
	} else {
		$r = 'Your booking request has been processed - you should get an email confirmation shortly';
	}
	
	return $r;
}

function crowbank_booking_cancellation($attr = [], $content = null, $tag = '') {
	global $petadmin;
	global $wp;
	
	$current_url = home_url(add_query_arg(array(), $wp->request));
	
	if (!isset($_REQUEST['bk_no']))
		return crowbank_error('Missing Booking Number');
		
		$bk_no = $_REQUEST['bk_no'];
		if ($bk_no == 0)
			return crowbank_error('Invalid Booking');
			
			$customer = get_customer();
			
			if (!$customer) {
				return crowbank_error('No customer');
			}
			
			$booking = $petadmin->bookings->get_by_no($bk_no);
			
			if (!$booking) {
				return crowbank_error('Invalid Booking Number');
			}
			
			if ($customer->no != $booking->customer->no) {
				return crowbank_error('Wrong customer');
			}
			
			$msg = new Message('booking-cancel',
					['cust_no' => $customer->no, 'bk_no' => $booking->no]);
			$msg->flush();
			
			$r = 'Your cancellation request has been processed - you should get an email confirmation shortly';
			return $r;
}

function crowbank_booking_cancellation_aa($attr = [], $content = null, $tag = '') {
	global $petadmin;
	global $wp;
	
	$current_url = home_url(add_query_arg(array(), $wp->request));
	
	if (!isset($_REQUEST['bk_no']))
		return crowbank_error('Missing Booking Number');
		
		$bk_no = $_REQUEST['bk_no'];
		if ($bk_no == 0)
			return crowbank_error('Invalid Booking');
			
			$customer = get_customer();
			$booking = $petadmin->bookings->get_by_no($bk_no);
			
			if (!$booking) {
				return crowbank_error('Invalid Booking Number');
			}
			
			if ($customer->no != $booking->customer->no) {
				return crowbank_error('Wrong customer');
			}
			
			if(isset($_REQUEST['confirmed'])) {
				$msg = new Message('booking-cancel',
						['cust_no' => $customer->no, 'bk_no' => $booking->no]);
				$msg->flush();
				
				$r = 'Your cancellation request has been processed';
				return $r;
			}
			
			$confirm_url = $current_url . '?bk_no=' . $bk_no . '&cust=' . $customer->no . '&confirmed=1';
			
			$r = '<script>function goBack() {window.history.back() }</script>';
			$r .= '<div class="um-col-alt">';
			$r .= '<span text-align="center">Are you sure you want to cancel the booking?</span><br>';
			$r .= '<div style="height: 30px"></div>';
			$r .= '<a class="cancel_booking_button um-left um-half" href="' . $confirm_url . '">Yes</a>';
			$r .= '<button onclick="goBack()" class="go_back_button um-right um-half">No</button></div>';
			
			return $r;
}

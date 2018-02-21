<?php
/*
 * Run as an action at the top of the booking-request-confirmation form.
 * Generate booking summary for confirmation, including input parameters,
 * and the result of availability and cost estimate.
 * 
 * Those results are injected into an empty html element in the form.
 * Finally, the function modifies the title of the Submit button to correspond
 * to the nature of the default action.
 * 
 * A sepearate function, running *after* booking-request-confirmation is submitted,
 * will create both the temporary booking and the Message back to Crowbank.
 */

function check_booking_confirmation( $form ) {
	global $petadmin, $petadmin_db, $wp;

	$customer = get_customer();
	
	populate_customer_details( $form );
	
	$booking = null;
	$bk_no = 0;
	
	if (isset($_REQUEST['bk_no'])) {
		$bk_no = $_REQUEST['bk_no'];
		if ($bk_no) {
			$booking = $petadmin->bookings->by_no($bk_no);
			if (!$booking or $booking->customer->no <> $customer->no) {
				echo crowbank_error('Invalid Booking');
				return -1;
			}
		}
	}
	
	if (isset($_REQUEST['pets'])) {
		$pet_numbers = htmlspecialchars_decode($_REQUEST['pets']);
	} else {
		echo crowbank_error('Must select at least one pet');
		return -1;
	}
	
	$pets_array = explode(', ', $pet_numbers);
	$pet_names = '';
	$dog_count = 0;
	$cat_count = 0;
	$pets = array();
	
	foreach ($pets_array as $pet_no) {
		$pet = $petadmin->pets->get_by_no($pet_no);
		if (!$pet) {
			echo crowbank_error('Unable to find pet #' . $pet_no);
			return -1;
		}

		if ($pet->species == 'Dog')
			$dog_count += 1;
		else
			$cat_count += 1;
		$pets[] = $pet;
	}
	
	if (isset($_REQUEST['start_date'])) {
		$start_date = new DateTime(htmlspecialchars_decode($_REQUEST['start_date']));
	} else {
		echo crowbank_error('Invalid or missing start date');
		return -1;
	}
	
	if (isset($_REQUEST['start_time'])) {
		$start_time = $_REQUEST['start_time'];
	} else {
		echo crowbank_error('Invalid or missing start time');
		return -1;
	}
	
	if (isset($_REQUEST['end_date'])) {
		$end_date = new DateTime(htmlspecialchars_decode($_REQUEST['end_date']));
	} else {
		echo crowbank_error('Invalid or missing end date');
		return -1;
	}
	
	if (isset($_REQUEST['end_time'])) {
		$end_time = $_REQUEST['end_time'];
	} else {
		echo crowbank_error('Invalid or missing end time');
		return -1;
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
		
	if ($kennel == 'Deluxe') {
		$is_deluxe = 1;
	}
	else {
		$is_deluxe = 0;
	}
			
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
	
	$result = $petadmin_db->execute($sql);
	
	foreach ($result as $row) {
		$availability = $row['availability'];
		$availability_statement = $availability_responses[$availability];
	}	
		
	if ($dog_count < 3 and $cat_count < 3) {
		$night_rate = 0.0;
		$first_dog = true;
		$first_cat = true;
		foreach ($pets as $pet) {
			$rate_category = $pet->breed->billcat;
			if ($pet->species == 'Dog' and $is_deluxe == 1) {
				$rate_category = 'Deluxe';
			}
			
			if (($pet->species == 'Dog' and $first_dog) or (($pet->species == 'Cat' and $first_cat))) {
				$rate_service = 'BOARD';
			} else {
				$rate_service = 'BOARD2';
			}
			
			$rate = $rates[$rate_category . $rate_service];
			$night_rate += $rate;
		}
		
		$interval = $start_date->diff($end_date)->format("%a");
		if ($end_time == 'pm') {
			$interval += 1;
		}
			
		$cost_estimate = $night_rate * $interval;
		
		if ($dog_count == 2 and $cat_count < 2) {
			$cost_comment = ' (with both dogs sharing a kennel)';
		} else if ($dog_count == 2 and $cat_count == 2) {
			$cost_comment = ' (with both dogs sharing a kennel and both cats sharing a pen)';
		} else if ($dog_count < 1 and $cat_count == 2) {
			$cost_comment = ' (with both cats sharing a pen)';
		}
	} else {
		$cost_estimate = '';
		$cost_comment = 'We will return to you with a total charge';
	}
	
	/* with preparation out of the way, start populating the html element */
	
	$r = '<div class="booking-confirmation"><table class="table"><tbody>';
	$r .= '<tr><td class="field-name">Pets</td><td class="field-value">';
	foreach ($pets as $pet) {
		$r .= $pet->description() . '<br>';
	}
	$r .= '</td></tr>';
	
	$r .= '<tr><td class="field-name">Arriving</td><td class="field-value">';
	$r .= $start_date->format('D d/m/y') . ' ';
	if ($start_time == 'am') {
		$r .= '11:00';
	} else {
		$r .= '16:00';
	}
	$r .= '</td></tr>';
	
	$r .= '<tr><td class="field-name">Leaving</td><td class="field-value">';
	$r .= $end_date->format('D d/m/y') . ' ';
	if ($end_time == 'am') {
		$r .= '10:00';
	} else {
		$r .= '14:00';
	}
	$r .= '</td></tr>';
	
	$r .= '<tr><td class="field-name">Estimated Cost</td><td class="field-value">';
	if ($cost_estimate) {
		$r .= '£' . number_format($cost_estimate, 2, '.', ',');
		if ($cost_comment) {
			$r .= '<br>' . $cost_comment;
		}
	} else {
		$r .= $cost_comment;
	}
	
	$r .= '</td></tr>';
	
	$r .= '<tr><td class="field-name">Availability</td><td class="field-value';
	
	
	if ($availability == 0) {
		$r .= ' free">Good availability for all requested dates';
		$form['button']['text'] = 'Create Booking';
	} else if ($availability == 1) {
		$r .= ' busy">Limited availability for some dates - please await confirmation';
		$form['button']['text'] = 'Submit Booking Request';
	} else {
		$r .= ' full">No availability for some dates - we suggest a standby booking';
		$form['button']['text'] = 'Create Standby Booking';
	}
		
	$r .= '</td></tr>';
	$r .= '</tbody></table></div>';
	
	foreach ( $form['fields'] as &$field ) {
		
		if ( $field->type == 'html' and $field->label == 'Booking Summary' ) {
			$field->content = $r;
		}
		
		if ( $field->type == 'hidden' and $field->label == 'Availability') {
			$field->defaultValue = $availability;
		}
		
		if ( $field->type == 'hidden' and $field->label == 'Cost Estimate') {
			$field->defaultValue = $cost_estimate;
		}
	}
}


function crowbank_booking_confirmation($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	/*
	 * This function is called *after* the customer submitted the booking confirmation form.
	 * It is invoked as part of the form Confirmation, i.e. all relevant fields are populated in the GET object.
	 * It is also invoked after the form Notification, i.e. an email message was sent to Crowbank, and the entry added to the database.
	 * 
	 * The purpose of this function, then, is to create a temporary booking entry that allows the customer to see an immediate
	 * feedback of his request. This booking has the special status 'R' for requested.
	 * 
	 * It also sends an appropriate message to Crowbank, facilitating the creation (or modification) of the actual booking
	 */
	
	$availability_responses = array('A new booking will be created, and you should receive an email confirmation shortly',
			'We will have to check availability, and get back to you',
			'We will create a standby booking for you, and will get back in touch as soon as space becomes available');
	
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
	 * availabililty=0,1, or 2
	 * cost_estimate=123.45
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
		$pet_numbers = htmlspecialchars_decode($_REQUEST['pets']);
	} else { 
		return crowbank_error('Must select at least one pet');
	}

	$pets_array = explode(', ', $pet_numbers);
	$pet_names = '';
	$pets = array();
	
	foreach ($pets_array as $pet_no) {
		$pet = $petadmin->pets->get_by_no($pet_no);
		if (!$pet)
			return crowbank_error('Unable to find pet #' . $pet_no);
		
		$pets[] = $pet;
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
	
	if (isset($_REQUEST['availabililty'])) {
		$availability = $_REQUEST['availability'];
	} else {
		crowbank_error('Must select choice of action');
	}
	
	if (isset($_REQUEST['cost_estimate'])) {
		$cost_estimate = $_REQUEST['cost_estimate'];
	}
	
	if ($bk_no) {
		$msg_type = 'booking-update';
	} else {
		$msg_type = 'booking-request';
	}
	
	if ($kennel == 'Deluxe')
		$is_deluxe = 1;
	else 
		$is_deluxe = 0;

	
	$msg = new Message($msg_type, ['cust_no' => $customer->no, 'bk_no' => $bk_no, 'pets' => $pet_numbers, 'start_date' => $start_date->format('Ymd'),
			'start_time' => $start_time, 'end_date' => $end_date->format('Ymd'), 'end_time' => $end_time, 'kennel' => $kennel,
			'comments' => $comments, 'availability' => $availability, 'cost_estimate' => $cost_estimate]);
	
	$msg->flush();
	
	if ($availability == 0) {
		$status = 'B';
	} else if ($availability == 1) {
		$status = 'P';
	} else if ($availability == 2) {
		$status = 'S';
	}
	
	$petadmin->bookings->create_booking($customer, $pets, $start_date, $start_time,
			$end_date, $end_time, $is_deluxe, $comments, $status, $msg->id, $cost_estimate);
	
	if ($bk_no) {
		$r = 'Your booking amendment request has been processed.<br>You should get an email confirmation shortly.';
	} else {
		$r = 'Your booking request has been processed.<br>';
		$r .= $availability_statement;
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

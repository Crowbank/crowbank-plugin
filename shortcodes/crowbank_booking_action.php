<?php
/*
 * Run as an action at the top of the booking-request-confirmation form.
 * Generate booking summary for confirmation, including input parameters,
 * and the result of availability and cost estimate.
 * 
 * Those results are injected into an empty html element in the form.
 * The function also fills the Subject and Pet Names hidden fields used
 * in the email notification sent upon successful completion.
 * The function modifies the title of the Submit button to correspond
 * to the nature of the default action.
 * 
 * A sepearate function, running *after* booking-request-confirmation is submitted,
 * will create both the temporary booking and the Message back to Crowbank.
 */

function check_booking_confirmation( $form ) {
	/* This function is called after submitting form 25 (booking) and before form 26 (Booking Request Confirmation) is rendered.
	 * That form is part of the Booking Request Confirmation page, which is the confirmation follow-up from form 25 (Booking)
	 * The REQUEST object, at this point, contains the value of all the fields filled in the Booking form.
	 * The Booking form can either be called empty (for a new booking), or full (to modify an existing booking).
	 * In the latter case, the bk_no is set to non-zero.
	 * 
	 * To facilitate some of the functionality here and anticipating the potential need to save a draft booking, one is created
	 * using the data fields. That is the case whether or not bk_no is set. If it is, that bk_no is also included in the draft booking.
	 */
	global $petadmin, $petadmin_db, $wp;

	$availability_responses = array('A new booking will be created, and you should receive an email confirmation shortly',
			'We will have to check availability, and get back to you',
			'We will create a standby booking for you, and will get back in touch as soon as space becomes available');
	
	$customer = get_customer();
	
	$booking = null;
	$bk_no = 0;
	$changed_fields = array();
	
	if (isset($_REQUEST['bk_no'])) {
		$bk_no = $_REQUEST['bk_no'];
		if ($bk_no) {
			/* We are following a modification of an existing booking */
			$booking = $petadmin->bookings->get_by_no($bk_no);
			if (!$booking or $booking->customer->no <> $customer->no) {
				echo crowbank_error('Invalid Booking');
				return -1;
			}
		}
	}
	
	/* Process pets in the booking */ 
	if (isset($_REQUEST['pets'])) {
		$pet_numbers = htmlspecialchars_decode($_REQUEST['pets']);
	} else {
		echo crowbank_error('Must select at least one pet');
		return -1;
	}
	
	$pets_array = explode(', ', $pet_numbers);
	sort($pets_array);
	$pet_numbers = implode(',', $pets_array);
	
	if ($booking) {
		$original_pet_numbers = '';
		foreach($booking->pets as $p) {
			if ($original_pet_numbers) {
				$original_pet_numbers .= ',';
			}
			$original_pet_numbers .= $p->no;
		}
		
		if ($original_pet_numbers <> $pet_numbers) {
			$changed_fields[] = 'pets';
		}
	}
	
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

		if ($pet->species == 'Dog') {
			$dog_count += 1;
		} else {
			$cat_count += 1;
		}
		$pets[] = $pet;
	}

	for($i = 0; $i < count($pets); $i++) {
		$name = $pets[$i]->name;
		if ($i == 0) {
			$pet_names = $name;
		} else if ($i == count($pets) - 1) {
			$pet_names .= " and " . $name;
		} else {
			$pet_names .= ", " . $name;
		}
	}
	
/* Process start date/time */

	if (isset($_REQUEST['start_date'])) {
		$start_date = new DateTime(htmlspecialchars_decode($_REQUEST['start_date']));
		if ($booking and $start_date->getTimestamp() != $booking->start_date->getTimestamp()) {
			$changed_fields[] = 'start';
		}
	} else {
		echo crowbank_error('Invalid or missing start date');
		return -1;
	}
	
	if (isset($_REQUEST['start_time'])) {
		$start_time = $_REQUEST['start_time'];
		if ($booking and $start_time != $booking->start_time_slot()) {
			$changed_fields[] = 'start';
		}
	} else {
		echo crowbank_error('Invalid or missing start time');
		return -1;
	}
	
	/* Process end date/time */

	if (isset($_REQUEST['end_date'])) {
		$end_date = new DateTime(htmlspecialchars_decode($_REQUEST['end_date']));
		if ($booking and $end_date->getTimestamp() != $booking->end_date->getTimestamp()) {
			$changed_fields[] = 'end';
		}
	} else {
		echo crowbank_error('Invalid or missing end date');
		return -1;
	}
	
	if (isset($_REQUEST['end_time'])) {
		$end_time = $_REQUEST['end_time'];
		if ($booking and $end_time != $booking->end_time_slot()) {
			$changed_fields[] = 'end';
		}
	} else {
		echo crowbank_error('Invalid or missing end time');
		return -1;
	}

	/* Process booking type (deluxe vs. standard) */
	
	if (isset($_REQUEST['kennel'])) {
		$kennel = $_REQUEST['kennel'];
	} else {
		$kennel = 'Standard';
	}
	
	if ($kennel == 'Deluxe') {
		$is_deluxe = 1;
	}
	else {
		$is_deluxe = 0;
	}
	
	if ($booking and $booking->deluxe != $is_deluxe) {
		$changed_fields[] = 'deluxe';
	}
	
/* Process comment field */

	$comment = '';
	if (isset($_REQUEST['comment'])) {
		$comment = htmlspecialchars_decode($_REQUEST['comment']);
		if ($booking and $booking->memo != $comment) {
			$changed_fields[] = 'comment';
		}
	}
	
	/* At this point, validation tests are done, and we need to create a draft booking
	 * 
	 */
	
	if ($booking) {
		$msg_type = 'booking-update';
	} else {
		$msg_type = 'booking-request';
	}

	$draft_booking = $petadmin->bookings->create_booking($customer, $pets, $start_date, $start_time, $end_date, $end_time,
		$is_deluxe, $comment, 'D');

	$cost_estimate = $draft_booking->get_cost_estimate();

	if ($booking and $cost_estimate != $booking->gross_amt) {
		$changed_fields[] = 'cost';
	}
	
	$cost_comment = $draft_booking->get_cost_comment();

	/* Work out availability */

	$availability = $draft_booking->get_availability();
	$availability_statement = $availability_responses[$availability];

	/* with preparation out of the way, start populating the html element */

	$overlapping = false;
	
	if (!$booking) {
		$overlapping = $petadmin->bookings->find_overlapping($customer, $start_date, $end_date);
	}
	
	foreach ( $form['fields'] as &$field ) {
		if ( $field->label == 'Draft Booking Number') {
			$field->defaultValue = $draft_booking->no;
		}
	}
	
	if ($booking) {
		$draft_booking->original_booking = $booking;
	}
	
	$deposit = $draft_booking->deposit();
	$deposit_url = '';
	if ($deposit > 0.0) {
		/* need to require deposit */
		$callback = 'http://37.19.30.17/wordpress/booking-request-followup';
		$deposit_url = $draft_booking->deposit_url($callback);
	}
	
	$r = '<div class="booking-confirmation"><table class="table"><tbody>';
	$r .= '<tr' . (in_array('pets', $changed_fields) ? ' class="changed_field"' : '') . '><td class="field-name">Pets</td><td class="field-value">';
	foreach ($pets as $pet) {
		$r .= $pet->description() . '<br>';
	}
	$r .= '</td></tr>';
	
	$r .= '<tr' . (in_array('start', $changed_fields) ? ' class="changed_field"' : '') . '><td class="field-name">Arriving</td><td class="field-value">';
	$r .= $start_date->format('D d/m/y') . ' ';
	if ($start_time == 'am') {
		$r .= '11:00';
	} else {
		$r .= '16:00';
	}
	$r .= '</td></tr>';
	
	$r .= '<tr' . (in_array('end', $changed_fields) ? ' class="changed_field"' : '') . '><td class="field-name">Leaving</td><td class="field-value">';
	$r .= $end_date->format('D d/m/y') . ' ';
	if ($end_time == 'am') {
		$r .= '10:00';
	} else {
		$r .= '14:00';
	}
	$r .= '</td></tr>';
	
	if ($dog_count > 0) {
		$r .= '<tr' . (in_array('deluxe', $changed_fields) ? ' class="changed_field"' : '') . '><td class="field-name">Kennel</td><td class="field-value">';
		if ($is_deluxe) {
			$r .= 'Deluxe';
		} else {
			$r .= 'Standard';
		}
		$r .= '</td></tr>';
	}
	
	$r .= '<tr' . (in_array('cost', $changed_fields) ? ' class="changed_field"' : '') . '><td class="field-name">Estimated Cost</td><td class="field-value">';
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
		if ($deposit > 0) {
			$form['button']['text'] = 'Pay Deposit';
			$r .= '. A deposit of £' . $deposit . ' will be required to create booking';
		} else {
			$form['button']['text'] = 'Create Booking';
		}
	} else if ($availability == 1) {
		$r .= ' busy">Limited availability for some dates - please await confirmation';
		$form['button']['text'] = 'Submit Booking Request';
	} else {
		$r .= ' full">No availability for some dates - we suggest a standby booking';
		$form['button']['text'] = 'Create Standby Booking';
	}
	
	if ($booking) {
		$form['button']['text'] = 'Amend Booking';
	}
		
	$r .= '</td></tr>';
	
	$r .= '<tr' . (in_array('comment', $changed_fields) ? ' class="changed_field"' : '') . '><td class="field-name">Comment</td><td class="field-value">';
	$r .= $comment . '</td></tr>';
	
	$r .= '</tbody></table></div>';
	
	$subject = 'Booking Request from ' . $customer->surname . ' for ' . $pet_names . ' - ';
	if ($availability == 0) {
		if ($deposit > 0) {
			$subject .= 'Deposit Paid';
			$status = 'C';
		} else {
			$subject .= 'Booking Created';
			$status = '';
		}
	} else if ($availability == 1) {
		$subject .= 'Provisional Booking Created - Must Check';
		$status = 'P';
	} else if ($availability == 2) {
		$subject .= 'Standby Booking Created';
		$status = 'S';
	}
	
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
		
		if ( $field->type == 'hidden' and $field->label == 'Subject') {
			$field->defaultValue = $subject;
		}
	
		if ( $field->type == 'hidden' and $field->label == 'Pet Names') {
			$field->defaultValue = $pet_names;
		}
		
		if ( $field->type == 'hidden' and $field->label == 'Draft Booking Number') {
			$field->defaultValue = $draft_booking->no;
		}
		
		if ( $field->type == 'hidden' and $field->label == 'Deposit') {
			$field->defaultValue = $deposit;
		}
		
		if ( $field->type == 'hidden' and $field->label == 'Deposit URL') {
			$field->defaultValue = $deposit_url;
		}
		
		if ( $field->type == 'hidden' and $field->label == 'Status') {
			$field->defaultValue = $status;
		}
	}
	
	return $form;
}
add_filter( 'gform_pre_render_26', 'check_booking_confirmation' );

function crowbank_booking_confirmation($attr = [], $content = null, $tag = '') {
	global $petadmin, $wp;
	
	/*
	 * This function is called *after* the customer submitted the booking confirmation form.
	 * It is invoked as part of the form Confirmation, i.e. all relevant fields are populated in the GET object.
	 * Alternatively, it can be invoked by the callback from WorldPay deposit payment
	 * It is also invoked after the form Notification, i.e. an email message was sent to Crowbank, and the entry added to the database.
	 *
	 * Before this function is called, a draft booking will have been created.
	 * 
	 * This function converts the draft booking into a normal booking, with one of a few status conditions:
	 * 1. If a deposit has just been paid, a 'C' (confirmed) booking can be created
	 * 2. If a deposit was not required, but availability is good, a '' (normal) booking is created.
	 * 3. If availability is limited, a 'P' (provisional) booking is created.
	 * 4. If there is no availability, a 'S' (standby) booking is created
	 * 
	 * It also sends an appropriate message to Crowbank, facilitating the creation (or modification) of the actual booking locally
	 */
	
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
	
	
	/* determine whether we have come here as callback from WorldPay deposit payment */
	
	$s = '';
	
	$booking = null;
	$bk_no = 0;
	
	$paid_amt = 0.0;
	
	$availability_responses = array('A new booking will be created, and you should receive an email confirmation shortly',
			'We will have to check availability, and get back to you',
			'We will create a standby booking for you, and will get back in touch as soon as space becomes available');
	
	$current_url = home_url(add_query_arg(array(), $wp->request));
	
	$customer = get_customer();
	
	if (isset($_REQUEST['authAmountString'])) {
		$cart = $_REQUEST['cartId'];
		$paid_amt = $_REQUEST['cost'];
		preg_match_all('/PBL-D(\d+)/', $cart, $matches);
		
		$draft_bk_no = -1 * $matches[1][0];

		$s = 'Thank you for paying the ' . $_REQUEST['authAmountString'] . ' deposit - ';
		$s .= 'Your booking is now confirmed';
		
		$status = 'C';
		
	} else {
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
		
		if (isset($_REQUEST['status'])) {
			$status = $_REQUEST['status'];
		} else {
			return crowbank_error('Invalid or missing status');
		}
		
		if (isset($_REQUEST['kennel'])) {
			$kennel = $_REQUEST['kennel'];
		} else {
			$kennel = 'Standard';
		}
		
		$comment = '';
		if (isset($_REQUEST['comment'])) {
			$comment = htmlspecialchars_decode($_REQUEST['comment']);
		}
		
		if (isset($_REQUEST['availability'])) {
			$availability = $_REQUEST['availability'];
		} else {
			$availability = 1;
		}
		
		if (isset($_REQUEST['cost_estimate'])) {
			$cost_estimate = $_REQUEST['cost_estimate'];
		}
	
		if (isset($_REQUEST['draft_bk_no'])) {
			$draft_bk_no = 	$_REQUEST['draft_bk_no'];
		} else {
			$draft_bk_no = 0;
		}
	}

	$draft_booking = $petadmin->bookings->get_by_no($draft_bk_no);
	if (!$draft_booking) {
		return crowbank_error('Cannot find draft booking (' . $draft_bk_no . ')');
	}
	
	if ($draft_bk_no->notes) {
		$bk_no = intval($draft_bk_no->notes);
		$booking = $petadmin->bookings->get_by_no($bk_no);
	} elseif (isset($_REQUEST['bk_no'])) {
		$bk_no = $_REQUEST['bk_no'];
		if ($bk_no) {
			$booking = $petadmin->bookings->get_by_no($bk_no);
			if (!$booking or $booking->customer->no <> $customer->no) {
				return crowbank_error('Invalid Booking');
			}
		}
	}

	if ($booking and $booking->paid_amt > 0.0) {
		$paid_amt = $booking->paid_amt;
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


	/*
	 * Check for duplicate forms
	 */

	$overlapping = false;
	
	if (!$bk_no) {
		$overlapping = $petadmin->bookings->find_overlapping($customer, $start_date, $end_date);
	}
	
	$msg = new Message($msg_type, ['cust_no' => $customer->no, 'bk_no' => $bk_no, 'pets' => $pet_numbers,
			'start_date' => $start_date->format('Ymd'), 'start_time' => $start_time, 'end_date' => $end_date->format('Ymd'),
			'end_time' => $end_time, 'deluxe' => $is_deluxe, 'comment' => $comment, 'availability' => $availability,
			'cost_estimate' => $cost_estimate, 'status' => $status, 'overlapping' => $overlapping, 'status' => $status,
			'paid_amt' => $paid_amt, 'draft_no' => $draft_bk_no]);
	
	$msg->flush();
	
	if ($booking) {
		$booking->update($pets, $start_date, $start_time, $end_date, $end_time, $is_deluxe, $comment, $status, $cost_estimate);
	} else {
		if ($overlapping) {
			$msg = 'Overlapping booking - no action';
			echo crowbank_error($msg);
		} else {
			$petadmin->bookings->create_booking($customer, $pets, $start_date, $start_time,
				$end_date, $end_time, $is_deluxe, $comment, $status, $cost_estimate);
		}
	}
	
	if ($bk_no) {
		$r = 'Your booking amendment request has been processed.<br>You should get an email confirmation shortly.';
	} else {
		$r = 'Your booking request has been processed.<br>';
		$r .= $availability_responses[$availability];
	}
	
	$r .= '<br><a href="http://dev.crowbankkennels.co.uk/my" class="w3-btn w3-blue">Return to Crowbank Home Screen</a>';
	
	return $r;
}
add_shortcode('crowbank_booking_confirmation', 'crowbank_booking_confirmation');

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
			
			$booking->cancel_booking();
			
			$r = 'Your cancellation request has been processed - you should get an email confirmation shortly';
			return $r;
}
add_shortcode('crowbank_booking_cancellation', 'crowbank_booking_cancellation');

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

function populate_booking_form ($form) {
	/* 
	 * This function is called to populate the (visible) booking request form.
	 * If this is a new booking, only pet names are populated.
	 * If this is an edit of an existing booking, all other fields are also populated.
	 */
	global $petadmin;
	
	$customer = get_customer();
	
	if (!$customer) {
		return $form;
	}
	
	$bk_no = 0;
	$booking = null;
	
	if (isset($_REQUEST['bk_no'])) {
		$bk_no = $_REQUEST['bk_no'];
		$booking = $petadmin->bookings->get_by_no($bk_no); 
		if ($booking->customer->no != $customer->no) {
			echo crowbank_error('Customer/booking mismatch');
			return $form;
		}
	}

	
	$pets = $customer->get_pets();
	
	foreach ( $form['fields'] as &$field ) {
		
		if ( $field->label == 'Pets') {
			$choices = array();
			
			foreach ($pets as $pet) {
				if ($pet->deceased != 'N') {
					continue;
				}
				
				$in_booking = true;
				if ($booking and ! $booking->check_pet($pet)) {
					$in_booking = false;
				}

				$choices[] = array( 'text' => $pet->name, 'value' => $pet->no, 'isSelected' => $in_booking);
			}
			
			// update 'Select a Post' to whatever you'd like the instructive option to be
			$field->choices = $choices;
		}
		
		if (!$booking) {
			continue;
		}
		
		if ($field->label == 'Start Date') {
			$field->defaultValue = $booking->start_date->format('Y-m-d');
		}
		
		if ($field->label == 'Start Time') {
			$start_time_slot = $booking->start_time_slot();
			
			$field->choices[0]['isSelected'] = ($start_time_slot == 'am');
			$field->choices[1]['isSelected'] = ($start_time_slot == 'pm');
		}
		
		if ($field->label == 'End Date') {
			$field->defaultValue = $booking->end_date->format('Y-m-d');
		}
		
		if ($field->label == 'End Time') {
			$end_time_slot = $booking->end_time_slot();
			
			$field->choices[0]['isSelected'] = ($end_time_slot == 'am');
			$field->choices[1]['isSelected'] = ($end_time_slot == 'pm');
		}
		
		if ($field->label == 'Kennel Type') {
			$field->choices[0]['isSelected'] = !$booking->deluxe;
			$field->choices[1]['isSelected'] = $booking->deluxe;
		}
		
		if ($field->label == 'Comments') {
			$field->defaultValue = $booking->memo;
		}
	}
	
	return $form;
}
add_filter( 'gform_pre_render_25', 'populate_booking_form' );

function booking_followup_confirmation( $confirmation ) {
	global $petadmin;
	
	$url = parse_url($confirmation['redirect']);
	$query = $url['query'];
	parse_str($query, $v);
	
	$draft_bk_no = $v['draft_bk_no'];
	$deposit = $v['deposit'];
	$deposit_url = $v['deposit_url'];
	
	if ($deposit > 0.0 and $deposit_url <> '') {
		$confirmation = array('redirect' => $deposit_url);
	}
	return $confirmation;	
}
add_filter( 'gform_confirmation_26', 'booking_followup_confirmation', 10, 4 );




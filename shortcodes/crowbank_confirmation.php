<?php
function confirmation_deposit($booking) {
	global $petadmin;
	
	$customer = $booking->customer;
	
	if ($booking->status == ' ' and $customer->nodeposit == 0 and $booking->paid_amt == 0.0) {
		if ($booking->has_dogs)
			$deposit = 50.0;
			else
				$deposit = 30.0;
				
				if ($deposit > $booking->gross_amt / 2.0)
					$deposit = $booking->gross_amt / 2.0;
	} else
		$deposit = 0.0;

	return $deposit;
}

function confirmation_title($booking) {
	global $petadmin;
	
	$customer = $booking->customer;
	$deposit = confirmation_deposit($booking);
		
	if ($booking->status == 'C')
		$title = 'Booking Cancellation';
	elseif ($booking->status == 'S')
		$title = 'Standby Booking';
	else {
		if ($deposit > 0.0) {
			if ($booking->deluxe)
				$title = 'Provisional Deluxe Booking';
			else
				$title = 'Provisional Booking';
		} else {
			if ($booking->deluxe)
				$title = 'Confirmed Deluxe Booking';
			else
				$title = 'Confirmed Booking';
		}
	}
	
	return $title;
}

function confirmation_body($booking, $format = 'html') {
	global $petadmin;

	$customer = $booking->customer;
	$deposit = confirmation_deposit($booking);
	$title = confirmation_title($booking);
	$deposit_url = $booking->deposit_url();
	$bk_no = $booking->no;
	
	if ($format == 'html') {
		$img = 'https://image.ibb.co/eKA0bk/Crowbank_Kennels_Logo.jpg';

		$c = '<style>.main-table {style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="2"}
	.conf-title {border-top: 1pt solid rgb(164, 166, 166); border-bottom: 1pt solid rgb(164, 166, 166); background-color: rgb(214, 224, 226);}
	.field-name {font-weight: bold; }
	.field-value {text-align: right; }
	.small-print {font-size: small; font-style: italic; }
	html, div, span, table, tr, td {vertical-align: inherit;}
	</style>';

		$c .= '<div style="font-family: Calibri; width=800px"><table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="2">
	<tbody><tr><td style="width: 585px;"><img style="width: 322px; height: 111px;" alt="Crowbank Kennels and Cattery" src="' . $img . '"/>
	</td><td style="width: 195px; font-weight: bold; vertical-align: top;">Crowbank Pet Boarding<br>
	Crowbank House, Arns<br>Cumbernauld G67 3JW<br>United Kingdom</td></tr><tr><td style="vertical-align: top;"><br>';

		$c .= $customer->display_name() . '<br>';
		$c .= '<nobr>' . $customer->addr1 . '</nobr><br/>';
		$c .= '<nobr>' . $customer->addr3 . '</nobr><br/>';
		$c .= '<nobr>' . $customer->postcode . '</nobr><br/></td>';
		$c .= '<td style="font-weight: bold; width: 195px; vertical-align: top;"><br>Tel: 01236 729454<br>
	<a href="http://www.crowbank.co.uk" target="_blank">www.crowbank.co.uk</a><br>
	<a href="mailto:info@crowbank.co.uk">info@crowbank.co.uk</a><br>
	Look for us on <a href="https://www.facebook.com/crowbank/" target="_blank">Facebook</a></td>
	</tr></tbody></table>';

		$c .= '<br><table class="main-table"><tbody><tr align="center"><td class="conf-title" colspan="3" rowspan="1">
	<span class="field-name" style="font-size: large;">';

		$c .= $title . '</span></td></tr><tr><td style="width: 333px;"></td><td style="width: 149px;"></td><td style="width: 401px;"></td></tr>';

		$c .= '<tr><td class="field-name">Booking No</td>';
		$c .= '<td class="field-value">' . $bk_no . '</td><td></td></tr>';

		$c .= '<tr><td class="field-name">Arriving</td>';
		$c .= '<td class="field-value">' . $booking->start_date->format('D d/m/y') . ' ' . substr($booking->start_time, 0, 5) . '</td><td></td></tr>';

		$c .= '<tr><td class="field-name">Leaving</td>';
		$c .= '<td class="field-value">' . $booking->end_date->format('D d/m/y') . ' ' . substr($booking->end_time, 0, 5) . '</td><td></td></tr>';

		if ($booking->status != 'C') {
			$c .= '<tr><td class="field-name">Total Amount</span></td>';
			$c .= '<td class="field-value">&pound;' . number_format($booking->gross_amt, 2) . '</td><td></td></tr>';
		}

		$c .= '<tr><td class="field-name">Paid</td>';
		$c .= '<td class="field-value">&pound;' . number_format($booking->paid_amt, 2) . '</td><td></td></tr>';

		if ($booking->status != 'C') {
			$c .= '<tr><td class="field-name">Outstanding</td>';
			$c .= '<td class="field-value">&pound;' . number_format($booking->outstanding_amt, 2) . '</td><td></td></tr>';
		}

		$c .= '<tr><td></td><td></td><td></td></tr><tr>';

		if ($booking->deluxe) {
			$border = 'rgb(93, 65, 172)';
			$background = 'rgb(170, 154, 215)';
		} elseif ($booking->status == 'C') {
			$border = 'rgb(164, 166, 166)';
			$background = 'rgb(210, 160, 140)';
		} else {
			$border = 'rgb(164, 166, 166)';
			$background = 'rgb(214, 224, 226)';
		}

		$c .= '<td style="border-top: 1pt solid ' . $border . '; border-bottom: 1pt solid ' . $border . '; background-color: ' . $background;
		$c .= '; width: 149px; text-align: center;" colspan="2" rowspan="1">';
		$c .= '<big><big><span style="font-weight: bold;">Guests</span></big></big></td><td style="width: 401px;"></td></tr><tr>';

		foreach ($booking->pets as $pet) {
			$c .= '<td colspan="1" rowspan="1" style="text-align: left; width: 333px;">' . $pet->name . ' (' . $pet->breed->desc . ')</td>';
			$c .= '<td style="text-align: right; width: 149px;">' . $pet->species . '</td>';
			$c .= '<td style="width: 401px;"></td></tr>';
		}

		$c .= '</tbody></table><br><br>';

		if ($booking->status == 'C') {
			$c .= 'Your booking has been <span style="font-weight: bold;">cancelled.</span>';
			if ($booking->paid_amt > 0.0) {
				$c .= 'As laid out in our Terms and Conditions, your deposit of &pound;' . number_format($booking->paid_amt, 2) . ' is non-refundable.';
			}
			$c .= 'We\'re sorry to see you go, but we hope you\'ll be back soon!<br><br>';
		} else {
			if ($deposit > 0.0) {
				$deposit_icon = 'https://image.ibb.co/kTue45/paydeposit.png';

				$c .= 'This is a <span style="font-weight: bold;">provisional booking</span>.';

				$c .= 'Please check the information above to ensure the details are correct.
	To secure the booking, please pay a <span style="font-weight: bold;">non-refundable* deposit of &pound;' . number_format($deposit, 2) . ' ( + a transaction fee of &pound;1.20).</span><br>';
				$c .= '<span style="font-style: italic;">We may cancel or resell the booking if the deposit is not paid within 7 days.</span><br>';
				$c .= '<br><div style="text-align: center;"><a href="' . $deposit_url . '" target="_blank">';
				$c .= '<img style="border: 0px solid ; width: 179px; height: 120px;" alt="Pay Deposit" src="' . $deposit_icon . '"></a><br>';
				$c .=  '<style="text-align: center">No Image? <a href="' . $deposit_url . '" target="_blank">Click Here to Pay Deposit</a></style><br>';
				$c .= '</div><br><span style="font-style: italic;">By paying your deposit you agree <span style="font-weight: bold;">in full</span>
	to our boarding </span><a style="font-style: italic;" href="http://www.crowbank.co.uk/terms-and-conditions/" target="_blank">Terms
	and Conditions</a><span style="font-style: italic;">,
	including those regarding <a href="http://www.crowbank.co.uk/vaccinations/" target="_blank">vaccinations</a>. </span>';
			} else {
				if ($booking->paid_amt > 0.0) {
					$c .= 'Your payment of &pound;' . number_format($booking->paid_amt, 2) . ' has been received, and your booking has been confirmed. ';
				} else {
					$c .= 'Your booking has been confirmed. ';
				}
			}

			$c .= 'Please check the information above to ensure the details are correct. Thank you and we\'ll see you on ' . $booking->start_date->format('d/m/Y') . '.<p></p>';
			$c .= 'By making this booking you agree <strong>in full</strong>
	to our boarding <a style="font-style: italic;" href="http://www.crowbank.co.uk/terms-and-conditions/" target="_blank">Terms
	and Conditions</a>, including those regarding <a href="http://www.crowbank.co.uk/vaccinations/" target="_blank">vaccinations</a>.';
		}

		$c .= '<div class="small-print"><span style="color: rgb(204, 0, 0); "><br>All pets must have a full set of up-to-date vaccinations. All dogs must also have a current annual 
		<strong>Kennel Cough vaccination</strong>. Kennel Cough vaccine must be administered <strong>at least 7 days</strong> prior to boarding. Ask your vet to be sure!</span><br>
	<br>We <span style="font-weight: bold;">only</span> accept and discharge guests during our <span style="font-weight: bold;">opening hours:</span><br style="font-weight: bold;"><br>';


		$c .= '<table style="text-align: left; width: 340px; height: 88px;" border="0" cellpadding="2" cellspacing="2">
	<tbody><tr><td></td><td style="width: 122px;"><span style="font-weight: bold;">Mon-Sat</span></td>
	<td style="width: 161px;">10:00-12:30</td></tr><tr><td></td>
	<td style="width: 122px;"></td><td style="width: 161px;">14:00-17:30</td></tr><tr><td></td><td style="width: 122px;"></td><td style="width: 161px;"></td>
	</tr><tr><td style="height: 25px;"></td><td style="width: 122px; height: 25px;"><span style="font-weight: bold;">Sun</span></td>
	<td style="width: 161px; height: 25px;">10:00-13:00</td></tr></tbody></table><br>';

		if ($deposit > 0 and $booking->status <> 'C') {
			$c .= '<div><small><span style="font-style: italic;">*Deposits are refundable up to 7 days after payment.
	Please contact us via <a href="mailto:crowbank.partners@gmail.com">email</a> to cancel your booking
	and your deposit will be refunded in full. After 7 days, deposits are <strong>non-refundable.</strong>
	</span></small><br></div><br>';
		}

		$c .= '<div style="text-align: center;"><strong>All bookings are subject to
	Crowbank Pet Boarding\'s <a href="http://www.crowbank.co.uk/terms-and-conditions/" target="_blank">Terms
	and Conditions</a></strong><br></div><br>
	This confirmation has been automatically generated. If you have received it in error, or if the details are incorrect, please reply and we will address any concerns you may have. Thank you!
	</div></div>';
	} else {
		$c = 'Crowbank Kennels and Cattery
Crowbank Pet Boarding
Crowbank House, Arns
Cumbernauld G67 3JW
United Kingdom
		
';
		$c .= $customer->display_name() . '
' . $customer->addr1 . '
' . $customer->addr3 . '
' . $customer->postcode . '
United Kingdom
		
Tel: 01236 729454
www.crowbank.co.uk
info@crowbank.co.uk
		
' . $title . '

';
if ($booking->status == 'P') {
	$c .= 'We have received your inquiry regarding booking ';
	$first = true;
	foreach ($booking->pets as $pet) {
		if (!$first)
			$c .= ', ';
		$first = false;
		$c .= $pet->name;
	}

	$c .= ' between ' . $booking->start_date->format('d/m/Y') . ' and ' . $booking->end_date->format('d/m/Y');

	$c .= '		
At the moment, we have availability for those dates at a total cost of ' . number_format($booking->gross_amt, 2) . '.
		
If you would like us to make the booking, please pay a deposit of ' . number_format($deposit, 2) . ' by pasting the following to your browser:
' . $deposit_url . '.
		
Alternatively, call us at 01236 729454 to pay over the phone.
	
Note we cannot guarantee availability until we receive your deposit. Once we receive your deposit, we will send you an email confirmation.
		
		
By paying your deposit you agree in full to our boarding Terms and Conditions (available on our website) including those regarding vaccinations.
		
All pets must have a full set of up-to-date vaccinations. All dogs must also have a current annual Kennel Cough vaccination.
Kennel Cough vaccine must be administered *at least* 7 days prior to boarding. Ask your vet to be sure!
		
We only accept and discharge guests during our opening hours:
		
Mon-Sat 10:00-12:30 and 14:00-17:30
Sun 10:00-13:00
		
		
		
*Deposits are refundable up to 7 days after payment. Please contact us via email to info@crowbank.co.uk to cancel your booking
and your deposit will be refunded in full. After 7 days, deposits are non-refundable.';
	} else {
	$c .= 'Booking No: ' . $booking->no . '
		
Arriving:  ' . $booking->start_date->format('d/m/Y') . '
Leaving: ' . $booking->end_date->formaT('d/m/Y') . '
';
if (!$booking->status == 'C') {
	$c .= 'Total Amount: ' . number_format($booking->gross_amt, 2) . '
';
}
	$c .= 'Paid: ' . number_format($booking->paid_amt, 2) . '
';
	if (!$booking->status == 'C') {
	$c .= 'Outstanding: ' . number_format($booking->outstanding_amt, 2) . '
';
	}
	
	$c .= '
Guests
';
	foreach($booking->pets as $pet) {
		$c .= $pet->name . '(' . $pet->breed->desc . ') [' . $pet->species . ']
';		
}
	if ($booking->status == 'C') {
		$c .= 'Your booking has been cancelled.
';
		if ($booking->paid_amt > 0.0) {
			$c .= 'As laid out in our Terms and Conditions, your deposit of ' . number_format($booking->paid_amt, 2) . ' is non-refundable.
';
		}
		$c .= 'We\'re sorry to see you go, but we hope you\'ll be back soon!
';		
	} else {
		if ($deposit > 0.0) {
			$c .= 'This is a provisional booking.

Please check the information above to ensure the details are correct.
To secure the booking, please pay a non-refundable* deposit of ' . number_format($deposit, 2) . '
		
We may cancel or resell the booking if the deposit is not paid within 7 days.
		
		
Paste the following url to your browser to pay: ' . $deposit_url . '
		
By paying your deposit you agree in full to our boarding Terms and Conditions (available on our website)
including those regarding vaccinations.
';		
		}
		if ($booking->paid_amt > 0) {
			$c .= 'Your payment of ' . number_format($booking->paid_amt, 2) . ' has been received, and your booking has been confirmed.
';
		} else {
			$c .= 'Your booking has been confirmed.
';
		}
		
		$c .= 'Please check the information above to ensure the details are correct.
Thank you and we\'ll see you on ' . $booking->start_date->format('d/m/Y') . '!
				
				
By making this booking you agree in full to our boarding Terms and Conditions including those regarding vaccinations.
';
	}				
				

	$c .= 'All pets must have a full set of up-to-date vaccinations. All dogs must also have a current annual Kennel Cough vaccination.
Kennel Cough vaccine must be administered <strong>at least 7 days prior to boarding. Ask your vet to be sure!
				
We only accept and discharge guests during our opening hours:

Mon-Sat 10:00-12:30 and 14:00-17:30
Sun 10:00-13:00
';

	if ($deposit > 0 and !$booking->status == 'C') {	
		$c .= '*Deposits are refundable up to 7 days after payment. Please contact us via email to info@crowbank.co.uk to cancel your booking
and your deposit will be refunded in full. After 7 days, deposits are non-refundable.
';
	}
	
	$c .= 'All bookings are subject to Crowbank Pet Boarding\'s Terms and Conditions

This email has been automatically generated. If you have received this email in error, or if the details are incorrect, please reply and we will
address any concerns you may have. Thank you!';
	}
}
	return $c;
}

function send_confirmation($booking, $html, $text, $subject, $email) {
	$customer = $booking->customer;
	$from = "Crowbank Kennels and Cattery <info@crowbank.co.uk>";
	$bcc = "confirmations@crowbank.co.uk";
	
	$headers = array('To: ' . $email, 'From: '. $from, 'Bcc: ' . $bcc);
	
	$target = array($email, $bcc);
	$body = array('text/html' => $html, 'text/plain' => $text);
	$ret = wp_mail( $target, $subject, $body, $headers, null );
	
	
	if ($ret) {
		crowbank_log ('sending email failed', 3);
	}
	
	$deposit = confirmation_deposit($booking);
	
	$msg = new Message('confirmation-sent',
			['cust_no' => $customer->no, 'bk_no' => $booking->no,
					'email' => $email, 'subject' => $subject, 'deposit' => $deposit
			]);
	$msg->flush();
	
	return 'OK';
}

function crowbank_confirmation($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);

	$attr = shortcode_atts([ 'bk_no' => 0, 'format' => 'D, j/m'], $attr, $tag);

	/* Use $_REQUEST as conatins both $_GET and $_POST */

	$requestType = $_SERVER['REQUEST_METHOD'];

	if (!isset($_REQUEST['bk_no'])) {
		return crowbank_error('No Booking Number');
	}

	$bk_no = $_REQUEST['bk_no'];

	$booking = $petadmin->bookings->get_by_no($bk_no);

	if (!$booking) {
		return crowbank_error('Invalid Booking Number');
	}

	$customer = get_customer();
	
	if ($customer->no != $booking->customer->no) {
		return crowbank_error('Wrong customer');
	}

	$body = confirmation_body($booking);
	$title = confirmation_title($booking);
	$deposit = confirmation_deposit($booking);
	
	$c = '<div style="border: solid 1px; padding: 10px;">' . $body . '</div>';
	
	$role = get_um_role();
	if ($role == 'employee' or $role == 'admin') {
		if ($requestType == 'POST') {
			/* A POST means this page is accessed following a send-confirmation event;
			 * regenerate confirmation, send as email and then display return message */
			$email = $_REQUEST['email-conf'];
			$text = confirmation_body($booking, 'text');
			$code = send_confirmation($booking, $body, $text, $title, $email); 
	
			
			$f = crowbank_error('Confirmation sent - ' . $code);
		} else {
			$f = '';
			$email = $customer->email;
		}

		/* add form to send email */
		$f .= '<div class="um-form"><form method="post" action>';
		$f .= '<div class="um-field-label"><label>E-Mail</label>';
		$f .= '<span class="um-tip um-tip-w" title="Address to which to send confirmation">';
		$f .= '<i class="um-icon-help-circled"></i></span>';
		$f .= '<div class="um-clear"></div></div>';
		$f .= '<div class="um-field-area um-half um-left">';
		$f .= '<input class="um-form-field valid" type="text" name="email-conf" value="';
		$f .= $email . '"></div>';
		
		$f .= '<input type="hidden" name="cust_no" value="';
		$f .= $customer->no . '">';
		$f .= '<input type="hidden" name="bk_no" value="';
		$f .= $booking->no . '">';
		$f .= '<div class="um-half um-right">';
		$f .= '<input type="submit" value="Send" class="um-button">';
		$f .= '</div></form></div>';
		
		$c = $f . $c;
	}
	return $c;
}

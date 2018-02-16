<?php
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
	$r .= 'Are you sure you want to cancel the booking?<br>';
	$r .= '<a class="um-button um-left um-half" href="' . $confirm_url . '">Yes</a>';
	$r .= '<button onclick="goBack()" class="um-button um-alt">No</button></div>';

	return $r;
}

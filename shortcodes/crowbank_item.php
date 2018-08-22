<?php
function crowbank_toggle($attr = [], $content = null, $tag = '') {
	$attr = array_change_key_case((array)$attr, CASE_LOWER);
	
	$attr = shortcode_atts([ 'name' => 'toggle', 'on' => 'On', 'off' => 'Off', 'on-class' => '', 'off-class' => ''], $attr, $tag);
	
	$name = $attr['name'];
	
	return	'<input class="tgl tgl-flat" id="' . $name . '" type="checkbox">
<label class="tgl-btn" data-tg-off="' . $attr['off'] . '" data-tg-on="' . $attr['on'] . '" for="' . $name . '"></label>';
}
add_shortcode('crowbank_toggle', 'crowbank_toggle');

function crowbank_item($attr = [], $content = null, $tag = '') {
	global $petadmin;
	global $wp;
	
	$current_url = home_url(add_query_arg(array(), $wp->request));
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);

	$attr = shortcode_atts([ 'type' => '', 'format' => 'D, j/m', 'page' => $current_url, 'text' => 'Link', 'default' => ''], $attr, $tag);

	if (!isset($attr['type'])) {
		return crowbank_error('type attribute must be specified');
	}

	$type = $attr['type'];

	if ($type == 'prev-day')
		return crowbank_prev_day($attr);
	elseif ($type == 'next-day') 
		return crowbank_next_day($attr);
	elseif ($type == 'prev-week')
		return crowbank_prev_week($attr);
	elseif ($type == 'next-week')
		return crowbank_next_week($attr);
	elseif ($type == 'prev-month')
		return crowbank_prev_month($attr);
	elseif ($type == 'next-month')
		return crowbank_next_month($attr);
	elseif ($type == 'today')
		return crowbank_today($attr);
	elseif ($type == 'weekstart')
		return crowbank_weekstart($attr);
	elseif ($type == 'date_link')
		return crowbank_date_link($attr);
	elseif ($type == 'new-booking-request')
		return crowbank_new_booking_request($attr);
	elseif ($type == 'new-pet')
		return crowbank_new_pet($attr);
	elseif ($type == 'edit-customer')
		return crowbank_edit_customer($attr);
	elseif ($type == 'owner-holiday')
		return crowbank_owner_holiday($attr);
	elseif ($type == 'alerts') {
		$default = $attr['default'];
		return crowbank_show_alerts($default);
	}
	elseif ($type == 'test-message')
		return crowbank_test_message($attr);
		else
		return crowbank_error("Unknown crowbank_item type $type");
}
add_shortcode('crowbank_item', 'crowbank_item');

function crowbank_prev_day($attr) {
	global $petadmin;

	$date = get_daily_date();

	$day = new DateInterval('P1D');

	$page = $attr['page'];

	$yesterday = clone($date);
	$yesterday->sub($day);

	return '<a href="' . $page . '?date=' . $yesterday->format('Y-m-d') . '">Previous Day</a>';
}

function crowbank_next_day($attr) {
	global $petadmin;

	$date = get_daily_date();

	$page = $attr['page'];
	
	$day = new DateInterval('P1D');

	$tomorrow = clone($date);
	$tomorrow->add($day);

	return '<a href="' . $page . '?date=' . $tomorrow->format('Y-m-d') . '">Next Day</a>';
}

function crowbank_prev_week($attr) {
	global $petadmin;
	$weekstart = get_weekstart();
	
	$week = new DateInterval('P7D');
	$page = $attr['page'];
	
	$prev = clone($weekstart);
	$prev->sub($week);
	
	return '<a href="' . $page . '/?weekstart=' . $prev->format('Y-m-d') . '">Previous Week</a>';
}

function crowbank_next_week($attr) {
	global $petadmin;
	$weekstart = get_weekstart();
	
	$week = new DateInterval('P7D');
	$page = $attr['page'];
	
	$next = clone($weekstart);
	$next->add($week);
	
	return '<a href="' . $page . '/?weekstart=' . $next->format('Y-m-d') . '">Next Week</a>';
}

function crowbank_prev_month($attr) {
	global $petadmin;
	$month = get_month();
	$year = get_year();
	$page = $attr['page'];
	
	$thisMonth = date("m", time());
	$thisYear = date("Y", time());
	
	$prevMonth = $month == 1 ? 12 : intval($month) - 1;
	$prevYear = $month == 1 ? intval($year) - 1 : $year;
	
	if ($prevYear < $thisYear or ($prevYear == $thisYear and $prevMonth < $thisMonth))
		return 'Previous Month';
	
	return '<a href="' . $page . '/?month=' . $prevMonth . '&year=' . $prevYear . '">Previous Month</a>';
}

function crowbank_next_month($attr) {
	global $petadmin;
	$month = get_month();
	$year = get_year();
	$page = $attr['page'];
	
	$nextMonth = $month == 12 ? 1 : intval($month) + 1;
	$nextYear = $month == 12 ? intval($year) + 1 : $year;
	
	return '<a href="' . $page . '/?month=' . $nextMonth . '&year=' . $nextYear . '">Next Month</a>';
}


function crowbank_today($attr) {
	$date = get_daily_date();
	$format = $attr['format'];

	return $date->format($format);
}

function crowbank_date_link($attr) {
	$date = get_daily_date();
	$page = $attr['page'];
	$text = $attr['text'];

	return '<a href="' . $page . '?date=' . $date->format('Y-m-d') . '">' . $text . '</a>';
}

function crowbank_weekstart($attr) {
	$weekstart = get_weekstart();
	$format = 'd/m/Y';

	return $weekstart->format($format);
}

function crowbank_new_booking_request($attr) {
	$customer = get_customer();
	
	$request_url = home_url('booking-request/?cust=' . $customer->no);
	
	
	$r = '<a class="booking_request_button" href="' . $request_url . '"><i class="fa fa-calendar" style="padding-right: 10px"></i> New Booking Request</a>';
	
	return $r;
}

function crowbank_edit_customer($attr) {
	$customer = get_customer();
	$update_url = home_url('edit-customer/?cust=' . $customer->no);
	
	$r = '<a class="table_button booking_edit_button" href="' . $update_url . '">Edit <span class="fa fa-fw fa-edit"></span></a>';
	return $r;
}

function crowbank_new_pet($attr) {
	$customer = get_customer();
	
	if ( !$customer ) {
		return crowbank_error ('No Current Customer');
	}
	$request_url = home_url('pet/?cust=' . $customer->no);
	
	$r = '<a class="booking_request_button" href="' . $request_url . '"><i class="fa"></i>New Pet</a>';
	
	return $r;
}

function crowbank_owner_holiday($attr) {
	global $petadmin;
	
	$date = get_daily_date();
	
}

function crowbank_test_message($attr) {
	$msg = new Message( 'test-message', ['value' => 'Test Value'] );
	
	$result = $msg->send();
	
	echo 'Sent message, result: ' . $result . '<br>';
}
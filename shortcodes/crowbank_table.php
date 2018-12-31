<?php
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

const RANKCLASS = array(
		'Kennel Manager' => 'supervisor_class',
		'Senior Shift Leader' => 'supervisor_class',
		'Shift Leader' => 'supervisor_class',
		'Kennel Assistant' => 'assistant_class',
		'Dog Walker' => 'walker_class',
		'Volunteer' => 'volunteer_class'
);

const MONTHS = array(
		1 => 'Jan',
		2 => 'Feb',
		3 => 'Mar',
		4 => 'Apr',
		5 => 'May',
		6 => 'Jun',
		7 => 'Jul',
		8 => 'Aug',
		9 => 'Sep',
		10 => 'Oct',
		11 => 'Nov',
		12 => 'Dec'
);

function crowbank_table($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);

	$attr = shortcode_atts([ 'style' => 'card', 'type' => 'count', 'class' => '', 'cust_no' => 0, 'emp_no' => 0, 'title' => '',
		'time' => 'future', 'function' => 'work', 'spec' => 'Dog', 'direction' => 'in',
		'weeks' => 20, 'year' =>'', 'offset' => 0, 'read_only' => false], $attr, $tag);

	if (!isset($attr['type'])) {
		return crowbank_error('type attribute must be specified');
	}

	$type = $attr['type'];
	$class = $attr['class'];
	
	$title = $attr['title'];
	$customer = get_customer();
	$attr['cust'] = $customer;
	$employee = get_employee();
	$attr['emp'] = $employee;
	
	if ($type == 'count')
		$r = crowbank_count_table($attr);
	elseif ($type == 'test')
		$r = crowbank_test($attr);
	elseif ($type == 'customer_data')
		$r = crowbank_customer_data($attr);
	elseif ($type == 'customer_pets')
		$r = crowbank_customer_pets($attr);
	elseif ($type == 'customer_bookings')
		$r = crowbank_customer_bookings($attr);
	elseif ($type == 'employee_list')
		$r = crowbank_employee_list($attr);
	elseif ($type == 'timesheet')
		$r = crowbank_timesheet($attr);
	elseif ($type == 'weather')
		$r = crowbank_weather($attr);
	elseif ($type == 'workers')
		$r = crowbank_workers($attr);
	elseif ($type == 'tasks')
		$r = crowbank_tasks($attr);
	elseif ($type == 'inouts')
		$r = crowbank_inouts($attr);
	elseif ($type == 'availability')
		$r = crowbank_availability($attr);
	elseif ($type == 'employee_data')
		$r = crowbank_employee_data($attr);
	elseif ($type == 'employee_timesheets')
		$r = crowbank_employee_timesheets($attr);
	elseif ($type == 'employee_payslips')
		$r = crowbank_employee_payslips($attr);
	elseif ($type == 'pet_inventory')
		$r = crowbank_pet_inventory($attr);
	else
		return crowbank_error('unknown type ' . $type);

	$rr = '<div' . ($class == '' ? '' : ' class="' . $class . '"') . '>';
	if ($title)
		$rr .= "<h2>$title</h2>";
	$r = $rr . $r . '</div>';
	return $r;
}
add_shortcode('crowbank_table', 'crowbank_table');

/* tables:

count
employees
customers(search)
customer_data(cust_id)
customer_pets(cust_id)
customer_bookings(cust_id, time)
rota(status, date)
in_out(direction, species, date)
inventory(date, species)
weekly_shifts(weekstart)
employee_data(emp_no)
employee_shifts(emp_no, weekstart, weekcount)
pet_data(pet_no)

*/

function crowbank_test($attr) {
	global $petadmin;

	$user = wp_get_current_user();

	if (0 == $user->ID)
		return 'No user logged in';

	$r = "<div$div_class " . 'style="overflow-x:auto;"><table class="table">
	<thead><th>Property</th><th>Value</th></thead>
	<tbody>';

	$r .= "<tr><td>Role</td><td>" . $user->roles[0] . "</td></tr>";
	$r .= "<tr><td>ID</td><td>" . $user->ID . "</td></tr>";
	$r .= "<tr><td>Name</td><td>" . $user->first_name . ' ' . $user->last_name . "</td></tr>";
	$r .= "<tr><td>Customer Number</td><td>" . (isset($user->cust_no) ? $user->cust_no : 'None Set') . "</td></tr>";
	$r .= "<tr><td>Employee Number</td><td>" . (isset($user->emp_no) ? $user->emp_no : 'None Set')  . "</td></tr>";
	$r .= '</table></div>';

	return $r;
}

function crowbank_count_table($attr) {
	global $petadmin;

	$r = '<div style="overflow-x:auto;"><table class="table">
	<thead><th>Table</th><th>Rows</th></thead>
	<tbody>';
	
	foreach ($petadmin->counts as $key=>$count) {
		$r .= "<tr><td>$key</td><td>$count</td></tr>";
	}
	
	$r .= '</tbody>
</table></div>';
	return $r;
}

function crowbank_customer_data($attr) {
	global $petadmin;

	$customer = $attr['cust'];

	if (!$customer)
		return crowbank_error('No customer specified');

	$full_address = $customer->addr1 . ', ' . $customer->addr3 . ' ' . $customer->postcode;
	$map_url = 'https://www.google.com/maps/dir/?api=1&origin=Crowbank+Kennels&destination=' . urlencode($full_address);
	$r = '<div style="overflow-x:auto;"><table class="table">';
	$r .= '<tbody><tr><td>Customer #</td><td>';
	$r .= $customer->no . '</td></tr>';
	$r .= '<tr><td>Name:</td><td>' . "$customer->title $customer->forename $customer->surname" . '</td></tr>';
	$r .= '<tr><td>Address:</td><td><a href="' . $map_url . '">' . "$customer->addr1<br>$customer->addr3<br>$customer->postcode" . '</a></td></tr>';
	$r .= '<tr><td>Phone Numbers:</td><td>';

	if ($customer->telno_home != '') {
		$r .= "$customer->telno_home (Home)<br>";
	}
	if ($customer->telno_mobile != '') {
		$r .= "$customer->telno_mobile (Mobile)<br>";
	}
	if ($customer->telno_mobile2 != '') {
		$r .= "$customer->telno_mobile2 (2nd Mobile)<br>";
	}

	$r .= '</td></tr><tr><td>Email:</td><td>' . $customer->email . '</td></tr>';
	$r .= '</tbody></table></div>';

	return $r;
}

function crowbank_customer_pets($attr)
{
	global $petadmin;

	$customer = $attr['cust'];
	$read_only = $attr['read_only'];

	if (!$customer)
		return crowbank_error('No customer specified');

	$r = '<div style="overflow-x:auto;"><table class="table">';
	$r .= '<thead><th>Name</th><th>Species</th><th>Breed</th><th>Date of Birth</th><th>Vaccinations</th>';
	if (!$read_only) {
		$r .= '<th></th><th></th>';
	}
	$r .= '</thead><tbody>';

	$pets = $customer->get_pets();
	foreach($pets as $pet) {
		if ($pet->deceased == 'Y') {
			continue;
		}

		$update_url = home_url('pet/?pet_no=' . $pet->no . '&cust=' . $customer->no);
		$remove_url = home_url('remove-pet/?pet_no=' . $pet->no . '&cust=' . $customer->no);
		
		$r .= "<tr><td>";
		if (!$read_only) {
			$r .= "<a href=" . '"' . $update_url . '">';
		}
		$r .= $pet->name;
		if (!$read_only) {
			$r .= "</a>";
		}
		$r .= "</td>";
		$r .= "<td>$pet->species</td>";
		$breed_desc = $pet->breed->desc;
		$r .= "<td>$breed_desc</td>";
		$dob = $pet->dob->format('d/m/Y');
		$r .= "<td>$dob</td>";
		if ($pet->vacc_status == 'Valid') {
			$vacc = "Valid to " . $pet->vacc_date->format("d/m/Y");
		} else {
			$vacc = $pet->vacc_status;
		}
		$r .= "<td>$vacc</td>";
		
		if (!$read_only) {
			$r .= '<td><a class="table_button booking_edit_button" href="' . $update_url . '">Edit <span class="fa fa-fw fa-edit"></span></a></td>';
			
			$r .= '<td><a class="table_button cancel_booking_button" href="' . $remove_url . '">Remove <span class="fa fa-fw fa-times"></span></a></td></tr>';
		}
	}



	$r .= '</tbody></table></div>';

	return $r;
}

function crowbank_customer_bookings($attr) {
	global $petadmin;

	$customer = $attr['cust'];
	$read_only = $attr['read_only'];
	$style = $attr['style'];

	if (!$customer)
		return crowbank_error('No customer specified');

	$time = $attr['time'];

	if ($time == 'past') {
		$bookings = $customer->get_past_bookings();
		$title = 'Past';
	}
	elseif ($time == 'present') {
		$bookings = $customer->get_current_bookings();
		$title = 'Current';
	}
	elseif ($time == 'future') {
		$bookings = $customer->get_future_bookings();
		$title = 'Future';
	}
	elseif ($time == 'draft') {
		$bookings = $customer->get_draft_bookings();
		$title = 'Draft';
	}
	else
		return crowbank_error('Unknown time ' . $time);

	$r = '';
	if ($bookings) {
		uasort($bookings, function($a, $b) { return $b->start_date->getTimestamp() - $a->start_date->getTimestamp(); });

		$r .=  "<h2>$title</h2>";
		$r .= '<div style="overflow-x:auto;">';
		if ($style == 'table') {
			$r .= '<table class="table">';
			$r .= '<thead><th>Booking #</th><th>Start Date</th><th>End Date</th><th style="width: 300px;">Pets</th>
	<th>Gross Amount</th><th>Paid Amount</th><th>Balance</th><th>Status</th><th></th>';
			if (!$read_only) {
				$r .= '<th></th><th></th>';
			}
			$r .= '<th></th><th></th></thead><tbody>';
		}
		foreach ($bookings as $booking) {			
			$buttons = [];
			if (!$read_only and ($time == 'future' or $time == 'draft')) {
				$update_url = home_url('booking-request/?bk_no=' . $booking->no . '&cust=' . $customer->no);
				$buttons[] = array(
						'link' => $update_url,
						'type' => 'Modify',
						'title' => 'Modify'
				);
			}

			if ($time == 'future' and $booking->status == ' ') {
				$deposit_url = $booking->deposit_url();
				if ($deposit_url) {
					$buttons[] = array(
						'link' => $update_url,
						'type' => 'Pay',
						'title' => 'Pay Deposit'
					);
				}
			}

			if (!$read_only and (($time == 'future' and ($booking->status == ' ' or $booking->status == 'V')) or $booking->status == 'D')) {
				$cancellation_url = home_url('cancellation-confirmation/?bk_no=' . $booking->no . '&cust=' . $customer->no .
						'&cust_surname=' . $customer->surname . '&bk_pets=' . $booking->pet_names() .
						'&bk_start_date=' . $booking->start_date->format("Y-m-d") . '&bk_end_date=' . $booking->end_date->format("Y-m-d"));
				$buttons[] = array(
						'link' => $cancellation_url,
						'type' => 'Cancel',
						'title' => 'Cancel'
				);
			}
			
			$r .= $booking->html($style, $time, $buttons);
		}
		if ($style == 'table') {
			$r .= '</tbody></table>';
		}		
	}
	
	$r .= '</div>';
	return $r;
}

function crowbank_employee_list($attr) {
	global $petadmin;

	$current = $petadmin->employees->current;

	$r = '<div style="overflow-x:auto;"><table class="table">
<thead><th>No.</th><th>Forename</th><th>Surname</th><th>Nickname</th><th>Start Date</th><th>Rank</th><th>Email</th><th>Facebook</th><th>Mobile</th></thead>
<tbody>';

	foreach ($current as $employee) {
		if ($employee->rank == 'Owner') {
			continue;
		}
		if (array_key_exists($employee->rank, RANKCLASS)) {
			$class = RANKCLASS[$employee->rank];
		}
		$r .= '<tr class="' . $class . '">' . "<td>$employee->no</td>" . "<td>$employee->forename</td>" . "<td>$employee->surname</td>";
		$r .= '<td><a href="' . home_url('employee/?emp=' . $employee->nickname) . '">' . $employee->nickname . '</a></td>';
		$r .= "<td>" . $employee->start_date->format('d/m/Y') . "</td>" . "<td>$employee->rank</td>";
		$r .= "<td>$employee->email</td><td><a href=" . '"http://www.facebook.com/' . $employee->facebook . '">' . $employee->facebook . "</a></td><td>$employee->mobile</td></tr>";
	}
	$r .= '</tbody></table></div>';

	return $r;
}

function crowbank_timesheet($attr) {
	global $petadmin;

	$weekstart = get_weekstart();
	
	$offset = $attr['offset'];
	
	if ($offset) {
		$offset_interval = new DateInterval('P' . 7 * $offset . 'D');
		$weekstart->add($offset_interval);
	}

	return $petadmin->timesheets->display_week($weekstart);
}

function crowbank_weather($attr) {
	global $petadmin;

	$date = get_daily_date();

	return $petadmin->weather->weather_table($date);
}

function crowbank_workers($attr) {
	global $petadmin;

	$function = $attr['function'];

	if ($function == 'work') {
		$code = 'X';
	} elseif ($function == 'available') {
		$code = ' ';
	} elseif ($function == 'unavailabile') {
		$code = 'U';
	} elseif ($function == 'vacation') {
		$code = 'H';
	} else {
		return crowbank_error ("Unknown function $function");
	}

	$date = get_daily_date();

	$wd = (int) $date->format('N') - 1;
	$weekstart = clone $date;
	if ($wd > 0) {
		$weekstart->sub(new DateInterval('P' . $wd . 'D'));
	}

	$petadmin->timesheets->load();
	$shifts = $petadmin->timesheets->weekly[$weekstart->getTimestamp()];
	uasort($shifts, function($a, $b) { return $a->employee->order - $b->employee->order; });

	$r = '<div style="overflow-x:auto;"><table class="table">';
	foreach ($shifts as $nickname => $shift) {
		if ($shift->employee->rank == 'Owner') {
			continue;
		}
					
		if ($shift->employee->rank == '') {
			$class = 'supervisor_class'; 
		} else if ($shift->employee->rank == '') {
			$class = 'assistant_class';
		} else {
			$class = 'walker_class';
		}
		
		$am = $shift->days[$wd]['am'];
		$pm = $shift->days[$wd]['pm'];
		
		$class = '';
		if (array_key_exists($shift->employee->rank, RANKCLASS)) {
			$class = RANKCLASS[$shift->employee->rank];
		}
		
		if ($am == $code or $pm == $code) {
			$r .= '<tr class="' . $class . '"><td><a href="' . home_url('employee/?emp=' . $nickname) . '">' . $nickname . '</a></td><td>';
			if ($am != $code) {
				$r .= " (afternoon only)";
			}
			if ($pm != $code) {
				$r .= " (morning only)";
			}
			$r .= "</td></tr>";
		}
	}

	$r .= '</table></div>';

	return $r;
}

function crowbank_tasks($attr) {
	global $petadmin;

	$date = get_daily_date();

	return $petadmin->tasks->task_table($date);
}

function crowbank_inouts($attr) {
	global $petadmin;

	$date = get_daily_date();
	$ts = $date->getTimestamp();

	$spec = $attr['spec'];
	$direction = $attr['direction'];
	$bookings = NULL;

	$bookings = $petadmin->bookings->inouts($ts, $direction);
	
	if ($direction == 'in') 
		return in_table($bookings, $spec);
	elseif ($direction == 'out')
		return out_table($bookings, $spec);

	return crowbank_error("Unknown direction $direction");
}

function in_table($bookings, $species) {
	if (!$bookings) {
		return '<div>No incoming ' . $species . 's</div>';
	}
	$r = '<div style="overflow-x:auto;"><table class = "table">
<thead><th>Bk #</th><th>Time</th><th>Surname</th><th>' . $species . '(s)</th></thead><tbody>';
	foreach ($bookings as $booking) {
		if (($species == 'Dog' and !$booking->has_dogs) or ($species == 'Cat' and !$booking->has_cats)) {
			continue;
		}
		
		$time = substr($booking->start_time, 0, 5);

		$style = '';
		if ($booking->checked_in) {
			$style = ' style="background: lightgrey;"';
		}
		
		$r .= '<tr' . $style . '><td><a href="' . $booking->confirmation_url();
		$r .= '">' . $booking->no . '</a></td><td>' . $time . '</td><td><a href="';
		$r .= $booking->customer->home_url() . '">';
		$r .= $booking->customer->surname . "</a></td><td>";
		foreach ($booking->pets as $pet) {
			if ($pet->species == $species) {
				$r .= '<a href="' . $pet->home_url() . '">';
				$breed = $pet->breed->desc;
				$r .= "$pet->name";
				if ($species == 'Dog') {
					$r .= " ($breed)";
				}
				$r .= "</a><br>";
			}
		}
		$r .= "</td></tr>";
	}
	$r .= '</tbody></table></div>';

	return $r;
}

function out_table($bookings, $species) {
	if (!$bookings) {
		return '<div>No outgoing ' . $species . 's</div>';
	}
	$r = '<div style="overflow-x:auto;"><table class = "table">
<thead><th>Bk #</th><th>Time</th><th>Surname</th><th>' . $species . '(s)</th></thead><tbody>';

	foreach ($bookings as $booking) {
		if (($species == 'Dog' and !$booking->has_dogs) or ($species == 'Cat' and !$booking->has_cats)) {
			continue;
		}
		$time = substr($booking->end_time, 0, 5);

		$style = '';
		if ($booking->checked_out) {
			$style = ' style="background: lightgrey;"';
		}
		
		
		$r .= '<tr' . $style . '><td><a href="' . $booking->confirmation_url();
		$r .= '">' . $booking->no . '</td><td>' . $time . '</td><td>';
		$r .= '<a href="' . $booking->customer->home_url();
		$r .= '">' . $booking->customer->surname . "</a></td><td>";
		foreach ($booking->pets as $pet) {
			if ($pet->species == $species) {
				$r .= '<a href="' . $pet->home_url() . '">';
				$breed = $pet->breed->desc;
				$r .= "$pet->name";
				if ($species == 'Dog') {
					$r .= " ($breed)";
				}
				$r .= "</a><br>";
			}
		}
		$due = $booking->gross_amt - $booking->paid_amt;
		$r .= "</td></tr>";
	}
	$r .= '</tbody></table></div>';

	return $r;
}

function crowbank_availability($attr) {
	global $petadmin;
	$date = get_daily_date();

	$inventory = $petadmin->inventory->get($date);
	$species = $attr['spec'];

	$title = ($species == 'Dog') ? 'Kennel' : 'Cattery';
	$capacity = ($species == 'Dog') ? 35 : 15;
	
	$r = '<div style="overflow-x:auto;"><table class="table">';
	$r .= '<thead><th>Time</th><th>Occupied</th><th>Available</th><th>Ins</th><th>Outs</th></thead>';
	$r .= '<tbody>';
	$r .= '<tr><td>Morning</td><td>' . $inventory->occupied('morning', $species) . '</td>';
	$r .= '<td>' . ($capacity - $inventory->occupied('morning', $species)) . '</td>';
	$r .= '<td></td><td></td></tr>';
	$r .= '<tr ' . ($inventory->occupied('am', $species) > $capacity ? 'class="alert alert-warning">' : '>');
	$r .= '<td>AM</td><td>' . $inventory->occupied('am', $species) . '</td>';
	$r .= '<td>' . ($capacity - $inventory->occupied('am', $species)) . '</td>';
	$r .= '<td>' . $inventory->pet_inout('in', 'am', $species) . '</td>';
	$r .= '<td>' . $inventory->pet_inout('out', 'am', $species) . '</td></tr>';
	$r .= '<tr><td>Noon</td><td>' . $inventory->occupied('noon', $species) . '</td>';
	$r .= '<td>' . ($capacity - $inventory->occupied('noon', $species)) . '</td>';
	$r .= '<td></td><td></td></tr>';
	$r .= '<tr ' . ($inventory->occupied('pm', $species) > $capacity ? 'class="alert alert-warning">' : '>');
	$r .= '<td>PM</td><td>' . $inventory->occupied('pm', $species) . '</td>';
	$r .= '<td>' . ($capacity - $inventory->occupied('pm', $species)) . '</td>';
	$r .= '<td>' . $inventory->pet_inout('in', 'pm', $species) . '</td><td>' . $inventory->pet_inout('out', 'pm', $species) . '</td></tr>';
	$r .= '<tr><td>Evening</td><td>' . $inventory->occupied('evening', $species) . '</td>';
	$r .= '<td>' . ($capacity - $inventory->occupied('evening', $species)) . '</td>';
	$r .= '<td></td><td></td></tr>';
	$r .= '</tbody></table></div>';

	return $r;
}

function crowbank_employee_data($attr) {
	global $petadmin;

	$employee = $attr['emp'];

	if (!$employee)
		return crowbank_error('No employee specified');

	$r = '<div style="overflow-x:auto;"><table class="table">';
	$r .= '<tbody><tr><td>Name:</td><td>';
	$r .= $employee->forename . ' ' . $employee->surname . '</td></tr>';
	$r .= '<tr><td>Start Date:</td><td>' . $employee->start_date->format('d/m/Y') . '</td></tr>';
	$r .= '<tr><td>Role:</td><td>' . $employee->rank . '</td></tr>';
	$r .= '<tr><td>Email:</td><td>' . $employee->email . '</td></tr>';
	$r .= '<tr><td>Facebook:</td><td>' . '<a href="http://www.facebook.com/' . $employee->facebook . '">' . $employee->facebook . '</a></td></tr>';
	$r .= '<tr><td>Mobile:</td><td>' . $employee->mobile . '</td></tr>';
	$r .= '<tr><td>Document Folder:</td><td><a href="' . $employee->shared . '" target="_blank">Click Here</td></tr>';
	$r .= '</tbody></table></div>';

	return $r;
}

function crowbank_employee_timesheets($attr) {
	global $petadmin;

	$employee = $attr['emp'];
	$weeks = $attr['weeks'];

	if (!$employee)
		return crowbank_error('No employee specified');

	$weekstart = get_weekstart();

	$r = '';
	$overrides = array();
	$post = false;
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		crowbank_log('A Post with ' . count($_POST) . ' elements');
		foreach ($_POST as $field => $value) {
			$overrides[] = $field;
		}
		$post = true;
	}
	
	$r .= '<form method="post" action="' . get_permalink() . '">';
	
	$r .= $petadmin->timesheets->display_employee($employee, $weekstart, $weeks, $post, $overrides);

	$r .= '<input type="hidden" name="emp" value="' . $employee->nickname . '">';
	
	$r .= '<input type="submit" value="Submit" class="um-button"></form>';

	return $r;
}

function crowbank_payslip_row($payslip, $deductions) {
	$r = '<td style="text-align:right">' . number_format($payslip['ew_hours'], 1) . '</td>';
	$r .= '<td style="text-align:right">' . number_format($payslip['ew_holiday_earned'], 1) . '</td>';
	$r .= '<td style="text-align:right">' . number_format($payslip['ew_holiday'], 1) . '</td>';
	$r .= '<td style="text-align:right">£' . number_format($payslip['ew_gross'], 2) . '</td>';
	if (in_array('paye', $deductions)) {
		$r .= '<td style="text-align:right">£' . number_format($payslip['ew_paye'], 2) . '</td>';
	}
	if (in_array('nic', $deductions)) {
		$r .= '<td style="text-align:right">£' . number_format($payslip['ew_nic'], 2) . '</td>';
	}
	if (in_array('studentloan', $deductions)) {
		$r .= '<td style="text-align:right">£' . number_format($payslip['ew_studentloan'], 2) . '</td>';
	}
	if (in_array('pension', $deductions)) {
		$r .= '<td style="text-align:right">£' . number_format($payslip['ew_pension'], 2) . '</td>';
	}
	$r .= '<td style="text-align:right">£' . number_format($payslip['ew_net'], 2) . '</td>';

	return $r;
}
function crowbank_employee_payslips($attr) {
	global $petadmin;
	
	$employee = $attr['emp'];
	
	if (!$employee) {
		return crowbank_error('No Employee');
	}
	
	$year = date('Y');
	$month = date('m') + 0;
	if ($month < 4) {
		$year--;
	}
	
	$payslips = $employee->get_payslips($year);
	$deductions = $employee->get_deductions($year);
	
	if (!$payslips) {
		return crowbank_error('No Payslips this year');
	}
	
	$r = '<div style="overflow-x:auto;"><div style="text-align: center"><span style="font-weight: bold">' . $year . ' - ' . ($year + 1) . '</span></div><br>';
	$r .= '<table class="table" style="border: solid 1px; ">
<thead style="text-align: left; font-weight: bold; ">
<th style="width: 70px;">Month</th>
<th style="width: 60px;">Hours Worked</th>
<th style="width: 60px;">Holidays Earned</th>
<th style="width: 60px;">Holidays Taken</th>
<th style="width: 80px;">Gross Pay</th>';
	if (in_array('paye', $deductions)) {
		$r .= '<th style="width: 80px;">PAYE</th>';
	}
	if (in_array('nic', $deductions)) {
		$r .= '<th style="width: 80px;">NIC</th>';
	}
	if (in_array('studentloan', $deductions)) {
		$r .= '<th style="width: 80px;">Student Loan</th>';
	}
	if (in_array('pension', $deductions)) {
		$r .= '<th style="width: 80px;">Pension</th>';
	}
	$r .= '<th style="width: 80px;">Net Pay</th></thead><tbody>';
	
	$cumm = array(
		'ew_hours' => 0.0,
		'ew_holiday_earned' => 0.0,
		'ew_holiday' => 0.0,
		'ew_gross' => 0.0,
		'ew_net' => 0.0,
		'ew_paye' => 0.0,
		'ew_nic' => 0.0,
		'ew_studentloan' => 0.0,
		'ew_pension' => 0.0
		);

	foreach ($payslips as $month => $payslip) {
		$r .= '<tr><td>' . MONTHS[$month] . '</td>' . crowbank_payslip_row($payslip, $deductions) . '</tr>';
		foreach($cumm as $key => $value) {
			$cumm[$key] += $payslip[$key];
		}		
	}
	
	$r .= '<tr style="font-weight: bold"><td>Total</td>' . crowbank_payslip_row($cumm, $deductions) . '</tr>';
	
	$holiday_remaining = $employee->get_holiday_remaining($year);
	$in_days = floor($holiday_remaining / 4.5) / 2.0;
	$r .= '<tr><td colspan="10">Holidays Remaining: ' . number_format($holiday_remaining, 1) . ' hours, or ' . $in_days . ' days</td></tr></tbody></table></div>';
	
	return $r;
}

function crowbank_pet_inventory($attr) {
	global $petadmin;

	$date = get_daily_date();
	$spec = 'Dog';
	if (isset($attr['spec']))
		$spec = $attr['spec'];

	if ($spec == 'Dog') {
		$run_types = [['Home', 1], ['Deluxe', 2], ['Double', 4], ['Sml Double', 6], ['Standard', 22]];
	} else {
		$run_types = [['Home', 1], ['Standard', 13]];
	}

	$inv = $petadmin->petinventory->get_by_date_and_spec($date, $spec);
	if (!$inv) {
		return crowbank_error('No pet inventory information found for ' . $date->format('d/m/Y'));
	}

	$r = '<div style="overflow-x:auto;"><table class="table" style="border: solid 1px; "><thead style="text-align: left; font-weight: bold; "><th style="width: 80px;">Run</th><th style="width: 60px;">Bk #</th>
<th style="width: 60px;">From</th><th style="width: 60px;">To</th><th style="width: 150px;">Surname</th><th style="width: 150px;">Pets</th>
<th style="width: 100px;">In/Out</th></thead><tbody>';

	foreach ($run_types as $rt) {
		$run_type = $rt[0];
		$run_capacity = $rt[1];
		if (!isset($inv[$run_type]) || !$inv[$run_type])
			continue;

		$used = count($inv[$run_type]);

		$r .= '<tr><td colspan="7" style="text-align: center; border: solid 1px; "><strong>' . $run_type . '  (' . $used . ' / ' . $run_capacity . ')</strong></td></tr>';
		foreach ($inv[$run_type] as $run_inv) {
			$r .= '<tr style="vertical-align: middle;"><td>' . $run_inv->run . '</td><td><a href="';
			$r .= $run_inv->booking->confirmation_url() . '">';
			$r .= $run_inv->booking->no . '</a></td><td><a href="' . home_url('daily/?date=' . $run_inv->booking->start_date->format('Y-m-d')) . '">';
			$r .= $run_inv->booking->start_date->format('d/m') . '</a></td>';
			$r .= '<td><a href="' . home_url('daily/?date=' . $run_inv->booking->end_date->format('Y-m-d')) . '">' . $run_inv->booking->end_date->format('d/m') . '</a></td><td>';
			$r .= '<a href="' . $run_inv->booking->customer->home_url() . '">' . $run_inv->booking->customer->surname . '</a></td><td>';
			foreach ($run_inv->pets as $pet) {
				$r .= $pet->name . ' (' . $pet->breed->short_desc . ')<br>';
			}
			$r .= '</td><td>' . $run_inv->in_out . '</td></tr>';
		}
	}

	$r .= '</tbody></table></div>';
	return $r;
}
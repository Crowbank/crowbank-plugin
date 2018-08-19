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
		'R' => ['requestedbooking', 'Requested']
);

const RANKCLASS = array(
		'Kennel Manager' => 'supervisor_class',
		'Senior Shift Leader' => 'supervisor_class',
		'Shift Leader' => 'supervisor_class',
		'Kennel Assistant' => 'assistant_class',
		'Dog Walker' => 'walker_class',
		'Volunteer' => 'volunteer_class'
);

function crowbank_table($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);

	$attr = shortcode_atts([ 'type' => 'count', 'class' => '', 'cust_no' => 0, 'emp_no' => 0, 'title' => '',
		'time' => 'future', 'function' => 'work', 'spec' => 'Dog', 'direction' => 'in',
		'weeks' => 20], $attr, $tag);

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

	$r = '<div style="overflow-x:auto;"><table class="table">';
	$r .= '<tbody><tr><td>Customer #</td><td>';
	$r .= $customer->no . '</td></tr>';
	$r .= '<tr><td>Name:</td><td>' . "$customer->title $customer->forename $customer->surname" . '</td></tr>';
	$r .= '<tr><td>Address:</td><td>' . "$customer->addr1<br>$customer->addr3<br>$customer->postcode" . '</td></tr>';
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

	if (!$customer)
		return crowbank_error('No customer specified');

	$r = '<div style="overflow-x:auto;"><table class="table">';
	$r .= '<thead><th>Name</th><th>Species</th><th>Breed</th><th>Date of Birth</th><th>Vaccinations</th><th></th><th></th></thead><tbody>';

	$pets = $customer->get_pets();
	foreach($pets as $pet) {
		if ($pet->deceased == 'Y') {
			continue;
		}

		$update_url = home_url('pet/?pet_no=' . $pet->no . '&cust=' . $customer->no);
		$remove_url = home_url('remove-pet/?pet_no=' . $pet->no . '&cust=' . $customer->no);
		
		$r .= "<tr><td><a href=" . '"' . $update_url . '">' . $pet->name . "</a></td>";
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
		
		$r .= '<td><a class="table_button booking_edit_button" href="' . $update_url . '">Edit <span class="fa fa-fw fa-edit"></span></a></td>';
		
		$r .= '<td><a class="table_button cancel_booking_button" href="' . $remove_url . '">Remove <span class="fa fa-fw fa-times"></span></a></td></tr>';
	}



	$r .= '</tbody></table></div>';

	return $r;
}

function crowbank_customer_bookings($attr) {
	global $petadmin;

	$customer = $attr['cust'];

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
	else
		return crowbank_error('Unknown time ' . $time);

	$r = '';
	if ($bookings) {
		uasort($bookings, function($a, $b) { return $b->start_date->getTimestamp() - $a->start_date->getTimestamp(); });

		$r .=  "<h2>$title</h2>";
		$r .= '<div style="overflow-x:auto;"><table class="table">';
		$r .= '<thead><th>Booking #</th><th>Start Date</th><th>End Date</th><th style="width: 300px;">Pets</th>
<th>Gross Amount</th><th>Paid Amount</th><th>Balance</th><th>Status</th><th></th><th></th><th></th></thead>';
		$r .= '<tbody>';
		foreach ($bookings as $booking) {
			$status = $booking->status;
			if ($status == '') {
				$status = 'B';
			}
			$status_desc = STATUS_ARRAY[$status][1];
			if ($time == 'present') {
				$status = '0';
			} else if ($time == 'past' and $status != 'C' and $status != 'N') {
				$status = '-';
				$status_desc = '';
			}

			$class = STATUS_ARRAY[$status][0];
			$start = $booking->start_date->format('d/m/y');
			$end = $booking->end_date->format('d/m/y');

/*			$r .= '<tr class="' . $class . '"><td><a href="' . $booking->confirmation_url();
			$r .= '">';
			$r .= $booking->no . '</a></td><td>' . $start . '</td><td>' . $end . '</td><td>';
*/
			
			$r .= '<tr class="' . $class . '"><td>';
			if ($booking->no > 0) {
				$r .= $booking->no;
			} else {
				$r .= 'N/A';
			}
			$r .= '</td><td>' . $start . '</td><td>' . $end . '</td><td>';
			foreach ($booking->pets as $pet) {
				$r .= "$pet->name<br>";
			}
			$r .= "</td><td align=right>" . number_format($booking->gross_amt, 2) . "</td><td align=right>" . number_format($booking->paid_amt, 2) .
			"</td><td align=right>" . number_format($booking->gross_amt-$booking->paid_amt, 2) . "</td><td>$status_desc</td><td>";

			if ($time == 'future') {
				$update_url = home_url('booking-request/?bk_no=' . $booking->no . '&cust=' . $customer->no);
				$r .= '<a class="table_button booking_edit_button" href="' . $update_url . '">Modify <span class="fa fa-fw fa-edit"></span></a>';
			}
			
			$r .= "</td><td>";
			
			if ($time == 'future' and $booking->status == ' ') {
				$deposit_url = $booking->deposit_url();
				if ($deposit_url) {
					$r .= '<a class="table_button deposit_button" href="' . $deposit_url . '">Deposit <span class="fa fa-fw fa-gbp"></span></a>';
				}
			}
			
			$r .= "</td><td>";
			
			if ($time == 'future' and ($booking->status == ' ' or $booking->status == 'V')) {
				$cancellation_url = home_url('cancellation-confirmation/?bk_no=' . $booking->no . '&cust=' . $customer->no .
						'&cust_surname=' . $customer->surname . '&bk_pets=' . $booking->pet_names() .
						'&bk_start_date=' . $booking->start_date->format("Y-m-d") . '&bk_end_date=' . $booking->end_date->format("Y-m-d"));
				$r .= '<a class="table_button cancel_booking_button" href="' . $cancellation_url . '">Cancel <span class="fa fa-fw fa-times"></span></a>';
			}
			
			$r .= "</td></tr>";
		}
		$r .= '</tbody></table></div>';
	}
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

	$week = new DateInterval('P7D');
	$next_week = clone $weekstart;
	$next_week->add($week);
	$last_week = clone $weekstart;

	if (!isset($petadmin->timesheets->weekly[$next_week->getTimestamp()])) {
		$next_week = NULL;
	}

	if (!isset($petadmin->timesheets->weekly[$last_week->getTimestamp()])) {
		$last_week = NULL;
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
	$r .= '<tr ' . ($inventory->occupied('am', $species) > $capacity ? 'class="alert">' : '>');
	$r .= '<td>AM</td><td>' . $inventory->occupied('am', $species) . '</td>';
	$r .= '<td>' . ($capacity - $inventory->occupied('am', $species)) . '</td>';
	$r .= '<td>' . $inventory->pet_inout('in', 'am', $species) . '</td>';
	$r .= '<td>' . $inventory->pet_inout('out', 'am', $species) . '</td></tr>';
	$r .= '<tr><td>Noon</td><td>' . $inventory->occupied('noon', $species) . '</td>';
	$r .= '<td>' . ($capacity - $inventory->occupied('noon', $species)) . '</td>';
	$r .= '<td></td><td></td></tr>';
	$r .= '<tr ' . ($inventory->occupied('pm', $species) > $capacity ? 'class="alert">' : '>');
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

function crowbank_pet_inventory($attr) {
	global $petadmin;

	$date = get_daily_date();
	$spec = 'Dog';
	if (isset($attr['spec']))
		$spec = $attr['spec'];

	if ($spec == 'Dog') {
		$run_types = [['Home', 1], ['Deluxe', 2], ['Double', 4], ['Standard', 28]];
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
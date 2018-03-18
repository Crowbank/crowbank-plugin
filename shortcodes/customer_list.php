<?php
function customer_list($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);
	
	$attr = shortcode_atts(array(), $attr, $tag);

	$customers = $petadmin->customers->get_list();

	$r = '<div id="customer_list">';
	
	$r .= '<label>Search: </label> <input id="inputfilter" type="text"></input>';
	
	$r .= '<table id="filterme" class="table table-responsive"><thead><th></th><th>Cust #</th><th>Surname</th><th>Forename</th>';
	$r .= '<th>Pets</th><th>Email</th><th>Phone #</th></thead>';
	$r .= '<tbody>';
	
	foreach ($customers as $customer) {
		$r .= '<tr><td>';
		
		$update_url = home_url('customer/?cust=' . $customer->no);
		$r .= '<a class="table_button booking_edit_button" href="' . $update_url;
		$r .= '"><span class="fa fa-fw fa-edit"></span></a>';
		$r .= '</td><td>' . $customer->no . '</td><td>' . $customer->surname . '</td><td>';
		$r .= $customer->forename . '</td><td>';
		$first = true;
		$pets = $customer->get_pets();
		foreach ($pets as $pet) {
			if (!$first) {
				$r .= '<br>';
			}
			$first = false;
			$r .= $pet->description();
		}
		$r .= '</td><td>' . $customer->email . '</td><td>';
		$first = true;
		if ($customer->telno_home != '') {
			$r .= $customer->telno_home;
			$first = false;
		}
		if ($customer->telno_mobile) {
			if (!$first) {
				$r .= '<br>';
			}
			$first = false;
			$r .= $customer->telno_mobile;
		}
		if ($customer->telno_mobile2) {
			if (!$first) {
				$r .= '<br>';
			}
			$r .= $customer->telno_mobile2;
		}
	}
	
	$r .= '</td></tr></tbody></table></div>';
	return $r;
}
	
	
	
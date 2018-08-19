<?php
require_once CROWBANK_ABSPATH . "classes/calendar_class.php";
require_once CROWBANK_ABSPATH . "classes/availability_class.php";

function crowbank_calendar_legend($attr = [], $content = null, $tag = '') {		
	$attr = array_change_key_case((array)$attr, CASE_LOWER);
	
	$attr = shortcode_atts([ 'class' => ''], $attr, $tag);
	
	$class = $attr['class'];
	
	$r = '<table id="calendar" class="table">';

	$r .= '<tr><td class="free"></td><td>Good Availability</td></tr>';
	$r .= '<tr><td class="busy"></td><td>Limited Availability</td></tr>';
	$r .= '<tr><td class="full"></td><td>No Availability</td></tr>';
	$r .= '</table>';
	$rr = '<div' . ($class == '' ? '' : ' class="' . $class . '"') . '>';
	
	$r = $rr . $r . '</div>';
	
	return $r;
}

function crowbank_calendar($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$months = array (1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
			7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
	
	$availability = new Availability();
	$availability->load();
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);
	
	$attr = shortcode_atts([ 'class' => '', 'title' => '', 'offset' => 0, 'runtype' => 'kennels'], $attr, $tag);

	$title = $attr['title'];
	$class = $attr['class'];
	$offset = $attr['offset'];
	$runtype = $attr['runtype'];
	$availabilityClasses = array();
	$availabilityClasses[0] = 'free';
	$availabilityClasses[1] = 'busy';
	$availabilityClasses[2] = 'full';

	if ($runtype == 'cattery') {
		$species = 'Cat';
		$run_type = 'Any';
	} else {
		$species = 'Dog';
		if ($runtype == 'kennels')
			$run_type = 'Any';
		else
			$run_type = 'Deluxe';
	}

	$classFunc = function($date) use ($availability, $species, $run_type, $availabilityClasses) {
		$a = $availability->availability($date, $species, $run_type);
		if (isset($availabilityClasses[$a]))
			return $availabilityClasses[$a];
		
		return '';
	};
	
	if (isset($_REQUEST['monthyear']))
		$monthyear = $_REQUEST['monthyear'];
	else
		$monthyear = 0;
	
	$month = intval(date("m", time()));
	$year = intval(date("Y", time()));
	
	$offset += $monthyear;
	
	if ($offset > 0) {
		$month += $offset;
		while ($month > 12) {
			$year += 1;
			$month -= 12;
		}
	}
	
	$calendar = new Calendar();
	$calendar->currentYear = $year;
	$calendar->currentMonth = $month;
	$calendar->classFunc = $classFunc;
	
	if (!$title) {
		$title = $months[$month] . ' ' . $year;
	}
	
	$calendar->title = $title;
	
	$r = $calendar->show();

	$rr = '<div' . ($class == '' ? '' : ' class="' . $class . '"') . '>';
	
	$r = $rr . $r . '</div>';
	return $r;
		
}
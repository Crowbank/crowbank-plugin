<?php
require_once CROWBANK_ABSPATH . "classes/calendar_class.php";
require_once CROWBANK_ABSPATH . "classes/availability_class.php";

function crowbank_calendar($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$availability = new Availability();
	$availability->load();
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);
	
	$attr = shortcode_atts([ 'year' => 0, 'month' => 0, 'class' => '', 'title' => '', 'species' => 'Dog', 'run_type' => 'Any', 'offset' => 0], $attr, $tag);

	$species = $attr['species'];
	$run_type = $attr['run_type'];
	$title = $attr['title'];
	$class = $attr['class'];
	$year = $attr['year'];
	$month = $attr['month'];
	$offset = $attr['offset'];
	$availabilityClasses = array();
	$availabilityClasses[0] = 'free';
	$availabilityClasses[1] = 'busy';
	$availabilityClasses[2] = 'full';
	
	
	$classFunc = function($date) use ($availability, $species, $run_type, $availabilityClasses) {
		$a = $availability->availability($date, $species, $run_type);
		if (isset($availabilityClasses[$a]))
			return $availabilityClasses[$a];
		
		return '';
	};
	
	if (!$year) {
		$year = get_year();
	}

	if (!$month) {
		$month = get_month();
	}	
	
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
		$title = ($species == 'Dog' ? 'Kennel' : 'Cattery') . ' Availability for ' . $month . '/' . $year;
	}
	
	$calendar->title = $title;
	
	$r = $calendar->show();

	$rr = '<div' . ($class == '' ? '' : ' class="' . $class . '"') . '>';
	
	$r = $rr . $r . '</div>';
	return $r;
		
}
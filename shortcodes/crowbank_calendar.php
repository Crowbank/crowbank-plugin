<?php
require_once CROWBANK_ABSPATH . "classes/calendar_class.php";
require_once CROWBANK_ABSPATH . "classes/availability_class.php";

function crowbank_calendar($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);
	
	$attr = shortcode_atts([ 'year' => 0, 'month' => 0, 'class' => '', 'title' => '', 'species' => 'Dog', 'run_type' => 'Any', 'offset' => 0], $attr, $tag);
	
	if (!isset($attr['type'])) {
		return crowbank_error('type attribute must be specified');
	}
	
	$species = $attr['species'];
	$run_type = $attr['run_type'];
	$title = $attr['title'];
	$class = $attr['class'];
	$year = $attr['year'];
	$month = $attr['month'];
	$offset = $attr['offset'];
	
	if (!$year) {
		if(isset($_REQUEST['year']) ){
			$year = $_REQUEST['year'];
		} else {
			$year = date("Y",time());
		}
	}
	
	if (!$month) {
		if( isset($_REQUEST['month'])) {
			$month = $_REQUEST['month'];
		} else {
			$month = date("m", time());
		}
	}	
	
	if ($offset > 0) {
		$month += $offset;
		while ($month > 12) {
			$year += 1;
			$month -= 12;
		}
	}
	
	$calendar = Calendar();
	$calendar->currentYear = $year;
	$calendar->currentMonth = $month;
	
	$r = $calendar->show();

	$rr = '<div' . ($class == '' ? '' : ' class="' . $class . '"') . '>';
	
	if (!$title) {
		$title = 'Availability for ' + $month + ' ' + $year;
	}
	
	$rr .= "<h2>$title</h2>";
	$r = $rr . $r . '</div>';
	return $r;
		
}
<?php
require_once 'classes/calendar_class.php';
require_once 'classes/petadmin_class.php';

function crowbank_availability($attr = [], $content = null, $tag = '') {
	$attr = array_change_key_case((array)$attr, CASE_LOWER);

	$attr = shortcode_atts(['spec' => 'Dog', 'runtype' => 'any'], $attr, $tag);

    if (isset($_REQUEST['year'])){
        $year = $_REQUEST['year'];
    } else {
        $year = date("Y", time());
    }          
         
    if (isset($_REQUEST['month'])) {
        $month = $_GET['month']; 
    } else {
        $month = date("m", time());
    }

	$spec = $attr['spec'];
	$runtype = $attr['runtype'];

	return display_calendar($year, $month, $spec, $runtype);            
}

function display_calendar($year, $month, $species, $runtype) {
	$availability = Availability::getAvailability();

	$class_func = function ($date) use ($species, $runtype, $availability) {
		$a = $availability->availability($date, $species, $runtype);
		if ($a == 0) {
			return "free";
		}
		if ($a == 1) {
			return "busy";
		}
		if ($a == 2) {
			return "full";
		}

		return "";
	};

	$calendar = new Calendar($year, $month, $class_func);
	return $calendar->show();
}

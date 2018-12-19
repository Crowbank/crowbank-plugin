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
add_shortcode('crowbank_calendar_legend', 'crowbank_calendar_legend');

function crowbank_load_calendar($runtype, $offset) {
	global $petadmin_db;
	
	$html = crowbank_calendar(['offset' => $offset, 'runtype' => $runtype], null, '', 1);
	$sql = "insert into crwbnk_calendar_cache (runtype, offset, html) values ('" . $runtype . "', " . $offset . ", '" . $html . "')";
	$petadmin_db->execute($sql);
}

function crowbank_load_calendars () {
	global $petadmin_db;
	
	$sql = 'truncate table crwbnk_calendar_cache';
	$petadmin_db->execute($sql);
	
	for ($i = 0; $i < 12; $i ++) {
		crowbank_load_calendar('kennels', $i);
		crowbank_load_calendar('deluxe', $i);
		crowbank_load_calendar('cattery', $i);
	}
	return 'calendars loaded<br>';
}
add_shortcode('crowbank_load_calendars', 'crowbank_load_calendars');

function crowbank_calendar($attr = [], $content = null, $tag = '', $force = 0) {
	global $petadmin;
	global $petadmin_db;
	
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
	$availabilityClasses[1] = 'free_busy';
	$availabilityClasses[2] = 'free_full';
	$availabilityClasses[10] = 'busy_free';
	$availabilityClasses[11] = 'busy';
	$availabilityClasses[12] = 'busy_full';
	$availabilityClasses[20] = 'full_free';
	$availabilityClasses[21] = 'full_busy';
	$availabilityClasses[22] = 'full';
	
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
	
	if ($force == 0) {
		/* read from database, rather than evaluate */
		
		$sql = "select html from crwbnk_calendar_cache where runtype = '" . $runtype . "' and offset = " . $offset;
		$result = $petadmin_db->execute($sql);
		foreach($result as $row) {
			$r = $row['html'];
		}
	} else {
		$classFunc = function($date) use ($availability, $species, $run_type, $availabilityClasses) {
			$aa = $availability->availability($date, $species, $run_type);
			$a = 10 * $aa['am'] + $aa['pm'];
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
	}
	return $r;
}
add_shortcode('crowbank_calendar', 'crowbank_calendar');

<?php
function crowbank_toggle($attr = [], $content = null, $tag = '') {
	$attr = array_change_key_case((array)$attr, CASE_LOWER);
	
	$attr = shortcode_atts([ 'name' => 'toggle', 'on' => 'On', 'off' => 'Off', 'on-class' => '', 'off-class' => ''], $attr, $tag);
	
	$name = $attr['name'];
	
	return	'<input class="tgl tgl-flat" id="' . $name . '" type="checkbox">
<label class="tgl-btn" data-tg-off="' . $attr['off'] . '" data-tg-on="' . $attr['on'] . '" for="' . $name . '"></label>';
}

function crowbank_item($attr = [], $content = null, $tag = '') {
	global $petadmin;
	
	$attr = array_change_key_case((array)$attr, CASE_LOWER);

	$attr = shortcode_atts([ 'type' => '', 'format' => 'D, j/m', 'page' => 'daily', 'text' => 'Link'], $attr, $tag);

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
	elseif ($type == 'today')
		return crowbank_today($attr);
	elseif ($type == 'weekstart')
		return crowbank_weekstart($attr);
	elseif ($type == 'date_link')
		return crowbank_date_link($attr);
	else
		return crowbank_error("Unknwon crowbank_item type $type");
}

function crowbank_prev_day($attr) {
	global $petadmin;
	$date = get_daily_date();

	$day = new DateInterval('P1D');

	$page = $attr['page'];

	$yesterday = clone($date);
	$yesterday->sub($day);

	return '<a href="' . home_url($page . '/?date=' . $yesterday->format('Y-m-d')) . '">Previous Day</a>';
}

function crowbank_next_day($attr) {
	global $petadmin;
	$date = get_daily_date();

	$page = $attr['page'];
	
	$day = new DateInterval('P1D');

	$tomorrow = clone($date);
	$tomorrow->add($day);

	return '<a href="' . home_url($page . '/?date=' . $tomorrow->format('Y-m-d')) . '">Next Day</a>';
}

function crowbank_prev_week($attr) {
	global $petadmin;
	$weekstart = get_weekstart();

	$week = new DateInterval('P7D');

	$prev = clone($weekstart);
	$prev->sub($week);

	return '<a href="' . home_url('weekly-rota/?weekstart=' . $prev->format('Y-m-d')) . '">Previous Week</a>';
}

function crowbank_next_week($attr) {
	global $petadmin;
	$weekstart = get_weekstart();

	$week = new DateInterval('P7D');

	$next = clone($weekstart);
	$next->add($week);

	return '<a href="' . home_url('weekly-rota/?weekstart=' . $next->format('Y-m-d')) . '">Next Week</a>';
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

	return '<a href="' . home_url($page . '/?date=' . $date->format('Y-m-d')) . '">' . $text . '</a>';
}

function crowbank_weekstart($attr) {
	$weekstart = get_weekstart();
	$format = 'd/m/Y';

	return $weekstart->format($format);
}

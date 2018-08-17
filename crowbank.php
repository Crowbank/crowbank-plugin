<?php
/*
Plugin Name: Crowbank Plugin
Description: Interface to Petadmin and other Crowbank functionality
Version:     20180210
Author:      Eran Yehduai
*/

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );
define( 'CROWBANK_ABSPATH', plugin_dir_path( __FILE__ ) );

if ($wpdb and !isset($GLOBALS['crowbank_session'])) {
	$sql = 'select max(session_no) from crwbnk_session where session_open=1';
	$session = $wpdb->get_var($sql);
	if (!$session) {
		$wpdb->insert('crwbnk_session', array('session_uri' => $_SERVER['REQUEST_URI']));
		$session = $wpdb->get_var($sql);
	}
	$GLOBALS['crowbank_session'] = $session;
	crowbank_log('Started Session');
}

if (!isset($GLOBALS['crowbank_var'])) {
	$GLOBALS['crowbank_var'] = 0;
}

function crowbank_log($msg, $severity = 0) {
	global $wpdb, $crowbank_session;
	
	if (!$wpdb)
		return;
		
		$trace = debug_backtrace();
		$t1 = $trace[1];
		if (isset($t1['file'])) {
			$f = $t1['file'];
			$file = basename($f);
		} else {
			$file = 'unknown';
		}
		if (isset($t1['line'])) {
			$line = $t1['line'];
		}
		else {
			$line = 0;
		}
		
		$code = $wpdb->insert('crwbnk_log', array('log_file' => $file, 'log_line' => $line, 'log_msg' => $msg, 'log_session' => $crowbank_session, 'log_severity' => $severity),
				array('%s', '%s', '%s', '%d'));
}

function crowbank_error($msg) {
	crowbank_log($msg, 2);
	return '<div class="crowbank-error">' . $msg . '</div>';
}

function crowbank_add_alert($msg, $level) {
	/* Add an alert to the queue, to be displayed by crowbank_show_alerts() */
	global $_SESSION;
	
	if (!in_array('crowbank_alerts', $_SESSION)) {
		$_SESSION['crowbank_alerts'] = array();
	}
	$_SESSION['crowbank_alerts'][] = array($msg, $level);
}

function crowbank_show_alert($msg, $level) {
	return '<div class="alert alert-' . $level . '"><span class="closebtn" onclick="this.parentElement.style.display=' . "'none';" . '">&times;</span>' .
	$msg . '</div>';
}

function crowbank_show_alerts($default) {
	/* Retrieve any alerts that have been queued, display and empty queue*/
	$r = '';
	if (!array_key_exists('crowbank_alerts', $_SESSION))
		return $default;
	
	$a = $_SESSION['crowbank_alerts'];
	if (count($a) == 0) {
		return $default;
	}
	
	for ($i = 0; $i < count($a); $i++) {
		$r .= crowbank_show_alert($a[$i][0], $a[$i][1]);
	}
	
	$_SESSION['crowbank_alerts'] = array();
	
	return $r;
}

function crowbank_activation() {
	crowbank_log('Activated');
}

function crowbank_deactivation(){
	crowbank_log('Deactivated');
}

function crowbank_uninstall(){
	crowbank_log('Uninstalled');
}

register_activation_hook( __FILE__, 'crowbank_activation' );
register_deactivation_hook( __FILE__, 'crowbank_deactivation' );
register_uninstall_hook(__FILE__, 'crowbank_uninstall');

function crowbank_shortcodes_init() {
	require_once CROWBANK_ABSPATH . 'classes/petadmin_class.php';
	require_once CROWBANK_ABSPATH . 'shortcodes/crowbank_table.php';
	require_once CROWBANK_ABSPATH . 'shortcodes/crowbank_item.php';
	require_once CROWBANK_ABSPATH . 'shortcodes/crowbank_confirmation.php';
	require_once CROWBANK_ABSPATH . 'shortcodes/crowbank_calendar.php';
	require_once CROWBANK_ABSPATH . 'shortcodes/crowbank_booking_action.php';
	require_once CROWBANK_ABSPATH . 'shortcodes/customer_list.php';
	
	add_shortcode('crowbank_table', 'crowbank_table');
	add_shortcode('crowbank_item', 'crowbank_item');
	add_shortcode('crowbank_toggle', 'crowbank_toggle');
	add_shortcode('crowbank_confirmation', 'crowbank_confirmation');
	add_shortcode('crowbank_calendar', 'crowbank_calendar');
	add_shortcode('crowbank_customer_list', 'customer_list');
	add_shortcode('crowbank_calendar_legend', 'crowbank_calendar_legend');
	add_shortcode('crowbank_booking_cancellation', 'crowbank_booking_cancellation');
	add_shortcode('crowbank_booking_confirmation', 'crowbank_booking_confirmation');
}

add_action('init', 'crowbank_shortcodes_init');

add_action( 'wp_enqueue_scripts', 'enqueue_load_fa' );
function enqueue_load_fa() {
	wp_enqueue_style( 'load-fa', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css' );
}

// add_filter( 'itsg_gf_ajaxupload_filename', 'my_itsg_gf_ajaxupload_filename', 10, 7 );
// function my_itsg_gf_ajaxupload_filename( $name, $file_path, $size, $type, $error, $index, $content_range ) {
// 	$field_id = isset( $_POST['field_id'] ) ? $_POST['field_id'] : null; // get the field_id out of the post
// 	$file_extension = pathinfo( $name, PATHINFO_EXTENSION );  // get the file type out of the URL
// 	$name = 'FileOfField' . $field_id . '.' . $file_extension; // put it together
// 	return $name; // now return the new name
// }

function time_slot_to_time($time_slot, $direction) {
	if ($time_slot == 'am' and $direction == 'in') {
		return '11:00';
	} else if ($time_slot == 'am' and $direction == 'out') {
		return '10:00';
	} else if ($time_slot == 'pm' and $direction == 'in') {
		return '16:00';
	} else if ($time_slot == 'pm' and $direction == 'out') {
		return '14:00';
	} else {
		return '';
	}
}


/*
function populate_customer_form($form) {
	global $petadmin;
	$customer = get_customer();
	
	if (!$customer ) {
		echo crowbank_error( 'No current customer' );
		return $form;
	}
	
	foreach( $form['fields'] as &$field ) {
		if ( $field->label == 'Customer Number' ) {
			$field->defaultValue = $customer->no;
		}
		
		if ( $field->label == 'Name' ) {
			$field->defaultValue = $customer->forename;
			$field->defaultValue = $customer->surname;
		}
		
		if ( $field->label == 'Address' ) {
			$field->defaultValue = $customer->addr1;
			$field->defaultValue = $customer->addr3;
			$field->defaultValue = $customer->postcode;
		}

		if ( $field->label == 'Home Phone' ) {
			$field->defaultValue = $customer->telno_home;
		}

		if ( $field->label == 'Mobile Phone' ) {
			$field->defaultValue = $customer->telno_mobile;
		}
		
		if ( $field->label == 'Mobile Phone 2' ) {
			$field->defaultValue = $customer->telno_mobile2;
		}
		
		if ( $field->label == 'Email Address' ) {
			$field->defaultValue = $customer->email;
		}
	}
	return $form;
}
*/

function populate_pet_form ( $form ) {
	global $petadmin;
	$customer = get_customer();
	
	if (!$customer ) {
		echo crowbank_error( 'No current customer' );
		return $form;
	}
	
	$pet_no = 0;
	$pet = null;
	
	if (isset( $_REQUEST['pet_no'])) {
		$pet_no = $_REQUEST['pet_no'];
		$pet = $petadmin->pets->get_by_no($pet_no);
	}
	
	foreach( $form['fields'] as &$field ) {
		if ( $field->label == 'Customer Number' ) {
			$field->defaultValue = $customer->no;
		}
		
		if ( $field->label == 'Vet' ) {
			$choices = array();
			$vets = $petadmin->vets->get_list();
			foreach ($vets as $vet) {
				$selected = ($pet_no != 0 and $pet->vet->no == $vet->no);
				$choices[] = array( 'text' => $vet->name, 'value' => $vet->no, 'isSelected' => $selected );
			}
			
			$field->choices = $choices;
		}
		
		if ( $field->label == 'Dog Breed' ) {
			$choices = array();
			
			$breeds = $petadmin->breeds->get_list('Dog');
			foreach ( $breeds as $breed ) {
				$selected = ($pet_no != 0 and $pet->breed->no == $breed->no);
				$choices[] = array( 'text' => $breed->desc, 'value' => $breed->no, 'isSelected' => $selected );
			}
			
			$field->choices = $choices;
		}
		
		if ( $field->label == 'Cat Breed' ) {
			$choices = array();
			
			$breeds = $petadmin->breeds->get_list('Cat');
			foreach ( $breeds as $breed ) {
				$selected = (($pet_no != 0 and $pet->breed->no == $breed->no) or
						($pet_no == 0 and $breed->no == 208));
				$choices[] = array( 'text' => $breed->desc, 'value' => $breed->no, 'isSelected' => $selected );
			}
			
			$field->choices = $choices;
		}
		
		if (!$pet) {
			continue;
		}
		
		if ( $field->label == 'Pet Number' ) {
			$field->defaultValue = $pet->no;
		}
		
		if ( $field->label == 'Name' ) {
			$field->defaultValue = $pet->name;
		}
		
		if ( $field->label == 'Species' ) {
			$field->choices[0]['isSelected'] = ($pet->species == 'Dog');
			$field->choices[1]['isSelected'] = ($pet->species == 'Cat');
		}
		
		if ( $field->label == 'Sex' ) {
			$field->choices[0]['isSelected'] = ( $pet->sex == 'M');
			$field->choices[1]['isSelected']= ( $pet->sex == 'F');
		}
		
		if ( $field->label == 'Spayed/Neutered' ) {
			$field->choices[0]['isSelected']= ( $pet->neutered == 'Y');
			$field->choices[1]['isSelected']= ( $pet->neutered == 'N');
		}
		
		
		if ( $field->label == 'Vet' ) {
			foreach ( $field->choices as $choice ) {
				$choice['isSelected'] = ( $choice['value'] == $pet->vet->no );
			}
		}
		
		if ( $field->label == 'Date of Birth' and $pet->dob ) {
			$field->defaultValue = $pet->dob->format('Y-m-d');
		}
		
		if ( $field->label == 'Vacc Date' and $pet->vacc_date ) {
			$field->defaultValue = $pet->vacc_date->format('Y-m-d');
		}
		
		if ( $field->label == 'KC Date' and $pet->kc_date ) {
			$field->defaultValue = $pet->kc_date->format('Y-m-d');
		}
		
		if ( $field->label == 'Comments' ) {
			$field->defaultValue = $pet->notes;
		}
		
		if ( $field->label == 'Vacc Status' ) {
			$field->defaultValue = $pet->vacc_status;
		}
		
		if ( $field->label == 'Vaccination Card' ) {
			if ( $pet->vacc_path ) {
				$field->content = '<iframe src="http://docs.google.com/gview?url=http://dev.crowbankkennels.co.uk/vaccinations/';
				$field->content .= $pet->vacc_path;
				$field->content .= '&embedded=true" style="width:100%;" frameborder="0"></iframe>';
			} else {
				$field->content = 'No Card on File<br>';
			}
		}
	}
	
	return $form;
}

function customer_submission( $entry, $form ) {
	global $petadmin, $petadmin_db;
	
	$customer = get_customer();

	if ($customer) {
		$cust_no = $customer->no;
		$msg_type = 'customer-update';
	}
	else {
		$cust_no = 0;
		$msg_type = 'customer-new';
	}
	
	$cust_forename = rgar( $entry, '2.3');
	$cust_surname = rgar( $entry, '2.6');
	
	$cust_addr1 = rgar( $entry, '3.1');
	$cust_addr3 = rgar( $entry, '3.3');
	$cust_postcode = rgar( $entry, '3.5');
	$cust_telno_home = rgar( $entry, '4');
	$cust_telno_mobile = rgar( $entry, '5');
	$cust_telno_mobile2 = rgar( $entry, '8');
	
	$msg = new Message( $msg_type, ['cust_no' => $cust_no, 'cust_forename' => $cust_forename,
			'cust_surname' => $cust_surname, 'cust_addr1' => $cust_addr1, 'cust_addr3' => $cust_addr3,
			'cust_postcode' => $cust_postcode, 'cust_telno_home' => $cust_telno_home,
			'cust_telno_mobile' => $cust_telno_mobile, 'cust_telno_mobile2' => $cust_telno_mobile2
	] );
	
	$msg->flush();
	
	$customer->update( $cust_forename, $cust_surname, $cust_addr1, $cust_addr3, $cust_postcode,
			$cust_telno_home, $cust_telno_mobile, $cust_telno_mobile2 );
	
	crowbank_add_alert('Thank you for updating your contact details', 'info');
}

function pet_submission( $entry, $form ) {
	global $petadmin, $petadmin_db;
	
	$cust_no = rgar( $entry, '1');
	$pet_no = rgar( $entry, '14' );
	$pet_name = rgar( $entry, '2');
	$pet_spec = rgar( $entry, '3');
	if ($pet_spec == 'Dog') {
		$pet_breed_no = rgar( $entry, '4');
	} else {
		$pet_breed_no = rgar( $entry, '13');
	}
	$pet_sex = rgar( $entry, '6');
	$pet_neutered = rgar( $entry, '7' );
	$pet_dob = rgar( $entry, '5' );
	$pet_vet_no = rgar( $entry, '8' );
	#	$pet_vacc_date = rgar( $entry, '9' );
	#	$pet_kc_date = rgar( $entry, '10' );
	$pet_comments = rgar( $entry, '12' );
	if ( $pet_no ) {
		$msg_type = 'pet-update';
	} else {
		$msg_type = 'pet-new';
	}
	
	$pet = null;
	$add_vacc_file = 0;
	
	if ( $pet_no ) {
		$pet = $petadmin->pets->get_by_no($pet_no);
	}
	
	if ( !empty( $entry[17] ) ) {
		$add_vacc_file = 1;
	}
	
	$msg = new Message( $msg_type, ['cust_no' => $cust_no, 'pet_no' => $pet_no, 'pet_name' => $pet_name,
			'pet_spec' => $pet_spec, 'pet_breed_no' => $pet_breed_no, 'pet_sex' => $pet_sex,
			'pet_neutered' => $pet_neutered, 'pet_dob' => $pet_dob, 'pet_vet_no' => $pet_vet_no,
			'pet_notes' => $pet_comments, 'add_vacc_file' => $add_vacc_file ] );
	
	$msg->flush();
	
	if ( !$pet_no ) {
		$pet_no = -$msg->id;
	}
	
	if ( !empty( $entry[17] ) ) {
		$form_id = 27;
		
		$upload_path = GFFormsModel::get_upload_path( $form_id );
		$upload_url = GFFormsModel::get_upload_url( $form_id );
		
		$file_url = unserialize($entry[17])[0][''];
		$pet_vacc_img = str_replace( $upload_url, $upload_path, $file_url);
		
		if ( $pet and $pet->vacc_path ) {
			$renamed = str_replace('.pdf', '.old.pdf', $pet->vacc_path);
			rename(VACC_FOLDER . $pet->vacc_path, VACC_FOLDER . $renamed);
		}
		
		copy($pet_vacc_img, VACC_FOLDER . $pet_no . '.pdf');
		$add_vacc_file = 1;
	}
	
	if ( $pet_no > 0 ) {
		$pet->update( $pet_name, $pet_spec, $pet_breed_no, $pet_sex,
				$pet_neutered, $pet_dob, $pet_vet_no, $pet_comments, $add_vacc_file );
		$msg = 'Thank you for updating ' . $pet_name . "'s information";
	} else {
		$petadmin->pets->create_pet( $cust_no, $msg->id, $pet_name, $pet_spec, $pet_breed_no, $pet_sex,
				$pet_neutered, $pet_dob, $pet_vet_no, $pet_comments, $add_vacc_file );
		$pet_no = -$msg->no;
		$msg = 'Thank you for registering ' . $pet_name;
	}
	
	crowbank_add_alert($msg, 'info');
}

function populate_customer_form ( $form ) {
	$customer = get_customer();
	if ($customer) {
		foreach ( $form['fields'] as &$field ) {
			if ( $field->label == 'Email Address' ) {
				$account_url = home_url('account');
				$html = 'Email Address: ' . $customer->email . '<br>';
				$html .= '<a href="' . $account_url . '">Change Password</a><br>';
				$field->content = $html;
			}
			
			if ( $field->label == 'Customer' ) {
				$field->defaultValue = $customer->no;
			}
		}
	}
	return $form;	
}

function populate_months( $form ) {
	$months = array (1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
			7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
	
	foreach ( $form['fields'] as &$field ) {
		
		if ( $field->type != 'select' || strpos( $field->cssClass, 'populate_months' ) === false ) {
			continue;
		}
		
		$choices = array();
		
		$month = intval(date("m", time()));
		$year = intval(date("Y", time()));
		
		for ($i = 0; $i < 12; $i++) {
			$text = $months[$month] . ' ' . $year;
			$choices[] = array( 'text' => $text, 'value' => $i);
			if ($month == 12) {
				$month = 1;
				$year += 1;
			} else
				$month += 1;
		}
		
		// update 'Select a Post' to whatever you'd like the instructive option to be
		$field->choices = $choices;
		
	}
	
	return $form;
}

function crowbank_check_new_user( $user_id, $args ) {
	global $ultimatemember, $petadmin;

	$email = strtolower($ultimatemember->user->profile['user_email']);
	crowbank_log( 'Checking for info on user with email ' . $email, 1);

	if ($petadmin->employees->get_by_email($email)) {
		$ultimatemember->user->set_role('employee');
		crowbank_log('Changed to employee', 2);
	}
	elseif ($petadmin->customers->get_by_email($email)) {
		$ultimatemember->user->set_role('customer');
		crowbank_log('Changed to customer', 2);
	}
}

function crowbank_display_log() {
	global $wpdb, $crowbank_session;
	$log_classes = array('log_debug', 'log_info', 'log_warn', 'log_error');


	$query = "select log_severity, log_timestamp, log_file, log_line, log_msg
from crwbnk_log where log_session = " . $crowbank_session;
	$results = $wpdb->get_results($query);

	echo '<div style="font-size: small; font-family: arial;">';
	echo '<table class="table"><thead style="color: blue; "><th>Timestamp</th><th>File</th><th>Line</th><th>Message</th></thead><tbody>';
	foreach ($results as $row) {
		echo '<tr class="' .  $log_classes[$row->log_severity] . '"><td>' . $row->log_timestamp . '</td><td>' . $row->log_file . '</td><td>' . $row->log_line . '</td><td>' . $row->log_msg . '</td></tr>';
	}
	echo '</tbody></table></div>';

	$wpdb->update('crwbnk_session', array('session_open' => 0), array('session_open' => 1));
}

function crowbank_my_redirect() {
	global $pagename, $crowbank_var;

	crowbank_log('Inside crowbank_my_redirect with pagename = ' . $pagename, 1);

	if (!is_page('my'))
		return;

	crowbank_log('Checking whether to redirect from my page. Var = ' . $crowbank_var);

	$role = um_user('role');
	crowbank_log('role was found to be ' . $role);

	if ($role == 'customer') {
		crowbank_log('redirecting to customer');
		exit(wp_redirect(home_url('customer')));
	}

	if ($role == 'employee') {
		crowbank_log('redirecting to employee');
		exit(wp_redirect(home_url('employee')));
	}

	crowbank_log('redirecting to daily');	
	exit(wp_redirect(home_url('daily')));
}

function current_customer_field( $field_name ) {
	$cust = get_customer();
	
	if (!$cust)
		return null;

	return $cust->return_field( $field_name );
}

function crowbank_account_pre_update_profile( $changes, $id ) {
	$customer = get_customer();
	$crowbank_changes = [];
	if ( isset($changes['first_name']) and $changes['first_name'] != $customer->forename) {
		$crowbank_changes['cust_forename'] = $changes['first_name'];
	}
	if ( isset($changes['last_name']) and $changes['last_name'] != $customer->surname) {
		$crowbank_changes['cust_surname'] = $changes['last_name'];
	}
	if ( isset($changes['user_email']) and $changes['user_email'] != $customer->email) {
		$crowbank_changes['cust_email'] = $changes['user_email'];
	}

	if ($crowbank_changes) {
		$customer->update( $crowbank_changes );
		
		$crowbank_changes['cust_no'] = $customer->no;
		
		$msg = new Message( $msg_type, $crowbank_changes );
		
		$msg->flush();
	}
}

add_filter( 'gform_pre_render_23', 'populate_months' );
add_filter( 'gform_pre_render_23', 'populate_months' );
add_filter( 'gform_pre_render_25', 'populate_booking_form' );
add_filter( 'gform_pre_render_26', 'check_booking_confirmation' );
add_filter( 'gform_pre_render_27', 'populate_pet_form');
add_filter( 'gform_pre_render_30', 'populate_customer_form');
add_action( 'gform_after_submission_27', 'pet_submission', 10, 2 );
add_action( 'gform_after_submission_30', 'customer_submission', 10, 2 );

add_filter('gform_field_value_cust_forename', function() { return current_customer_field('cust_forename'); });
add_filter('gform_field_value_cust_surname', function() { return current_customer_field('cust_surname'); });
add_filter('gform_field_value_cust_addr1', function() { return current_customer_field('cust_addr1'); });
add_filter('gform_field_value_cust_addr3', function() { return current_customer_field('cust_addr3'); });
add_filter('gform_field_value_cust_postcode', function() { return current_customer_field('cust_postcode'); });
add_filter('gform_field_value_cust_email', function() { return current_customer_field('cust_email'); });
add_filter('gform_field_value_cust_telno_home', function() { return current_customer_field('cust_telno_home'); });
add_filter('gform_field_value_cust_telno_mobile', function() { return current_customer_field('cust_telno_mobile'); });
add_filter('gform_field_value_cust_telno_mobile2', function() { return current_customer_field('cust_telno_mobile2'); });
/* add_action('um_account_pre_update_profile', 'crowbank_account_pre_update_profile'); */


add_action('um_after_account_general', 'show_extra_fields', 100);
function show_extra_fields() {
	
	global $ultimatemember;
	$id = um_user('ID');
	$output = '';
	
	$names = [ "phone_number",'job', "company", "tax-number","sector","company-employee-count","company-address"  ];
	
	$fields = [];
	foreach( $names as $name )
		$fields[ $name ] = $ultimatemember->builtin->get_specific_field( $name );
		global $ultimatemember; $id = um_user('ID');
		$fields = apply_filters( 'um_account_secure_fields', $fields, $id );
		
		foreach( $fields as $key => $data )
			$output .= $ultimatemember->fields->edit_field( $key, $data );
			
			echo $output;
}

function get_custom_fields( $fields ) {
	global $ultimatemember;
	$array=array();
	foreach ($fields as $field ) {
		if ( isset( $ultimatemember->builtin->saved_fields[$field] ) ) {
			$array[$field] = $ultimatemember->builtin->saved_fields[$field];
		} else if ( isset( $ultimatemember->builtin->predefined_fields[$field] ) ) {
			$array[$field] = $ultimatemember->builtin->predefined_fields[$field];
		}
	}
	return $array;
}

function crowbank_gform_script() {
	wp_enqueue_script( 'crowbank_alert', plugins_url( '/js/booking-form.js', __FILE__));
}

function crowbank_hooks_init() {
	add_action('um_post_registration_global_hook', 'crowbank_check_new_user',10, 2);
	add_action('wp_footer', 'crowbank_display_log' );
	add_action('template_redirect', 'crowbank_my_redirect', 10010);
	add_action('gform_enqueue_scripts_25', 'crowbank_gform_script', 10, 2 );
}
add_action('init', 'crowbank_hooks_init');

function crowbank_styles_and_scripts()
{
	// Register the style like this for a plugin:
	wp_register_style( 'crowbank-style', plugins_url( '/css/crowbank2.css', __FILE__ ));
	wp_register_style( 'calendar-style', plugins_url( '/css/calendar.css', __FILE__ ));
	wp_register_script( 'customer_table', plugins_url( '/js/customer_table.js', __FILE__));
	wp_register_script( 'crowbank', plugins_url( '/js/crowbank.js', __FILE__));
	
	// For either a plugin or a theme, you can then enqueue the style:
	wp_enqueue_style( 'crowbank-style', false, array(), null );
	wp_enqueue_style( 'calendar-style', false, array(), null);
	
	wp_enqueue_script( 'customer_table', plugins_url( '/js/customer_table.js', __FILE__),
			array(), false, true);
	wp_enqueue_script( 'crowbank', plugins_url( '/js/crowbank.js', __FILE__),
			array(), false, true);
}
add_action( 'wp_enqueue_scripts', 'crowbank_styles_and_scripts' );

function remove_cssjs_ver( $src ) {
	if( strpos( $src, '?ver=' ) )
		$src = remove_query_arg( 'ver', $src );
		return $src;
}
add_filter( 'style_loader_src', 'remove_cssjs_ver', 1000 );
add_filter( 'script_loader_src', 'remove_cssjs_ver', 1000 );


function get_url_by_slug($slug) {
	$page_url_id = get_page_by_path( $slug );
	$page_url_link = get_permalink($page_url_id);
	return $page_url_link;
}

function get_customer() {
	global $petadmin;

	$customer = NULL;

	if ($user = wp_get_current_user()) {
		if ($user->data) {
			$email = $user->data->user_email;
			$customer = $petadmin->customers->get_by_email($email);
		}
	}

	if (!$customer and isset($_REQUEST['cust'])) {
		$customers = $petadmin->customers->get_by_any($_REQUEST['cust']);
		if (count($customers) == 1) {
			$customer = $customers[0];
		}
	}
	
	if (!$customer and isset($_REQUEST['customer'])) {
		$customers = $petadmin->customers->get_by_any($_REQUEST['customer']);
		if (count($customers) == 1) {
			$customer = $customers[0];
		}
	}
	
	return $customer;
}

function get_employee() {
	global $petadmin;

	$employee = NULL;

	if ($user = wp_get_current_user()) {
		$email = $user->data->user_email;
		$employee = $petadmin->employees->get_by_email($email);
	}

	if (!$employee and isset($_REQUEST['emp'])) {
		$employee = $petadmin->employees->get_by_any($_REQUEST['emp']);
	}
	
	if (!$employee and isset($_REQUEST['employee'])) {
		$employee = $petadmin->employees->get_by_any($_REQUEST['employee']);
	}
	
	return $employee;
}

function get_weekstart() {
	if (isset($_GET['weekstart'])) {
		$weekstart = new DateTime($_GET['weekstart']);
	}
	else {
		$today = new DateTime();
		$today = new DateTime($today->format('Y-m-d'));
		$wd = (int) $today->format('N') - 1;
		if ($wd > 0) {
			$weekstart = $today->sub(new DateInterval('P' . $wd . 'D'));
		} else {
			$weekstart = $today;
		}
	}

	return $weekstart;
}

function get_month() {
	if( isset($_REQUEST['month'])) {
		$month = $_REQUEST['month'];
	} else {
		$month = date("m", time());
	}
	return $month;
}

function get_year() {
	if(isset($_REQUEST['year']) ){
		$year = $_REQUEST['year'];
	} else {
		$year = date("Y",time());
	}
	return $year;
}

function get_daily_date() {
	if (isset($_GET['date'])) {
	$date = new DateTime($_GET['date']);
	} else {
		$date = new DateTime();
		$date = new DateTime($date->format('Y-m-d'));
	}
	return $date;
}

function get_um_role() {
	$user = wp_get_current_user();
	$um_user_role = get_user_meta($user->ID,'role',true);
	return $um_user_role;
}

function clean_trace($trace) {
	$ret = array();

	foreach($trace as $element) {
		$ret[] = ['file' => $element['file'], 'line' => $element['line'], 'function' => $element['function']];
	}

	return $ret;
}



if ( !function_exists( 'wp_mail' ) ) :
/**
 * Send mail, similar to PHP's mail
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * Using the two 'wp_mail_from' and 'wp_mail_from_name' hooks allow from
 * creating a from address like 'Name <email@address.com>' when both are set. If
 * just 'wp_mail_from' is set, then just the email address will be used with no
 * name.
 *
 * The default content type is 'text/plain' which does not allow using HTML.
 * However, you can set the content type of the email by using the
 * {@see 'wp_mail_content_type'} filter.
 *
 * The default charset is based on the charset used on the blog. The charset can
 * be set using the {@see 'wp_mail_charset'} filter.
 *
 * @since 1.2.1
 *
 * @global PHPMailer $phpmailer
 *
 * @param string|array $to          Array or comma-separated list of email addresses to send message.
 * @param string       $subject     Email subject
 * @param string       $message     Message contents
 * @param string|array $headers     Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @return bool Whether the email contents were sent successfully.
 */
function wp_mail_disabled( $to, $subject, $message, $headers = '', $attachments = array() ) {
	// Compact the input, apply the filters, and extract them back out
	
	/**
	 * Filters the wp_mail() arguments.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
	 *                    subject, message, headers, and attachments values.
	 */
	$msg = 'About to send email to ' . $to . ', subject: ' . $subject;
	crowbank_log($msg);
	
	$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );
	
	if ( isset( $atts['to'] ) ) {
		$to = $atts['to'];
	}
	
	if ( !is_array( $to ) ) {
		$to = explode( ',', $to );
	}
	
	if ( isset( $atts['subject'] ) ) {
		$subject = $atts['subject'];
	}
	
	if ( isset( $atts['message'] ) ) {
		$message = $atts['message'];
	}
	
	if ( isset( $atts['headers'] ) ) {
		$headers = $atts['headers'];
	}
	
	if ( isset( $atts['attachments'] ) ) {
		$attachments = $atts['attachments'];
	}
	
	if ( ! is_array( $attachments ) ) {
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
	}
	global $phpmailer;
	
	// (Re)create it, if it's gone missing
	if ( ! ( $phpmailer instanceof PHPMailer ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$phpmailer = new PHPMailer( true );
	}
	
	// Headers
	$cc = $bcc = $reply_to = array();
	
	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( !is_array( $headers ) ) {
			// Explode the headers out, so this function can take both
			// string headers and an array of headers.
			$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$tempheaders = $headers;
		}
		$headers = array();
		
		// If it's actually got contents
		if ( !empty( $tempheaders ) ) {
			// Iterate through the raw headers
			foreach ( (array) $tempheaders as $header ) {
				if ( strpos($header, ':') === false ) {
					if ( false !== stripos( $header, 'boundary=' ) ) {
						$parts = preg_split('/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
					}
					continue;
				}
				// Explode them out
				list( $name, $content ) = explode( ':', trim( $header ), 2 );
				
				// Cleanup crew
				$name    = trim( $name    );
				$content = trim( $content );
				
				switch ( strtolower( $name ) ) {
					// Mainly for legacy -- process a From: header if it's there
					case 'from':
						$bracket_pos = strpos( $content, '<' );
						if ( $bracket_pos !== false ) {
							// Text before the bracketed email is the "From" name.
							if ( $bracket_pos > 0 ) {
								$from_name = substr( $content, 0, $bracket_pos - 1 );
								$from_name = str_replace( '"', '', $from_name );
								$from_name = trim( $from_name );
							}
							
							$from_email = substr( $content, $bracket_pos + 1 );
							$from_email = str_replace( '>', '', $from_email );
							$from_email = trim( $from_email );
							
							// Avoid setting an empty $from_email.
						} elseif ( '' !== trim( $content ) ) {
							$from_email = trim( $content );
						}
						break;
					case 'content-type':
						if ( strpos( $content, ';' ) !== false ) {
							list( $type, $charset_content ) = explode( ';', $content );
							$content_type = trim( $type );
							if ( false !== stripos( $charset_content, 'charset=' ) ) {
								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
							} elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
								$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
								$charset = '';
							}
							
							// Avoid setting an empty $content_type.
						} elseif ( '' !== trim( $content ) ) {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						$cc = array_merge( (array) $cc, explode( ',', $content ) );
						break;
					case 'bcc':
						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
						break;
					case 'reply-to':
						$reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
						break;
					default:
						// Add it to our grand headers array
						$headers[trim( $name )] = trim( $content );
						break;
				}
			}
		}
	}
	
	// Empty out the values that may be set
	$phpmailer->clearAllRecipients();
	$phpmailer->clearAttachments();
	$phpmailer->clearCustomHeaders();
	$phpmailer->clearReplyTos();
	
	// From email and name
	// If we don't have a name from the input headers
	if ( !isset( $from_name ) )
		$from_name = 'WordPress';
		
		/* If we don't have an email from the input headers default to wordpress@$sitename
		 * Some hosts will block outgoing mail from this address if it doesn't exist but
		 * there's no easy alternative. Defaulting to admin_email might appear to be another
		 * option but some hosts may refuse to relay mail from an unknown domain. See
		 * https://core.trac.wordpress.org/ticket/5007.
		 */
		
		if ( !isset( $from_email ) ) {
			// Get the site domain and get rid of www.
			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}
			
			$from_email = 'wordpress@' . $sitename;
		}
		
		/**
		 * Filters the email address to send from.
		 *
		 * @since 2.2.0
		 *
		 * @param string $from_email Email address to send from.
		 */
		$from_email = apply_filters( 'wp_mail_from', $from_email );
		
		/**
		 * Filters the name to associate with the "from" email address.
		 *
		 * @since 2.3.0
		 *
		 * @param string $from_name Name associated with the "from" email address.
		 */
		$from_name = apply_filters( 'wp_mail_from_name', $from_name );
		
		try {
			$phpmailer->setFrom( $from_email, $from_name, false );
		} catch ( phpmailerException $e ) {
			$mail_error_data = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
			$mail_error_data['phpmailer_exception_code'] = $e->getCode();
			
			/** This filter is documented in wp-includes/pluggable.php */
			do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );
			
			return false;
		}
		
		// Set mail's subject and body
		$phpmailer->Subject = $subject;
		
		$phpmailer->Body = '';
		$phpmailer->AltBody = '';
		
		if ( is_string($message) ) {
			$phpmailer->Body = $message;
			
			// Set Content-Type and charset
			// If we don't have a content-type from the input headers
			if ( !isset( $content_type ) )
				$content_type = 'text/plain';
				
				/**
				 * Filter the wp_mail() content type.
				 *
				 * @since 2.3.0
				 *
				 * @param string $content_type Default wp_mail() content type.
				 */
				$content_type = apply_filters( 'wp_mail_content_type', $content_type );
				
				$phpmailer->ContentType = $content_type;
				
				// Set whether it's plaintext, depending on $content_type
				if ( 'text/html' == $content_type )
					$phpmailer->IsHTML( true );
					
					// For backwards compatibility, new multipart emails should use
					// the array style $message. This never really worked well anyway
				if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
					$phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
		} elseif ( is_array($message) ) {
			foreach ($message as $type => $bodies) {
				foreach ((array) $bodies as $body) {
					if ($type === 'text/html') {
						$phpmailer->Body = $body;
					}
					elseif ($type === 'text/plain') {
						$phpmailer->AltBody = $body;
					}
					else {
						$phpmailer->AddAttachment($body, '', 'base64', $type);
					}
				}
			}
		}
		
		// Set destination addresses, using appropriate methods for handling addresses
		$address_headers = compact( 'to', 'cc', 'bcc', 'reply_to' );
		
		foreach ( $address_headers as $address_header => $addresses ) {
			if ( empty( $addresses ) ) {
				continue;
			}
			
			foreach ( (array) $addresses as $address ) {
				try {
					// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
					$recipient_name = '';
					
					if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
						if ( count( $matches ) == 3 ) {
							$recipient_name = $matches[1];
							$address        = $matches[2];
						}
					}
					
					switch ( $address_header ) {
						case 'to':
							$phpmailer->addAddress( $address, $recipient_name );
							break;
						case 'cc':
							$phpmailer->addCc( $address, $recipient_name );
							break;
						case 'bcc':
							$phpmailer->addBcc( $address, $recipient_name );
							break;
						case 'reply_to':
							$phpmailer->addReplyTo( $address, $recipient_name );
							break;
					}
				} catch ( phpmailerException $e ) {
					continue;
				}
			}
		}
		
		// Set to use PHP's mail()
		$phpmailer->isMail();
		
		// Set Content-Type and charset
		// If we don't have a content-type from the input headers
		if ( !isset( $content_type ) )
			$content_type = 'text/plain';
			
			/**
			 * Filters the wp_mail() content type.
			 *
			 * @since 2.3.0
			 *
			 * @param string $content_type Default wp_mail() content type.
			 */
			$content_type = apply_filters( 'wp_mail_content_type', $content_type );
			
			$phpmailer->ContentType = $content_type;
			
			// Set whether it's plaintext, depending on $content_type
			if ( 'text/html' == $content_type )
				$phpmailer->isHTML( true );
				
				// If we don't have a charset from the input headers
				if ( !isset( $charset ) )
					$charset = get_bloginfo( 'charset' );
					
					// Set the content-type and charset
					
					/**
					 * Filters the default wp_mail() charset.
					 *
					 * @since 2.3.0
					 *
					 * @param string $charset Default email charset.
					 */
					$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );
					
					// Set custom headers
					if ( !empty( $headers ) ) {
						foreach ( (array) $headers as $name => $content ) {
							$phpmailer->addCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
						}
						
						if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
							$phpmailer->addCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
					}
					
					if ( !empty( $attachments ) ) {
						foreach ( $attachments as $attachment ) {
							try {
								$phpmailer->addAttachment($attachment);
							} catch ( phpmailerException $e ) {
								continue;
							}
						}
					}
					
					/**
					 * Fires after PHPMailer is initialized.
					 *
					 * @since 2.2.0
					 *
					 * @param PHPMailer &$phpmailer The PHPMailer instance, passed by reference.
					 */
					do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );
					
					// Send!
					try {
						$ret = $phpmailer->send();
						crowbank_log('send successful');
						return $ret;
					} catch ( phpmailerException $e ) {
						
						$mail_error_data = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
						$mail_error_data['phpmailer_exception_code'] = $e->getCode();
						
						/**
						 * Fires after a phpmailerException is caught.
						 *
						 * @since 4.4.0
						 *
						 * @param WP_Error $error A WP_Error object with the phpmailerException message, and an array
						 *                        containing the mail recipient, subject, message, headers, and attachments.
						 */
						do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );
						
						crowbank_log($e->getMessage(), 3);
						
						return false;
					}
}
endif;

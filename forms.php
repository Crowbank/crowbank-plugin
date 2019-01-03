<?php

/* 23: Availabililty Calendar */

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
add_filter( 'gform_pre_render_23', 'populate_months' );

/* 25: Booking */

function crowbank_gform_script() {
	wp_enqueue_script( 'crowbank_alert', plugins_url( '/js/booking-form.js', __FILE__));
}

add_action('gform_enqueue_scripts_25', 'crowbank_gform_script', 10, 2 );

/* 27: Pet Form */

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
add_filter( 'gform_pre_render_27', 'populate_pet_form');

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
		$id = crowbank_localid(array('type' => 'Pet'));
		$pet_no = -$id;
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
add_action( 'gform_after_submission_27', 'pet_submission', 10, 2 );

/* 30: Customer Form */

function current_customer_field( $field_name ) {
	$cust = get_customer();
	
	if (!$cust)
		return null;

	return $cust->return_field( $field_name );
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
add_action( 'gform_after_submission_30', 'customer_submission', 10, 2 );

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
add_filter( 'gform_pre_render_30', 'populate_customer_form');


add_filter('gform_field_value_cust_forename', function() { return current_customer_field('cust_forename'); });
add_filter('gform_field_value_cust_surname', function() { return current_customer_field('cust_surname'); });
add_filter('gform_field_value_cust_addr1', function() { return current_customer_field('cust_addr1'); });
add_filter('gform_field_value_cust_addr3', function() { return current_customer_field('cust_addr3'); });
add_filter('gform_field_value_cust_postcode', function() { return current_customer_field('cust_postcode'); });
add_filter('gform_field_value_cust_email', function() { return current_customer_field('cust_email'); });
add_filter('gform_field_value_cust_telno_home', function() { return current_customer_field('cust_telno_home'); });
add_filter('gform_field_value_cust_telno_mobile', function() { return current_customer_field('cust_telno_mobile'); });
add_filter('gform_field_value_cust_telno_mobile2', function() { return current_customer_field('cust_telno_mobile2'); });


<?php
function crowbank_availability_prep( $atts ) {
	/* This function enters js code to switch the active availability tab based on the following logic:
	 * 1. If 'tab' is in $_REQUEST, its value (one of kennels, deluxe or cattery) is used
	 * 2. Otherwise, if a customer is not set, use kennels
	 * 3. If the active customer has only cats, use cattery
	 * 4. If the most recent booking was deluxe, use deluxe
	 * 5. Otherwise use kennels
	 */	
	
	$tab = 'kennels';
	if (array_key_exists('tab', $_REQUEST)) {
		$tab = $_REQUEST['tab'];
	} else {
		/* is there a current customer? */
		$customer = get_customer();
		if ($customer) {
			/* are all pets cats? */
			$pets = $customer->get_pets();
			$only_cats = true;
			foreach( $pets as $pet_no => $pet ) {
				if ($pet->species == 'Dog' and $pet->deceased == 'N') {
					$only_cats = false;
					break;
				}
			}
			if ( $only_cats ) {
				$tab = 'cattery';
			} else {
				/* customer has some dogs; look at last booking */
				if ($customer->is_deluxe()) {
					$tab = 'deluxe';
				}
			}
		}
	}
	
	$s = 'jQuery(document).ready(function($){';
	$s .= 'jQuery("#' . $tab . ' a").triggerHandler(' . "'click');";
	$s .= "console.log('ready from crowbank_availability_prep has run');";
	$s .= '});';
	
	return '<script>' . $s . '</script>';
}
add_shortcode ('crowbank_availability_prep', 'crowbank_availability_prep');

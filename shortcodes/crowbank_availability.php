<?php
function crowbank_tabgroup( $atts, $content = null ) {
	extract(shortcode_atts(array(
			'style' => 'horizontal',
			'id' => 'tab'
	), $atts));
	
	$GLOBALS['tab_count'] = 0;
	$i = 1;
	
	do_shortcode( $content );
	
	if( is_array( $GLOBALS['tabs'] ) ){
		
		foreach( $GLOBALS['tabs'] as $tab ){
			if( $tab['icon'] != '' ){
				$icon = '<i class="icon-'.$tab['icon'].'"></i>';
			}
			else{
				$icon = '';
			}
			$tabs[] = '<li class="crowbank_tab" id="' . $tab['id'] . '"><a href="#panel_' . $tab['id'] . '">'.$icon . $tab['title'].'</a></li>';
			$panes[] = '<div class="panel" id="panel_' . $tab['id'] . '"><p>'.$tab['content'].'</p></div>';
			$i++;
			$icon = '';
		}
		$return = '<div class="crowbank_tabset id="'.$id.'" tabstyle-'.$style.' clearfix"><ul class="tabs">'.
				implode( "\n", $tabs ).'</ul><div class="panels">'.implode( "\n", $panes ).'</div></div>';
	}
	return $return;
}
add_shortcode( 'crowbank_tabgroup', 'crowbank_tabgroup' );

function crowbank_tab( $atts, $content = null) {
	extract(shortcode_atts(array(
			'title' => '',
			'icon'  => '',
			'id' => ''
	), $atts));
	
	$x = $GLOBALS['tab_count'];
	if ($id == '') {
		$id = $x;
	}
	$GLOBALS['tabs'][$x] = array( 'id' => $id, 'title' => sprintf( $title, $GLOBALS['tab_count'] ), 'icon' => $icon, 'content' =>  do_shortcode( $content ) );
	$GLOBALS['tab_count']++;
}
add_shortcode( 'crowbank_tab', 'crowbank_tab' );

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

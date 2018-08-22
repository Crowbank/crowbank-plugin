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


<?php
/*
Plugin Name: Liberal Arts Events Custom Fields
Version: 1.2
Author: Stephanie Leary
Author URI: http://stephanieleary.com/
Description: Populates the list of campus buildings for event venues. Provides a shortcode to embed campus maps.
License: GPL2
*/

// Shortcodes


// [building id="1602"] or [building 1602] becomes http://aggiemap.tamu.edu/?bldg=1602
// [bldg 1602] also works

add_shortcode( 'building', 'tamu_campus_map_link_shortcode' );
add_shortcode( 'bldg', 'tamu_campus_map_link_shortcode' );

function tamu_campus_map_link_shortcode( $atts, $content = null ) {
    $bldg = $link = '';

	if ( isset( $atts['id'] ) )
		$bldg = (int)$atts['id'];
	elseif ( isset( $atts[0] ) )
		$bldg = (int)$atts[0];
	
	if ( !empty( $bldg ) )
		$link = 'http://aggiemap.tamu.edu/?bldg=' . $bldg;

	return $link;
}

// [aggiemap id="1602"] or [aggiemap 1602] becomes an iframe containing the map at http://aggiemap.tamu.edu/?bldg=1602

add_shortcode( 'aggiemap', 'tamu_campus_map_embed_shortcode' );

function tamu_campus_map_embed_shortcode( $atts, $content = null ) {
    $bldg = $link = $iframe = '';

	if ( isset( $atts['id'] ) )
		$bldg = (int)$atts['id'];
	elseif ( isset( $atts[0] ) )
		$bldg = (int)$atts[0];
	
	if ( !empty( $bldg ) ) {
		$link = 'http://aggiemap.tamu.edu/?bldg=' . $bldg;
		
		$iframe = sprintf( '<div id="aggiemap">
		<iframe width="640" height="480" src="%s"></iframe>
		</div>', $link );
		
	}

	return $iframe;
}



// jQuery autocomplete for building names/numbers
add_action('admin_enqueue_scripts', 'tamu_campus_building_scripts');

function tamu_campus_building_scripts( $hook ) {
	// Don't bother loading all these scripts unless we're on a post edit screen
	if ( 'edit.php' == $hook || 'post-new.php' == $hook || 'post.php' == $hook ) {
		// use jQuery UI Autocomplete rather than WP's Suggest script (which requires AJAX URL for source data)
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'campus-buildings-autocomplete', plugins_url( '/js/campus-buildings-autocomplete.js', __FILE__ ), array( 'jquery-ui-autocomplete' ) );
		// in that last script, create var campus_buildings to hold the returned array of building names
		wp_localize_script( 'campus-buildings-autocomplete', 'campus_buildings', tamu_campus_buildings_get_array() );
	}
}

// Get list of building names and URLs from AggieMaps and format for use in autocomplete
function tamu_campus_buildings_get_array() {
	$links = array();
	
	// do we have a saved building list? Let's not hit this URL every time we load an edit screen.
	if ( false === ( $html = get_transient( '_campus_building_list' ) ) ) {
		// No? OK, grab a fresh copy of the list.
		$html = wp_remote_retrieve_body( wp_remote_get( 'http://aggiemap.tamu.edu/include/directoryDesktop.asp' ) );
		// save a copy of the list for 12 hours
		set_transient( '_campus_building_list', $html, 60*60*12 );
	}
	
	// turn that HTML list of links into an array
	$doc = new DOMDocument();
	$doc->loadHTML($html);
	libxml_clear_errors();
	libxml_use_internal_errors(false);
	$xmlStr = $doc->saveXml($doc->documentElement);
	$xml = new SimpleXmlElement($xmlStr);

	foreach ($xml->xpath('//a') as $node) {
	    $href = $node->attributes()->href;
		$bldgnum = (int) substr( strrchr( $href, "=" ), 1 );
		$links[] = array( 'label' => (string) $node, 'value' => $bldgnum );
		//$links[] = (string) $node . " [$bldgnum]";
	}
	
	return $links;
}

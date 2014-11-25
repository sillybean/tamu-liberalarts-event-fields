<?php
/*
Plugin Name: Liberal Arts Events Custom Fields
Version: 1.0
Author: Stephanie Leary
Author URI: http://stephanieleary.com/
Description: Populates the list of campus buildings for event venues.
License: GPL2
*/

// jQuery autocomplete
add_action('admin_enqueue_scripts', 'tamu_campus_building_scripts');

function tamu_campus_building_scripts( $hook ) {
	// Don't bother loading all these scripts unless we're on a post edit screen
	if ( 'edit.php' == $hook || 'post-new.php' == $hook || 'post.php' == $hook ) {
		// use jQuery UI Autocomplete rather than WP's Suggest script (which requires AJAX URL for source data)
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'campus-buildings-autocomplete', plugins_url( '/js/campus-buildings-autocomplete.js', __FILE__ ), array( 'jquery-ui-autocomplete' ) );
		// in that last script, create var campus_buildings to hold the returned array of building names
		wp_localize_script( 'campus-buildings-autocomplete', 'campus_buildings', tamus_campus_buildings_get_array() );
	}
}

function tamus_campus_buildings_get_array() {
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
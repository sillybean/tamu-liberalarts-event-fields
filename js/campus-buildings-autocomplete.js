jQuery(function($) {
	// campus_buildings array is set in the PHP file using wp_localize_script()
	$( "#tamu_campus_building input" ).autocomplete({
        source: campus_buildings
    });
});
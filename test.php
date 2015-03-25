<?php
/*
Plugin Name: Plugin Updater Tester
Plugin URI: http://ironbounddesigns.com
Description: Test plugin updates
Version: 0.9
Author: Iron Bound Designs
Author URI: http://ironbounddesigns.com
License: GPLv2
*/

require_once( 'itelic-plugin-updater.php' );

set_site_transient('update_plugins', null);

$updater = new ITELIC_Plugin_Updater( 'http://www.itelic.dev', 19, __FILE__, array(
	'version' => 0.9,
	'key' => 'ATAZ-espg-6769'
) );

add_action('init', function(){

});

/*add_action( 'admin_notices', function () {

	$response    = $updater->get_latest_version( 'ATAZ-espg-6769' );

	var_dump( $response );
} );*/
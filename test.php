<?php
/*
Plugin Name: Plugin Updater Tester
Plugin URI: http://ironbounddesigns.com
Description: Test plugin updates
Version: 1.0
Author: Iron Bound Designs
Author URI: http://ironbounddesigns.com
License: GPLv2
*/

require_once( 'itelic-plugin-updater.php' );

add_action( 'init', function () {
	$updater = new ITELIC_Plugin_Updater( 'http://www.itelic.dev', 19, __FILE__ );
	$json    = $updater->get_latest_version( 'QPYP-qdnv-0218' );

	var_dump( $json );
} );
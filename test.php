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

define( 'ITELIC_DEMO_VERSION', '1.0' );
define( 'ITELIC_DEMO_PRODUCT_ID', 19 );
define( 'ITELIC_DEMO_STORE_URL', 'http://www.itelic.dev' );

//set_site_transient( 'update_plugins', null );

/**
 * Register an admin menu to contain our license key.
 */
function itelic_demo_add_license_page() {
	add_options_page( __( "Exchange Licensing Demo" ), __( "Exchange Licensing Demo" ), 'manage_options', 'itelic-demo', 'itelic_demo_render_license_page' );
}

add_action( 'admin_menu', 'itelic_demo_add_license_page' );

/**
 * Render the license page.
 */
function itelic_demo_render_license_page() {

	$key = get_option( 'itelic_demo_license_key' );

	?>

	<div class="wrap">
		<h2><?php _e( "Exchange Licensing Demo" ); ?></h2>

		<form action="<?php admin_url( 'options-general.php?page=itelic-demo' ); ?>" method="POST">

			<table class="form-table">
				<tbody>
				<tr>
					<th><label for="itelic-demo-license-key"><?php _e( "License Key" ); ?></label></th>
					<td><input type="text" id="itelic-demo-license-key" name="itelic_demo_license_key" value="<?php echo esc_attr( $key ); ?>"></td>
				</tr>
				</tbody>
			</table>

			<p>
				<?php submit_button( __( "Activate" ), 'primary', 'activate', false ); ?>
				<?php submit_button( __( "Deactivate" ), 'secondary', 'deactivate', false ); ?>
			</p>

			<?php wp_nonce_field( 'itelic-demo-save' ); ?>
		</form>

	</div>

<?php

}

/**
 * Activates/Deactivates license key depending on which submit button was
 * pressed.
 */
function itelic_demo_save_license_key() {

	if ( ! isset( $_GET['page'] ) || $_GET['page'] != 'itelic-demo' ) {
		return;
	}

	if ( empty( $_POST['itelic_demo_license_key'] ) || empty( $_POST['_wpnonce'] ) ) {
		return;
	}

	// verify capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// verify intent
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'itelic-demo-save' ) ) {
		return;
	}

	// sanitize key
	$key = sanitize_text_field( $_POST['itelic_demo_license_key'] );

	if ( isset( $_POST['activate'] ) ) {
		update_option( 'itelic_demo_license_key', $key );

		$id = itelic_make_plugin_updater()->activate( $key );

		if ( is_wp_error( $id ) ) {
			itelic_demo_display_admin_notice( 'error', sprintf( __( "Could not activate license key because %s" ), $id->get_error_message() ) );
		} else {
			itelic_demo_display_admin_notice( 'success', __( "License key activated." ) );
			update_option( 'itelic_demo_activation_id', $id );
		}
	} elseif ( isset( $_POST['deactivate'] ) ) {
		$id = get_option( 'itelic_demo_activation_id' );

		if ( ! $id ) {

			// this license key was never activated, so we don't need to do anything
			return;
		}

		$response = itelic_make_plugin_updater()->deactivate( $key, $id );

		if ( is_wp_error( $response ) ) {
			itelic_demo_display_admin_notice( 'error', sprintf( __( "Could not deactivate the license key because %s" ), $id->get_error_message() ) );
		} else {
			itelic_demo_display_admin_notice( 'success', __( "License key deactivated." ) );
			delete_option( 'itelic_demo_license_key' );
		}
	}
}

add_action( 'admin_notices', 'itelic_demo_save_license_key' );

/**
 * Display an admin notice.
 *
 * @param string $type
 * @param string $message
 *
 * @return void
 */
function itelic_demo_display_admin_notice( $type, $message ) {

	?>
	<div class="notice notice-<?php echo esc_attr( $type ); ?>">
		<p><?php echo $message; ?></p>
	</div>

<?php
}

/**
 * Generates a plugin updater object.
 *
 * @return ITELIC_Plugin_Updater
 */
function itelic_make_plugin_updater() {

	static $updater = null;

	if ( $updater === null ) {
		$updater = new ITELIC_Plugin_Updater( ITELIC_DEMO_STORE_URL, ITELIC_DEMO_PRODUCT_ID, __FILE__, array(
			'version'       => ITELIC_DEMO_VERSION,
			'key'           => get_option( 'itelic_demo_license_key' ),
			'activation_id' => get_option( 'itelic_demo_activation_id', 0 )
		) );
	}

	return $updater;
}

add_action( 'admin_init', 'itelic_make_plugin_updater', 0 );
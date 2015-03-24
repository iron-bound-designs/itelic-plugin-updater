<?php

/**
 * Class ITELIC_Plugin_Updater
 */
class ITELIC_Plugin_Updater {

	/**
	 * Activate the site.
	 */
	const EP_ACTIVATE = 'activate';

	/**
	 * Deactivate the site.
	 */
	const EP_DEACTIVATE = 'deactivate';

	/**
	 * Returns info about the license key.
	 */
	const EP_INFO = 'info';

	/**
	 * Get the latest version.
	 */
	const EP_VERSION = 'version';

	/**
	 * Download the plugin file.
	 */
	const EP_DOWNLOAD = 'download';

	/**
	 * Return info about the product.
	 */
	const EP_PRODUCT = 'product';

	/**
	 * GET method.
	 */
	const METHOD_GET = 'GET';

	/**
	 * POST method.
	 */
	const METHOD_POST = 'POST';

	/**
	 * @var string
	 */
	private $store_url;

	/**
	 * @var int
	 */
	private $product_id;

	/**
	 * @var string
	 */
	private $file;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $key = '';

	/**
	 * Constructor.
	 *
	 * @param string $store_url  This is the URL to your store.
	 * @param int    $product_id This is the product ID of your plugin.
	 * @param string $file       The __FILE__ constant of your main plugin file.
	 * @param array  $args       Additional args.
	 *
	 * @throws Exception
	 */
	public function __construct( $store_url, $product_id, $file, $args = array() ) {
		$this->store_url  = trailingslashit( $store_url );
		$this->product_id = $product_id;
		$this->file       = $file;

		if ( empty( $args['version'] ) ) {
			throw new Exception( "Version required." );
		}

		$this->version = $args['version'];

		if ( $args['key'] ) {
			$this->key = $args['key'];
		}
	}

	/**
	 * Activate a license key for this site.
	 *
	 * @param string $key License Key
	 *
	 * @return int|WP_Error Activation Record ID on success, WP_Error object on failure.
	 */
	public function activate( $key ) {

		$params = array(
			'location' => site_url()
		);

		$response = $this->call_api( self::EP_ACTIVATE, self::METHOD_POST, $key, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			return $response->id;
		}
	}

	/**
	 * Deactivate the license key on this site.
	 *
	 * @param string $key           License Key
	 * @param int    $activation_id ID returned from ITELIC_Plugin_Updater::activate
	 *
	 * @return boolean|WP_Error Boolean True on success, WP_Error object on failure.
	 */
	public function deactivate( $key, $activation_id ) {
		$params = array(
			'location_id' => $activation_id
		);

		$response = $this->call_api( self::EP_DEACTIVATE, self::METHOD_POST, $key, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			return true;
		}
	}

	/**
	 * Get the latest version of the plugin.
	 *
	 * @param string $key
	 *
	 * @return object|WP_Error
	 *
	 * @throws Exception
	 */
	public function get_latest_version( $key ) {

		$response = $this->call_api( self::EP_VERSION, self::METHOD_GET, $key );

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( isset( $response->list->{$this->product_id} ) ) {
			return $response->list->{$this->product_id};
		} else {
			throw new Exception( "Product ID and License Key don't match." );
		}
	}

	/**
	 * Make a call to the API.
	 *
	 * This method is suitable for client consumption,
	 * but the convenience methods provided are preferred.
	 *
	 * @param string $endpoint
	 * @param string $method
	 * @param string $key
	 * @param array  $params
	 *
	 * @return object|WP_Error Decoded JSON on success, WP_Error object on error.
	 *
	 * @throws Exception If invalid HTTP method.
	 */
	public function call_api( $endpoint, $method, $key = '', $params = array() ) {

		$args = array(
			'headers' => array()
		);
		$args = wp_parse_args( $params, $args );

		if ( $key ) {
			$args['headers']['Authorization'] = $this->generate_basic_auth( $key );
		}

		$url = $this->generate_endpoint_url( $endpoint );

		if ( $method == self::METHOD_GET ) {
			$response = wp_remote_get( $url, $args );
		} elseif ( $method == self::METHOD_POST ) {
			$response = wp_remote_post( $url, $args );
		} else {
			throw new Exception( "Invalid HTTP Method" );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );

		$json = json_decode( $response_body );

		if ( ! $json->success ) {
			return $this->response_to_error( $json );
		} else {
			return $json->body;
		}
	}

	/**
	 * Convert the JSON decoded response to an error object.
	 *
	 * @param stdClass $response
	 *
	 * @return WP_Error
	 *
	 * @throws Exception If response is not an error. To check for an error look at the 'success' property.
	 */
	protected function response_to_error( stdClass $response ) {

		if ( $response->success ) {
			throw new Exception( "Response object is not an error." );
		}

		return new WP_Error( $response->error->code, $response->error->message );
	}

	/**
	 * Generate the endpoint URl.
	 *
	 * @param string $endpoint
	 *
	 * @return string
	 */
	protected function generate_endpoint_url( $endpoint ) {

		$base = $this->store_url . 'itelic-api';

		return "$base/$endpoint/";
	}

	/**
	 * Generate a basic auth header based on the license key.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function generate_basic_auth( $key ) {
		return 'Basic ' . base64_encode( $key . ':' );
	}

}

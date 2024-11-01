<?php
/**
 * Zakeke API
 *
 * Handles API endpoint requests.
 *
 * @category API
 * @package  ZakekeConfigurator/API
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZakekeConfigurator_API {

	/**
	 * Setup class.
	 */
	public function __construct() {
        // Init REST API routes.
        add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
        add_filter( 'woocommerce_rest_is_request_to_rest_api', array( __CLASS__, 'is_request_to_rest_api' ) );
	}

	/**
	 * Init ZAKEKE REST API.
	 */
	public function rest_api_init() {
		$this->rest_api_includes();
        $this->register_rest_routes();
	}

    /**
     * Add Zakeke as REST api
     *
     * @return bool
     */
    public static function is_request_to_rest_api( $is_api_request ) {
        if ( $is_api_request ) {
            return true;
        }

        $rest_prefix = trailingslashit( rest_get_url_prefix() );

        // Check if our endpoint.
        if (false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'zakeke-configurator/' )) {
            wc_maybe_define_constant( 'REST_REQUEST', true );
            return true;
        }
        return false;
    }

	/**
	 * Include REST API classes.
	 */
	private function rest_api_includes() {
		include_once( 'api/class-zakeke-configurator-rest-settings-controller.php' );
		include_once( 'api/class-zakeke-configurator-rest-enabled-controller.php' );
	}

	/**
	 * Register REST API routes.
	 */
	private function register_rest_routes() {
		$controllers = array(
			'ZakekeConfigurator_REST_Enabled_Controller',
			'ZakekeConfigurator_REST_Settings_Controller'
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}
}

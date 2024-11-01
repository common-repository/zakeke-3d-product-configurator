<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZakekeConfigurator_Webservice {

	/**
	 * Zakeke integration settings.
	 *
	 * @var ZakekeConfigurator_Integration
	 */
	private $integration;

	/**
	 * Debug mode.
	 *
	 * @var boolean
	 */
	private $debug;

	/**
	 * Logger instance.
	 *
	 * @var WC_Logger
	 */
	private $logger;

	/**
	 * Setup class.
	 */
	public function __construct() {
		$this->integration = new ZakekeConfigurator_Integration();
		$this->logger      = new WC_Logger();
	}

	/**
	 * Performs the underlying HTTP request.
	 *
	 * @param  string $method HTTP method (GET|POST|PUT|PATCH|DELETE)
	 * @param  string $resource MailChimp API resource to be called
	 * @param  array $args array of parameters to be passed
	 * @param string $auth_token Authentication token
	 *
	 * @throws Exception
	 * @return array          array of decoded result
	 */
	private function request( $method, $resource, $args = array(), $auth_token = null ) {
		$url = ZAKEKE_CONFIGURATOR_WEBSERVICE_URL . $resource;

		global $wp_version;

		$request_args = array(
			'method'      => $method,
			'redirection' => 5,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
				'User-Agent'   => 'woocommerce-zakeke-configurator/' . ZAKEKE_CONFIGURATOR_VERSION . '; WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
			),
		);

		if ( ! is_null( $auth_token ) ) {
			$request_args['headers']['Authorization'] = 'Bearer ' . $auth_token;
		}

		// attach arguments (in body or URL)
        if ( ! empty( $args ) ) {
            if ($method === 'GET') {
                $url = $url . '?' . http_build_query($args);
            } else {
                $request_args['body'] = json_encode($args);
            }
        }

		$raw_response = wp_remote_request( $url, $request_args );

		$this->maybe_log( $url, $method, $args, $raw_response );

		if ( is_wp_error( $raw_response )
		     || ( is_array( $raw_response )
		          && $raw_response['response']['code']
		          && floor( $raw_response['response']['code'] ) / 100 >= 4 )
		) {
			throw new Exception( 'ZakekeConfigurator_Webservice::request ' . $resource . ' ' . $raw_response['response']['code'] );
		}

		$json   = wp_remote_retrieve_body( $raw_response );
		$result = json_decode( $json, true );

		return $result;
	}

	/**
	 * Get the minimal data required to get the authentication token
	 *
	 * @return array
	 */
	private function auth_minimal_data() {
		$data = array(
			'api_client_id'  => $this->integration->get_option( 'api_client_id' ),
			'api_secret_key' => $this->integration->get_option( 'api_secret_key' )
		);

		return $data;
	}

	/**
	 * Zakeke authentication token.
	 *
	 * @param array $data
	 *
	 * @throws Exception
	 * @return string
	 */
	public function auth_token( $data ) {
        global $wp_version;

        $auth_data = $this->auth_minimal_data();
		$data = array_merge( $data, array(
            'grant_type' => 'client_credentials'
        ) );

        $request_args = array(
            'method'      => 'POST',
            'headers'     => array(
                'Authorization' => 'Basic ' . base64_encode($auth_data['api_client_id'] . ':' . $auth_data['api_secret_key']),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
                'User-Agent'   => 'woocommerce-zakeke-configurator/' . ZAKEKE_CONFIGURATOR_VERSION . '; WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
            ),
            'body'        => http_build_query($data, null, '&')
        );

        $url = ZAKEKE_CONFIGURATOR_WEBSERVICE_URL . '/token';
        $raw_response = wp_remote_request( $url, $request_args );

        $this->maybe_log( $url, 'POST', $request_args, $raw_response );

        if ( is_wp_error( $raw_response )
            || ( is_array( $raw_response )
                && $raw_response['response']['code']
                && floor( $raw_response['response']['code'] ) / 100 >= 4 )
        ) {
            throw new Exception( 'ZakekeConfigurator_Webservice::auth_token '. print_r( $raw_response, true ) );
        }

        $json   = wp_remote_retrieve_body( $raw_response );
        $result = json_decode( $json, true );

        return $result['access_token'];
	}

	/**
	 * Associate the guest with a customer
	 *
	 * @param string $guest_code - Guest identifier.
	 * @param string $customer_id - Customer identifier.
	 *
	 * @throws Exception
	 * @return void
	 */
	public function associate_guest( $guest_code, $customer_id ) {
		$data = array(
            'visitorcode' => $guest_code,
            'customercode' => $customer_id
        );

		self::auth_token( $data );
	}

	/**
	 * Get the needed data for adding a product to the cart
	 *
	 * @param string $configuration Zakeke configuration identifier.
	 * @param int $qty Quantity.
	 *
	 * @throws Exception
	 * @return array
	 */
	public function cart_info( $configuration, $qty ) {
        $auth_data = array(
            'access_type' => 'S2S'
        );

        $auth_token = $this->auth_token( $auth_data );

		$resource = '/v1/compositions/' . $configuration . '/cartinfo';
		return self::request( 'GET', $resource, array(), $auth_token );
	}

	/**
	 * Order containing Zakeke customized products placed.
	 *
	 * @param array $data The data of the order.
	 *
	 * @throws Exception
	 * @return void
	 */
	public function place_order( $data ) {
        $auth_token_data = array(
		    'access_type' => 'S2S'
        );

        $user_id = get_current_user_id();
        if ( $user_id > 0 ) {
            $auth_token_data['customercode'] = (string) $user_id;
        } else {
            $auth_token_data['visitorcode'] = zakeke_configurator_guest_code();
        }

		$auth_token = $this->auth_token( $auth_token_data );

		self::request( 'POST', '/v1/order', $data, $auth_token );
	}

	/**
	 * Get the Zakeke design preview files
	 *
	 * @param string $designId Zakeke design identifier.
	 *
	 * @throws Exception
	 * @return array
	 */
	public function get_previews( $designId ) {
		$auth_token = self::auth_token( self::auth_minimal_data() );

		$data = array(
			'docid' => $designId
		);

		$json = self::request(
			'GET',
			'/api/designs/0/previewfiles',
			$data,
			$auth_token
		);

		$previews = array();
		foreach ( $json as $preview ) {
			if ( $preview['format'] == 'SVG' ) {
				continue;
			}

			$previewObj        = new stdClass();
			$previewObj->url   = $preview['url'];
			$previewObj->label = $preview['sideName'];
			$previews[]        = $previewObj;
		}

		return $previews;
	}

	/**
	 * Get the Zakeke design output zip
	 *
	 * @param string $designId Zakeke design identifier.
	 *
	 * @throws Exception
	 * @return string
	 */
	public function get_zakeke_output_zip( $designId ) {
		$auth_token = self::auth_token( self::auth_minimal_data() );

		$data = array(
			'docid' => $designId
		);
		$json = self::request( 'GET', '/api/designs/0/outputfiles/zip', $data,
			$auth_token );

		return $json['url'];
	}

	/**
	 * Conditionally log Zakeke Webservice Call
	 *
	 * @param  string $url Zakeke url.
	 * @param  string $method HTTP Method.
	 * @param  array $args HTTP Request Body.
	 * @param  array $response WP HTTP Response.
	 *
	 * @return void
	 */
	private function maybe_log( $url, $method, $args, $response ) {
		if ( ! $this->debug ) {
			return;
		}

		$this->logger->add( 'zakeke-configurator', "Zakeke Webservice Call URL: $url \n METHOD: $method \n BODY: " . print_r( $args,
				true ) . ' \n RESPONSE: ' . print_r( $response, true ) );
	}
}
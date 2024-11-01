<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ZakekeConfigurator_Integration' ) ) :

	/**
	 * Zakeke_Integration Class.
	 */
	class ZakekeConfigurator_Integration extends WC_Integration {

		/**
		 * Zakeke Integration Constructor.
		 */
		public function __construct() {
			$this->id                 = 'zakeke-configurator';
			$this->method_title       = __( 'Zakeke 3D Product Configurator', 'zakeke-configurator' );
            $this->method_description = __( 'Integrate Zakeke into WooCommerce. You can get your Zakeke API info by accessing the "Integration" section of the Zakeke configurator back office.',
                'zakeke-configurator' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->api_client_id       = $this->get_option( 'api_client_id' );
			$this->api_secret_key      = $this->get_option( 'api_secret_key' );
			$this->force_customization = $this->get_option( 'force_customization' );
            $this->hide_price          = $this->get_option( 'hide_price' );
			$this->debug               = $this->get_option( 'debug' );

			// Actions.
			add_action( 'woocommerce_update_options_integration_' . $this->id,
				array( $this, 'process_admin_options' ) );
		}

		/**
		 * Initialize integration settings form fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'api_client_id'   => array(
					'title'       => __( 'Zakeke API client id', 'zakeke-configurator' ),
					'type'        => 'text',
					'description' => __( 'Your Zakeke API client id.',
                        'zakeke-configurator' )
				),
				'api_secret_key'  => array(
					'title'       => __( 'Zakeke API secret key', 'zakeke-configurator' ),
					'type'        => 'password',
					'description' => __( 'Your Zakeke API secret key.',
                        'zakeke-configurator' )
				),
				'force_customization' => array(
					'title'       => __( 'Force product configuration', 'zakeke-configurator' ),
					'type'        => 'checkbox',
					'default'     => 'yes',
					'description' => __( 'Replace the "Add to cart" button with the "Configure" button for configurable products.',
						'zakeke-configurator' )
				),
                'hide_price' => array(
                    'title'       => __( 'Hide price inside the configurator', 'zakeke-configurator' ),
                    'type'        => 'checkbox',
                    'default'     => 'no',
                    'description' => __( 'When checked, Zakeke will not show the price in the configurator.',
                        'zakeke-configurator' )
                ),
				'debug'               => array(
					'title'       => __( 'Debug Log', 'zakeke-configurator' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable logging', 'zakeke-configurator' ),
					'default'     => 'no',
					'description' => __( 'Log events such as API requests.',
                        'zakeke-configurator' ),
				)
			);
		}
	}

endif;
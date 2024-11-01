<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ZakekeConfigurator_AJAX {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
        add_action( 'wc_ajax_zakeke_configurator_get_auth', array( __CLASS__, 'get_auth' ) );
		add_action( 'wc_ajax_zakeke_configurator_get_price', array( __CLASS__, 'get_price' ) );
	}

    /**
     * Get a matching variation price based on posted attributes.
     */
    public static function get_auth() {
        ob_start();

        $webservice = new ZakekeConfigurator_Webservice();

        $auth_token_data = array();

        $user_id = get_current_user_id();
        if ( $user_id > 0 ) {
            $auth_token_data['customercode'] = (string) $user_id;
        } else {
            $auth_token_data['visitorcode'] = zakeke_configurator_guest_code();
        }

        try {
            $zakeke_auth_token = $webservice->auth_token( $auth_token_data );
            wp_send_json( array(
                'token'      => $zakeke_auth_token
            ) );
        } catch (Exception $e) {
            wp_send_json( array(
                'error' => $e->getMessage()
            ) );
        }

        die();
    }

	/**
	 * Get a matching variation price based on posted attributes.
	 */
	public static function get_price() {
		ob_start();

		if ( ! empty( $_POST['product_id'] ) ) {
			$product_id = $_POST['product_id'];
		} else {
			$product_id = $_POST['add-to-cart'];
		}

		// Sanitize
		$qty = wc_stock_amount( preg_replace( '/[^0-9\.]/', '',  $_POST['quantity'] ) );
		if ($qty <= 0) {
			$qty = 1;
		}

		if ( ! ( $product = wc_get_product( absint( $product_id ) ) ) ) {
			die();
		}

		if ( $product->is_type( 'variable' ) ) {
			/** @var WC_Product_Data_Store_Interface $data_store */
            $data_store = WC_Data_Store::load( 'product' );
            $variation_id = $data_store->find_matching_product_variation( $product, wp_unslash( $_POST ) );
			if ( $variation_id ) {
			    $product = wc_get_product( $variation_id );
            }
		}

        do_action( 'zakeke_configurator_before_ajax_price', $product, $qty );

        $integration = new ZakekeConfigurator_Integration();
        $hide_price = $integration->hide_price;

        $original_price = 0.0;
        $zakeke_price = 0.0;

		if ($hide_price !== 'yes') {
		    $original_price = (float) wc_get_price_to_display( $product );

            if (isset($_POST['zakeke_price']) && $_POST['zakeke_price'] > 0.0) {
                $zakeke_price += (float)$_POST['zakeke_price'];
            }

            $zakeke_final_price = (float) wc_get_price_to_display( $product, array( 'price' => $zakeke_price ) );
        }

		wp_send_json( array(
            'is_purchasable'      => $product->is_purchasable(),
            'is_in_stock'         => $product->is_in_stock(),
            'price_including_tax' => $original_price + $zakeke_final_price
        ) );

		die();
	}
}

ZakekeConfigurator_AJAX::init();

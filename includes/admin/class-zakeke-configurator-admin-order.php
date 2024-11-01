<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ZakekeConfigurator_Admin_Order {

	/**
	 * Setup class.
	 */
	public static function init() {
		add_filter( 'woocommerce_admin_order_item_thumbnail', array( __CLASS__, 'change_cart_item_thumbnail' ), 10, 3 );
	}

	public static function change_cart_item_thumbnail( $image, $item_id, $item ) {
		if ( $zakeke_data = $item->get_meta( 'zakeke_configurator_data' ) ) {
			$image = '<img src="' . esc_url( $zakeke_data['preview'] ) . '" class="attachment-thumbnail size-thumbnail wp-post-image" alt="" title="" width="150" height="150">';
		}

		return $image;
	}
}

ZakekeConfigurator_Admin_Order::init();

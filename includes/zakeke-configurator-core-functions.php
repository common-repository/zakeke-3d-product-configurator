<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether the product is customizable.
 *
 * @param int $product_id
 *
 * @return bool Whether the product is customizable.
 */
function zakeke_is_configurable( $product_id ) {
	$zakeke_enabled = get_post_meta( $product_id, 'zakeke_configurator_enabled', 'no' );

	return 'yes' === $zakeke_enabled;
}

/**
 * Get the Zakeke guest identifier using cookies.
 *
 * @return string
 */
function zakeke_configurator_guest_code() {
    if ( isset( $_COOKIE['zakeke-guest'] ) ) {
        return $_COOKIE['zakeke-guest'];
    }

    $value = wp_generate_password( 32, false );
    /**Ten years */
    $period = 315360000;
    wc_setcookie( 'zakeke-guest', $value, time() + $period, is_ssl() );

    return $value;
}

/**
 * Calculate Zakeke price.
 *
 * @param float $price
 * @param float $zakekePrice
 * @param int $qty
 *
 * @return float
 */
function zakeke_configurator_calculate_price($price, $zakekePrice, $qty) {
	return $zakekePrice;
}

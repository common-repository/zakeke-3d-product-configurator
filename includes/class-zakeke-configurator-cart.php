<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class ZakekeConfigurator_Cart {

	/**
	 * Setup class.
	 */
	public static function init() {
		add_filter( 'woocommerce_add_cart_item', array( __CLASS__, 'add_cart_item' ), 10 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_thumbnail', array( __CLASS__, 'change_cart_item_thumbnail' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'item_meta_display' ), 20, 2 );
		add_filter( 'woocommerce_update_cart_action_cart_updated', array( __CLASS__, 'cart_updated' ) );
	}

	/**
	 * Check if the product added to cart is customized
	 *
	 * @return bool
	 */
	private static function is_zakeke_product() {
		return ( isset( $_POST['zakeke_configuration'] ) && ( $_POST['zakeke_configuration'] !== 'new' && ! empty( $_POST['zakeke_configuration'] ) ) );
	}

	public static function add_cart_item( $cart_item ) {
        $integration = new ZakekeConfigurator_Integration();

		if ( isset( $cart_item['zakeke_configurator_data'] ) && $integration->hide_price !== 'yes' ) {
			$zakeke_data = $cart_item['zakeke_configurator_data'];

			$product = $cart_item['data'];

			$final_price = $zakeke_data['price'];
			$product->set_price( $product->get_price() + $final_price );
		}

		return $cart_item;
	}

	public static function add_cart_item_data( $cart_item_meta, $product_id ) {
		if ( self::is_zakeke_product() ) {
			$webservice = new ZakekeConfigurator_Webservice();

			// Sanitize
			$qty = wc_stock_amount( preg_replace( '/[^0-9\.]/', '',  $_POST['quantity'] ) );
			if ($qty <= 0) {
				$qty = 1;
			}

			$zakeke_cart_data = $webservice->cart_info( $_POST['zakeke_configuration'], $qty );

			if ( ! empty( $_POST['product_id'] ) ) {
				$product_id = $_POST['product_id'];
			} else {
				$product_id = $_POST['add-to-cart'];
			}

			$product        = wc_get_product( absint( $product_id ) );
			$original_price = (float) $product->get_price();

			$zakeke_price = zakeke_configurator_calculate_price( $original_price, $zakeke_cart_data['price'], $qty );

			if ( get_option( 'woocommerce_tax_display_shop' ) === 'excl' ) {
				$zakeke_tax_price = (float) wc_get_price_excluding_tax( $product, array( 'price' => $zakeke_price ) );
			} else {
				$zakeke_tax_price = (float) wc_get_price_including_tax( $product, array( 'price' => $zakeke_price ) );
			}

			$zakeke_excl_tax_price = (float) wc_get_price_excluding_tax( $product, array( 'price' => $zakeke_price ) );

			$original_final_excl_tax_price = (float)  wc_get_price_excluding_tax( $product );

			$cart_item_meta['zakeke_configurator_data'] = array(
				'composition'                   => $_POST['zakeke_configuration'],
				'preview'                       => $zakeke_cart_data['preview'],
				'price'                         => $zakeke_price,
				'price_tax'                     => $zakeke_tax_price,
				'price_excl_tax'                => $zakeke_excl_tax_price,
				'original_final_price'          => $original_price,
				'original_final_excl_tax_price' => $original_final_excl_tax_price,
                'items'                         => wp_json_encode( $zakeke_cart_data['items'], JSON_HEX_QUOT )
			);
		}

		return $cart_item_meta;
	}

	public static function change_cart_item_thumbnail( $thumbnail, $cart_item = null ) {
		if ( ! is_null( $cart_item ) && isset( $cart_item['zakeke_configurator_data'] ) ) {
			$zakeke_data = $cart_item['zakeke_configurator_data'];
			$preview    = $zakeke_data['preview'];

            $dom = new DOMDocument;
            libxml_use_internal_errors( true );
            $dom->loadHTML( $thumbnail );
            $xpath = new DOMXPath( $dom );
            libxml_clear_errors();
            $doc    = $dom->getElementsByTagName( 'img' )->item( 0 );
            $src    = $xpath->query( './/@src' );
            $srcset = $xpath->query( './/@srcset' );

            foreach ( $src as $s ) {
                $s->nodeValue = $preview;
            }

            foreach ( $srcset as $s ) {
                $s->nodeValue = $preview;
            }

            $doc->setAttribute( 'data-src', $preview );
            $doc->setAttribute( 'data-srcset', $preview );

            return $dom->saveXML( $doc );
		}

		return $thumbnail;
	}

	public static function get_cart_item_from_session( $cart_item, $values ) {
		if ( isset( $values['zakeke_configurator_data'] ) ) {
			$cart_item['zakeke_configurator_data'] = $values['zakeke_configurator_data'];
		}

		if ( isset( $cart_item['zakeke_configurator_data'] ) ) {
			self::add_cart_item( $cart_item );
		}

		return $cart_item;
	}

	public static function item_meta_display( $item_data, $cart_item ) {
		if ( isset( $cart_item['zakeke_configurator_data'] ) ) {
			$zakeke_data = $cart_item['zakeke_configurator_data'];

			$items = json_decode( $zakeke_data['items'], true );

			foreach ($items as $item) {
			    if ( strpos ( $item['attributeCode'], 'zakekePlatform' ) !== false ) {
			        continue;
                }

                $item_data[] = array(
                    'key'   => $item['attributeName'],
                    'value' => $item['selectedOptionName']
                );
            }
		}

		return $item_data;
	}

	public static function cart_updated( $cart ) {
		$webservice = new ZakekeConfigurator_Webservice();

		$cart_totals = isset( $_POST['cart'] ) ? $_POST['cart'] : '';

        $integration = new ZakekeConfigurator_Integration();

		if ( ! WC()->cart->is_empty() && is_array( $cart_totals ) && $integration->hide_price !== 'yes' ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

				// Skip product if no updated quantity was posted
				if ( ! isset( $cart_totals[ $cart_item_key ] ) || ! isset( $cart_totals[ $cart_item_key ]['qty'] )
				     || ! isset( $values['zakeke_configurator_data'] )
				) {
					continue;
				}

				$zakeke_data = $values['zakeke_configurator_data'];

				$cart_item_data = &WC()->cart->cart_contents[ $cart_item_key ];

				$qty = (int) $cart_totals[ $cart_item_key ]['qty'];
				if ( $qty <= 0 ) {
					$qty = 1;
				}

				$zakeke_cart_data = $webservice->cart_info( $zakeke_data['composition'], $qty );

                $cart_item_data['zakeke_configurator_data']['preview'] = $zakeke_cart_data['preview'];
				$cart_item_data['zakeke_configurator_data']['price']   = $zakeke_cart_data['price'];
                $cart_item_data['zakeke_configurator_data']['items']   = wp_json_encode( $zakeke_cart_data['items'], JSON_HEX_QUOT );

				$original_price = $zakeke_data['original_final_price'];

				$zakeke_price = zakeke_configurator_calculate_price(
					$original_price,
					$cart_item_data['zakeke_configurator_data']['price'],
					$qty
				);

				/** @var WC_Product $product */
				$product = $values['data'];

                if ( get_option( 'woocommerce_tax_display_shop' ) === 'excl' ) {
                    $zakeke_tax_price = (float) wc_get_price_excluding_tax( $product, array( 'price' => $zakeke_price ) );
                } else {
                    $zakeke_tax_price = (float) wc_get_price_including_tax( $product, array( 'price' => $zakeke_price ) );
                }

                $zakeke_excl_tax_price = (float) wc_get_price_excluding_tax( $product, array( 'price' => $zakeke_price ) );

				$cart_item_data['zakeke_configurator_data']['price']          = $zakeke_price;
				$cart_item_data['zakeke_configurator_data']['price_tax']      = $zakeke_tax_price;
				$cart_item_data['zakeke_configurator_data']['price_excl_tax'] = $zakeke_excl_tax_price;

				$product->set_price( $original_price + $zakeke_price );
			}
		}

		return $cart;
	}
}

ZakekeConfigurator_Cart::init();

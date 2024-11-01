<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ZakekeConfigurator_Designer Class.
 */
class ZakekeConfigurator_Designer {

	/**
	 * Setup class.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 20 );
        add_shortcode( 'zakeke_configurator', __CLASS__ . '::output' );
	}

	public static function enqueue_scripts() {
	    wp_register_style( 'zakeke-configurator', get_zakeke_configurator()->plugin_url() . '/assets/css/frontend/configurator.css',
            array(),  ZAKEKE_CONFIGURATOR_VERSION );

		wp_register_script( 'zakeke-configurator', get_zakeke_configurator()->plugin_url() . '/assets/js/frontend/configurator.js',
			array( 'jquery' ), ZAKEKE_CONFIGURATOR_VERSION );

		wp_enqueue_style( 'zakeke-configurator' );
		wp_enqueue_script( 'zakeke-configurator' );
	}

    /**
     * Get the default parameters for the shortcode starting from a product
     *
     * @param WC_Product $product
     * @return array
     */
    private static function default_parameters( $product ) {
        $quantity = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );

        $data = array(
            'authAjaxUrl'   => WC_AJAX::get_endpoint( 'zakeke_configurator_get_auth' ),
            'priceAjaxUrl'  => WC_AJAX::get_endpoint( 'zakeke_configurator_get_price' ),
            'zakekeUrl'     => ZAKEKE_CONFIGURATOR_BASE_URL,
            'modelCode'     => (string) $product->get_id(),
            'name'          => $product->get_title(),
            'qty'           => $quantity,
            'currency'      => get_woocommerce_currency(),
            'culture'       => str_replace( '_', '-', get_locale() ),
            'ecommerce'     => 'woocommerce',
            'attributes'     => array()
        );

        if( $product->is_type('variable') ){
            $attributes = $product->get_attributes();
            foreach ( $attributes as $attribute_slug => $attribute ) {
                if ( ! $attribute['data']['variation'] ) {
                    continue;
                }

                $terms = wc_get_product_terms( $product->get_id(), $attribute_slug, array(
                    'fields' => 'all'
                ) );

                foreach ( $terms as $term ) {
                    if ( $term->term_id === $attribute['data']['options'][0] ) {
                        $data['attributes'][ $attribute_slug ] = $term->slug;
                        break;
                    }
                }
            }
        }

        $default_attributes = $product->get_default_attributes();
        if ($default_attributes) {
            foreach ($default_attributes as $attribute_slug => $attribute) {
                $data['attributes'][ $attribute_slug ] = $attribute;
            }
        }

        foreach ( $_REQUEST as $key => $value ) {
            $prefix = substr( $key, 0, 10 );
            if ( 'attribute_' === $prefix ) {
                $short_key = substr( $key, 10 );
                $data['attributes'][ $short_key ] = $value;
            } else {
                $data['request'][ $key ] = $value;
            }
        }

        $zakekeOption = $_REQUEST['zakeke_configuration'];
        if ( 'new' !== $zakekeOption ) {
            $data['compositionId'] = $zakekeOption;
        }

        return $data;
    }

	/**
	 * Load the Zakeke configurator template.
	 *
	 * @return string
	 */
	public static function template_loader() {
		$file     = 'zakeke-configurator.php';
		$template = locate_template( $file );
		if ( ! $template ) {
			$template = get_zakeke_configurator()->plugin_path() . '/templates/' . $file;
		}

		return $template;
	}

	public static function output ( $atts = [] ) {
	    $product = wc_get_product();

	    if ( ! isset( $atts['product_id'] ) && ! $product ) {
            echo '<-- Zakeke Configurator: product_id parameter not set --!>';
            return;
        }

        if ( ! $product ) {
            $product = wc_get_product( intval( $atts['product_id'] ) );
        } else {
            $atts['product_id'] = $product->get_id();
        }

        $atts['modelCode'] = $atts['product_id'];

        $final_atts = shortcode_atts(
            self::default_parameters( $product ),
            $atts,
            'zakeke_configurator'
        );

	    include self::template_loader();
    }
}

ZakekeConfigurator_Designer::init();

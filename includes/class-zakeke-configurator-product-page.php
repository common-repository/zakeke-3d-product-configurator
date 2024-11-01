<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ZakekeConfigurator_Designer Class.
 */
class ZakekeConfigurator_ProductPage {

	/**
	 * Setup class.
	 */
	public static function init() {
        add_filter( 'post_class', array( __CLASS__, 'post_class' ), 20, 3 );

        add_filter( 'woocommerce_product_supports', array( __CLASS__, 'no_ajax_add_to_cart' ), 30, 3 );

        add_filter( 'woocommerce_loop_add_to_cart_args', array( __CLASS__, 'add_zakeke_class' ), 20, 2 );

        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

        add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'add_to_cart_text' ), 20, 2 );
        add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'single_add_to_cart_text' ),
            20, 2 );
        add_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'add_to_cart_url' ), 30, 2 );

        add_action( 'woocommerce_before_single_product', array( __CLASS__, 'product_page' ) );

		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'add_customize_input' ) );

        if ( ! self::should_show_configurator() ) {
            return;
        }

        remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 );

        //add_action( 'wp', array( __CLASS__, 'authorization' ), 20 );
        add_filter( 'template_include', array( __CLASS__, 'template_loader' ), 1100 );
	}

    private static function should_show_configurator() {
        return ( ! empty( $_REQUEST['zakeke_configuration'] ) && 'new' === $_REQUEST['zakeke_configuration'] );
    }

    private static function has_force_customization () {
	    static $force_customization = null;

	    if ( is_null( $force_customization ) ) {
            $integration = new ZakekeConfigurator_Integration();
            $force_customization = $integration->force_customization;
        }

        return $force_customization === 'yes';
    }

    public static function post_class( $classes, $class = '', $post_id = '' ) {
        if ( ! ( $post_id && zakeke_is_configurable( $post_id ) ) ) {
            return $classes;
        }

        $classes[] = 'product-type-zakeke';
        $classes[] = 'product-type-external';

        return $classes;
    }

	public static function no_ajax_add_to_cart( $enabled, $feature, $product ) {
		if ( 'ajax_add_to_cart' == $feature && zakeke_is_configurable( $product->get_id() ) ) {
			$enabled = false;
		}

		return $enabled;
	}

    public static function add_zakeke_class( $args, $product ) {
        if ( zakeke_is_configurable( $product->get_id() ) ) {
           $args['class'] = $args['class'] . ' product-type-zakeke';
        }

        return $args;
    }

	public static function enqueue_scripts() {
		wp_register_script( 'zakeke-configurator-product-page', get_zakeke_configurator()->plugin_url() . '/assets/js/frontend/product-page.js',
			array(), ZAKEKE_CONFIGURATOR_VERSION, false );
        wp_enqueue_script( 'zakeke-configurator-product-page' );
	}

	public static function product_page() {
	    /** @var WC_Product $product */
		global $product;

		if ( self::has_force_customization() ) {
			return;
		}

		if ( zakeke_is_configurable( $product->get_id() ) ) {
            add_action('woocommerce_before_add_to_cart_button', array(__CLASS__, 'add_customize_button'), 25);
        }
	}

	public static function add_customize_button() {
		?>
        <div class="group">
            <button class="zakeke-configurator-customize-button button" type="button" class=" button "><?php _e( 'Configure',
                    'zakeke-configurator' ) ?></button>
        </div>
		<?php
	}

	public static function add_customize_input() {
        /** @var WC_Product $product */
		global $product;

		if ( ! zakeke_is_configurable( $product->get_id() ) ) {
			return;
		}

        ?>
            <input type="hidden" name="yith-wacp-is-excluded" value="yes"/>
        <?php

		if ( self::has_force_customization() ) {
			?>
            <input type="hidden" name="zakeke_configuration" value="new" />
			<?php
		} else {
			?>
            <input type="hidden" name="zakeke_configuration" />
			<?php
		}
	}

    /**
     * @param string $text
     * @param WC_Product $product
     *
     * @return string
     */
	public static function single_add_to_cart_text( $text, $product ) {
		if ( self::has_force_customization() && zakeke_is_configurable( $product->get_id() ) ) {
			$text = __( 'Configure', 'zakeke-configurator' );
		}

		return $text;
	}

    /**
     * @param string $text
     * @param WC_Product $product
     *
     * @return string
     */
	public static function add_to_cart_text( $text, $product ) {
		if ( 'simple' === $product->get_type() && self::has_force_customization() && zakeke_is_configurable( $product->get_id() ) ) {
			$text = __( 'Configure', 'zakeke-configurator' );
		}

		return $text;
	}

    /**
     * @param string $url
     * @param WC_Product $product
     *
     * @return string
     */
	public static function add_to_cart_url( $url, $product ) {
		$product_id = $product->get_id();
		if ( 'simple' === $product->get_type() && self::has_force_customization() && zakeke_is_configurable( $product_id ) ) {
			$url = get_permalink( $product_id ) . '?zakeke_configuration=new&add-to-cart=' . $product->get_id();
		}

		return $url;
	}

    /**
     * Load the Zakeke configurator template.
     *
     * @param mixed $template
     *
     * @return string
     */
    public static function template_loader( $template ) {
        $file     = 'zakeke-configurator-product-page.php';
        $template = locate_template( $file );
        if ( ! $template ) {
            $template = get_zakeke_configurator()->plugin_path() . '/templates/' . $file;
        }

        return $template;
    }
}

ZakekeConfigurator_ProductPage::init();

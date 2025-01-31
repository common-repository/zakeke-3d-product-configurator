<?php
/**
 * Plugin Name: Zakeke 3D Product Configurator
 * Plugin URI: https://www.zakeke.com/
 * Description: Innovative platform to let your customers to customize products in your e-store. Multi-language, multi-currency, 3D view and print-ready outputs.
 * Version: 1.0.8
 * Author: Zakeke
 * Author URI: https://www.zakeke.com
 * Requires at least: 4.7
 * Tested up to: 5.2
 * WC requires at least: 3.1
 * WC tested up to: 3.7
 *
 * Text Domain: zakeke-configurator
 * Domain Path: /i18n/languages/
 *
 * @package ZakekeConfigurator
 * @author Zakeke
 *
 * License: GPL2+
 *
 * Zakeke 3D Product Configurator is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Zakeke 3D Product Configurator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Zakeke Interactive Product Configurator. If not, see write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ZakekeConfigurator' ) ) :

	/**
	 * Main Zakeke Class.
	 */
	final class ZakekeConfigurator {

		const CAPABILITY = "edit_zakeke";

		/**
		 * Zakeke version.
		 *
		 * @var string
		 */
		public $version = '1.0.8';

		/**
		 * The Zakeke integration settings.
		 *
		 * @var ZakekeConfigurator_API
		 */
		private $api;

		/**
		 * The single instance of the class.
		 *
		 * @var ZakekeConfigurator
		 */
		protected static $_instance = null;

		/**
		 * Main Zakeke Instance.
		 *
		 * Ensures only one instance of Zakeke is loaded or can be loaded.
		 *
		 * @static
		 * @see get_zakeke_configurator()
		 * @return ZakekeConfigurator - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'zakeke-configurator' ), '1.0' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'zakeke-configurator' ), '1.0' );
		}

		public function construct() {
            $this->define_constants();

            $this->load_plugin_textdomain();

            if ( self::woocommerce_did_load() ) {
                $this->includes();
                $this->init_hooks();
            }

            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

            register_activation_hook( __FILE__, array( $this, 'activate' ) );
        }

		/**
		 * Zakeke Constructor.
		 */
		public function __construct() {
		    if ( self::woocommerce_did_load() ) {
		        $this->construct();
            } else {
                add_action( 'plugins_loaded' , array( $this, 'construct' ) );
            }
		}

        /**
         * Initialize Zakeke for the first run.
         */
		public function activate() {
            GLOBAL $wp_rewrite;
            add_option('zakeke_configurator_do_activation_redirect', true);
            $wp_rewrite->flush_rules(false);
        }

		private static function woocommerce_did_load() {
		    return function_exists( 'WC' ) && version_compare( WC()->version, 3.0, '>=' );
        }

		/**
		 * Define constant if not already set.
		 *
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Define Zakeke Constants.
		 */
		private function define_constants() {
			$this->define( 'ZAKEKE_CONFIGURATOR_PLUGIN_FILE', __FILE__ );
			$this->define( 'ZAKEKE_CONFIGURATOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'ZAKEKE_CONFIGURATOR_VERSION', $this->version );
            $this->define( 'ZAKEKE_CONFIGURATOR_BASE_URL', 'https://portal.zakeke.com' );
            $this->define( 'ZAKEKE_CONFIGURATOR_WEBSERVICE_URL', 'https://api.zakeke.com' );
		}

		/**
		 * What type of request is this?
		 *
		 * @param  string $type admin, ajax, cron or frontend.
		 *
		 * @return bool
		 */
		private function is_request( $type ) {
			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}

		/**
		 * Include required frontend files.
		 */
		public function frontend_includes() {
			include_once( 'includes/class-zakeke-configurator-ajax.php' );
			include_once( 'includes/class-zakeke-configurator-product-page.php' );
			include_once( 'includes/class-zakeke-configurator-designer.php' );
			include_once( 'includes/class-zakeke-configurator-cart.php' );
			include_once( 'includes/class-zakeke-configurator-guest.php' );

			include_once( 'includes/support/class-configurator-dynamic-pricing-and-discounts-for-woocommerce.php' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			include_once( 'includes/zakeke-configurator-core-functions.php' );
			include_once( 'includes/class-zakeke-configurator-integration.php' );
			include_once( 'includes/class-zakeke-configurator-api.php' );
			include_once( 'includes/class-zakeke-configurator-install.php' );
			include_once( 'includes/class-zakeke-configurator-webservice.php' );
            include_once( 'includes/class-zakeke-configurator-order.php' );

			if ( $this->is_request( 'admin' ) ) {
                include_once( 'includes/admin/class-zakeke-configurator-admin-get-started.php' );
				include_once( 'includes/admin/class-zakeke-configurator-admin-order.php' );
			}

			if ( $this->is_request( 'frontend' ) ) {
				$this->frontend_includes();
			}

			$this->api = new ZakekeConfigurator_API();
		}

        /**
         * Don't duplicate Zakeke metadata
         *
         * @param WC_Product $duplicate
         * @param WC_Product $product
         */
		public function product_duplicate( $duplicate, $product ) {
		    $duplicate->delete_meta_data( 'zakeke_configurator_enabled' );
        }

		/**
		 * Hook into actions and filters.
		 */
		private function init_hooks() {
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

			if ( is_admin() ) {
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_links' ) );
			}

			add_action( 'woocommerce_product_duplicate_before_save', array( $this, 'product_duplicate' ), 10, 2 );
		}

		/**
		 * Show row meta on the plugin screen.
		 *
		 * @param    mixed $links Plugin Row Meta
		 * @param    mixed $file Plugin Base file
		 *
		 * @return    array
		 */
		public static function plugin_row_meta( $links, $file ) {
			if ( plugin_basename( __FILE__ ) == $file ) {
                $row_meta = array();

                $row_meta['docs'] = '<a href="https://zakeke.zendesk.com/hc/en-us/articles/360013104193-Install-Instructions" target="_blank" aria-label="' . esc_attr__( 'View Zakeke documentation',
                        'zakeke-configurator' ) . '">' . esc_html__( 'Documentation', 'zakeke-configurator' ) . '</a>';

                $row_meta['register'] = '<a href="https://portal.zakeke.com/en-US/Admin/Register?environment=Composer" target="_blank" aria-label="' . esc_attr__( 'Register to Zakeke',
                        'zakeke-configurator' ) . '">' . esc_html__( 'Register to Zakeke to use the plugin', 'zakeke-configurator' ) . '</a>';

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		/**
		 * Add Documentation and Register to Zakeke links
		 *
		 * @param array $links
		 *
		 * @return array
		 */
		public function add_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . esc_url( $this->settings_url() ) . '" aria-label="' . esc_attr__( 'View Zakeke settings',
						'zakeke-configurator' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>'
			);

			return array_merge( $action_links, $links );
		}

		/**
		 * Get the plugin path.
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Get the plugin url.
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the settings url.
		 * @return string
		 */
		public function settings_url() {
			$settings_url = admin_url( 'admin.php?page=wc-settings&tab=integration&section=zakeke-configurator' );

			return $settings_url;
		}

		/**
		 * Load Localization files.
		 */
		private function load_plugin_textdomain() {
			load_plugin_textdomain( 'zakeke-configurator', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
		}

		/**
		 * Add the Zakeke settings tab to WooCommerce
		 */
		function add_integration( $integrations ) {
			$integrations[] = 'ZakekeConfigurator_Integration';

			return $integrations;
		}
	}

endif;

/**
 * Main instance of Zakeke.
 *
 * Returns the main instance of Zakeke to prevent the need to use globals.
 *
 * @return ZakekeConfigurator
 */
function get_zakeke_configurator() {
	return ZakekeConfigurator::instance();
}

$GLOBALS['zakeke-configurator'] = get_zakeke_configurator();

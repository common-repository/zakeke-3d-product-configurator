<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zakeke_Install Class.
 */
class ZakekeConfigurator_Install {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Install Zakeke.
	 */
	public static function install() {
		self::create_capabilities();

		self::update_zakeke_version();
	}

	/**
	 * Create Zakeke capabilities.
	 */
	public static function create_capabilities() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$wp_roles->add_cap( 'shop_manager', ZakekeConfigurator::CAPABILITY );
		$wp_roles->add_cap( 'administrator', ZakekeConfigurator::CAPABILITY );
	}

	/**
	 * Remove Zakeke capability.
	 */
	public static function remove_capabilities() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$wp_roles->remove_cap( 'shop_manager', ZakekeConfigurator::CAPABILITY );
		$wp_roles->remove_cap( 'administrator', ZakekeConfigurator::CAPABILITY );
	}


	/**
	 * Check Zakeke version and run the updater is required.
	 *
	 * This check is done on all requests and runs if he versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'zakeke_configurator_version' ) !== get_zakeke_configurator()->version ) {
			self::install();
		}
	}

	/**
	 * Update Zakeke version to current.
	 */
	private static function update_zakeke_version() {
		delete_option( 'zakeke_configurator_version' );
		add_option( 'zakeke_configurator_version', get_zakeke_configurator()->version );
	}
}

ZakekeConfigurator_Install::init();
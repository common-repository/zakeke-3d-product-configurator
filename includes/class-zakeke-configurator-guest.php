<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ZakekeConfigurator_Guest Class.
 */
class ZakekeConfigurator_Guest {

	/**
	 * Setup class.
	 */
	public static function init() {
		add_action( 'wp_login', array( __CLASS__, 'login' ), 20, 2 );
		add_action( 'wp_logout', array( __CLASS__, 'logout' ) );
	}

	public static function login( $user_login, $user ) {
		if ( isset( $_COOKIE['zakeke-guest'] ) ) {
			$webservice = new ZakekeConfigurator_Webservice();

			$user_id    = $user->ID;
			$guest_code = $_COOKIE['zakeke-guest'];
			try {
				$webservice->associate_guest( $guest_code, $user_id );
			} catch ( Exception $e ) {

			}
		}
	}

	public static function logout() {
		if ( isset( $_COOKIE['zakeke-guest'] ) ) {
			setcookie( 'zakeke-guest', '', time() - 3600, '/', COOKIE_DOMAIN, is_ssl(), false );
		}
	}

}

ZakekeConfigurator_Guest::init();

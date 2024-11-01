<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ZakekeConfigurator_Admin_Get_Started {

    /**
     * Setup class.
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'redirect' ) );
        add_action( 'admin_menu', array( __CLASS__, 'add_get_started_submenu' ) );
    }

    public static function activate() {
        GLOBAL $wp_rewrite;
        add_option('zakeke_configurator_do_activation_redirect', true);
        $wp_rewrite->flush_rules(false);
    }

    /**
     * Redirects the plugin to the about page after the activation
     */
    public static function redirect() {
        if (get_option('zakeke_configurator_do_activation_redirect', false)) {
            delete_option('zakeke_configurator_do_activation_redirect');
            wp_redirect(admin_url('admin.php?page=zakeke-configurator-about'));
        }
    }

    /**
     * Builds all the plugin menu and submenu
     */
    public static function add_get_started_submenu() {
        add_submenu_page(null, __('Get Started', 'zakeke-configurator'), __('Get Started', 'zakeke-configurator'), 'manage_product_terms', 'zakeke-configurator-about', array( __CLASS__, 'get_about_page' ) );
    }

    /**
     * Builds the about page
     */
    public static function get_about_page() {
        ?>
        <div id='zakeke-about-page'>
            <div class="wrap">
                <div id="features-wrap">
                    <h2 class="feature-h2"><?php _e('Getting Started', 'zakeke-configurator'); ?></h2>
                        <div style="background-color: #fff; text-align: center; padding: 20px">
                        <div>
                            <img class="vc_single_image-img" src="https://ps.w.org/zakeke-interactive-product-designer/assets/icon-128x128.jpg">
                            <p><a class="button" target="_blank" href="https://zakeke.zendesk.com/hc/en-us/articles/360013104193-Install-Instructions"><?php _e('START USING ZAKEKE', 'zakeke-configurator'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

ZakekeConfigurator_Admin_Get_Started::init();

<?php
/**
 * MagePress functions and definitions.
 *
 * @package Mag_Products_Integration
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/*
Plugin Name: Mag Products Integration for WordPress
Plugin URI: https://wordpress.org/plugins/mag-products-integration/
Description: This plugin let you display products of your Magento store, directly in your WordPress. It connects to Magento through the REST API.
Version: 1.2.12
Requires at least: 4.0
Author: Francis Santerre
Author URI: http://santerref.com/
Domain Path: /languages
Text Domain: mag-products-integration
*/

define( 'MAG_PRODUCTS_INTEGRATION_PLUGIN_VERSION', '1.2.12' );
define( 'MAG_PRODUCTS_INTEGRATION_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAG_PRODUCTS_INTEGRATION_MODULE_VERIFY_INSTALLATION_PATH', '/wordpress/plugin/verify' );
define( 'MAG_PRODUCTS_INTEGRATION_MODULE_STORES_PATH', '/wordpress/plugin/stores' );

require_once 'autoload.php';

if ( ! function_exists( 'magepress' ) ) {
	/**
	 * Create global instance of the plugin. Allows developer to remove/add plugin actions/filters.
	 *
	 * @since 1.2.2
	 *
	 * @return MagePress\Mag
	 */
	function magepress() {
		static $magepress;

		if ( ! isset( $magepress ) ) {
			$magepress = MagePress\Mag::get_instance();
			$magepress->init();
		}

		return $magepress;
	}

	magepress();

	register_activation_hook( __FILE__, array( magepress(), 'activate' ) );
	register_deactivation_hook( __FILE__, array( magepress(), 'deactivate' ) );
}

if ( ! function_exists( 'magepress_admin' ) ) {
	/**
	 * Create global instance of the plugin admin. Allows developer to remove/add plugin actions/filters.
	 *
	 * @since 1.2.2
	 *
	 * @return MagePress\Mag_Admin
	 */
	function magepress_admin() {
		static $magepress_admin;

		if ( ! isset( $magepress_admin ) ) {
			$magepress_admin = MagePress\Mag::get_instance()->get_admin();
			$magepress_admin->init();
		}

		return $magepress_admin;
	}

	magepress_admin();
}

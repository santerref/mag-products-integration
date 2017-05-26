<?php

namespace Mag_Products_Integration;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define( 'MAG_PRODUCTS_INTEGRATION_PLUGIN_VERSION', '1.2.5' );
define( 'MAG_PRODUCTS_INTEGRATION_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/*
Plugin Name: Mag Products Integration for WordPress
Plugin URI: https://wordpress.org/plugins/mag-products-integration/
Description: This plugin let you display products of your Magento store, directly in your WordPress. It connects to Magento through the REST API.
Version: 1.2.7
Requires at least: 4.0
Author: Francis Santerre
Author URI: http://santerref.com/
Domain Path: /languages
Text Domain: mag-products-integration
*/

require_once MAG_PRODUCTS_INTEGRATION_PLUGIN_PATH . 'class.mag-products-integration-admin.php';
require_once MAG_PRODUCTS_INTEGRATION_PLUGIN_PATH . 'class.mag-products-integration-shortcode.php';
require_once MAG_PRODUCTS_INTEGRATION_PLUGIN_PATH . 'class.mag-products-integration-cache.php';

/**
 * Class Mag
 *
 * @since   1.0.0
 *
 * @package Mag_Products_Integration
 */
class Mag {

	/** @var Mag $instance Singleton of Mag */
	protected static $instance;

	/** @var Mag_Admin $admin Instance of Mag_Admin */
	protected $admin;

	/** @var Mag_Shortcode $shortcode Instance of Mag_Shortcode */
	protected $shortcode;

	/** @var Mag_Cache $cache Instance of Mag_Cache */
	protected $cache;

	/**
	 * Create the instances of $admin and $shortcode
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->admin     = new Mag_Admin();
		$this->shortcode = new Mag_Shortcode();
		$this->cache     = new Mag_Cache();
	}

	/**
	 * @since 1.0.0
	 *
	 * @return Mag Singleton
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) || ! self::$instance instanceof self ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Initialization of the plugin. Load plugin text domain and execute initialization functions.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		load_plugin_textdomain( 'mag-products-integration', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
		add_shortcode( 'magento', array( $this->shortcode, 'do_shortcode' ) );
		add_action( 'init', array( self::$instance, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'euqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
		add_action( 'wp_head', array( $this, 'output_colors_css' ), 999 );
		add_action( 'customize_register', array( $this->admin, 'customize_register' ) );
		add_action( 'customize_preview_init', array( $this->admin, 'customize_preview' ) );
	}

	/**
	 * Enqueue plugin's default CSS styles for the products list
	 *
	 * @since 1.0.0
	 */
	public function euqueue_scripts() {
		wp_enqueue_style( 'magento-style', plugins_url( 'css/style.css', __FILE__ ), array(), MAG_PRODUCTS_INTEGRATION_PLUGIN_VERSION );
	}

	/**
	 * Add Settings link on the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings = array(
			'<a href="' . admin_url( 'admin.php?page=mag-products-integration/class.mag-products-integration-admin.php' ) . '">' . __( 'Settings' ) . '</a>',
		);

		return array_merge( $settings, $links );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return Mag_Admin instance
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * @since 1.2.0
	 *
	 * @return Mag_Cache instance
	 */
	public function get_cache() {
		return $this->cache;
	}

	/**
	 * The plugin is ready when a valid API endpoint is available.
	 *
	 * @since 1.0.0
	 *
	 * @return string Valid Magento REST API endpoint or empty string.
	 */
	public function is_ready() {
		$is_ready = get_option( 'mag_products_integration_rest_api_url' );

		return $is_ready;
	}

	/**
	 * Determine if the plugin if fully installed or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the plugin is configured and the Magento module installed, false otherwise.
	 */
	public function is_module_installed() {
		$url_validated      = get_option( 'mag_products_integration_rest_api_url_validated' );
		$default_store_code = get_option( 'mag_products_integration_default_store_code' );
		$module_installed   = get_option( 'mag_products_integration_magento_module_installed' );
		$stores_code        = get_option( 'mag_products_integration_stores_code' );

		return ( $url_validated && ! empty( $default_store_code ) && ! empty( $module_installed ) && ! empty( $stores_code ) );
	}

	/**
	 * Function executed on plugin activation.
	 * Update plugin's options to set default values.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		update_option( 'mag_products_integration_rest_api_url_validated', get_option( 'mag_products_integration_rest_api_url_validated', 0 ) );
		update_option( 'mag_products_integration_stores_code', get_option( 'mag_products_integration_stores_code', '' ) );
		update_option( 'mag_products_integration_default_store_code', get_option( 'mag_products_integration_default_store_code', '' ) );
		update_option( 'mag_products_integration_magento_module_installed', get_option( 'mag_products_integration_magento_module_installed', 0 ) );
	}

	/**
	 * Function executed on plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

	}

	/**
	 * Output customizer colours values into an inline CSS style.
	 *
	 * @since 1.2.7
	 */
	public function output_colors_css() {
		$current_price_color = get_theme_mod( 'magento_color_current_price', '#3399cc' );
		$regular_price_color = get_theme_mod( 'magento_color_regular_price', '#858585' );
		$button_color        = get_theme_mod( 'magento_color_button', '#3399cc' );
		$button_text_color   = get_theme_mod( 'magento_color_button_text', '#FFFFFF' );
		$button_hover_color  = get_theme_mod( 'magento_color_button_hover', '#2e8ab8' );

		ob_start();
		?>
        <style>
            .magento-wrapper ul.products li.product .price .current-price {
                color: <?php echo $current_price_color ?>;
            }

            .magento-wrapper ul.products li.product .price .regular-price {
                color: <?php echo $regular_price_color ?>;
            }

            .magento-wrapper ul.products li.product .url a {
                background: <?php echo $button_color ?>;
                color: <?php echo $button_text_color ?>;
            }

            .magento-wrapper ul.products li.product .url a:hover {
                background: <?php echo $button_hover_color ?>;
            }
        </style>
		<?php
		$css = ob_get_clean();
		echo str_replace( array( "\t", "\n", "  " ), "", $css );
	}
}

register_activation_hook( __FILE__, array( Mag::get_instance(), 'activate' ) );
register_deactivation_hook( __FILE__, array( Mag::get_instance(), 'deactivate' ) );

if ( ! function_exists( 'mag_products_integration' ) ) {
	/**
	 * Create global instance of the plugin. Allows developer to remove/add plugin actions/filters.
	 *
	 * @since 1.2.2
	 *
	 * @return Mag
	 */
	function mag_products_integration() {
		global $mag_products_integration;

		if ( ! isset( $mag_products_integration ) ) {
			$mag_products_integration = Mag::get_instance();
			$mag_products_integration->init();
		}

		return $mag_products_integration;
	}

	mag_products_integration();
}

if ( is_admin() ) {
	if ( ! function_exists( 'mag_products_integration_admin' ) ) {
		/**
		 * Create global instance of the plugin admin. Allows developer to remove/add plugin actions/filters.
		 *
		 * @since 1.2.2
		 *
		 * @return Mag_Admin
		 */
		function mag_products_integration_admin() {
			global $mag_products_integration_admin;

			if ( ! isset( $mag_products_integration_admin ) ) {
				$mag_products_integration_admin = Mag::get_instance()->get_admin();
				$mag_products_integration_admin->init();
			}

			return $mag_products_integration_admin;
		}

		mag_products_integration_admin();
	}
}

<?php
/**
 * Interface with must have functions for store integrations.
 *
 * @package Mag_Products_Integration
 */

namespace MagePress\stores;

interface Store_Interface {

	/**
	 * The name of the e-commerce store integration.
	 *
	 * @return string The name of the store (used in the dropdown on the settings page).
	 * @since 2.0.0
	 */
	public static function label();

	/**
	 * Generate the required inputs for the settings form.
	 *
	 * @return string The HTML fields.
	 * @since 2.0.0
	 */
	public function admin_page();

	/**
	 * Execute the shortcode.
	 *
	 * @param array  $atts The attributes of the shortcode (not processed).
	 * @param string $content The content between the shortcode.
	 *
	 * @return mixed The products list rendered in HTML or errors messages if something fails.
	 * @since 2.0.0
	 */
	public function do_shortcode( $atts, $content = '' );

	/**
	 * Verify if everything is ready to fetch the products and render them.
	 *
	 * @return bool True if ready, false if not.
	 * @since 2.0.0
	 */
	public function ready();

	/**
	 * Register the settings for the admin form.
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function register_settings();
}

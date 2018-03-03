<?php

/**
 * Class MagAdminTest
 *
 * @package Mag_Products_Integration
 */
class MagAdminTest extends WP_UnitTestCase {

	function test_dismiss_module_notice() {
		$this->assertFalse( get_option( 'mag_products_integration_dismiss_module_notice', false ) );
		magepress_admin()->dismiss_module_notice();
		$this->assertTrue( get_option( 'mag_products_integration_dismiss_module_notice', false ) );
	}

	function test_registered_settings() {
		magepress_admin()->register_settings();

		global $wp_registered_settings, $new_whitelist_options;

		if ( ! is_array( $wp_registered_settings ) ) {
			$wp_registered_settings = array();
		}

		// WordPress 4.0 fallback.
		if ( ! is_array( $new_whitelist_options ) ) {
			$new_whitelist_options = array();
		}

		if ( isset( $new_whitelist_options['mag_products_integration'] ) ) {
			$wp_registered_settings = array_merge( $wp_registered_settings, array_flip( $new_whitelist_options['mag_products_integration'] ) );
		}

		$this->assertArrayHasKey( 'mag_products_integration_rest_api_url', $wp_registered_settings );
		$this->assertArrayHasKey( 'mag_products_integration_cache_enabled', $wp_registered_settings );
		$this->assertArrayHasKey( 'mag_products_integration_cache_lifetime', $wp_registered_settings );
	}

}

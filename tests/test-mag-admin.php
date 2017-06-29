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
		do_action( 'admin_init' );

		global $wp_registered_settings;
		if ( ! is_array( $wp_registered_settings ) ) {
			$wp_registered_settings = array();
		}

		$this->assertArrayHasKey( 'mag_products_integration_rest_api_url', $wp_registered_settings );
		$this->assertArrayHasKey( 'mag_products_integration_cache_enabled', $wp_registered_settings );
		$this->assertArrayHasKey( 'mag_products_integration_cache_lifetime', $wp_registered_settings );
	}

}

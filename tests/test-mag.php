<?php

/**
 * Class MagTest
 *
 * @package Mag_Products_Integration
 */
class MagTest extends WP_UnitTestCase {

	function test_is_ready() {
		$this->assertFalse( magepress()->is_ready() );

		update_option( 'mag_products_integration_rest_api_url', true );
		update_option( 'mag_products_integration_rest_api_url_validated', true );

		$this->assertTrue( magepress()->is_ready() );
	}

	function test_is_module_installed() {
		$this->assertFalse( magepress()->is_module_installed() );

		update_option( 'mag_products_integration_rest_api_url_validated', true );
		$this->assertFalse( magepress()->is_module_installed() );

		update_option( 'mag_products_integration_default_store_code', true );
		$this->assertFalse( magepress()->is_module_installed() );

		update_option( 'mag_products_integration_magento_module_installed', true );
		$this->assertFalse( magepress()->is_module_installed() );

		update_option( 'mag_products_integration_stores_code', true );
		$this->assertTrue( magepress()->is_module_installed() );
	}

	function test_output_colors_css() {
		ob_start();
		magepress()->output_colors_css();
		$css = ob_get_clean();

		$this->assertNotEmpty( $css );
		$this->assertRegExp( '/^<style>.+<\/style>$/i', $css );
	}

}

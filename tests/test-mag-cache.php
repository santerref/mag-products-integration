<?php

/**
 * Class MagCacheTest
 *
 * @package Mag_Products_Integration
 */
class MagCacheTest extends WP_UnitTestCase {

	function test_default_options() {
		$this->assertTrue( magepress()->get_cache()->is_enabled() );
		$this->assertEquals( magepress()->get_cache()->get_lifetime(), 60 * 60 );
		$this->assertEmpty( magepress()->get_cache()->get_cached_products( 'shortcode1' ) );
		$this->assertTrue( magepress()->get_cache()->is_expired() );
	}

	function test_set_cached_products_with_save() {
		magepress()->get_cache()->set_cached_products( [ 'a' => 'b' ], 'shortcode1' );
		$this->assertEquals( get_option( 'mag_products_integration_call_magento_api' ), 0 );

		$cached_products = get_transient( 'mag_products_integration_cached_products' );
		$this->assertNotEmpty( $cached_products );
		$this->assertArrayHasKey( 'shortcode1', $cached_products );
		$this->assertEquals( magepress()->get_cache()->get_cached_products( 'shortcode1' ), $cached_products['shortcode1'] );
	}

	function test_set_cached_products_without_save() {
		magepress()->get_cache()->set_cached_products( [ 'a' => 'b' ], 'shortcode1', false );
		$this->assertEquals( get_option( 'mag_products_integration_call_magento_api' ), 0 );

		$cached_products = get_transient( 'mag_products_integration_cached_products' );
		$this->assertEmpty( $cached_products );
	}

	function test_force_update_cache() {
		magepress()->get_cache()->set_cached_products( [ 'a' => 'b' ], 'shortcode1' );
		magepress()->get_cache()->force_update_cache();

		$this->assertEmpty( magepress()->get_cache()->get_cached_products( 'shortcode1' ) );
		$this->assertEquals( get_option( 'mag_products_integration_call_magento_api' ), 1 );
		$this->assertEmpty( get_transient( 'mag_products_integration_cached_products' ) );
	}

	function test_update_expiration() {
		magepress()->get_cache()->set_cached_products( [ 'a' => 'b' ], 'shortcode1' );

		sleep( 2 );

		$this->assertNotEmpty( get_transient( 'mag_products_integration_cached_products' ) );
		magepress()->get_cache()->update_expiration( 1 );

		sleep( 2 );

		$this->assertEmpty( get_transient( 'mag_products_integration_cached_products' ) );
	}

}

<?php

/**
 * Class MagProductsIntegrationTest
 *
 * @package Mag_Products_Integration
 */
class MagProductsIntegrationTest extends WP_UnitTestCase {

	function test_magepress() {
		$this->assertInstanceOf( MagePress\Mag::class, magepress() );
		$this->assertInstanceOf( MagePress\Mag_Cache::class, magepress()->get_cache() );
		$this->assertInstanceOf( MagePress\Mag_Admin::class, magepress()->get_admin() );
	}

	function test_magepress_admin() {
		$this->assertInstanceOf( MagePress\Mag_Admin::class, magepress_admin() );
	}

}

<?php

/**
 * Class MagTest
 *
 * @package Mag_Products_Integration
 */
class MagTest extends WP_UnitTestCase {

	function test_output_colors_css() {
		ob_start();
		magepress()->output_colors_css();
		$css = ob_get_clean();

		$this->assertNotEmpty( $css );
		$this->assertRegExp( '/^<style>.+<\/style>$/i', $css );
	}

}

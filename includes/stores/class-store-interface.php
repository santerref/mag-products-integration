<?php

namespace MagePress\stores;

interface Store_Interface {

	public static function label();

	public function admin_page();

	public function do_shortcode( $atts, $content = '' );

	public function ready();

	public function register_settings();
}

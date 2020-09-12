<?php

namespace MagePress;

use MagePress\stores\Store_Abstract;
use MagePress\stores\Store_Interface;
use MagePress\stores\Store_Magento1;
use MagePress\stores\Store_Magento2;

class Mag_Store_Manager {

	/** @var Store_Abstract */
	protected $store;

	protected static $stores = [
		Store_Magento1::class,
		Store_Magento2::class,
	];

	public function __construct() {
		add_shortcode( 'magento', array( $this, 'do_shortcode' ) );
		$selected_store_class = get_option( 'mag_products_integration_selected_store_class' );
		if ( $selected_store_class && in_array( $selected_store_class, static::$stores ) ) {
			if ( class_exists( $selected_store_class ) ) {
				$this->store = new $selected_store_class();
			}
		}
	}

	public function get_available_stores() {
		$stores = [];
		foreach ( static::$stores as $store ) {
			$stores[ $store ] = call_user_func( $store . '::label' );
		}

		return $stores;
	}

	public function get_store() {
		return $this->store;
	}

	public function has_store() {
		return $this->store instanceof Store_Interface;
	}

	public function do_shortcode( $atts, $content = '' ) {
		if ( $this->has_store() && $this->store->ready() ) {
			return $this->store->do_shortcode( $atts, $content );
		}
	}
}

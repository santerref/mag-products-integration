<?php

namespace Mag_Products_Integration;

/**
 * Class Mag_Cache
 *
 * @since 1.2.0
 *
 * @package Mag_Products_Integration
 */
class Mag_Cache {

	/** @const int DEFAULT_CACHE_LIFETIME 1 hour */
	const DEFAULT_CACHE_LIFETIME = HOUR_IN_SECONDS;

	/** @const bool DEFAULT_CACHE_ENABLED Enabled by default */
	const DEFAULT_CACHE_ENABLED = true;

	/** @var string|int $lifetime Cache lifetime */
	protected $lifetime;

	/** @var bool $enabled Cache enabled or not */
	protected $enabled;

	/**
	 * @var bool $expired Cache expired or not
	 * @since 1.2.2
	 */
	protected $expired;

	/** @var array $cached_products Products in cache */
	protected $cached_products;

	/** @var bool $call_magento_api Bypass cache and fetch products using REST API */
	protected $call_magento_api;

	/**
	 * Load default values
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->load_options();
		$this->cached_products = get_transient( 'mag_products_integration_cached_products' );
		if ( $this->cached_products === false ) {
			$this->cached_products = array();
			$this->expired         = true;
		} else {
			$this->expired = false;
		}
	}

	/**
	 * Load cache_enabled and cache_lifetime options.
	 *
	 * We don't load the cached products because this can be slow.
	 *
	 * @since 1.2.0
	 */
	protected function load_options() {
		$this->lifetime = get_option( 'mag_products_integration_cache_lifetime', self::DEFAULT_CACHE_LIFETIME );
		// Compatibility with 1.2.1
		if ( $this->lifetime == 'indefinite' ) {
			$this->lifetime = YEAR_IN_SECONDS;
		}
		$this->enabled          = get_option( 'mag_products_integration_cache_enabled', self::DEFAULT_CACHE_ENABLED );
		$this->call_magento_api = get_option( 'mag_products_integration_call_magento_api', 1 );
	}

	/**
	 * Tells if the cache is enabled
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Get cache lifetime
	 *
	 * @since 1.2.0
	 *
	 * @return int lifetime in seconds
	 */
	public function get_lifetime() {
		return $this->lifetime;
	}

	/**
	 * Tells if the cache is expired or not
	 *
	 * @since 1.2.0
	 *
	 * @param string $shortcode_id Unique identifier of the shortcode
	 *
	 * @return bool Cache expired or not
	 */
	public function is_expired( $shortcode_id = null ) {
		if ( ! $this->expired ) {
			if ( ! is_null( $shortcode_id ) ) {
				if ( ! isset( $this->cached_products[ $shortcode_id ] ) ) {
					return true;
				}
			}
		} else {
			return true;
		}

		return false;
	}

	/**
	 * Load cached products from database using get_option()
	 *
	 * @since 1.2.0
	 *
	 * @param string $shortcode_id Unique identifier of the shortcode
	 *
	 * @return array Products array, may be empty.
	 */
	public function get_cached_products( $shortcode_id ) {
		return isset( $this->cached_products[ $shortcode_id ] ) ? $this->cached_products[ $shortcode_id ] : array();
	}

	/**
	 * Set cached products.
	 *
	 * @since 1.2.0
	 *
	 * @param array $products Products to be saved
	 * @param string $shortcode_id Unique identifier of the shortcode
	 * @param bool|true $save Call update_option() or not
	 */
	public function set_cached_products( $products, $shortcode_id, $save = true ) {
		update_option( 'mag_products_integration_call_magento_api', 0 );
		if ( ! is_array( $products ) ) {
			$products = array();
		}
		$this->cached_products[ $shortcode_id ] = $products;
		if ( $save ) {
			set_transient( 'mag_products_integration_cached_products', $this->cached_products, $this->lifetime );
		}
	}

	/**
	 * Update transient cache
	 *
	 * @since 1.2.2
	 *
	 * @param $expiration int Time until expiration in seconds from now
	 */
	public function update_expiration( $expiration ) {
		set_transient( 'mag_products_integration_cached_products', $this->cached_products, $expiration );
	}

	/**
	 * Force update cache, even if the cache is not expired
	 *
	 * @since 1.2.0
	 */
	public function force_update_cache() {
		update_option( 'mag_products_integration_call_magento_api', 1 );
		delete_transient( 'mag_products_integration_cached_products' );
	}

}

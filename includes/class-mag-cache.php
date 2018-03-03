<?php
/**
 * Cache wrapper over the transient functions of WordPress.
 *
 * @package Mag_Products_Integration
 */

namespace MagePress;

/**
 * Class Mag_Cache
 *
 * @since 1.2.0
 */
class Mag_Cache {

	/**
	 * 1 hour.
	 *
	 * @const int DEFAULT_CACHE_LIFETIME
	 */
	const DEFAULT_CACHE_LIFETIME = HOUR_IN_SECONDS;

	/**
	 * Enabled by default.
	 *
	 * @const bool DEFAULT_CACHE_ENABLED
	 */
	const DEFAULT_CACHE_ENABLED = true;

	/**
	 * Cache lifetime.
	 *
	 * @var string|int $lifetime
	 */
	protected $lifetime;

	/**
	 * Cache enabled or not.
	 *
	 * @var bool $enabled
	 */
	protected $enabled;

	/**
	 * Cache expired or not.
	 *
	 * @var bool $expired
	 * @since 1.2.2
	 */
	protected $expired;

	/**
	 * Products in cache.
	 *
	 * @var array $cached_products
	 */
	protected $cached_products;

	/**
	 * Bypass cache and fetch products using REST API.
	 *
	 * @var bool $call_magento_api
	 */
	protected $call_magento_api;

	/**
	 * Load default values
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->load_options();
		$this->cached_products = get_transient( 'mag_products_integration_cached_products' );
		if ( false === $this->cached_products ) {
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
		// Compatibility with 1.2.1.
		if ( 'indefinite' == $this->lifetime ) {
			$this->lifetime = YEAR_IN_SECONDS;
		}
		$this->enabled          = get_option( 'mag_products_integration_cache_enabled', self::DEFAULT_CACHE_ENABLED );
		$this->call_magento_api = get_option( 'mag_products_integration_call_magento_api', 1 );
	}

	/**
	 * Tells if the cache is enabled.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Get cache lifetime.
	 *
	 * @since 1.2.0
	 *
	 * @return int Lifetime in seconds.
	 */
	public function get_lifetime() {
		return $this->lifetime;
	}

	/**
	 * Tells if the cache is expired or not.
	 *
	 * @since 1.2.0
	 *
	 * @param string $shortcode_id Unique identifier of the shortcode.
	 *
	 * @return bool Cache expired or not.
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
	 * Load cached products from database using get_option().
	 *
	 * @since 1.2.0
	 *
	 * @param string $shortcode_id Unique identifier of the shortcode.
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
	 * @param array     $products Products to be saved.
	 * @param string    $shortcode_id Unique identifier of the shortcode.
	 * @param bool|true $save Call update_option() or not.
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
	 * Update transient cache.
	 *
	 * @since 1.2.2
	 *
	 * @param int $expiration Time until expiration in seconds from now.
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

		unset( $this->cached_products );
		$this->cached_products = array();
	}

}

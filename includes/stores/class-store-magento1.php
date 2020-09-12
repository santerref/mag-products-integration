<?php

namespace MagePress\stores;

use MagePress\Mag;
use MagePress\Mag_Cache;

class Store_Magento1 extends Store_Abstract {

	public static function label() {
		return __( 'Magento 1', 'mag-products-integration' );
	}

	public function ready() {
		return true;
	}

	public function admin_page() {
		?>
		<p>
			<?php _e( 'You have to <strong>enable REST API</strong> first in your Magento store and <strong>give the product API Resources</strong> to your Guest role. Otherwise, it will be impossible to retreive your products.', 'mag-products-integration' ); ?>
		</p>

		<p style="color: #b50000; font-weight: bold;"><?php _e( 'Magento module is optional. If you are not using it, make sure to use the cache to reduce page load time.', 'mag-products-integration' ); ?></p>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'Disable Customizer CSS', 'mag-products-integration' ); ?></th>
				<td>
					<input type="checkbox" name="mag_products_integration_disable_customizer_css"<?php echo get_option( 'mag_products_integration_disable_customizer_css', false ) ? ' checked' : ''; ?> />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Magento REST API URL', 'mag-products-integration' ); ?></th>
				<td><input type="text" class="regular-text" name="mag_products_integration_rest_api_url" value="<?php echo esc_attr( get_option( 'mag_products_integration_rest_api_url' ) ); ?>"/>

					<p class="description"><?php _e( 'Do not forget to <strong>put the trailing slash</strong>. Ex: http://yourmagentostore.com/api/rest/', 'mag-products-integration' ); ?></p>
				</td>
			</tr>

			<?php if ( Mag::get_instance()->is_ready() && ! $this->is_module_installed() ) : ?>
				<tr valign="top">
					<th scope="row"></th>
					<td>
						<a href="#" id="verify-magento-module"><?php _e( 'Verify Magento module installation and get available stores', 'mag-products-integration' ); ?>&#8594;</a>
					</td>
				</tr>
			<?php elseif ( Mag::get_instance()->is_ready() && $this->is_module_installed() ) : ?>
				<tr valign="top">
					<th scope="row"><?php _e( 'Magento module installed', 'mag-products-integration' ); ?></th>
					<td><?php _e( 'Yes', 'mag-products-integration' ); ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Available stores code', 'mag-products-integration' ); ?></th>
					<td><?php echo esc_html( implode( ', ', unserialize( get_option( 'mag_products_integration_stores_code', array() ) ) ) ); ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Default store code', 'mag-products-integration' ); ?></th>
					<td><?php echo esc_html( get_option( 'mag_products_integration_default_store_code', '' ) ); ?></td>
				</tr>
			<?php endif; ?>

			<?php if ( Mag::get_instance()->is_ready() ) : ?>
				<tr valign="top">
					<th scope="now"><?php _e( 'Enable cache', 'mag-products-integration' ); ?></th>
					<td>
						<input type="checkbox" name="mag_products_integration_cache_enabled"<?php echo Mag::get_instance()->get_cache()->is_enabled() ? ' checked' : ''; ?> />
					</td>
				</tr>

				<tr valign="top"
					class="cache-lifetime"
					<?php
					if ( ! Mag::get_instance()->get_cache()->is_enabled() ) :
						?>
						style="display: none;"<?php endif; ?>>
					<th scope="now"><?php _e( 'Cache lifetime', 'mag-products-integration' ); ?></th>
					<td>
						<?php
						$this->display_cache_lifetime_html(
							get_option(
								'mag_products_integration_cache_lifetime',
								Mag_Cache::DEFAULT_CACHE_LIFETIME
							)
						);
						?>
						<button type="button" class="button button-secondary" data-flush-cache><?php _e( 'Flush cache', 'mag-products-integration' ) ?></button>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Display <select> for cache lifetime
	 *
	 * @param int $default_lifetime Default lifetime to be selected.
	 *
	 * @since 1.2.0
	 *
	 */
	protected function display_cache_lifetime_html( $default_lifetime = Mag_Cache::DEFAULT_CACHE_LIFETIME ) {
		// Compatibility with 1.2.1.
		if ( 'indefinite' == $default_lifetime ) {
			$default_lifetime = YEAR_IN_SECONDS;
		}
		$options = array(
			array(
				'lifetime' => HOUR_IN_SECONDS,
				'label' => __( '1 hour', 'mag-products-integration' ),
			),
			array(
				'lifetime' => 6 * HOUR_IN_SECONDS,
				'label' => __( '6 hours', 'mag-products-integration' ),
			),
			array(
				'lifetime' => 12 * HOUR_IN_SECONDS,
				'label' => __( '12 hours', 'mag-products-integration' ),
			),
			array(
				'lifetime' => DAY_IN_SECONDS,
				'label' => __( '1 day', 'mag-products-integration' ),
			),
			array(
				'lifetime' => 3 * DAY_IN_SECONDS,
				'label' => __( '3 days', 'mag-products-integration' ),
			),
			array(
				'lifetime' => WEEK_IN_SECONDS,
				'label' => __( '1 week', 'mag-products-integration' ),
			),
			array(
				'lifetime' => YEAR_IN_SECONDS,
				'label' => __( '1 year', 'mag-products-integration' ),
			),
		);

		$html = '<select name="mag_products_integration_cache_lifetime">';
		foreach ( $options as $option ) {
			$html .= '<option value="' . $option['lifetime'] . '"';
			if ( $option['lifetime'] == $default_lifetime ) {
				$html .= ' selected';
			}
			$html .= '>' . $option['label'] . '</option>';
		}
		$html .= '</select>';

		echo $html;
	}

	/**
	 * Render the products list.
	 *
	 * @param array  $atts Shortcode parameter.
	 * @param string $content Currently not used.
	 *
	 * @return string Products list HTML.
	 * @since 1.0.0
	 *
	 */
	public function do_shortcode( $atts, $content = '' ) {
		if ( Mag::get_instance()->is_ready() ) {
			$atts = shortcode_atts(
				array(
					'limit' => '12',
					'title' => 'h2',
					'class' => '',
					'sku' => '',
					'category' => '',
					'name' => '',
					'store' => get_option( 'mag_products_integration_default_store_code', '' ),
					'target' => '',
					'dir' => 'desc',
					'order' => 'entity_id',
					'prefix' => '',
					'suffix' => ' $',
					'image_width' => '',
					'image_height' => '',
					'hide_image' => false,
					'description' => true,
				), $atts, 'magento'
			);

			if ( 'true' == $atts['hide_image'] ) {
				$atts['hide_image'] = true;
			} elseif ( 'false' == $atts['hide_image'] ) {
				$atts['hide_image'] = false;
			} else {
				$atts['hide_image'] = (bool) intval( $atts['hide_image'] );
			}

			$filters = array();
			if ( ! empty( $atts['sku'] ) ) {
				$filters['sku'] = explode( ',', $atts['sku'] );
				foreach ( $filters['sku'] as $key => $value ) {
					$filters['sku'][ $key ] = trim( $value );
				}
			}
			if ( ! empty( $atts['category'] ) ) {
				$filters['category'] = $atts['category'];
			}
			if ( ! empty( $atts['dir'] ) ) {
				if ( ! preg_match( '/^(asc|desc)$/i', $atts['dir'] ) ) {
					$atts['dir'] = 'asc';
				}
				$filters['dir'] = $atts['dir'];
			}
			if ( ! empty( $atts['order'] ) ) {
				if ( in_array( $atts['order'], array( 'sku', 'name', 'entity_id' ) ) ) {
					$filters['order'] = $atts['order'];
				}
			}
			if ( ! empty( $atts['name'] ) ) {
				$filters['name'] = explode( ',', $atts['name'] );
				foreach ( $filters['name'] as $key => $value ) {
					$filters['name'][ $key ] = trim( $value );
					$filters['name'][ $key ] = str_replace( ' ', '%20', $value );
				}
			}
			if ( ! empty( $atts['limit'] ) && intval( $atts['limit'] ) > 0 ) {
				$filters['limit'] = $atts['limit'];
			}
			if ( ! empty( $atts['image_width'] ) && is_numeric( $atts['image_width'] ) ) {
				$filters['image_width'] = intval( $atts['image_width'] );
			}
			if ( ! empty( $atts['image_height'] ) && is_numeric( $atts['image_height'] ) ) {
				$filters['image_height'] = intval( $atts['image_height'] );
			}

			$store = ! empty( $atts['store'] ) ? $atts['store'] : '';

			$magento_module_installed = get_option( 'mag_products_integration_magento_module_installed', 0 );
			if ( ! empty( $store ) && $magento_module_installed ) {
				$available_stores = unserialize( get_option( 'mag_products_integration_stores_code', array() ) );
				if ( in_array( $atts['store'], $available_stores ) ) {
					$store = $atts['store'];
				} else {
					$store = '';
				}
			}

			if ( empty( $store ) ) {
				return '<p class="store-attribute-required">' . __( 'The "store" attribute of the shortcode [magento] is mandatory since 1.2.11. If the attribute is set, please make sure that the store code exists.', 'mag-products-integration' ) . '</p>';
			}

			$shortcode_id = sha1( serialize( $filters ) . $store );
			$products = $this->retrieve_products( $filters, $store, $shortcode_id );

			$description_length = false;
			$show_description = true;
			if ( is_numeric( $atts['description'] ) ) {
				$description_length = abs( intval( $atts['description'] ) );
				if ( 0 == $description_length ) {
					$show_description = false;
				}
			} else {
				$show_description = filter_var( $atts['description'], FILTER_VALIDATE_BOOLEAN );
			}

			ob_start();
			if ( ! empty( $products ) ) {
				if ( isset( $products['messages'] ) && isset( $products['messages']['error'] ) && is_array( $products['messages']['error'] ) ) {
					echo '<div id="magento-products" class="errors"><ul>';
					foreach ( $products['messages']['error'] as $message ) {
						echo '<li>' . esc_html( $message['code'] ) . ' : ' . esc_html( $message['message'] ) . '</li>';
					}
					echo '</ul></div>';
				} else {
					do_action( 'mag_products_integration_before_products' );
					echo '<div class="magento-wrapper' . ( ( ! empty( $atts['class'] ) ) ? ' ' . esc_attr( $atts['class'] ) : '' ) . '"><ul class="products">';
					foreach ( $products as $product ) {
						$product = apply_filters( 'mag_products_integration_product', $product );
						echo '<li class="product">';
						do_action( 'mag_products_integration_before_product', $product );
						if ( ! empty( $product['image_url'] ) && ! $atts['hide_image'] ) {
							do_action( 'mag_products_integration_before_image', $product );
							$image = '<div class="image">';
							$image .= '<a' . ( ( ! empty( $atts['target'] ) ) ? ' target="' . esc_attr( $atts['target'] ) . '"' : '' ) . ' href="' . esc_url( $product['url'] ) . '">';
							$image .= '<img src="' . esc_html( $product['image_url'] ) . '" alt="' . esc_html( $product['name'] ) . '"';
							if ( ! empty( $atts['image_width'] ) ) {
								$image .= ' width="' . intval( $atts['image_width'] ) . '"';
							}
							if ( ! empty( $atts['image_height'] ) ) {
								$image .= ' height="' . intval( $atts['image_height'] ) . '"';
							}
							$image .= '/>';
							$image .= '</a></div>';
							$image = apply_filters(
								'mag_products_integration_product_image', $image, $product,
								$atts['image_width'],
								$atts['image_width'],
								$atts['image_height']
							);
							echo $image;
							do_action( 'mag_products_integration_after_image', $product );
						}
						do_action( 'mag_products_integration_before_title', $product );
						echo '<' . esc_attr( $atts['title'] ) . ' class="name">';
						echo '<a' . ( ( ! empty( $atts['target'] ) ) ? ' target="' . esc_attr( $atts['target'] ) . '"' : '' ) . ' href="' . esc_url( $product['url'] ) . '">';
						echo apply_filters(
							'mag_products_integration_product_name',
							esc_html( wp_strip_all_tags( $product['name'] ) ),
							$product['name']
						);
						echo '</a>';
						echo '</' . esc_attr( $atts['title'] ) . '>';
						do_action( 'mag_products_integration_after_title', $product );
						if ( ! empty( $product['short_description'] ) && true === $show_description ) {
							do_action( 'mag_products_integration_before_short_description', $product );
							$description = esc_html( wp_strip_all_tags( $product['short_description'] ) );
							if ( false !== $description_length ) {
								$description = substr( $description, 0, $description_length );
							}
							echo apply_filters(
								'mag_products_integration_product_short_description',
								'<div class="short-description"><p>' . $description . '</p></div>',
								$product['short_description'],
								$description_length
							);
							do_action( 'mag_products_integration_after_short_description', $product );
						}
						if ( $product['final_price_without_tax'] > 0 ) {
							do_action( 'mag_products_integration_before_price', $product );
							echo '<div class="price">';
							echo '<span class="current-price">';
							echo apply_filters(
								'mag_products_integration_product_final_price_without_tax',
								esc_attr( $atts['prefix'] ) . esc_html( number_format( $product['final_price_without_tax'], 2 ) ) . esc_attr( $atts['suffix'] ), $atts['prefix'],
								$product['final_price_without_tax'], $atts['suffix']
							);
							echo '</span>';
							if ( $product['regular_price_without_tax'] != $product['final_price_without_tax'] ) {
								echo '<span class="regular-price">';
								echo apply_filters(
									'mag_products_integration_product_regular_price_without_tax',
									esc_attr( $atts['prefix'] ) . esc_html( number_format( $product['regular_price_without_tax'], 2 ) ) . esc_attr( $atts['suffix'] ), $atts['prefix'],
									$product['regular_price_without_tax'],
									$atts['suffix']
								);
								echo '</span>';
							}
							echo '</div>';
							do_action( 'mag_products_integration_after_price', $product );
						}
						do_action( 'mag_products_integration_before_add_to_cart_button', $product );
						echo '<div class="url">';
						if ( $product['is_in_stock'] && 'simple' == $product['type_id'] ) {
							if ( ! empty( $product['buy_now_url'] ) ) {
								echo apply_filters(
									'mag_products_integration_product_buy_it_now_button',
									'<a class="buy-it-now" href="' . esc_html( $product['buy_now_url'] ) . '">' . __( 'Buy it now', 'mag-products-integration' ) . '</a>',
									$product['buy_now_url']
								);
							} else {
								echo apply_filters(
									'mag_products_integration_product_buy_it_now_button',
									'<a class="buy-it-now" href="' . esc_html( $product['url'] ) . '">' . __( 'Buy it now', 'mag-products-integration' ) . '</a>',
									$product['url']
								);
							}
						} else {
							echo apply_filters(
								'mag_products_integration_product_view_details_button',
								'<a class="view-details" href="' . esc_html( $product['url'] ) . '">' . __( 'View details', 'mag-products-integration' ) . '</a>',
								$product['url']
							);
						}
						echo '</div>';
						do_action( 'mag_products_integration_after_add_to_cart_button', $product );
						do_action( 'mag_products_integration_after_product', $product );
						echo '</li>';
					}
					echo '</ul></div>';
					do_action( 'mag_products_integration_after_products' );
				}
			} else {
				do_action( 'mag_products_integration_no_products_found' );
			}
			$content = ob_get_clean();

			return $content;
		}
	}

	/**
	 * Make request to Magento REST API.
	 *
	 * @param array  $filters Request parameters.
	 * @param string $store Magento store code.
	 * @param string $shortcode_id Unique identifier of the shortcode.
	 *
	 * @return array|mixed|object
	 * @since 1.0.0
	 *
	 */
	protected function retrieve_products( $filters, $store, $shortcode_id ) {
		$fetch_from_magento = true;
		$products = array();
		if ( Mag::get_instance()->get_cache()->is_enabled() ) {
			if ( ! Mag::get_instance()->get_cache()->is_expired( $shortcode_id ) ) {
				$fetch_from_magento = false;
				$products = Mag::get_instance()->get_cache()->get_cached_products( $shortcode_id );
				if ( empty( $products ) ) {
					$fetch_from_magento = true;
				}
			}
		}
		if ( $fetch_from_magento ) {
			$url = 'products?';
			$http_query = '';

			if ( is_array( $filters ) && count( $filters ) > 0 ) {
				$http_query = $this->build_http_query( $filters, $store );
			}

			if ( ! empty( $http_query ) ) {
				$rest_api_url = get_option( 'mag_products_integration_rest_api_url' ) . $url . $http_query;
			} else {
				$rest_api_url = get_option( 'mag_products_integration_rest_api_url' ) . $url;
			}

			$response = wp_remote_get(
				$rest_api_url, array(
					'timeout' => 10,
				)
			);
			if ( is_array( $response ) && 200 == $response['response']['code'] ) {
				$products = json_decode( $response['body'], true );
			}

			$magento_module_installed = get_option( 'mag_products_integration_magento_module_installed', 0 );

			/**
			 * If Magento module is not installed, we have to fetch products URL, stock and type one by one.
			 */
			if ( ! $magento_module_installed ) {
				foreach ( $products as $key => $product ) {
					$single_product_rest_api_url = preg_replace(
						'/\/products(.*)/',
						'/products/' . $product['entity_id'], $rest_api_url
					);
					if ( ! empty( $store ) ) {
						$single_product_rest_api_url .= '?___store=' . $store . '&store=' . $store;
					}

					$response = wp_remote_get(
						$single_product_rest_api_url, array(
							'timeout' => 10,
						)
					);
					if ( is_array( $response ) && 200 == $response['response']['code'] ) {
						$product_arr = json_decode( $response['body'], true );

						$products[ $key ]['url'] = $product_arr['url'];
						$products[ $key ]['is_in_stock'] = $product_arr['is_in_stock'];
						$products[ $key ]['type_id'] = $product_arr['type_id'];
						$products[ $key ]['image_url'] = $product_arr['image_url'];
						$products[ $key ]['buy_now_url'] = $product_arr['buy_now_url'];
					}
				}
			}
			if ( Mag::get_instance()->get_cache()->is_enabled() ) {
				Mag::get_instance()->get_cache()->set_cached_products( $products, $shortcode_id );
			}
		}

		return $products;
	}

	/**
	 * Parse all shortcode filters and build an HTTP query.
	 *
	 * @param array  $filters Request parameters.
	 * @param string $store Magento store code.
	 *
	 * @return string
	 * @since 1.2.8
	 *
	 */
	protected function build_http_query( $filters, $store ) {
		$get_filters = array(
			'filter' => array(),
		);

		$i = 1;

		if ( isset( $filters['category'] ) ) {
			$get_filters['category_id'] = $filters['category'];
			unset( $filters['category'] );
		}

		foreach ( $filters as $key => $value ) {
			switch ( $key ) {
				case 'limit':
				case 'order':
				case 'dir':
				case 'image_width':
				case 'image_height':
					$get_filters[ $key ] = $value;
					break;
				case 'sku':
					$get_filters['filter'][ $i ] = array(
						'attribute' => $key,
					);

					$get_filters['filter'][ $i ]['in'] = array();

					$j = 0;
					foreach ( $value as $v ) {
						$get_filters['filter'][ $i ]['in'][ $j ] = $v;
						$j ++;
					}
					break;
				case 'name':
					foreach ( $value as $v ) {
						$get_filters['filter'][ $i ] = array(
							'attribute' => $key,
							'like' => $v,
						);
						$i ++;
					}
					break;
			}
			$i ++;
		}

		if ( ! empty( $store ) ) {
			$get_filters['___store'] = $store;
			$get_filters['store'] = $store;
		}

		return http_build_query( $get_filters );
	}

	public function register_settings() {
		$this->register_setting(
			'mag_products_integration_rest_api_url', array(
				$this,
				'validate_rest_api_url',
			)
		);
		$this->register_setting(
			'mag_products_integration_cache_enabled', array(
				$this,
				'validate_cache_enabled',
			)
		);
		$this->register_setting(
			'mag_products_integration_cache_lifetime', array(
				$this,
				'validate_cache_lifetime',
			)
		);
		$this->register_setting( 'mag_products_integration_disable_customizer_css' );
	}

	/**
	 * Validate the syntax of the Magento REST API URL and verify if it's a valid API endpoint.
	 *
	 * @param string $mag_products_integration_rest_api_url URL of the Magento REST API endpoint.
	 *
	 * @return string The URL if everything is fine, empty string otherwise.
	 * @since 1.0.0
	 *
	 */
	public function validate_rest_api_url( $mag_products_integration_rest_api_url ) {
		$valid = false;
		if ( get_option( 'mag_products_integration_rest_api_url_validated' ) && get_option( 'mag_products_integration_rest_api_url', null ) == $mag_products_integration_rest_api_url ) {
			return $mag_products_integration_rest_api_url;
		} else {
			update_option( 'mag_products_integration_rest_api_url_validated', 0 );
			update_option( 'mag_products_integration_magento_module_installed', 0 );
		}
		if ( ! filter_var( $mag_products_integration_rest_api_url, FILTER_VALIDATE_URL ) ) {
			add_settings_error(
				'mag_products_integration', 'mag_products_integration_rest_api_url',
				/* translators: REST API URL typed by the user. */
				sprintf( __( 'The URL "%s" is invalid.', 'mag-products-integration' ), $mag_products_integration_rest_api_url )
			);

			return '';
		}

		if ( ! preg_match( '/api\.php\?type=rest/i', $mag_products_integration_rest_api_url ) ) {
			$mag_products_integration_rest_api_url = trailingslashit( $mag_products_integration_rest_api_url );
		}

		$response = wp_remote_get(
			$mag_products_integration_rest_api_url, array(
				'headers' => array(
					'Accept' => 'application/json',
				),
				'timeout' => 10,
			)
		);

		if ( $response instanceof \WP_Error ) {
			add_settings_error(
				'mag_products_integration', 'mag_products_integration_rest_api_url',
				_n(
					'Something went wrong while the plugin was trying to connect to the API. Please verify the error below.',
					'Something went wrong while the plugin was trying to connect to the API. Please verify the errors below.',
					count( $response->errors ), 'mag-products-integration'
				)
			);
			foreach ( $response->get_error_messages() as $error ) {
				add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', $error );
			}
		} elseif ( is_array( $response ) && ! empty( $response['body'] ) ) {
			$decoded_array = json_decode( $response['body'], true );
			if ( null !== $decoded_array ) {
				$valid = true;
				add_settings_error(
					'mag_products_integration', 'mag_products_integration_rest_api_url',
					__( 'The API URL has been successfully validated.', 'mag-products-integration' ), 'updated'
				);
				update_option( 'mag_products_integration_default_store_code', '' );
			} else {
				if ( preg_match( '/api\/rest\/$/i', $mag_products_integration_rest_api_url ) ) {
					$mag_products_integration_rest_api_url_alternative = str_replace(
						'api/rest/', 'api.php?type=rest',
						$mag_products_integration_rest_api_url
					);

					$response = wp_remote_get(
						$mag_products_integration_rest_api_url_alternative, array(
							'headers' => array(
								'Accept' => 'application/json',
							),
							'timeout' => 10,
						)
					);

					if ( is_array( $response ) && ! empty( $response['body'] ) ) {
						$decoded_array = json_decode( $response['body'], true );
						if ( null !== $decoded_array ) {
							$valid = true;
							$mag_products_integration_rest_api_url = '';
							add_settings_error(
								'mag_products_integration',
								'mag_products_integration_rest_api_url',
								sprintf( /* translators: Link to "How to enable rewrite" documentation page. */
									__(
										'The REST API is enabled but the URL rewrite is not working. Read more here: %s',
										'mag-products-integration'
									),
									'<a target="_blank" href="http://magentowp.santerref.com/htaccess.html">http://magentowp.santerref.com/htaccess.html</a>'
								)
							);
						}
					}
				}

				if ( ! $valid ) {
					$mag_products_integration_rest_api_url = '';
					add_settings_error(
						'mag_products_integration', 'mag_products_integration_rest_api_url',
						__( 'The URL is not a valid API endpoint.', 'mag-products-integration' )
					);
				}
			}
		} else {
			add_settings_error(
				'mag_products_integration', 'mag_products_integration_rest_api_url',
				__( 'The URL is not a valid API endpoint.', 'mag-products-integration' )
			);
		}

		update_option( 'mag_products_integration_rest_api_url_validated', intval( $valid ) );

		return $mag_products_integration_rest_api_url;
	}

	/**
	 * Validate the checkbox value for the "enable cache" option.
	 *
	 * @param int $cache_enabled The value of the checkbox on the admin page.
	 *
	 * @return bool Whether or not the cached is enabled by the user.
	 */
	public function validate_cache_enabled( $cache_enabled ) {
		if ( empty( $cache_enabled ) ) {
			Mag::get_instance()->get_cache()->force_update_cache();
		}

		return $cache_enabled;
	}

	/**
	 * Make sure that the lifetime is not altered.
	 *
	 * If the selected lifetime is different from the current, update to expire option value.
	 *
	 * @param int $mag_products_integration_cache_lifetime Lifetime choosen by the user.
	 *
	 * @return string Validated lifetime.
	 * @since 1.2.0
	 *
	 */
	public function validate_cache_lifetime( $mag_products_integration_cache_lifetime ) {
		$valid_values = array(
			HOUR_IN_SECONDS,
			6 * HOUR_IN_SECONDS,
			12 * HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			3 * DAY_IN_SECONDS,
			WEEK_IN_SECONDS,
			YEAR_IN_SECONDS,
		);

		$current_lifetime = Mag::get_instance()->get_cache()->get_lifetime();
		if ( $mag_products_integration_cache_lifetime != $current_lifetime ) {
			Mag::get_instance()->get_cache()->update_expiration( time() + $mag_products_integration_cache_lifetime );
		}

		if ( ! in_array( $mag_products_integration_cache_lifetime, $valid_values ) ) {
			$mag_products_integration_cache_lifetime = Mag_Cache::DEFAULT_CACHE_LIFETIME;
		}

		return $mag_products_integration_cache_lifetime;
	}

	/**
	 * Show the Magento module not installed notice.
	 *
	 * @since 1.0.0
	 */
	public function notify_magento_module_not_verified() {
		?>
		<div class="error notice is-dismissible">
			<p>
				<?php
				_e(
					'Please verify Magento module installation and load available stores. <a id="dismiss-module-notice" href="#">Dismiss this notice, I am not going to use the Magento module.</a>',
					'mag-products-integration'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Determine if the plugin if fully installed or not.
	 *
	 * @return bool True if the plugin is configured and the Magento module installed, false otherwise.
	 * @since 1.0.0
	 *
	 */
	public function is_module_installed() {
		$url_validated = get_option( 'mag_products_integration_rest_api_url_validated' );
		$default_store_code = get_option( 'mag_products_integration_default_store_code' );
		$module_installed = get_option( 'mag_products_integration_magento_module_installed' );
		$stores_code = get_option( 'mag_products_integration_stores_code' );

		return ( $url_validated && ! empty( $default_store_code ) && ! empty( $module_installed ) && ! empty( $stores_code ) );
	}

	/**
	 * Verify if the Magento 1 extension is installed.
	 *
	 * @since 2.0.0
	 */
	public function additional_verifications() {
		if ( ! $this->is_module_installed() ) {
			$dismiss_module_notice = get_option( 'mag_products_integration_dismiss_module_notice', false );
			if ( ! $dismiss_module_notice ) {
				add_action( 'admin_notices', array( $this, 'notify_magento_module_not_verified' ) );
			}
		}
	}
}

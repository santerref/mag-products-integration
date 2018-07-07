<?php
/**
 * Functions to generate and output the HTML of the shortcode.
 *
 * @package Mag_Products_Integration
 */

namespace MagePress;

/**
 * Class Mag_Shortcode
 *
 * @since   1.0.0
 */
class Mag_Shortcode {

	/**
	 * Make request to Magento REST API.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $filters Request parameters.
	 * @param string $store Magento store code.
	 * @param string $shortcode_id Unique identifier of the shortcode.
	 *
	 * @return array|mixed|object
	 */
	public static function retrieve_products( $filters, $store, $shortcode_id ) {
		$fetch_from_magento = true;
		$products           = array();
		if ( Mag::get_instance()->get_cache()->is_enabled() ) {
			if ( ! Mag::get_instance()->get_cache()->is_expired( $shortcode_id ) ) {
				$fetch_from_magento = false;
				$products           = Mag::get_instance()->get_cache()->get_cached_products( $shortcode_id );
				if ( empty( $products ) ) {
					$fetch_from_magento = true;
				}
			}
		}
		if ( $fetch_from_magento ) {
			$url        = 'products?';
			$http_query = '';

			if ( is_array( $filters ) && count( $filters ) > 0 ) {
				$http_query = self::build_http_query( $filters, $store );
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

						$products[ $key ]['url']         = $product_arr['url'];
						$products[ $key ]['is_in_stock'] = $product_arr['is_in_stock'];
						$products[ $key ]['type_id']     = $product_arr['type_id'];
						$products[ $key ]['image_url']   = $product_arr['image_url'];
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
	 * @since 1.2.8
	 *
	 * @param array  $filters Request parameters.
	 * @param string $store Magento store code.
	 *
	 * @return string
	 */
	protected static function build_http_query( $filters, $store ) {
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
							'like'      => $v,
						);
						$i ++;
					}
					break;
			}
			$i ++;
		}

		if ( ! empty( $store ) ) {
			$get_filters['___store'] = $store;
			$get_filters['store']    = $store;
		}

		return http_build_query( $get_filters );
	}

	/**
	 * Render the products list.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $atts Shortcode parameter.
	 * @param string $content Currently not used.
	 *
	 * @return string Products list HTML.
	 */
	public static function do_shortcode( $atts, $content = '' ) {
		if ( Mag::get_instance()->is_ready() ) {
			$atts = shortcode_atts(
				array(
					'limit'        => '12',
					'title'        => 'h2',
					'class'        => '',
					'sku'          => '',
					'category'     => '',
					'name'         => '',
					'store'        => get_option( 'mag_products_integration_default_store_code', '' ),
					'target'       => '',
					'dir'          => 'desc',
					'order'        => 'entity_id',
					'prefix'       => '',
					'suffix'       => ' $',
					'image_width'  => '',
					'image_height' => '',
					'hide_image'   => false,
					'description'  => true,
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
			$products     = self::retrieve_products( $filters, $store, $shortcode_id );

			$description_length = false;
			$show_description   = true;
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
							$image  = '<div class="image">';
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
							$image  = apply_filters(
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

}

<?php

namespace Mag_Products_Integration;

/**
 * Class Mag_Shortcode
 *
 * @since 1.0.0
 *
 * @package Mag_Products_Integration
 */
class Mag_Shortcode {

	public function __construct() {
	}

	/**
	 * Make request to Magento REST API.
	 *
	 * @since 1.0.0
	 *
	 * @param array $filters Request parameters.
	 * @param string $store Magento store code.
	 *
	 * @return array|mixed|object
	 */
	public function retrieve_products( $filters, $store, $shortcode_id ) {
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
			$url      = 'products';
			$base_url = get_option( 'mag_products_integration_rest_api_url' );
			if ( substr( $base_url, - 1 ) !== '/' ) {
				$base_url .= '/';
			}
			if ( is_array( $filters ) && count( $filters ) > 0 ) {

				$get_filters = '';
				$i           = 1;
				$category    = false;
				if ( isset( $filters['category'] ) ) {
					$url .= '?category_id=' . $filters['category'];
					unset( $filters['category'] );
					$category = true;
				}
				foreach ( $filters as $key => $value ) {
					if ( $i == 1 && ! $category ) {
						$separator = '?';
					} else {
						$separator = '&';
					}
					switch ( $key ) {
						case 'limit':
						case 'order':
						case 'dir':
						case 'image_width':
						case 'image_height':
							$get_filters .= $separator . $key . "=" . urlencode( $value );
							break;
						case 'sku':
							$get_filters .= $separator . "filter[" . $i . "][attribute]=" . $key;
							$j = 0;
							foreach ( $value as $v ) {
								$get_filters .= "&filter[" . $i . "][in][" . $j . "]=" . urlencode( $v );
								$j ++;
							}
							break;
						case 'name':
							foreach ( $value as $v ) {
								$get_filters .= $separator . "filter[" . $i . "][attribute]=" . $key . "&filter[" . $i . "][like]=" . urlencode( $v );
								if ( $i == 1 && ! $category ) {
									$separator = '&';
								}
								$i ++;
							}
							break;
					}
					$i ++;
				}

				if ( ! empty( $get_filters ) ) {
					$rest_api_url = get_option( 'mag_products_integration_rest_api_url' ) . $url . $get_filters;
					$separator    = '&';
				} else {
					if ( $category ) {
						$rest_api_url = get_option( 'mag_products_integration_rest_api_url' ) . $url;
						$separator    = '&';
					} else {
						$rest_api_url = get_option( 'mag_products_integration_rest_api_url' ) . $url;
						$separator    = '?';
					}
				}

				if ( ! empty( $store ) ) {
					$rest_api_url .= $separator . '___store=' . $store;
				}

			} else {
				$rest_api_url = get_option( 'mag_products_integration_rest_api_url' ) . $url;
			}

			$response = wp_remote_get( $rest_api_url );
			if ( $response['response']['code'] == 200 ) {
				$products = json_decode( $response['body'], true );
			}

			$magento_module_installed = get_option( 'mag_products_integration_magento_module_installed', 0 );

			/**
			 * If Magento module is not installed, we have to fetch products URL, stock and type one by one.
			 */
			if ( ! $magento_module_installed ) {
				foreach ( $products as $key => $product ) {
					$single_product_rest_api_url = preg_replace( '/\/products(.*)/', '/products/' . $product['entity_id'], $rest_api_url );
					if ( ! empty( $store ) ) {
						$single_product_rest_api_url .= '?___store=' . $store;
					}

					$response = wp_remote_get( $single_product_rest_api_url );
					if ( $response['response']['code'] == 200 ) {
						$product_arr                     = json_decode( $response['body'], true );
						$products[ $key ]['url']         = $product_arr['url'];
						$products[ $key ]['is_in_stock'] = $product_arr['is_in_stock'];
						$products[ $key ]['type_id']     = $product_arr['type_id'];
						$products[ $key ]['image_url']   = $product_arr['image_url'];
						if ( isset( $product_arr['buy_now_url'] ) ) {
							$products[ $key ]['buy_now_url'] = $product_arr['buy_now_url'];
						} else {
							$products[ $key ]['buy_now_url'] = '';
						}
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
	 * Render the products list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode parameter.
	 * @param string $content Currently not used.
	 *
	 * @return string Products list HTML.
	 */
	public static function do_shortcode( $atts, $content = "" ) {
		if ( Mag::get_instance()->is_ready() ) {
			$atts = shortcode_atts( array(
				'limit'        => '12',
				'title'        => 'h2',
				'class'        => '',
				'sku'          => '',
				'category'     => '',
				'name'         => '',
				'store'        => get_option( 'mag_products_integration_default_store_code', '' ),
				'width'        => '100%',
				'target'       => '',
				'dir'          => 'desc',
				'order'        => 'entity_id',
				'prefix'       => '',
				'suffix'       => ' $',
				'image_width'  => '',
				'image_height' => '',
				'hide_image'   => false
			), $atts, 'magento' );

			if ( $atts['hide_image'] == 'true' ) {
				$atts['hide_image'] = true;
			} elseif ( $atts['hide_image'] == 'false' ) {
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

			$store = '';
			if ( ! empty( $atts['store'] ) ) {
				$available_stores = unserialize( get_option( 'mag_products_integration_stores_code', array() ) );
				if ( in_array( $atts['store'], $available_stores ) ) {
					$store = $atts['store'];
				}
			}

			$shortcode_id = sha1( serialize( $filters ) . $store );

			$products = self::retrieve_products( $filters, $store, $shortcode_id );

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
						echo '<li class="product">';
						if ( ! empty( $product['image_url'] ) && ! $atts['hide_image'] ) {
							do_action( 'mag_products_integration_before_image', $product );
							$image = '<div class="image">';
							$image .= '<a' . ( ( ! empty( $atts['target'] ) ) ? ' target="' . esc_attr( $atts['target'] ) . '"' : '' ) . ' href="' . esc_url( $product['url'] ) . '">';
							$image .= '<img style="width:' . esc_attr( $atts['width'] ) . '" src="' . esc_html( $product['image_url'] ) . '" alt="' . esc_html( $product['name'] ) . '" />';
							$image .= '</a></div>';
							$image = apply_filters( 'mag_products_integration_product_image', $image, $product, $atts['width'], $atts['image_width'], $atts['image_height'] );
							echo $image;
							do_action( 'mag_products_integration_after_image', $product );
						}
						do_action( 'mag_products_integration_before_title', $product );
						echo '<' . esc_attr( $atts['title'] ) . ' class="name">';
						echo '<a' . ( ( ! empty( $atts['target'] ) ) ? ' target="' . esc_attr( $atts['target'] ) . '"' : '' ) . ' href="' . esc_url( $product['url'] ) . '">';
						echo apply_filters( 'mag_products_integration_product_name', esc_html( $product['name'] ), $product['name'] );
						echo '</a>';
						echo '</' . esc_attr( $atts['title'] ) . '>';
						do_action( 'mag_products_integration_after_title', $product );
						if ( ! empty( $product['short_description'] ) ) {
							do_action( 'mag_products_integration_before_short_description', $product );
							echo apply_filters( 'mag_products_integration_product_short_description', '<div class="short-description"><p>' . esc_html( $product['short_description'] ) . '</p></div>', $product['short_description'] );
							do_action( 'mag_products_integration_after_short_description', $product );
						}
						if ( $product['final_price_without_tax'] > 0 ) {
							do_action( 'mag_products_integration_before_price', $product );
							echo '<div class="price">';
							echo '<span class="current-price">';
							echo apply_filters( 'mag_products_integration_product_final_price_without_tax', esc_attr( $atts['prefix'] ) . esc_html( number_format( $product['final_price_without_tax'], 2 ) ) . esc_attr( $atts['suffix'] ), $atts['prefix'], $product['final_price_without_tax'], $atts['suffix'] );
							echo '</span>';
							if ( $product['regular_price_without_tax'] != $product['final_price_without_tax'] ) {
								echo '<span class="regular-price">';
								echo apply_filters( 'mag_products_integration_product_regular_price_without_tax', esc_attr( $atts['prefix'] ) . esc_html( number_format( $product['regular_price_without_tax'], 2 ) ) . esc_attr( $atts['suffix'] ), $atts['prefix'], $product['regular_price_without_tax'], $atts['suffix'] );
								echo '</span>';
							}
							echo '</div>';
							do_action( 'mag_products_integration_after_price', $product );
						}
						do_action( 'mag_products_integration_before_add_to_cart_button', $product );
						echo '<div class="url">';
						if ( $product['is_in_stock'] && $product['type_id'] == 'simple' ) {
							echo apply_filters( 'mag_products_integration_product_buy_it_now_button', '<a class="buy-it-now" href="' . esc_html( $product['buy_now_url'] ) . '">' . __( 'Buy it now', 'mag-products-integration' ) . '</a>', $product['buy_now_url'] );
						} else {
							echo apply_filters( 'mag_products_integration_product_view_details_button', '<a class="view-details" href="' . esc_html( $product['url'] ) . '">' . __( 'View details', 'mag-products-integration' ) . '</a>', $product['url'] );
						}
						echo '</div>';
						do_action( 'mag_products_integration_after_add_to_cart_button', $product );
						echo '</li>';
					}
					echo '</ul></div>';
					do_action( 'mag_products_integration_after_products' );
					if ( Mag::get_instance()->get_admin()->use_jquery_script() ) {
						echo '<script type="text/javascript">var max = -1; jQuery(".magento-wrapper ul > li").each(function() { var h = jQuery(this).height(); max = h > max ? h : max; }); jQuery(".magento-wrapper ul > li").css({height: max+"px"});</script>';
					}
				}
			} else {
				do_action( 'mag_products_integration_no_products_found' );
			}
			$content = ob_get_clean();

			return $content;
		}
	}

}

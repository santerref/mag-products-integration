<?php

namespace MagePress\stores;

use MagePress\Mag_Product;

class Store_Magento2 extends Store_Abstract {

	public static function label() {
		return __( 'Magento 2', 'mag-products-integration' );
	}

	public function admin_page() {
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'Magento 2 base URL', 'mag-products-integration' ); ?></th>
				<td>
					<input type="text" class="regular-text" name="mag_products_integration_m2_base_url"
						   value="<?php esc_attr_e( get_option( 'mag_products_integration_m2_base_url' ) ); ?>"/>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Access Token', 'mag-products-integration' ); ?></th>
				<td>
					<input type="password" class="regular-text" name="mag_products_integration_m2_access_token"
						   value="<?php esc_attr_e( get_option( 'mag_products_integration_m2_access_token' ) ); ?>"/>
				</td>
			</tr>
		</table>
		<?php
	}

	public function do_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( [
			'limit' => '12',
			'title' => 'h2',
			'class' => '',
			'sku' => '',
			'category' => '',
			'name' => '',
			'store' => null,
			'target' => '',
			'dir' => 'desc',
			'order' => 'entity_id',
			'prefix' => '',
			'suffix' => ' $',
			'image_width' => '',
			'image_height' => '',
			'hide_image' => false,
			'show_description' => true,
		], $atts, 'magento' );

		$base_url = get_option( 'mag_products_integration_m2_base_url' );
		if ( $base_url && filter_var( $base_url, FILTER_VALIDATE_URL ) ) {
			$base_url = rtrim( $base_url, '/' );
			$path = '/rest/V1/products';
			if ( $atts['store'] ) {
				$path = '/rest/' . $atts['store'] . '/V1/products';
			}
			$url = $base_url . $path;
			$query = http_build_query( [
				'searchCriteria' => [
					'pageSize' => $atts['limit'],
					'sortOrders' => [
						[
							'field' => $this->get_array_value( $atts, 'order', 'entity_id', 'string' ),
							'direction' => $this->get_array_value( $atts, 'dir', 'desc', 'string' ),
						],
					],
				],
			] );
			$url = $url . '?' . $query;
			$response = wp_remote_get( $url, [
				'headers' => [
					'Authorization' => 'Bearer ' . get_option( 'mag_products_integration_m2_access_token' ),
				],
			] );

			if ( is_array( $response ) && ! empty( $response['body'] ) && $response['response']['code'] == 200 ) {
				$magento_products = json_decode( $response['body'], true );
				if ( isset( $magento_products['items'] ) ) {
					$products = [];
					foreach ( $magento_products['items'] as $product ) {
						$products[] = new Mag_Product( [
							'name' => $product['name'],
							'sku' => $product['sku'],
							'image_url' => $this->get_image_url( $base_url, $product ),
							'url' => $this->get_url( $base_url, $product ),
							'short_description' => $this->get_custom_attribute( $this->get_array_value( $product, 'custom_attributes', [], 'array' ), 'short_description', '' ),
							'price' => $this->get_array_value( $product, 'price' ),
							'special_price' => $this->get_special_price( $product ),
						] );
					}

					return $this->products_html( $products );
				}
			}
		}
	}

	public function ready() {
		return true;
	}

	public function register_settings() {
		$this->register_setting( 'mag_products_integration_m2_base_url' );
		$this->register_setting( 'mag_products_integration_m2_access_token' );
	}

	protected function get_custom_attribute( array $custom_attributes, $attribute_code, $default_value ) {
		$value = $default_value;
		$attribute_code = strtolower( trim( $attribute_code ) );
		foreach ( $custom_attributes as $custom_attribute ) {
			if ( isset( $custom_attribute['attribute_code'] ) && isset( $custom_attribute['value'] ) ) {
				if ( $attribute_code == $custom_attribute['attribute_code'] ) {
					$value = $custom_attribute['value'];
					break;
				}
			}
		}

		return $value;
	}

	protected function get_special_price( array $product ) {
		$special_price = $this->get_custom_attribute( $this->get_array_value( $product, 'custom_attributes', [], 'array' ), 'special_price', false );
		$special_from_date = $this->get_custom_attribute( $this->get_array_value( $product, 'custom_attributes', [], 'array' ), 'special_from_date', false );
		$special_to_date = $this->get_custom_attribute( $this->get_array_value( $product, 'custom_attributes', [], 'array' ), 'special_to_date', false );

		if ( ! empty( $special_price ) ) {
			$special_from_date = empty( $special_from_date ) ? ( time() - 10 ) : strtotime( $special_from_date );
			$special_to_date = empty( $special_to_date ) ? ( time() + 60 * 60 * 24 ) : strtotime( $special_to_date );

			if ( $special_from_date > time() || $special_to_date <= time() ) {
				$special_price = false;
			}
		}

		return $special_price;
	}

	protected function get_image_url( $base_url, array $product ) {
		$media_gallery_entries = $this->get_array_value( $product, 'media_gallery_entries' );
		$image_url = '';

		if ( ! empty( $media_gallery_entries ) ) {
			foreach ( $media_gallery_entries as $media_gallery_entry ) {
				if ( ! empty( $media_gallery_entry['types'] ) && in_array( 'image', $media_gallery_entry['types'] ) ) {
					$image_url = $base_url . '/media/catalog/product' . $media_gallery_entry['file'];
					break;
				}
			}
		}

		if ( empty( $image_url ) ) {
			$image_path = $this->get_array_value( $product, 'media_gallery_entries.0.file' );
			if ( ! empty( $image_path ) ) {
				$image_url = $base_url . '/media/catalog/product' . $image_path;
			}
		}

		return $image_url;
	}

	protected function get_url( $base_url, array $product ) {
		$url_key = $this->get_custom_attribute( $this->get_array_value( $product, 'custom_attributes', [], 'array' ), 'url_key', false );
		$url = '';

		if ( $url_key ) {
			$url = $base_url . '/' . $url_key . '.html';
		}

		return $url;
	}
}

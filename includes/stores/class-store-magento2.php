<?php

namespace MagePress\stores;

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
					<input type="text" class="regular-text" name="mag_products_integration_m2_base_url" value="<?php esc_attr_e( get_option( 'mag_products_integration_m2_base_url' ) ); ?>"/>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Access Token', 'mag-products-integration' ); ?></th>
				<td>
					<input type="password" class="regular-text" name="mag_products_integration_m2_access_token" value="<?php esc_attr_e( get_option( 'mag_products_integration_m2_access_token' ) ); ?>"/>
				</td>
			</tr>
		</table>
		<?php
	}

	public function do_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( [
			'store'    => null,
			'limit'    => '12',
			'category' => null,
		], $atts, 'magento' );

		$base_url = get_option( 'mag_products_integration_m2_base_url' );
		if ( $base_url && filter_var( $base_url, FILTER_VALIDATE_URL ) ) {
			$path = '/rest/V1/products';
			if ( $atts['store'] ) {
				$path = '/rest/' . $atts['store'] . '/V1/products';
			}
			$base_url = rtrim( $base_url, '/' ) . $path;
			$query    = http_build_query( [
				'searchCriteria' => [
					'pageSize' => $atts['limit'],
				],
			] );
			$base_url = $base_url . '?' . $query;
			$response = wp_remote_get( $base_url, [
				'headers' => [
					'Authorization' => 'Bearer ' . get_option( 'mag_products_integration_m2_access_token' ),
				],
			] );

			if ( is_array( $response ) && ! empty( $response['body'] ) && $response['response']['code'] == 200 ) {
				$magento_products = json_decode( $response['body'], true );
				if ( isset( $magento_products['items'] ) ) {
					$products = [];
					foreach ( $magento_products['items'] as $product ) {
						$products[] = [
							'name' => $product['name'],
							'sku'  => $product['sku'],
						];
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
		register_setting( 'mag_products_integration', 'mag_products_integration_m2_base_url' );
		register_setting( 'mag_products_integration', 'mag_products_integration_m2_access_token' );
	}
}

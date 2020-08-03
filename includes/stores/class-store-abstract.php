<?php

namespace MagePress\stores;

use MagePress\Mag_Product;

abstract class Store_Abstract implements Store_Interface {

	protected function products_html( $products, $atts = [], $custom_template = null ) {
		$html = '';

		if ( is_array( $products ) && ! empty( $products ) ) {
			ob_start();
			do_action( 'mag_products_integration_before_products' );
			echo '<div class="' . trim( implode( ' ', [ 'magento-wrapper', $this->get_array_value( $atts, 'class', '' ) ] ) ) . '">';
			echo '<ul class="products">';
			foreach ( $products as $product ) {
				if ( $product instanceof Mag_Product ) {
					echo '<li class="product">';
					$this->get_template_part( 'templates/product', $custom_template, [
						'product' => apply_filters( 'mag_products_integration_product', $product ),
						'hide_image' => $this->get_array_value( $atts, 'hide_image', false, 'boolean' ),
						'target' => $this->get_array_value( $atts, 'target', '_self', 'string' ),
						'image_width' => $this->get_array_value( $atts, 'image_width', 0, 'int' ),
						'image_height' => $this->get_array_value( $atts, 'image_width', 0, 'int' ),
						'title' => $this->get_array_value( $atts, 'title', 'h2', 'string' ),
						'show_description' => $this->get_array_value( $atts, 'description', true, 'boolean' ),
						'description_length' => $this->get_array_value( $atts, 'description_length', 0, 'int' ),
						'prefix' => $this->get_array_value( $atts, 'prefix', '', 'string' ),
						'suffix' => $this->get_array_value( $atts, 'suffix', ' $', 'string' ),
					] );
					echo '</li>';
				}
			}
			echo '</ul>';
			echo '</div>';
			do_action( 'mag_products_integration_after_products' );
			$html = ob_get_clean();
		}

		return $html;
	}

	protected function get_template_part( $slug, $name = null, $variables = [] ) {
		$templates = [];
		$name = (string) $name;
		if ( '' !== $name ) {
			$templates[] = "{$slug}-{$name}.php";
		}
		$templates[] = "{$slug}.php";

		foreach ( $templates as $template_name ) {
			if ( ! $template_name ) {
				continue;
			}

			$file_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . '/' . $template_name;
			if ( file_exists( $file_path ) ) {
				if ( is_array( $variables ) ) {
					$variables = [ 'magepress' => $variables ];
					extract( $variables );
				}

				include $file_path;
			}
		}
	}

	protected function get_array_value( $array, $key, $default_value = null, $cast = null ) {
		$key = strtolower( trim( $key ) );

		$key_parts = explode( '.', $key );
		if ( count( $key_parts ) > 1 ) {
			return $this->get_array_value( $array[ $key_parts[0] ], implode( '.', array_slice( $key_parts, 1 ) ), $default_value );
		} else {
			$value = ! empty( $array[ $key_parts[0] ] ) ? $array[ $key_parts[0] ] : $default_value;
			if ( $cast ) {
				$cast = strtolower( trim( $cast ) );
				switch ( $cast ) {
					case 'boolean':
						if ( is_numeric( $value ) ) {
							$value = abs( intval( $value ) );
						}
						$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
						break;
					case 'int':
						$value = intval( $value );
						break;
					case 'string':
						$value = strval( $value );
						break;
					case 'array':
						if ( ! is_array( $value ) ) {
							$value = [ $value ];
						}
						break;
				}
			}

			return $value;
		}
	}
}

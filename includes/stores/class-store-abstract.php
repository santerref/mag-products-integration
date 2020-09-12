<?php
/**
 * Abstract class of store with reusable functions.
 *
 * @package Mag_Products_Integration
 */

namespace MagePress\stores;

use MagePress\Mag_Product;

/**
 * Class Store_Abstract
 *
 * @since 2.0.0
 */
abstract class Store_Abstract implements Store_Interface {

	/**
	 * This function must be used to give additional feedbacks to users with notices.
	 */
	public function additional_verifications() {
	}

	/**
	 * Generate the final HTML of the products list.
	 *
	 * @param \MagePress\Mag_Product[] $products An array of product to render.
	 * @param array                    $atts The attributes from the shortcode.
	 * @param null                     $custom_template Use a custom template to render the products.
	 *
	 * @return false|string
	 */
	protected function products_html( array $products, $atts = array(), $custom_template = null ) {
		$html = '';

		if ( ! empty( $products ) ) {
			ob_start();
			do_action( 'mag_products_integration_before_products' );
			echo '<div class="' . esc_attr( trim( implode( ' ', array( 'magento-wrapper', $this->get_array_value( $atts, 'class', '' ) ) ) ) ) . '">';
			echo '<ul class="products">';
			foreach ( $products as $product ) {
				if ( $product instanceof Mag_Product ) {
					echo '<li class="product">';
					$this->get_template_part(
						'templates/product',
						$custom_template,
						array(
							'product'            => apply_filters( 'mag_products_integration_product', $product ),
							'hide_image'         => $this->get_array_value( $atts, 'hide_image', false, 'boolean' ),
							'target'             => $this->get_array_value( $atts, 'target', '_self', 'string' ),
							'image_width'        => $this->get_array_value( $atts, 'image_width', 0, 'int' ),
							'image_height'       => $this->get_array_value( $atts, 'image_width', 0, 'int' ),
							'title'              => $this->get_array_value( $atts, 'title', 'h2', 'string' ),
							'show_description'   => $this->get_array_value( $atts, 'description', true, 'boolean' ),
							'description_length' => $this->get_array_value( $atts, 'description_length', 0, 'int' ),
							'prefix'             => $this->get_array_value( $atts, 'prefix', '', 'string' ),
							'suffix'             => $this->get_array_value( $atts, 'suffix', ' $', 'string' ),
						)
					);
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

	/**
	 * Loads a template part into a template.
	 *
	 * Provides a simple mechanism for child themes to overload reusable sections of code
	 * in the theme.
	 *
	 * Includes the named template part for a theme or if a name is specified then a
	 * specialised part will be included. If the theme contains no {slug}.php file
	 * then no template will be included.
	 *
	 * The template is included using require, not require_once, so you may include the
	 * same template part multiple times.
	 *
	 * For the $name parameter, if the file is called "{slug}-special.php" then specify
	 * "special".
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string $name The name of the specialised template.
	 * @param array  $variables Optional. Additional arguments passed to the template.
	 *                     Default empty array.
	 *
	 * @since 2.0.0
	 */
	protected function get_template_part( $slug, $name = null, $variables = array() ) {
		$templates = array();
		$name      = (string) $name;
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
					$variables = array( 'magepress' => $variables );
					// @codingStandardsIgnoreStart
					extract( $variables );
					// @codingStandardsIgnoreEnd
				}

				include $file_path;
			}
		}
	}

	/**
	 * Find deep value in array using dot annotation.
	 *
	 * $array['a']['b'] can be found using get_array_value($array, 'a.b');
	 *
	 * @param array  $array The array to find the value.
	 * @param string $key The path to the array using dot notation.
	 * @param mixed  $default_value The default value to return if the value is not found.
	 * @param mixed  $cast Cast the requested value to make sure it will be this type that is returned (booleean, int, string or array).
	 *
	 * @return mixed The requested value if found or the default value.
	 * @since 2.0.0
	 */
	protected function get_array_value( array $array, $key, $default_value = null, $cast = null ) {
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
							$value = array( $value );
						}
						break;
				}
			}

			return $value;
		}
	}

	/**
	 * Returns the name of the settings group for the current store class.
	 *
	 * @return string The option group.
	 */
	public function get_option_group() {
		// @codingStandardsIgnoreStart
		return 'mag_products_integration_' . sha1( base64_encode( static::class ) );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Wrapper for the register_setting function to automatically use the option group.
	 *
	 * @param string $option_name The name of an option to sanitize and save.
	 * @param array  $args Data used to describe the setting when registered.
	 */
	protected function register_setting( $option_name, $args = array() ) {
		register_setting( $this->get_option_group(), $option_name, $args );
	}
}

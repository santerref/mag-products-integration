<?php

namespace MagePress;

class Mag_Product {
	protected $attributes = [];

	public function __construct( $attributes = [] ) {
		$this->attributes = $attributes;
	}

	protected function get( $key, $default_value = null ) {
		$key = strtolower( trim( $key ) );
		$value = isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : $default_value;

		return apply_filters( "mag_products_integration_product_{$key}", $value );
	}

	protected function set_attribute( $key, $value ) {
		$key = strtolower( trim( $key ) );
		$this->attributes[ $key ] = $value;

		return $this;
	}

	public function add_attributes( array $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$this->set_attribute( $key, $value );
		}

		return $this;
	}

	public function get_name() {
		return wp_strip_all_tags( $this->get( 'name', '' ) );
	}

	public function get_image_url() {
		return $this->get( 'image_url', '' );
	}

	public function has_image() {
		return ! empty( $this->get_image_url() );
	}

	public function hide_image() {
		return $this->get( 'hide_image', false );
	}

	public function get_url() {
		return $this->get( 'url', '' );
	}

	public function get_image( $width = 0, $height = 0 ) {
		ob_start(); ?>
		<img
			src="<?php esc_attr_e( $this->get_image_url() ) ?>"
			alt="<?php esc_attr_e( $this->get_name() ) ?>"
			<?php echo $width ? 'width="' . esc_attr( $width ) . '"' : '' ?>
			<?php echo $height ? 'width="' . esc_attr( $height ) . '"' : '' ?>
		/>
		<?php
		$image_html = ob_get_clean();

		return apply_filters( 'mag_products_integration_product_image', $image_html, $this, $width, $height );
	}

	public function get_short_description( $strip_tags = true ) {
		return $strip_tags ? wp_strip_all_tags( $this->get( 'short_description', '' ) ) : $this->get( 'short_description', '' );
	}

	public function has_short_description() {
		return ! empty( $this->get_short_description() );
	}

	public function has_price() {
		return ! empty( $this->get_price() );
	}

	public function get_price() {
		return floatval( $this->get( 'price', 0 ) );
	}

	public function has_special_price() {
		return ! empty( $this->get_special_price() );
	}

	public function get_special_price() {
		return floatval( $this->get( 'special_price', 0 ) );
	}
}

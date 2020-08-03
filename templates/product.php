<?php
/**
 * @var array $magepress An array of extracted variables.
 */

/** @var MagePress\Mag_Product $product */
$product = $magepress['product'];

do_action( 'mag_products_integration_before_product', $product );
?>

<?php if ( ! $magepress['hide_image'] && $product->has_image() ):
	do_action( 'mag_products_integration_before_image', $product ); ?>

	<div class="image">
		<a href="<?php echo esc_url( $product->get_url() ) ?>" target="<?php esc_attr_e( $magepress['target'] ) ?>">
			<?php echo $product->get_image( $magepress['image_width'], $magepress['image_height'] ) ?>
		</a>
	</div>

	<?php
	do_action( 'mag_products_integration_after_image', $product );
endif; ?>

<?php do_action( 'mag_products_integration_before_title', $product ); ?>
<?php echo sprintf( '<a href="%1$s" target="%2$s"><%3$s class="name">%4$s</%3$s></a>', $product->get_url(), $magepress['target'], esc_attr( $magepress['title'] ), esc_html( $product->get_name() ) ); ?>
<?php do_action( 'mag_products_integration_after_title', $product ); ?>

<?php if ( $magepress['show_description'] && $product->has_short_description() ): ?>
	<?php do_action( 'mag_products_integration_before_short_description', $product ); ?>
	<?php echo sprintf(
		'<div class="short-description"><p>%1$s</p></div>',
		$magepress['description_length'] > 0 ?
			substr( $product->get_short_description(), 0, $magepress['description_length'] ) :
			$product->get_short_description()
	) ?>
	<?php do_action( 'mag_products_integration_after_short_description', $product ); ?>
<?php endif; ?>

<?php if ( $product->has_price() ): ?>
	<?php do_action( 'mag_products_integration_before_price', $product ); ?>
	<div class="<?php echo trim( implode( ' ', [ 'price', $product->has_special_price() ? 'has-special' : '' ] ) ) ?>">
		<span class="base-price"><?php echo sprintf( '%1$s%2$s%3$s', esc_attr( $magepress['prefix'] ), esc_attr( number_format( $product->get_price(), 2 ) ), esc_attr( $magepress['suffix'] ) ) ?></span>
		<?php if ( $product->has_special_price() ): ?>
			<span class="special-price"><?php echo sprintf( '%1$s%2$s%3$s', esc_attr( $magepress['prefix'] ), esc_attr( number_format( $product->get_special_price(), 2 ) ), esc_attr( $magepress['suffix'] ) ) ?></span>
		<?php endif; ?>
	</div>
	<?php do_action( 'mag_products_integration_after_price', $product ); ?>
<?php endif; ?>

<?php do_action( 'mag_products_integration_before_add_to_cart_button', $product ); ?>
<?php echo sprintf( '<div class="url"><a class="view-details" href="%1$s">%2$s</a></div>', $product->get_url(), __( 'View details', 'mag-products-integration' ) ) ?>
<?php do_action( 'mag_products_integration_after_add_to_cart_button', $product ); ?>

<?php
do_action( 'mag_products_integration_after_product', $magepress['product'] );

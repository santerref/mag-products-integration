<?php

namespace MagePress\stores;

abstract class Store_Abstract implements Store_Interface {

	protected function products_html( $products, $custom_template = null ) {
		$html = '';

		if ( is_array( $products ) && ! empty( $products ) ) {
			ob_start();
			foreach ( $products as $product ) {
				$this->get_template_part( 'templates/product', $custom_template, [
					'product' => $product,
				] );
			}
			$html = ob_get_clean();
		}

		return $html;
	}

	protected function get_template_part( $slug, $name = null, $variables = [] ) {
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
					$variables = [ 'magepress' => $variables ];
					extract( $variables );
				}

				include $file_path;
			}
		}
	}
}

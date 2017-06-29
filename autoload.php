<?php
/**
 * PHP autoloader that respects WordPress filename standards.
 *
 * @package Mag_Products_Integration
 */

spl_autoload_register( function ( $class ) {

	// project-specific namespace prefix.
	$prefix = 'MagePress\\';

	// base directory for the namespace prefix.
	$base_dir = __DIR__ . '/includes/';

	// does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		// no, move to the next registered autoloader.
		return;
	}

	// get the relative class name.
	$relative_class = substr( $class, $len );

	// replace all underscores with hyphen.
	$relative_class = str_replace( '_', '-', $relative_class );

	// prepend class name with class-.
	$relative_class = strtolower( preg_replace( '/([^\\\\]+)$/i', '$2class-$1', $relative_class ) );

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php.
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// if the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

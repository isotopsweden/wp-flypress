<?php

/**
 * Plugin Name: Flypress
 * Plugin URI: https://github.com/isotopsweden/wp-flypress
 * Description: Use Flysystem with WordPress
 * Author: Isotop
 * Author URI: https://www.isotop.se
 * Version: 1.0.0
 * Textdomain: wp-flypress
 */

// Load Composer autoload if it exists.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Boot the plugin.
 */
add_action( 'plugins_loaded', function () {
	return new \Isotop\Flypress\Flypress;
} );

/**
 * Custom autoload that will load image editors on the fly.
 *
 * @param  string $class_name
 */
function flypress_autoload( $class_name ) {
	if ( strpos( $class_name, 'Flypress' ) === false ) {
		return;
	}

	$parts = explode( '\\', $class_name );
	$class_file = 'class-' . strtolower( str_replace( '_', '-', $parts[2] ) ) . '.php';
	$class_path = __DIR__ . '/src/' . $class_file;

	if ( file_exists( $class_path ) ) {
		require $class_path;
	}
}

spl_autoload_register( 'flypress_autoload' );

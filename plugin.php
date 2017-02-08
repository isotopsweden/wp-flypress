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

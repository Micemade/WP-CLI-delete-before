<?php
/**
 * Plugin Name: WP-CLI Delete Before
 * Plugin URI: https://example.com/wp-cli-delete-before
 * Description: A WP-CLI command to delete posts of a specified type and status created before a given date.
 * Version: 1.0.0
 * Author: Micemade
 * Author URI: https://micemade.com
 * License: GPL2+
 * Text Domain: wp-cli-delete-before
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_autoload = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
if ( file_exists( $plugin_autoload ) ) {
	require_once $plugin_autoload;
	Micemade\WPCliDeleteBefore\DeleteBeforeRegistrar::register();
} else {
	add_action(
		'admin_notices',
		function() {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'Plugin autoload file is missing. Run the composer install command first.', 'wp-cli-delete-before' ) .
				'</p></div>';
		}
	);
}

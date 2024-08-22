<?php
/**
 * Plugin Name: Create Content Model
 * Description: Create content models in WP Admin.
 * Version: 1.0
 *
 * @package content-model
 */

declare( strict_types = 1 );

define( 'CONTENT_MODEL_PLUGIN_FILE', __FILE__ );
define( 'CONTENT_MODEL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_MODEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Requires a file if it exists.
 *
 * @param string $file The file to require.
 */
function content_model_require_if_exists( string $file ) {
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

content_model_require_if_exists( __DIR__ . '/includes/json-initializer/0-load.php' );
content_model_require_if_exists( __DIR__ . '/includes/runtime/0-load.php' );
content_model_require_if_exists( __DIR__ . '/includes/manager/0-load.php' );
content_model_require_if_exists( __DIR__ . '/includes/exporter/0-load.php' );

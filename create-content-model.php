<?php
/**
 * Plugin Name: Create Content Model
 * Description: Create content models in WP Admin.
 * Version: 1.0
 *
 * @package content-model
 */

declare( strict_types = 1 );

if ( defined( 'CONTENT_MODEL_PLUGIN_FILE' ) ) {
	wp_die(
		esc_html__( 'Only one version of the Create Content Model (Main, Scaffolded) plugin can be active.' ),
		esc_html__( 'Plugin Activation Error' ),
		array(
			'link_text' => esc_html__( 'Return to Plugins Page' ),
			'link_url'  => esc_url( admin_url( 'plugins.php' ) ),
		)
	);
}

define( 'CONTENT_MODEL_PLUGIN_FILE', __FILE__ );
define( 'CONTENT_MODEL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_MODEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! function_exists( 'content_model_require_if_exists' ) ) {
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
}

content_model_require_if_exists( __DIR__ . '/includes/json-initializer/0-load.php' );
content_model_require_if_exists( __DIR__ . '/includes/runtime/0-load.php' );
content_model_require_if_exists( __DIR__ . '/includes/manager/0-load.php' );
content_model_require_if_exists( __DIR__ . '/includes/exporter/0-load.php' );

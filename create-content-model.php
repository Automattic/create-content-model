<?php
/**
 * Plugin Name: Create Content Model
 * Description: Create content models in WP Admin.
 * Version: 1.0
 *
 * @package data-types
 */

declare( strict_types = 1 );

define( 'CONTENT_MODEL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_MODEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/runtime/0-load.php';
require_once __DIR__ . '/includes/manager/0-load.php';
require_once __DIR__ . '/includes/exporter/0-load.php';

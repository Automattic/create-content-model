<?php
/**
 * Loads the manager.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

require_once __DIR__ . '/class-content-model-loader.php';

add_action( 'init', array( Content_Model_Loader::class, 'get_instance' ) );

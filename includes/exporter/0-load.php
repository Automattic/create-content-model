<?php
/**
 * Loads the exporter functionality.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

require_once __DIR__ . '/class-content-model-exporter.php';

add_action( 'init', array( Content_Model_Exporter::class, 'get_instance' ) );

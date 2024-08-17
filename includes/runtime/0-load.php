<?php
/**
 * Loads the runtime.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

require_once __DIR__ . '/class-content-model-manager.php';
require_once __DIR__ . '/class-content-model.php';
require_once __DIR__ . '/class-content-model-block.php';
require_once __DIR__ . '/class-content-model-data-extractor.php';
require_once __DIR__ . '/class-content-model-data-hydrator.php';

add_action( 'init', array( Content_Model_Manager::class, 'get_instance' ) );

<?php
/**
 * Loads the JSON initializer.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

require_once __DIR__ . '/class-content-model-json-initializer.php';

add_action(
	'init',
	array( Content_Model_Json_Initializer::class, 'maybe_register_content_models_from_json' )
);

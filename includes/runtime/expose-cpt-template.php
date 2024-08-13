<?php
/**
 * Expose templating related goodies.
 *
 * @package data-types
 */

/**
 * Register a block that represents the content model template.
 */
function register_content_model_template_block() {
	$block_name = 'data-types/type-template';

	$args = array(
		'api_version'     => 1,
		'title'           => 'Content model template',
		'attributes'      => array(),
		// translators: %s is the content model's name.
		'description'     => __( 'Template for the content model.' ),
		'category'        => 'text',
		'render_callback' => function () {
			global $post;

			if ( empty( $post ) ) {
				return __( 'This will render the content model template for the current content model.' );
			}

			$template = get_content_model_template( $post->post_type );

			$parsed_template = parse_blocks( $template );
			$hydrated_blocks = hydrate_blocks_with_content( $parsed_template );

			return implode( '', array_map( 'render_block', $hydrated_blocks ) );
		},
	);

	register_block_type( $block_name, $args );
}

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'data-types/type-template',
			CONTENT_MODEL_PLUGIN_URL . '/includes/runtime/type-template-inserter.js',
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-i18n' ),
			'v1',
			true
		);
	}
);

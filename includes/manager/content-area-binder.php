<?php
/**
 * Exposes the content area binder within Gutenberg.
 *
 * @package data-types
 */

add_action(
	'enqueue_block_editor_assets',
	function () {
		global $post;

		if ( 'content_model' !== $post->post_type ) {
			return;
		}

		wp_enqueue_script(
			'data-types/content-area-binder',
			CONTENT_MODEL_PLUGIN_URL . '/includes/manager/content-area-binder.js',
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
			'v1',
			true
		);
	}
);

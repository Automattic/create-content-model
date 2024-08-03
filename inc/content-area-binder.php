<?php
/**
 * Exposes the content area binder within Gutenberg.
 *
 * @package data-types
 */

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script(
			'data-types/content-area-binder',
			plugin_dir_url( __FILE__ ) . '/content-area-binder.js',
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
			'v1',
			true
		);
	}
);

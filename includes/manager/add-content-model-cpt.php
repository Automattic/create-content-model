<?php
/**
 * Adds the Content Model manager to the sidebar.
 *
 * @package create-content-model
 */

add_action(
	'init',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		register_post_type(
			'content_model',
			array(
				'label'        => 'Content Models',
				'public'       => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
			)
		);
	},
	0
);

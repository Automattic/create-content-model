<?php
/**
 * Loads the existing content models.
 *
 * @package create-content-model
 */

add_action(
	'init',
	function () {
		$content_models = get_registered_content_models();

		foreach ( $content_models as $content_model ) {
			register_post_type(
				$content_model->slug,
				array(
					'label'        => $content_model->name,
					'public'       => true,
					'show_in_menu' => true,
					'show_in_rest' => true,
					'icon'         => 'dashicons-admin-site',
				)
			);

			$meta_fields = get_content_model_custom_fields( $content_model );

			foreach ( $meta_fields as $meta_field ) {
				register_post_meta(
					$content_model->slug,
					$meta_field,
					array(
						'show_in_rest' => true,
						'single'       => true,
						'type'         => 'string',
					)
				);
			}
		}
	},
	0
);


/**
 * Get all registered content models.
 */
function get_registered_content_models() {
	$content_models = get_posts( array( 'post_type' => 'content_model' ) );

	return array_map(
		function ( $content_model ) {
			return (object) array(
				'slug'     => $content_model->post_name,
				'name'     => $content_model->post_title,
				'template' => $content_model->post_content,
			);
		},
		$content_models
	);
}

/**
 * Resolve all custom fields from the content model's template.
 *
 * @param object $content_model The content model.
 */
function get_content_model_custom_fields( $content_model ) {
	$blocks = parse_blocks( $content_model->template );
	return _get_custom_fields( $blocks );
}

/**
 * Gets custom fields from blocks.
 *
 * @param array $blocks The blocks.
 */
function _get_custom_fields( $blocks ) {
	$acc = array();

	foreach ( $blocks as $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( 'post_content' === $binding ) {
			continue;
		}

		if ( ! is_null( $binding ) ) {
			$acc[] = $binding;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$acc = array_merge( $acc, _get_custom_fields( $block['innerBlocks'] ) );
		}
	}

	return $acc;
}

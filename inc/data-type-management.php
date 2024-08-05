<?php
/**
 * Adds data type registration capabilities.
 *
 * @package data-types
 */

/**
 * Register the Data Types Manager in the sidebar, as well as the existing data types.
 */
add_action(
	'init',
	function () {
		register_post_type(
			'data_types',
			array(
				'label'        => 'Data Types',
				'public'       => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
			)
		);

		$data_types = get_registered_data_types();

		foreach ( $data_types as $data_type ) {
			register_post_type(
				$data_type->slug,
				array(
					'label'        => $data_type->name,
					'public'       => true,
					'show_in_menu' => true,
					'show_in_rest' => true,
					'icon'         => 'dashicons-admin-site',
				)
			);

			$meta_fields = get_data_type_custom_fields( $data_type );

			foreach ( $meta_fields as $meta_field ) {
				register_post_meta(
					$data_type->slug,
					$meta_field,
					array(
						'show_in_rest' => true,
						'single'       => true,
						'type'         => 'string',
						'default'      => $meta_field,
					)
				);
			}
		}
	},
	0
);

/**
 * Get all registered data types.
 */
function get_registered_data_types() {
	$data_types = get_posts( array( 'post_type' => 'data_types' ) );

	return array_map(
		function ( $data_type ) {
			return (object) array(
				'slug'     => $data_type->post_name,
				'name'     => $data_type->post_title,
				'template' => $data_type->post_content,
			);
		},
		$data_types
	);
}

/**
 * Get all register data type slugs.
 */
function get_data_type_slugs() {
	return array_map( fn( $data_type ) => $data_type->slug, get_registered_data_types() );
}

/**
 * Resolve all custom fields from the data type's template.
 *
 * @param object $data_type The data type.
 */
function get_data_type_custom_fields( $data_type ) {
	$blocks = parse_blocks( $data_type->template );
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

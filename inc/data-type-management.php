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

		$data_types = new WP_Query( array( 'post_type' => 'data_types' ) );

		while ( $data_types->have_posts() ) {
			$data_types->the_post();
			$data_type = get_post();
			$cpt_slug  = strtolower( $data_type->post_title );

			$blocks = parse_blocks( $data_type->post_content );

			register_post_type(
				$cpt_slug,
				array(
					'label'        => $data_type->post_title,
					'public'       => true,
					'show_in_menu' => true,
					'show_in_rest' => true,
					'icon'         => 'dashicons-admin-site',
					'template'     => _convert_parsed_blocks_for_js( $blocks ),
				)
			);

			$meta_fields = _get_meta_fields( $blocks );

			foreach ( $meta_fields as $meta_field ) {
				register_post_meta(
					$cpt_slug,
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
 * Converts parsed blocks to a format Gutenberg can understand.
 *
 * @param array $blocks A list of blocks.
 */
function _convert_parsed_blocks_for_js( $blocks ) {
	$template = array();
	foreach ( $blocks as $block ) {
		if ( null === $block['blockName'] && empty( trim( $block['innerHTML'] ) ) ) {
			continue;
		}

		$entry = array( $block['blockName'], $block['attrs'] );
		if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$entry[] = _convert_parsed_blocks_for_js( $block['innerBlocks'] );
		}
		$template[] = $entry;
	}
	return $template;
}

/**
 * Parse the blocks looking for bound attributes.
 *
 * TODO: Fix recursion.
 *
 * @param array $blocks The blocks from the CPT template.
 */
function _get_meta_fields( $blocks ) {
	$meta_fields = array();

	foreach ( $blocks as $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( is_null( $binding ) || 'post_content' === $binding ) {
			continue;
		}

		$meta_fields[] = $binding;
	}

	return $meta_fields;
}

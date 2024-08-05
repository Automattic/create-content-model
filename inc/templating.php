<?php
/**
 * Expose templating related goodies.
 *
 * @package data-types
 */

add_filter( 'get_block_type_variations', 'register_block_variations', 10, 2 );

/**
 * Adds block variations for the bound blocks.
 *
 * @param array         $variations The existing block variations.
 * @param WP_Block_Type $block_type The block type.
 */
function register_block_variations( $variations, $block_type ) {
	if ( 'core/paragraph' === $block_type->name ) {
		foreach ( get_registered_data_types() as $data_type ) {
			foreach ( get_data_type_custom_fields( $data_type ) as $custom_field ) {
				$variations[] = array(
					'name'       => $custom_field,
					'title'      => ucwords( $custom_field ),
					'category'   => $data_type->slug . '-fields',
					'icon'       => 'book-alt',
					'attributes' => array(
						'metadata' => array(
							'bindings' => array(
								'content' => array(
									'source' => 'core/post-meta',
									'args'   => array(
										'key' => $custom_field,
									),
								),
							),
						),
					),
				);
			}
		}
	}

	return $variations;
}

add_filter( 'block_categories_all', 'register_field_categories' );

/**
 * Groups custom field block variations into specific categories.
 *
 * @param array $categories The existing block categories.
 */
function register_field_categories( $categories ) {
	foreach ( get_registered_data_types() as $data_type ) {
		$categories[] = array(
			'slug'  => $data_type->slug . '-fields',
			// translators: %s is data type name.
			'title' => sprintf( __( '%s fields' ), ucwords( $data_type->name ) ),
		);
	}

	return $categories;
}

add_action( 'init', 'register_data_type_template_block' );

/**
 * Register a block that represents the data type template.
 */
function register_data_type_template_block() {
	$block_name = 'data-types/type-template';

	$args = array(
		'api_version'     => 1,
		'title'           => 'Data type template',
		'attributes'      => array(),
		// translators: %s is the data type's name.
		'description'     => __( 'Template for the data type.' ),
		'category'        => 'text',
		'render_callback' => function () {
			global $post;

			if ( ! $post ) {
				return __( 'No post found' );
			}

			$template = get_data_type_template( $post->post_type );

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
			plugin_dir_url( __FILE__ ) . '/type-template-inserter.js',
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-i18n' ),
			'v1',
			true
		);
	}
);

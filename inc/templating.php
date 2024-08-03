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

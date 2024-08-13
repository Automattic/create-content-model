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
	foreach ( get_registered_content_models() as $content_model ) {
		// TODO: Group by block type.
		foreach ( get_content_model_custom_fields( $content_model ) as $custom_field ) {
			if ( $block_type->name !== $custom_field->block_name ) {
				continue;
			}

			$variations[] = array(
				'name'       => $custom_field->field,
				'title'      => ucwords( $custom_field->field ),
				'category'   => $content_model->slug . '-fields',
				'icon'       => 'book-alt',
				'attributes' => array(
					'metadata' => array(
						'bindings' => array(
							$custom_field->attribute => array(
								'source' => 'core/post-meta',
								'args'   => array(
									'key' => $custom_field->field,
								),
							),
						),
					),
				),
			);
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
	foreach ( get_registered_content_models() as $content_model ) {
		$categories[] = array(
			'slug'  => $content_model->slug . '-fields',
			// translators: %s is content model name.
			'title' => sprintf( __( '%s fields' ), ucwords( $content_model->name ) ),
		);
	}

	return $categories;
}

add_action( 'init', 'register_content_model_template_block' );

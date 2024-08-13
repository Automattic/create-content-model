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
	$block_variations = get_block_variations_from_content_models();

	if ( empty( $block_variations[ $block_type->name ] ) ) {
		return $variations;
	}

	foreach ( $block_variations[ $block_type->name ] as $block_variation ) {
		$variations[] = array(
			'title'      => $block_variation['block_variation_name'],
			'category'   => $block_variation['cpt_slug'] . '-fields',
			'attributes' => array(
				'metadata' => array(
					'bindings' => $block_variation['bindings'],
				),
			),
		);
	}

	return $variations;
}


/**
 * Extracts all the block variations from all content models.
 */
function get_block_variations_from_content_models() {
	static $blocks = null;

	if ( ! $blocks ) {
		$blocks = array();

		foreach ( get_registered_content_models() as $content_model ) {
			$variations = get_block_variations_from_content_model( $content_model );

			foreach ( $variations as $block_variation_name => $variation ) {
				$blocks[ $variation['blockName'] ] = $blocks[ $variation['blockName'] ] ?? array();

				$blocks[ $variation['blockName'] ][] = array(
					'block_variation_name' => $block_variation_name,
					'cpt_slug'             => $content_model->slug,
					'bindings'             => $variation['bindings'],
				);
			}
		}
	}

	return $blocks;
}

/**
 * Extracts the block variations from a specific content model.
 *
 * @param object $content_model The content model.
 *
 * return array The block variations, grouped by block name.
 */
function get_block_variations_from_content_model( $content_model ) {
	$blocks = array();

	foreach ( get_content_model_custom_fields( $content_model ) as $custom_field ) {
		$existing_block              = $blocks[ $custom_field->block_variation_name ] ?? array();
		$existing_block['blockName'] = $custom_field->block_name;
		$existing_block['bindings']  = $existing_block['bindings'] ?? array();

		$existing_block['bindings'][ $custom_field->attribute ] = array(
			'source' => 'core/post-meta',
			'args'   => array(
				'key' => $custom_field->field,
			),
		);

		$blocks[ $custom_field->block_variation_name ] = $existing_block;
	}

	return $blocks;
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

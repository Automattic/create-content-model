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
	// TODO: Do not expose block variations when creating a content model.

	$block_variations = get_block_variations_from_content_models();

	if ( empty( $block_variations[ $block_type->name ] ) ) {
		return $variations;
	}

	foreach ( $block_variations[ $block_type->name ] as $block_variation ) {
		$variation = array(
			'name'       => '__' . $block_variation['block_variation_name'] . '/' . $block_type->name,
			'title'      => $block_variation['block_variation_name'],
			'category'   => $block_variation['cpt_slug'] . '-fields',
			'attributes' => array(),
		);

		if ( 'core/group' === $block_type->name ) {
			$variation['innerBlocks'] = array(
				array(
					'core/paragraph',
					array(
						'content' => $block_variation['bindings']['content']['args']['key'],
					),
				),
			);
		}

		$variation['attributes']['metadata'] = array(
			'bindings' => $block_variation['bindings'],
		);

		$variations[] = $variation;
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

add_filter( 'pre_render_block', 'render_group_variation', 99, 2 );

/**
 * Since there are no official bindings for group variations and its inner blocks,
 * we need to inject the inner blocks and HTML ourselves.
 *
 * @param string|null $pre_render The pre-rendered content.
 * @param array       $parsed_block The parsed block.
 *
 * @return string|null The rendered block.
 */
function render_group_variation( $pre_render, $parsed_block ) {
	$bound_meta_field = $parsed_block['attrs']['metadata']['bindings']['content']['args']['key'] ?? null;

	if ( 'core/group' !== $parsed_block['blockName'] || ! $bound_meta_field ) {
		return $pre_render;
	}

	$content = get_post_meta( get_the_ID(), $bound_meta_field, true );

	if ( ! $content ) {
		return $pre_render;
	}

	$parsed_block['innerBlocks']  = parse_blocks( $content );
	$parsed_block['innerHTML']    = inject_content_into_block_markup( $content, $parsed_block['innerHTML'] );
	$parsed_block['innerContent'] = array( $parsed_block['innerHTML'] );

	remove_filter( 'pre_render_block', 'render_group_variation', 99 );

	$rendered_group = render_block( $parsed_block );

	add_filter( 'pre_render_block', 'render_group_variation', 99, 2 );

	return $rendered_group;
}

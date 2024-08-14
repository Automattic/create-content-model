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

	foreach ( get_registered_content_models() as $content_model ) {
		$block_variations = get_content_model_block_variations( $content_model );

		if ( empty( $block_variations[ $block_type->name ] ) ) {
			continue;
		}

		foreach ( $block_variations[ $block_type->name ] as $block_variation ) {
			$variation = array(
				'name'     => '__' . $block_variation['block_variation_name'] . '/' . $block_type->name,
				'title'    => $block_variation['block_variation_name'],
				'category' => $content_model->slug . '-fields',
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
			} else {
				$variation['attributes'] = array(
					'metadata' => array(
						'bindings' => $block_variation['bindings'],
					),
				);
			}

			$variations[] = $variation;
		}
	}

	return $variations;
}


/**
 * Resolve all block variations from the content model.
 *
 * @param object $content_model The content model.
 */
function get_content_model_block_variations( $content_model ) {
	static $block_variations = array();

	if ( ! isset( $block_variations[ $content_model->slug ] ) ) {
		$blocks = parse_blocks( $content_model->template );

		$block_variations[ $content_model->slug ] = _get_block_variations( $blocks );
	}

	return $block_variations[ $content_model->slug ];
}

/**
 * Gets variations from the blocks.
 *
 * @param array      $blocks The blocks.
 * @param array|null $acc The accumulator for recursion.
 */
function _get_block_variations( $blocks, $acc = array() ) {
	foreach ( $blocks as $block ) {
		$bindings = $block['attrs']['metadata']['contentModelBinding'] ?? array();

		if ( ! empty( $block['innerBlocks'] ) ) {
			$acc = array_merge( $acc, _get_block_variations( $block['innerBlocks'], $acc ) );
		}

		if ( ! isset( $bindings['__block_variation_name'] ) ) {
			continue;
		}

		$acc[ $block['blockName'] ] = $acc[ $block['blockName'] ] ?? array();

		unset( $block['attrs']['metadata']['contentModelBinding'] );

		if ( empty( $block['attrs']['metadata'] ) ) {
			unset( $block['attrs']['metadata'] );
		}

		$block_bindings = array();

		foreach ( $bindings as $attribute => $binding ) {
			if ( 'post_content' === $binding || '__block_variation_name' === $attribute ) {
				continue;
			}

			$block_bindings[ $attribute ] = array(
				'source' => 'core/post-meta',
				'args'   => array(
					'key' => $binding,
				),
			);
		}

		if ( ! empty( $block_bindings ) ) {
			$acc[ $block['blockName'] ][] = array(
				'block_variation_name' => $bindings['__block_variation_name'],
				'bindings'             => $block_bindings,
			);
		}
	}

	return $acc;
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

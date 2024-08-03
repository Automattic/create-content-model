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
		// TODO: Get the template from the post type.
		$template = get_post( 80 );
		$template = parse_blocks( $template->post_content );

		// TODO: Fix recursion.
		foreach ( $template as $block ) {
			$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

			if ( is_null( $binding ) || 'post_content' === $binding ) {
				continue;
			}

			$variations[] = array(
				'name'       => $binding,
				'title'      => ucwords( $binding ),
				'icon'       => 'book-alt',
				'attributes' => array(
					'metadata' => array(
						'bindings' => array(
							'content' => array(
								'source' => 'core/post-meta',
								'args'   => array(
									'key' => $binding,
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

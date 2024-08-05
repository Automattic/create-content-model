<?php
/**
 * Adds data entry capabilities.
 *
 * @package data-types
 */

add_action( 'save_post', 'redirect_content_areas', 99, 2 );

/**
 * Redirects content areas to the appropriate place after saving the post.
 *
 * @param int     $post_id The post ID.
 * @param WP_Post $post The post.
 */
function redirect_content_areas( $post_id, $post ) {
	if ( ! in_array( $post->post_type, get_data_type_slugs(), true ) || 'publish' !== $post->post_status ) {
		return;
	}

	remove_action( 'save_post', 'redirect_content_areas', 99 );

	$blocks = parse_blocks( wp_unslash( $post->post_content ) );

	$post_content = _find_post_content( $blocks );
	$meta_fields  = _find_meta_fields( $blocks );

	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $post_content ?? '',
			'meta_input'   => $meta_fields,
		)
	);

	add_action( 'save_post', 'redirect_content_areas', 99, 2 );
}

/**
 * Finds the post_content content area within blocks.
 *
 * @param array $blocks The blocks.
 *
 * @return string The post content markup.
 */
function _find_post_content( $blocks ) {
	foreach ( $blocks as $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( 'post_content' === $binding ) {
			return serialize_blocks( $block['innerBlocks'] );
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$result = _find_post_content( $block['innerBlocks'] );

			if ( $result ) {
				return $result;
			}
		}
	}

	return null;
}

/**
 * Finds meta fields within blocks.
 *
 * @param array $blocks The blocks.
 *
 * @return array The meta fields key-value pair: key is the meta field name, and value is the markup.
 */
function _find_meta_fields( $blocks ) {
	$acc = array();

	foreach ( $blocks as $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( 'post_content' === $binding ) {
			continue;
		}

		if ( ! is_null( $binding ) ) {
			$acc[ $binding ] = serialize_blocks( $block['innerBlocks'] );
			continue;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$acc = array_merge( $acc, _find_meta_fields( $block['innerBlocks'] ) );
		}
	}

	return $acc;
}

add_action( 'the_post', 'hydrate_template_with_content' );

/**
 * In the editor, display the template and fill it with the data.
 *
 * @param WP_Post $post The current post.
 */
function hydrate_template_with_content( $post ) {
	if ( ! in_array( $post->post_type, get_data_type_slugs(), true ) ) {
		return;
	}

	$template = get_data_type_template( $post->post_type );

	$parsed_template = parse_blocks( $template );
	$hydrated_blocks = hydrate_blocks_with_content( $parsed_template );

	$post->post_content = serialize_blocks( $hydrated_blocks );
}

/**
 * Iterates over blocks and fills the bindings with content (from post content or meta fields).
 *
 * @param array $blocks The blocks.
 *
 * @return array Filled blocks.
 */
function hydrate_blocks_with_content( $blocks ) {
	foreach ( $blocks as $index => $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( is_null( $binding ) ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks[ $index ]['innerBlocks'] = hydrate_blocks_with_content( $block['innerBlocks'] );
			}

			continue;
		}

		if ( 'post_content' === $binding ) {
			$content = get_the_content();
		} else {
			$content = get_post_meta( get_the_ID(), $binding, true );
		}

		// If can't find the corresponding content, do not try to inject it.
		if ( ! $content ) {
			continue;
		}

		$blocks[ $index ]['innerBlocks'] = parse_blocks( $content );

		$blocks[ $index ]['innerHTML']    = inject_content_into_block_markup( $content, $block['innerHTML'] );
		$blocks[ $index ]['innerContent'] = array( $blocks[ $index ]['innerHTML'] );
	}

	return $blocks;
}

/**
 * Get the template of a specific data type.
 *
 * @param string $data_type_slug The slug of the data type.
 */
function get_data_type_template( $data_type_slug ) {
	foreach ( get_registered_data_types() as $data_type ) {
		if ( $data_type->slug === $data_type_slug ) {
			return $data_type->template;
		}
	}
}

/**
 * Adds the content (from post_content or post meta) within the markup.
 *
 * @param string $content The content.
 * @param string $block_markup The innerHTML of the block.
 */
function inject_content_into_block_markup( $content, $block_markup ) {
	$p = new WP_HTML_Tag_Processor( $block_markup );
	$p->next_tag();
	$p2 = new WP_HTML_Tag_Processor( '<div>' );
	$p2->next_tag();
	foreach ( $p->get_attribute_names_with_prefix( '' ) ?? array() as $attribute ) {
		$p2->set_attribute( $attribute, $p->get_attribute( $attribute ) );
	}

	return $p2->get_updated_html() . $content . '</div>';
}

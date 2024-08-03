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

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	remove_action( 'save_post', 'redirect_content_areas', 99 );

	$blocks = parse_blocks( wp_unslash( $post->post_content ) );

	$data = extract_content_from_blocks( $blocks );

	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $data['post_content'],
			'meta_input'   => $data['meta_fields'],
		)
	);

	add_action( 'save_post', 'redirect_content_areas', 99, 2 );
}

/**
 * Extract contents from the blocks.
 *
 * TODO: Fix recursion.
 *
 * @param array $blocks The blocks from the post.
 */
function extract_content_from_blocks( $blocks ) {
	$data = array(
		'post_content' => '',
		'meta_fields'  => array(),
	);

	foreach ( $blocks as $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( is_null( $binding ) ) {
			continue;
		}

		if ( 'post_content' === $binding ) {
			$data['post_content'] = serialize_blocks( $block['innerBlocks'] );
			continue;
		}

		$data['meta_fields'][ $binding ] = serialize_blocks( $block['innerBlocks'] );
	}

	return $data;
}

add_action( 'the_post', 'hydrate_data_with_content' );

/**
 * In the editor, hydrate the template with the actual data and display it.
 *
 * @param WP_Post $post The current post.
 */
function hydrate_data_with_content( $post ) {
	if ( ! in_array( $post->post_type, get_data_type_slugs(), true ) ) {
		return;
	}

	$template = get_data_type_template( $post->post_type );
	$template = parse_blocks( $template );

	// TODO: Fix recursion.
	foreach ( $template as $key => $block ) {
		$binding = $block['attrs']['metadata']['data-types/binding'] ?? null;

		if ( is_null( $binding ) ) {
			continue;
		}

		if ( 'post_content' === $binding ) {
			$content = $post->post_content;
		} else {
			$content = get_post_meta( get_the_ID(), $binding, true );
		}

		$template[ $key ]['innerBlocks'] = parse_blocks( $content );

		$new_content = backfill_html( $block['innerHTML'], $content );

		$template[ $key ]['innerHTML']    = $new_content;
		$template[ $key ]['innerContent'] = array( $new_content );
	}

	$post->post_content = serialize_blocks( $template );
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
 * Backfill the HTML.
 *
 * @param string $inner_html The markup.
 * @param string $block_content The content of the block.
 */
function backfill_html( $inner_html, $block_content ) {
	$p = new WP_HTML_Tag_Processor( $inner_html );
	$p->next_tag();
	$p2 = new WP_HTML_Tag_Processor( '<div>' );
	$p2->next_tag();
	foreach ( $p->get_attribute_names_with_prefix( '' ) ?? array() as $attribute ) {
		$p2->set_attribute( $attribute, $p->get_attribute( $attribute ) );
	}

	return $p2->get_updated_html() . $block_content . '</div>';
}

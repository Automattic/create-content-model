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
	if ( ! in_array( $post->post_type, get_content_model_slugs(), true ) || 'publish' !== $post->post_status ) {
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
		$binding = $block['attrs']['metadata']['contentModelBinding'] ?? array();

		foreach ( array_values( $binding ) as $field ) {
			if ( 'post_content' === $field ) {
				return serialize_blocks( $block['innerBlocks'] );
			}
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
		$binding = $block['attrs']['metadata']['contentModelBinding'] ?? array();

		foreach ( $binding as $attribute => $field ) {
			if ( 'post_content' === $field ) {
				continue;
			}

			if ( 'core/group' === $block['blockName'] ) {
				$acc[ $field ] = serialize_blocks( $block['innerBlocks'] );
				continue;
			}

			if ( 'content' === $attribute ) {
				// TODO: Use DOMDocument.
				$inner_html = trim( $block['innerHTML'] );
				$inner_html = preg_replace( '/^<p>|<\/p>$/', '', $inner_html );

				$acc[ $field ] = $inner_html;
				continue;
			}

			if ( isset( $block['attrs'][ $attribute ] ) ) {
				$acc[ $field ] = $block['attrs'][ $attribute ];
				continue;
			}

			$block_metadata   = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
			$block_attributes = $block_metadata->get_attributes();

			if ( 'attribute' === $block_attributes[ $attribute ]['source'] ) {
				$acc[ $field ] = _extract_attribute( $block_attributes[ $attribute ], $block['innerHTML'] );
			}

			// TODO: Support rich-text.
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
	if ( ! in_array( $post->post_type, get_content_model_slugs(), true ) ) {
		return;
	}

	$template = get_content_model_template( $post->post_type );

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
		$binding = $block['attrs']['metadata']['contentModelBinding'] ?? null;

		if ( is_null( $binding ) ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks[ $index ]['innerBlocks'] = hydrate_blocks_with_content( $blocks[ $index ]['innerBlocks'] );
			}

			continue;
		}

		foreach ( $binding as $attribute => $field ) {
			if ( 'post_content' === $field ) {
				$content = get_the_content();
			} else {
				$content = get_post_meta( get_the_ID(), $field, true );
			}

			// If can't find the corresponding content, do not try to inject it.
			if ( ! $content ) {
				continue;
			}

			// TODO: Check if source is rich-text!
			if ( 'content' === $attribute ) {
				$blocks[ $index ]['innerBlocks'] = parse_blocks( $content );

				$blocks[ $index ]['innerHTML']    = inject_content_into_block_markup( $content, $blocks[ $index ]['innerHTML'] );
				$blocks[ $index ]['innerContent'] = array( $blocks[ $index ]['innerHTML'] );

				continue;
			}

			$block_metadata   = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
			$block_attributes = $block_metadata->get_attributes();

			if ( ! isset( $block_attributes[ $attribute ]['source'] ) ) {
				$blocks[ $index ]['attrs'][ $attribute ] = $content;
				continue;
			}

			if ( 'attribute' === $block_attributes[ $attribute ]['source'] ) {
				$blocks[ $index ]['innerHTML']    = _replace_attribute( $block_attributes[ $attribute ], $content, $blocks[ $index ]['innerHTML'] );
				$blocks[ $index ]['innerContent'] = array( $blocks[ $index ]['innerHTML'] );
			}

			// TODO: Support rich-text.
		}
	}

	return $blocks;
}

/**
 * Get the template of a specific content model.
 *
 * @param string $content_model_slug The slug of the content model.
 */
function get_content_model_template( $content_model_slug ) {
	foreach ( get_registered_content_models() as $content_model ) {
		if ( $content_model->slug === $content_model_slug ) {
			return $content_model->template;
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
	$outer_tag = strtolower( $p->get_tag() );

	$p2 = new WP_HTML_Tag_Processor( "<{$outer_tag}>" );
	$p2->next_tag();
	foreach ( $p->get_attribute_names_with_prefix( '' ) ?? array() as $attribute ) {
		$p2->set_attribute( $attribute, $p->get_attribute( $attribute ) );
	}

	return $p2->get_updated_html() . $content . "</{$outer_tag}>";
}

/**
 * Get all register content model slugs.
 */
function get_content_model_slugs() {
	return array_map( fn( $content_model ) => $content_model->slug, get_registered_content_models() );
}

/**
 * Extract attribute value from the markup.
 *
 * @param array  $attribute_metadata The attribute metadata from the block.json file.
 * @param string $markup The markup.
 *
 * @return mixed|null The attribute value.
 */
function _extract_attribute( $attribute_metadata, $markup ) {
	$dom = new DOMDocument();
	$dom->loadHTML( $markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	$xpath = new DOMXPath( $dom );

	$matches = $xpath->query( '//' . $attribute_metadata['selector'] );

	foreach ( $matches as $match ) {
		if ( $match instanceof \DOMElement ) {
			return $match->getAttribute( $attribute_metadata['attribute'] );
		}
	}
}

/**
 * Replace attribute value in the markup.
 *
 * @param array  $attribute_metadata The attribute metadata from the block.json file.
 * @param mixed  $attribute_value The attribute value.
 * @param string $markup The markup.
 *
 * @return string The updated markup.
 */
function _replace_attribute( $attribute_metadata, $attribute_value, $markup ) {
	$dom = new DOMDocument();
	$dom->loadHTML( $markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	$xpath = new DOMXPath( $dom );

	$matches = $xpath->query( '//' . $attribute_metadata['selector'] );

	foreach ( $matches as $match ) {
		if ( $match instanceof \DOMElement ) {
			$match->setAttribute( $attribute_metadata['attribute'], $attribute_value );
		}
	}

	return $dom->saveHTML();
}

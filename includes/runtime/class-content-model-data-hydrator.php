<?php
/**
 * Manages the existing content models.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

/**
 * Manages the registered Content Models.
 */
class Content_Model_Data_Hydrator {
	/**
	 * The blocks from the entry.
	 *
	 * @var array
	 */
	private $blocks;

	/**
	 * Initializes the Content_Model_Data_Hydrator instance with the given blocks.
	 *
	 * @param array $blocks The blocks to initialize with.
	 * @return void
	 */
	public function __construct( $blocks ) {
		$this->blocks = $blocks;
	}

	/**
	 * Fills the bound attributes with content from the post.
	 *
	 * @param ?array $blocks The blocks.
	 *
	 * @return array The template blocks, filled with data.
	 */
	public function hydrate( $blocks = null ) {
		$blocks ??= $this->blocks;

		foreach ( $blocks as $index => $block ) {
			$content_model_block = new Content_Model_Block( $block );

			$blocks[ $index ]['attrs']['lock'] = array(
				'move'   => true,
				'remove' => true,
			);

			if ( empty( $content_model_block->get_bindings() ) ) {
				if ( ! empty( $block['innerBlocks'] ) ) {
					$blocks[ $index ]['innerBlocks'] = $this->hydrate( $blocks[ $index ]['innerBlocks'] );
				}

				continue;
			}

			foreach ( $content_model_block->get_bindings() as $attribute => $binding ) {
				$field = $binding['args']['key'];

				if ( 'post_content' === $field ) {
					$content = get_the_content();
				} else {
					$content = get_post_meta( get_the_ID(), $field, true );
				}

				// If can't find the corresponding content, do not try to inject it.
				if ( ! $content ) {
					continue;
				}

				// Inflate the stored blocks into Group.
				if ( 'core/group' === $block['blockName'] ) {
					self::inject_content_into_block( $content, $blocks[ $index ] );

					continue;
				}

				$block_metadata   = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
				$block_attributes = $block_metadata->get_attributes();
				$block_attribute  = $block_attributes[ $attribute ];

				if ( ! isset( $block_attribute['source'] ) ) {
					$blocks[ $index ]['attrs'][ $attribute ] = $content;
					continue;
				}

				$inner_html = self::replace_attribute( $block_attribute, $content, $blocks[ $index ]['innerHTML'] );

				$blocks[ $index ]['innerHTML']    = $inner_html;
				$blocks[ $index ]['innerContent'] = array( $inner_html );
			}

			$blocks[ $index ]['attrs'] = apply_filters( 'hydrate_block_attributes', $blocks[ $index ]['attrs'] );
		}

		return $blocks;
	}

	/**
	 * Adds the HTML and blocks (from post_content or post meta) within the block.
	 *
	 * @param string $content The content.
	 * @param array  $parsed_block The parsed block.
	 */
	public static function inject_content_into_block( $content, &$parsed_block ) {
		$p = new WP_HTML_Tag_Processor( $parsed_block['innerHTML'] );
		$p->next_tag();
		$outer_tag = strtolower( $p->get_tag() );

		$p2 = new WP_HTML_Tag_Processor( "<{$outer_tag}>" );
		$p2->next_tag();
		foreach ( $p->get_attribute_names_with_prefix( '' ) ?? array() as $attribute ) {
			$p2->set_attribute( $attribute, $p->get_attribute( $attribute ) );
		}

		$updated_inner_html = $p2->get_updated_html() . $content . "</{$outer_tag}>";

		$parsed_block['innerBlocks']  = parse_blocks( $content );
		$parsed_block['innerHTML']    = $updated_inner_html;
		$parsed_block['innerContent'] = array( $updated_inner_html );
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
	private static function replace_attribute( $attribute_metadata, $attribute_value, $markup ) {
		$dom = new DOMDocument();
		$dom->loadXML( $markup, LIBXML_NOXMLDECL );

		$xpath = new DOMXPath( $dom );

		$matches = $xpath->query( "//*[local-name()='" . $attribute_metadata['selector'] . "']" );

		foreach ( $matches as $match ) {
			if ( $match instanceof \DOMElement ) {
				if ( 'attribute' === $attribute_metadata['source'] ) {
					$attribute = $attribute_metadata['attribute'];
					$value     = $match->getAttribute( $attribute );

					if ( 'class' === $attribute ) {
						$value .= ' ' . $attribute_value;
					} else {
						$value = $attribute_value;
					}

					$match->setAttribute( $attribute, $value );
				} else {
					self::replace_node_inner_html( $match, $attribute_value );
				}
			}
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $dom->saveXML( $dom->documentElement, LIBXML_NOXMLDECL );
	}

	/**
	 * Replace node inner HTML.
	 *
	 * @param \DOMElement $node The HTML node.
	 * @param string      $html The desired inner HMTL.
	 *
	 * @return void
	 */
	private static function replace_node_inner_html( $node, $html ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$fragment = $node->ownerDocument->createDocumentFragment();
		$fragment->appendXML( $html );

		while ( $node->hasChildNodes() ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$node->removeChild( $node->firstChild );
		}

		$node->appendChild( $fragment );
	}
}

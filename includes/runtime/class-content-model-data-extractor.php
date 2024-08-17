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
class Content_Model_Data_Extractor {
	/**
	 * The blocks from the entry.
	 *
	 * @var array
	 */
	private $blocks;

	/**
	 * Initializes the Content_Model_Data_Extractor instance with the given blocks.
	 *
	 * @param array $blocks The blocks to initialize with.
	 * @return void
	 */
	public function __construct( $blocks ) {
		$this->blocks = $blocks;
	}

	/**
	 * Finds the post_content content area within blocks.
	 *
	 * @param ?array $blocks The blocks.
	 *
	 * @return string The post content markup.
	 */
	public function get_post_content( $blocks = null ) {
		$blocks ??= $this->blocks;

		foreach ( $blocks as $block ) {
			$content_model_block = new Content_Model_Block( $block );

			if ( 'post_content' === $content_model_block->get_binding( 'content' ) ) {
				return serialize_blocks( $block['innerBlocks'] );
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$result = $this->get_post_content( $block['innerBlocks'] );

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
	public function get_meta_fields( $blocks = null ) {
		$blocks ??= $this->blocks;

		$acc = array();

		foreach ( $blocks as $block ) {
			$content_model_block = new Content_Model_Block( $block );

			foreach ( $content_model_block->get_bindings() as $attribute => $field ) {
				// post_content is not a meta attribute.
				if ( 'post_content' === $field ) {
					continue;
				}

				// Serialize all inner blocks from Group.
				if ( 'core/group' === $block['blockName'] ) {
					$acc[ $field ] = serialize_blocks( $block['innerBlocks'] );
					continue;
				}

				// If the bound attribute is present in the block attributes, use that.
				if ( isset( $block['attrs'][ $attribute ] ) ) {
					$acc[ $field ] = $block['attrs'][ $attribute ];
				} else {
					// Extract meta field value from the block's markup.
					$meta_value_from_markup = $this->extract_attribute_value_from_block_markup( $block, $attribute );

					if ( $meta_value_from_markup ) {
						$acc[ $field ] = $meta_value_from_markup;
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$acc = array_merge( $acc, $this->get_meta_fields( $block['innerBlocks'] ) );
			}
		}

		return $acc;
	}

	/**
	 * Extracts the value of an attribute from the innerHTML of a block.
	 *
	 * @param array  $block The block.
	 * @param string $attribute The attribute name.
	 *
	 * @return mixed|null The value of the attribute, or null if not found.
	 */
	public static function extract_attribute_value_from_block_markup( $block, $attribute ) {
		$registered_block = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );

		if ( ! $registered_block ) {
			return null;
		}

		$allowed_attributes = $registered_block->get_attributes();

		if ( ! isset( $allowed_attributes[ $attribute ]['source'] ) ) {
			return null;
		}

		return self::extract_attribute( $allowed_attributes[ $attribute ], $block['innerHTML'] );
	}


	/**
	 * Extract attribute value from the markup.
	 *
	 * @param array  $attribute_metadata The attribute metadata from the block.json file.
	 * @param string $markup The markup.
	 *
	 * @return mixed|null The attribute value.
	 */
	public static function extract_attribute( $attribute_metadata, $markup ) {
		$dom = new DOMDocument();
		$dom->loadXML( $markup, LIBXML_NOXMLDECL );

		$xpath = new DOMXPath( $dom );

		$matches = $xpath->query( '//' . $attribute_metadata['selector'] );

		foreach ( $matches as $match ) {
			if ( $match instanceof \DOMElement ) {
				if ( 'attribute' === $attribute_metadata['source'] ) {
					return $match->getAttribute( $attribute_metadata['attribute'] );
				}

				return implode(
					'',
					array_map(
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						fn( $node ) => $node->ownerDocument->saveXML( $node ),
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						iterator_to_array( $match->childNodes ),
					)
				);
			}
		}

		return null;
	}
}

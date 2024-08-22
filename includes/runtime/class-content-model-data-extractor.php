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
	 * The bound blocks from the content model template.
	 *
	 * @var array
	 */
	private $bound_blocks;

	/**
	 * Initializes the Content_Model_Data_Extractor instance with the given blocks.
	 *
	 * @param array $blocks The blocks to initialize with.
	 * @param array $bound_blocks The bound blocks from the content model.
	 * @return void
	 */
	public function __construct( $blocks, $bound_blocks ) {
		$this->blocks       = $blocks;
		$this->bound_blocks = $bound_blocks;
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
			$content_model_block  = new Content_Model_Block( $block );
			$block_variation_name = $content_model_block->get_block_variation_name();

			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var Content_Model_Block|null $bound_block */
			$bound_block = $this->bound_blocks[ $block_variation_name ] ?? null;

			if ( $bound_block ) {
				$content_binding = $bound_block->get_binding( 'content' );

				if ( 'post_content' === $content_binding['args']['key'] ) {
					if ( ! empty( $block['innerBlocks'] ) ) {
						return serialize_blocks( $block['innerBlocks'] );
					}

					$meta_value_from_markup = $this->extract_attribute_value_from_block_markup( $block, 'content' );

					return $meta_value_from_markup;
				}
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
			$content_model_block  = new Content_Model_Block( $block );
			$block_variation_name = $content_model_block->get_block_variation_name();

			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var Content_Model_Block|null $bound_block */
			$bound_block = $this->bound_blocks[ $block_variation_name ] ?? null;

			if ( $bound_block && ! empty( $bound_block->get_bindings() ) ) {
				$bindings = $bound_block->get_bindings();

				foreach ( $bindings as $attribute => $binding ) {
					$field = $binding['args']['key'];

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

		$html_handler = new Content_Model_Html_Manipulator( $block['innerHTML'] );

		return $html_handler->extract_attribute( $allowed_attributes[ $attribute ] );
	}
}

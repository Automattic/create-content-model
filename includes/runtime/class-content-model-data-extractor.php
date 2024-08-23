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
	 * @var Content_Model_Block[]
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
	 * @return string|null The post content markup, or nothing if not found.
	 */
	public function get_post_content() {
		return content_model_block_walker(
			$this->blocks,
			function ( $block ) {
				$content_model_block  = new Content_Model_Block( $block );
				$block_variation_name = $content_model_block->get_block_variation_name();

				$bound_block = $this->bound_blocks[ $block_variation_name ] ?? null;

				if ( $bound_block ) {
					$content_binding = $bound_block->get_binding( 'content' );

					if ( 'post_content' === $content_binding['args']['key'] ) {
						return serialize_blocks( $block['innerBlocks'] );
					}
				}

				return $block;
			},
			false // Breadth-first because it's more likely that post content will be at the top level.
		);
	}

	/**
	 * Finds meta fields within blocks.
	 *
	 * @return array The meta fields key-value pair: key is the meta field name, and value is the markup.
	 */
	public function get_meta_fields() {
		$meta_fields = array();

		$aggregate_meta_fields = function ( $block ) use ( &$meta_fields ) {
			$content_model_block  = new Content_Model_Block( $block );
			$block_variation_name = $content_model_block->get_block_variation_name();

			$bound_block = $this->bound_blocks[ $block_variation_name ] ?? null;

			if ( ! $bound_block || empty( $bound_block->get_bindings() ) ) {
				return $block;
			}

			$bindings = $bound_block->get_bindings();

			foreach ( $bindings as $attribute => $binding ) {
				$field = $binding['args']['key'];

				// post_content is not a meta attribute.
				if ( 'post_content' === $field ) {
					continue;
				}

				// Serialize all inner blocks from Group.
				if ( 'core/group' === $block['blockName'] ) {
					$meta_fields[ $field ] = serialize_blocks( $block['innerBlocks'] );
					continue;
				}

				// If the bound attribute is present in the block attributes, use that.
				if ( isset( $block['attrs'][ $attribute ] ) ) {
					$meta_fields[ $field ] = $block['attrs'][ $attribute ];
					continue;
				}

				// Extract meta field value from the block's markup.
				$meta_value_from_markup = $this->extract_attribute_value_from_block_markup( $block, $attribute );

				if ( $meta_value_from_markup ) {
					$meta_fields[ $field ] = $meta_value_from_markup;
				}
			}

			return $block;
		};

		content_model_block_walker( $this->blocks, $aggregate_meta_fields );

		return $meta_fields;
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

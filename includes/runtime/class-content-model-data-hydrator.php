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
					$blocks[ $index ]['innerBlocks'] = parse_blocks( $content );

					$html_handler = new Content_Model_Html_Manipulator( $block['innerHTML'] );

					$block_attribute['source']   = 'rich-text';
					$block_attribute['selector'] = 'div';

					$blocks[ $index ]['innerHTML']    = $html_handler->replace_attribute( $block_attribute, $content );
					$blocks[ $index ]['innerContent'] = array( $blocks[ $index ]['innerHTML'] );

					continue;
				}

				$block_metadata   = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
				$block_attributes = $block_metadata->get_attributes();
				$block_attribute  = $block_attributes[ $attribute ];

				if ( ! isset( $block_attribute['source'] ) ) {
					$blocks[ $index ]['attrs'][ $attribute ] = $content;
					continue;
				}

				$html_handler = new Content_Model_Html_Manipulator( $block['innerHTML'] );

				$blocks[ $index ]['innerHTML']    = $html_handler->replace_attribute( $block_attribute, $content );
				$blocks[ $index ]['innerContent'] = array( $blocks[ $index ]['innerHTML'] );
			}

			$blocks[ $index ]['attrs'] = apply_filters( 'hydrate_block_attributes', $blocks[ $index ]['attrs'] );
		}

		return $blocks;
	}
}

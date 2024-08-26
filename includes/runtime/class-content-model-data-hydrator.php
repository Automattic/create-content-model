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
	 * Whether to strip attribute metadata.
	 *
	 * @var bool
	 */
	private $strip_attribute_metadata;

	/**
	 * Initializes the Content_Model_Data_Hydrator instance with the given blocks.
	 *
	 * @param array $blocks The blocks to initialize with.
	 * @param bool  $strip_attribute_metadata Whether to strip attribute metadata.
	 * @return void
	 */
	public function __construct( $blocks, $strip_attribute_metadata ) {
		$this->blocks = $blocks;

		$this->strip_attribute_metadata = $strip_attribute_metadata;
	}

	/**
	 * Fills the bound attributes with content from the post.
	 *
	 * @return array The template blocks, filled with data.
	 */
	public function hydrate() {
		return content_model_block_walker(
			$this->blocks,
			array( $this, 'hydrate_block' )
		);
	}

	/**
	 * Hydrates a block with information from the post.
	 *
	 * @param array $block The block.
	 *
	 * @return array The hydrated block.
	 */
	public function hydrate_block( $block ) {
		$block['attrs']['lock'] = array(
			'move'   => true,
			'remove' => true,
		);

		$content_model_block = new Content_Model_Block( $block );

		if ( 'core/group' !== $content_model_block->get_block_name() ) {
			return $block;
		}

		$binding = $content_model_block->get_binding( 'content' );

		if ( ! $binding ) {
			return $block;
		}

		$field = $binding['args']['key'];

		if ( 'post_content' === $field ) {
			$content = get_the_content();
		} else {
			$content = get_post_meta( get_the_ID(), $field, true );
		}

		// If can't find the content, do not try to inject it.
		if ( ! $content ) {
			return $block;
		}

		if ( $this->strip_attribute_metadata ) {
			$content = implode(
				'',
				array_map( fn( $block ) => render_block( $block ), parse_blocks( $content ) )
			);
		}

		$html_handler = new Content_Model_Html_Manipulator( $block['innerHTML'] );

		$block_attribute = $content_model_block->get_attribute_metadata( 'content' );

		$block['innerHTML']    = $html_handler->replace_attribute( $block_attribute, $content );
		$block['innerContent'] = array( $block['innerHTML'] );

		return $block;
	}
}

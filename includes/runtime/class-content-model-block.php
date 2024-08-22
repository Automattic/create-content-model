<?php
/**
 * Manages the existing content models.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

/**
 * Represents a block from a content model template.
 */
final class Content_Model_Block {
	public const BLOCK_VARIATION_NAME_ATTR = 'name';
	public const CONTENT_MODEL_SLUG_ATTR   = 'content_model_slug';

	/**
	 * The block name.
	 *
	 * @var string
	 */
	private $block_name = '';

	/**
	 * The registered block variation name.
	 *
	 * @var string
	 */
	private $block_variation_name = '';

	/**
	 * The registered block variation slug.
	 *
	 * @var string
	 */
	private $block_variation_slug = '';

	/**
	 * Associative array representing the bound attribute as the key and the post meta field as the value.
	 *
	 * @var array
	 */
	private $bindings = array();

	/**
	 * The content model associated with this block.
	 *
	 * @var ?Content_Model
	 */
	private ?Content_Model $content_model;

	/**
	 * Initializes the Content_Model_Block, extracting the bindings.
	 *
	 * @param array          $block The block instance to initialize with.
	 * @param ?Content_Model $content_model The content model associated with the block.
	 */
	public function __construct( array $block, Content_Model $content_model = null ) {
		$this->block_name    = $block['blockName'];
		$this->content_model = $content_model;

		$metadata = $block['attrs']['metadata'] ?? array();

		if ( empty( $metadata[ self::BLOCK_VARIATION_NAME_ATTR ] ) ) {
			return;
		}

		$this->block_variation_name = $metadata[ self::BLOCK_VARIATION_NAME_ATTR ];
		$this->block_variation_slug = sanitize_title_with_dashes( $this->block_variation_name );

		if ( ! $this->content_model && ! empty( $metadata[ self::CONTENT_MODEL_SLUG_ATTR ] ) ) {
			$content_model_slug = $metadata[ self::CONTENT_MODEL_SLUG_ATTR ];

			$this->content_model = Content_Model_Manager::get_instance()->get_content_model_by_slug( $content_model_slug );
		}

		$this->bindings = $metadata['bindings'] ?? array();

		/**
		 * If not instanciated directly by a content model, do not register hooks.
		 */
		if ( ! $content_model ) {
			return;
		}

		add_filter( 'get_block_type_variations', array( $this, 'register_block_variation' ), 10, 2 );

		add_filter( 'block_variation_attributes', array( $this, 'hydrate_block_variation_attributes' ), 10, 2 );

		if ( $this->should_render_group_variation() ) {
			add_filter( 'pre_render_block', array( $this, 'render_group_variation' ), 99, 2 );
		}
	}

	/**
	 * Retrieves the block variation name.
	 *
	 * @return string The block name.
	 */
	public function get_block_variation_name() {
		return $this->block_variation_name;
	}

	/**
	 * Retrieves the content model instance associated with this block.
	 *
	 * @return Content_Model The content model instance.
	 */
	public function get_content_model() {
		return $this->content_model;
	}

	/**
	 * Retrieves the underlying block name.
	 *
	 * @return string The block name.
	 */
	public function get_block_name() {
		return $this->block_name;
	}

	/**
	 * Retrieves the bindings for the Content_Model_Block instance.
	 *
	 * @return array The bindings for the block.
	 */
	public function get_bindings() {
		return $this->bindings;
	}

	/**
	 * Retrieves the value of a specific binding for the Content_Model_Block instance.
	 *
	 * @param string $binding_key The key of the binding to retrieve.
	 * @return mixed The value of the binding, or null if not found.
	 */
	public function get_binding( $binding_key ) {
		return $this->bindings[ $binding_key ] ?? null;
	}

	/**
	 * Register a block variations for this block.
	 *
	 * @param array         $variations The existing block variations.
	 * @param WP_Block_Type $block_type The block type.
	 */
	public function register_block_variation( $variations, $block_type ) {
		if ( $block_type->name !== $this->block_name || empty( $this->get_bindings() ) ) {
			return $variations;
		}

		$variation = array(
			'name'       => '__' . $this->block_variation_slug . '/' . $block_type->name,
			'title'      => $this->block_variation_name,
			'category'   => $this->content_model->slug . '-fields',
			'attributes' => array(
				'metadata' => array(
					self::BLOCK_VARIATION_NAME_ATTR => $this->block_variation_name,
					self::CONTENT_MODEL_SLUG_ATTR   => $this->content_model->slug,
				),
			),
		);

		if ( 'core/group' === $this->block_name ) {
			$content_binding = $this->get_binding( 'content' );

			if ( ! $content_binding ) {
				return $variations;
			}

			$variation['innerBlocks'] = array(
				array(
					'core/paragraph',
					array(
						'content' => $content_binding['args']['key'],
					),
				),
			);
		}

		$variation['attributes']['metadata']['bindings'] = $this->get_bindings();

		$variations[] = $variation;

		return $variations;
	}

	/**
	 * Determines if the current block should intercept Group rendering.
	 *
	 * @return bool Returns true if the block is a 'core/group' and has a 'content' binding, false otherwise.
	 */
	private function should_render_group_variation() {
		return 'core/group' === $this->block_name && null !== $this->get_binding( 'content' );
	}

	/**
	 * Replaces the variation attributes in the block with the ones from the content model template.
	 *
	 * @param array               $block_attributes The block attributes from the incoming block variation.
	 * @param Content_Model_Block $block_variation The incoming block variation.
	 *
	 * @return array The replaced attributes from the block variation.
	 */
	public function hydrate_block_variation_attributes( $block_attributes, $block_variation ) {
		if ( $block_variation->get_block_variation_name() !== $this->block_variation_name ) {
			return $block_attributes;
		}

		if ( $block_variation->get_content_model() !== $this->content_model ) {
			return $block_attributes;
		}

		$block_attributes['metadata'] = array_merge(
			$block_attributes['metadata'] ?? array(),
			array(
				self::BLOCK_VARIATION_NAME_ATTR => $this->block_variation_name,
				self::CONTENT_MODEL_SLUG_ATTR   => $this->content_model->slug,
				'bindings'                      => $this->get_bindings(),
			)
		);

		return $block_attributes;
	}

	/**
	 * Since there are no official bindings for group variations and its inner blocks,
	 * we need to inject the inner blocks and HTML ourselves.
	 *
	 * @param string|null $pre_render The pre-rendered content.
	 * @param array       $parsed_block The current parsed block.
	 *
	 * @return string|null The rendered block.
	 */
	public function render_group_variation( $pre_render, $parsed_block ) {
		$tentative_block = new Content_Model_Block( $parsed_block );

		if ( $tentative_block->block_name !== $this->block_name ) {
			return $pre_render;
		}

		$content_binding = $tentative_block->get_binding( 'content' );

		if ( ! $content_binding ) {
			return $pre_render;
		}

		$field = $content_binding['args']['key'];

		if ( 'post_content' === $field ) {
			$content = get_the_content();
		} else {
			$content = get_post_meta( get_the_ID(), $field, true );
		}

		if ( ! $content ) {
			return $pre_render;
		}

		$parsed_block['innerBlocks'] = parse_blocks( $content );

		$html_handler = new Content_Model_Html_Handler( $parsed_block['innerHTML'] );

		$block_attribute['source']   = 'rich-text';
		$block_attribute['selector'] = 'div';

		$parsed_block['innerHTML']    = $html_handler->replace_attribute( $block_attribute, $content );
		$parsed_block['innerContent'] = array( $parsed_block['innerHTML'] );

		remove_filter( 'pre_render_block', array( $this, 'render_group_variation' ), 99 );

		$rendered_group = render_block( $parsed_block );

		add_filter( 'pre_render_block', array( $this, 'render_group_variation' ), 99, 2 );

		return $rendered_group;
	}
}

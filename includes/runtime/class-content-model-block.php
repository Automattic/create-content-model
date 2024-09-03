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
	 * The raw block information.
	 *
	 * @var array
	 */
	private $raw_block;

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
		$this->raw_block     = $block;

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
		 * If not instantiated directly by a content model, do not register hooks.
		 */
		if ( ! $content_model ) {
			return;
		}

		add_filter( 'get_block_type_variations', array( $this, 'register_block_variation' ), 10, 2 );

		add_filter( 'pre_render_block', array( $this, 'render_group_variation' ), 99, 2 );
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
			'name'       => sanitize_title( $this->block_variation_name ),
			'title'      => $this->block_variation_name,
			'category'   => $this->content_model->slug . '-blocks',
			'attributes' => array_merge(
				$this->raw_block['attrs'],
				array(
					'metadata' => array(
						self::BLOCK_VARIATION_NAME_ATTR => $this->block_variation_name,
						self::CONTENT_MODEL_SLUG_ATTR   => $this->content_model->slug,
					),
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

		if ( $tentative_block->get_block_variation_name() !== $this->block_variation_name ) {
			return $pre_render;
		}

		if ( $tentative_block->get_block_name() !== 'core/group' ) {
			return $pre_render;
		}

		$hydrator = new Content_Model_Data_Hydrator( array( $parsed_block ), true );

		remove_filter( 'pre_render_block', array( $this, 'render_group_variation' ), 99 );

		// Accessing index zero because we've passed an array with one element above.
		$result = render_block( $hydrator->hydrate()[0] );

		add_filter( 'pre_render_block', array( $this, 'render_group_variation' ), 99, 2 );

		return $result;
	}

	/**
	 * Returns the metadata for an attribute.
	 *
	 * @param string $attribute_name The attribute.
	 *
	 * @return array The metadata.
	 */
	public function get_attribute_metadata( $attribute_name ) {
		$block_metadata   = WP_Block_Type_Registry::get_instance()->get_registered( $this->get_block_name() );
		$block_attributes = $block_metadata->get_attributes();

		if ( isset( $block_attributes[ $attribute_name ] ) ) {
			return $block_attributes[ $attribute_name ];
		}

		if ( 'core/group' === $this->get_block_name() && 'content' === $attribute_name ) {
			return array(
				'source'   => 'rich-text',
				'selector' => 'div',
			);
		}
	}

	/**
	 * Get the type of an attribute, according to our case handling.
	 *
	 * @param string $attribute_name The name of the attribute.
	 *
	 * @return string The type of the attribute.
	 */
	public function get_attribute_type( $attribute_name ) {
		$block_attribute = $this->get_attribute_metadata( $attribute_name );

		$block_attribute_type = $block_attribute['type'] ?? 'string';

		if ( ! in_array( $block_attribute_type, array( 'integer', 'number', 'boolean' ), true ) ) {
			$block_attribute_type = 'string';
		}

		return $block_attribute_type;
	}

	/**
	 * Get the default value from an attribute in the template.
	 *
	 * @param string $attribute_name The attribute name.
	 *
	 * @return mixed The default value.
	 */
	public function get_default_value_for_attribute( $attribute_name ) {
		$block_attribute = $this->raw_block['attrs'][ $attribute_name ] ?? null;

		if ( $block_attribute ) {
			return $block_attribute;
		}

		if ( 'content' === $attribute_name && 'core/group' === $this->block_name ) {
			return serialize_blocks( $this->raw_block['innerBlocks'] );
		}

		$attribute_metadata = $this->get_attribute_metadata( $attribute_name );

		if ( isset( $attribute_metadata['source'] ) ) {
			$html_manipulator = new Content_Model_Html_Manipulator( $this->raw_block['innerHTML'] );

			$attribute_value = $html_manipulator->extract_attribute( $attribute_metadata );

			if ( $attribute_value ) {
				return $attribute_value;
			}
		}
	}
}

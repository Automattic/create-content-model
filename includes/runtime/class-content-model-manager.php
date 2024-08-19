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
class Content_Model_Manager {
	public const BLOCK_NAME     = 'content-model/template';
	public const POST_TYPE_NAME = 'content_model';

	/**
	 * The instance.
	 *
	 * @var ?Content_Model_Manager
	 */
	private static $instance = null;

	/**
	 * Inits the singleton of the Content_Model_Manager class.
	 *
	 * @return Content_Model_Manager
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Holds the registered content models.
	 *
	 * @var Content_Model[]
	 */
	private $content_models = array();

	/**
	 * Initializes the Content_Model_Manager instance.
	 *
	 * @return void
	 */
	private function __construct() {
		$this->register_content_models();
		$this->register_content_model_template_block();

		add_filter( 'get_block_templates', array( $this, 'hydrate_block_variations_within_templates' ) );
	}

	/**
	 * Retrieves the registered content models.
	 *
	 * @return Content_Model[] An array of registered content models.
	 */
	public function get_content_models() {
		return $this->content_models;
	}

	/**
	 * Retrieves a content model by its slug.
	 *
	 * @param string $slug The slug of the content model to retrieve.
	 * @return Content_Model|null The content model with the matching slug, or null if not found.
	 */
	public function get_content_model_by_slug( $slug ) {
		foreach ( $this->content_models as $content_model ) {
			if ( $slug === $content_model->slug ) {
				return $content_model;
			}
		}

		return null;
	}

	/**
	 * Registers all content models.
	 *
	 * @return void
	 */
	private function register_content_models() {
		$content_model_names = $this->get_content_models_from_database();

		foreach ( $content_model_names as $content_model_name ) {
			$this->content_models[] = new Content_Model( $content_model_name );
		}
	}

	/**
	 * Retrieves the list of registered content models.
	 *
	 * @return WP_Post[] An array of WP_Post objects representing the registered content models.
	 */
	private function get_content_models_from_database() {
		return get_posts( array( 'post_type' => self::POST_TYPE_NAME ) );
	}

	/**
	 * Registers a content model template block.
	 *
	 * This function registers a block type for the content model template and enqueues the necessary scripts for the block editor.
	 *
	 * @return void
	 */
	private function register_content_model_template_block() {
		$args = array(
			'api_version'     => 1,
			'title'           => 'Content model template',
			'attributes'      => array(),
			'description'     => __( 'Template for the content model.' ),
			'category'        => 'text',
			'render_callback' => function () {
				global $post;

				if ( empty( $post ) ) {
					return __( 'This will render the content model template for the current content model.' );
				}

				$content_model = $this->get_content_model_by_slug( $post->post_type );
				$hydrator = new Content_Model_Data_Hydrator( $content_model->template );
				$hydrated_blocks = $hydrator->hydrate();

				return implode( '', array_map( 'render_block', $hydrated_blocks ) );
			},
		);

		register_block_type( self::BLOCK_NAME, $args );

		add_action(
			'enqueue_block_editor_assets',
			function () {
				wp_enqueue_script(
					Content_Model_Manager::BLOCK_NAME,
					CONTENT_MODEL_PLUGIN_URL . '/includes/runtime/type-template-inserter.js',
					array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-i18n' ),
					'v1',
					true
				);

				wp_add_inline_script( Content_Model_Manager::BLOCK_NAME, 'window.BLOCK_NAME = "' . Content_Model_Manager::BLOCK_NAME . '";', 'before' );
			}
		);
	}

	/**
	 * Go through the templates and hydrate the block variations.
	 *
	 * @param WP_Block_Template[] $templates The parsed templates.
	 *
	 * @return WP_Block_Template[] The hydrated templates.
	 */
	public function hydrate_block_variations_within_templates( $templates ) {
		foreach ( $templates as $template ) {
			$blocks = parse_blocks( wp_unslash( $template->content ) );
			$blocks = $this->hydrate_block_variations( $blocks );

			$template->content = serialize_blocks( $blocks );
		}

		return $templates;
	}

	/**
	 * Recursively hydrates (i.e., replaces the bound attributes from the block variation
	 * defined in the CPT template) block variations.
	 *
	 * @param array $blocks The blocks from the front-end template to hydrate.
	 *
	 * @return array The hydrated blocks.
	 */
	private function hydrate_block_variations( $blocks ) {
		foreach ( $blocks as $index => $block ) {
			if ( $block['innerBlocks'] ) {
				$blocks[ $index ]['innerBlocks'] = $this->hydrate_block_variations( $blocks[ $index ]['innerBlocks'] );
			}

			$tentative_block = new Content_Model_Block( $blocks[ $index ] );

			if ( ! empty( $tentative_block->get_block_variation_name() ) ) {
				$blocks[ $index ]['attrs'] = apply_filters(
					'block_variation_attributes',
					$blocks[ $index ]['attrs'],
					$tentative_block->get_block_variation_name()
				);
			}
		}

		return $blocks;
	}
}

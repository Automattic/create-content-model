<?php
/**
 * Adds the Content Model manager to the sidebar.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

/**
 * Loads the Content Models post type for managing models.
 */
class Content_Model_Loader {
	private const BINDINGS_KEY = 'contentModelBindings';

	/**
	 * The instance.
	 *
	 * @var ?Content_Model_Loader
	 */
	private static $instance = null;


	/**
	 * Inits the singleton of the Content_Model_Loader class.
	 *
	 * @return Content_Model_Loader
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the Content_Model_Loader class.
	 *
	 * Checks if the current user has the capability to manage options.
	 * If they do, it registers the post type and enqueues the manager scripts.
	 *
	 * @return void
	 */
	private function __construct() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->register_post_type();

		add_action( 'enqueue_block_editor_assets', array( $this, 'maybe_enqueue_scripts' ) );

		add_action( 'save_post', array( $this, 'map_template_to_bindings_api_signature' ), 99, 2 );

		add_action( 'save_post', array( $this, 'flush_rewrite_rules_on_slug_change' ), 99, 2 );

		/**
		 * We need two different hooks here because the Editor and the front-end read from different sources.
		 *
		 * The Editor reads the whole post, while the front-end reads only the post content.
		 */
		add_action( 'the_post', array( $this, 'map_template_to_content_model_editor_signature' ) );

		/**
		 * Update title placeholder to be more suitable for creating a new model.
		 */
		add_filter( 'enter_title_here', array( $this, 'set_title_placeholder' ), 10, 2 );
	}

	/**
	 * Registers the post type for the content models.
	 *
	 * @return void
	 */
	private function register_post_type() {
		register_post_type(
			Content_Model_Manager::POST_TYPE_NAME,
			array(
				'labels'             => array(
					'name'                  => __( 'Content Models' ),
					'singular_name'         => __( 'Content Model' ),
					'menu_name'             => __( 'Content Models' ),
					'all_items'             => __( 'All Content Models' ),
					'add_new'               => __( 'Add New Model' ),
					'add_new_item'          => __( 'Add New Content Model' ),
					'edit_item'             => __( 'Edit Content Model' ),
					'new_item'              => __( 'New Content Model' ),
					'view_item'             => __( 'View Content Model' ),
					'search_items'          => __( 'Search Content Models' ),
					'not_found'             => __( 'No content models found' ),
					'not_found_in_trash'    => __( 'No content models found in trash' ),
					'parent_item_colon'     => __( 'Parent Content Model:' ),
					'featured_image'        => __( 'Featured Image' ),
					'set_featured_image'    => __( 'Set featured image' ),
					'remove_featured_image' => __( 'Remove featured image' ),
					'use_featured_image'    => __( 'Use as featured image' ),
					'archives'              => __( 'Content Model archives' ),
					'insert_into_item'      => __( 'Insert into content model' ),
					'uploaded_to_this_item' => __( 'Uploaded to this content model' ),
					'filter_items_list'     => __( 'Filter content models list' ),
					'items_list_navigation' => __( 'Content models list navigation' ),
					'items_list'            => __( 'Content models list' ),
					'attributes'            => __( 'Content Model Attributes' ),
				),
				'public'             => true,
				'publicly_queryable' => false,
				'menu_position'      => 60,
				'menu_icon'          => 'dashicons-edit',
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'supports'           => array( 'title', 'editor', 'custom-fields' ),
				'template'           => array(
					array(
						'core/paragraph',
						array( 'placeholder' => __( 'Start building your model' ) ),
					),
				),
			)
		);

		register_post_meta(
			Content_Model_Manager::POST_TYPE_NAME,
			'fields',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => '',
				'default'           => '[]',
				'show_in_rest'      => array(
					'schema ' => array(
						'type' => 'string',
					),
				),
			)
		);

		register_post_meta(
			Content_Model_Manager::POST_TYPE_NAME,
			'blocks',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => '',
				'default'           => '[]',
				'show_in_rest'      => array(
					'schema ' => array(
						'type' => 'string',
					),
				),
			)
		);

		$cpt_fields = array(
			'plural_label' => array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
			),
			'icon'         => array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'default'      => 'admin-post',
			),
		);

		foreach ( $cpt_fields as $field_name => $field_args ) {
			register_post_meta(
				Content_Model_Manager::POST_TYPE_NAME,
				$field_name,
				$field_args
			);
		}
	}

	/**
	 * Enqueue the helper scripts if opening the content model manager.
	 *
	 * @return void
	 */
	public function maybe_enqueue_scripts() {
		global $post;

		if ( ! $post || Content_Model_Manager::POST_TYPE_NAME !== $post->post_type ) {
			return;
		}

		$asset_file = include CONTENT_MODEL_PLUGIN_PATH . '/includes/manager/dist/manager.asset.php';

		wp_enqueue_script(
			'content-model/manager',
			CONTENT_MODEL_PLUGIN_URL . '/includes/manager/dist/manager.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_localize_script(
			'content-model/manager',
			'contentModelData',
			array(
				'BINDINGS_KEY'              => self::BINDINGS_KEY,
				'BLOCK_VARIATION_NAME_ATTR' => Content_Model_Block::BLOCK_VARIATION_NAME_ATTR,
				'POST_TYPE_NAME'            => Content_Model_Manager::POST_TYPE_NAME,
			)
		);
	}

	/**
	 * Maps our bindings to the bindings API signature.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post.
	 */
	public function map_template_to_bindings_api_signature( $post_id, $post ) {
		if ( Content_Model_Manager::POST_TYPE_NAME !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		remove_action( 'save_post', array( $this, 'map_template_to_bindings_api_signature' ), 99 );

		$blocks = parse_blocks( wp_unslash( $post->post_content ) );
		$blocks = content_model_block_walker( $blocks, array( $this, 'map_block_to_bindings_api_signature' ) );
		$blocks = serialize_blocks( $blocks );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $blocks,
			)
		);

		add_action( 'save_post', array( $this, 'map_template_to_bindings_api_signature' ), 99, 2 );
	}

	/**
	 * Maps bindings from our signature to a language the bindings API can understand. This is necessary because in
	 * content editing mode, you should be able to override the bound attribute's values.
	 *
	 * @param array $block The blocks from the template.
	 *
	 * @return array $block The blocks from the template.
	 */
	public static function map_block_to_bindings_api_signature( $block ) {
		$existing_bindings = $block['attrs']['metadata'][ self::BINDINGS_KEY ] ?? array();

		if ( empty( $existing_bindings ) ) {
			return $block;
		}

		$block['attrs']['metadata']['bindings'] = array();

		foreach ( $existing_bindings as $attribute => $field ) {
			$block['attrs']['metadata']['bindings'][ $attribute ] = array(
				'source' => 'post_content' === $field ? 'core/post-content' : 'core/post-meta',
				'args'   => array( 'key' => $field ),
			);
		}

		unset( $block['attrs']['metadata'][ self::BINDINGS_KEY ] );

		return $block;
	}

	/**
	 * In the editor, display the template and fill it with the data.
	 *
	 * @param WP_Post $post The current post.
	 */
	public function map_template_to_content_model_editor_signature( $post ) {
		if ( Content_Model_Manager::POST_TYPE_NAME !== $post->post_type ) {
			return;
		}

		$blocks = parse_blocks( wp_unslash( $post->post_content ) );
		$blocks = content_model_block_walker( $blocks, array( $this, 'map_block_to_content_model_editor_signature' ) );
		$blocks = serialize_blocks( $blocks );

		$post->post_content = $blocks;
	}

	/**
	 * Maps bindings from the bindings API signature to ours. This is necessary because in
	 * content editing mode, you should be able to override the bound attribute's values.
	 *
	 * @param array $block The block from the template.
	 *
	 * @return array $block The block from the template.
	 */
	public static function map_block_to_content_model_editor_signature( $block ) {
		$existing_bindings = $block['attrs']['metadata']['bindings'] ?? array();

		if ( empty( $existing_bindings ) ) {
			return $block;
		}

		$block['attrs']['metadata'][ self::BINDINGS_KEY ] = array();

		foreach ( $existing_bindings as $attribute => $binding ) {
			$block['attrs']['metadata'][ self::BINDINGS_KEY ][ $attribute ] = $binding['args']['key'];
		}

		unset( $block['attrs']['metadata']['bindings'] );

		return $block;
	}


	/**
	 * Flushes the rewrite rules when the slug of a content model changes.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post.
	 */
	public function flush_rewrite_rules_on_slug_change( $post_id, $post ) {
		if ( Content_Model_Manager::POST_TYPE_NAME !== $post->post_type ) {
			return;
		}

		flush_rewrite_rules();
	}

	/**
	 * Sets the title placeholder for the Content Model post type.
	 *
	 * @param string  $title The default title placeholder.
	 * @param WP_Post $post  The current post object.
	 * @return string The modified title placeholder.
	 */
	public function set_title_placeholder( $title, $post ) {
		if ( Content_Model_Manager::POST_TYPE_NAME === $post->post_type ) {
			return __( 'Add model name' );
		}
		return $title;
	}
}

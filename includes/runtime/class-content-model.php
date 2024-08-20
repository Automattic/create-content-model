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
final class Content_Model {
	/**
	 * The slug of the content model.
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * The title of the content model.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * The parsed template (i.e., array of blocks) of the content model.
	 *
	 * @var array
	 */
	public $template = array();

	/**
	 * Holds the bound blocks in the content model.
	 *
	 * @var Content_Model_Block[]
	 */
	public $blocks = array();


	/**
	 * Holds the fields of the content model.
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Initializes the Content_Model instance with the given WP_Post object.
	 *
	 * @param WP_Post $content_model_post The WP_Post object representing the content model.
	 * @return void
	 */
	public function __construct( WP_Post $content_model_post ) {
		$this->slug     = $content_model_post->post_name;
		$this->title    = $content_model_post->post_title;
		$this->template = parse_blocks( $content_model_post->post_content );

		$this->register_post_type();

		// TODO: Not load this eagerly.
		$this->blocks = $this->inflate_template_blocks( $this->template );
		$this->fields = json_decode( get_post_meta( $content_model_post->ID, 'fields', true ), true );
		$this->register_meta_fields();
		$this->maybe_enqueue_the_fields_ui();

		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );

		add_action( 'save_post', array( $this, 'extract_fields_from_blocks' ), 99, 2 );
		add_action( 'the_post', array( $this, 'hydrate_template_with_content' ) );

		add_filter( 'get_post_metadata', array( $this, 'cast_meta_field_types' ), 10, 3 );
	}

	/**
	 * Registers the custom post type for the content model.
	 *
	 * @return void
	 */
	private function register_post_type() {
		register_post_type(
			$this->slug,
			array(
				'label'        => $this->title,
				'public'       => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'icon'         => 'dashicons-admin-site',
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
			)
		);
	}

	/**
	 * Recursively inflates (i.e., maps the block into Content_Model_Block) the blocks.
	 *
	 * @param array $blocks The template blocks to inflate.
	 * @return Content_Model_Block[] The Content_Model_Block instances.
	 */
	private function inflate_template_blocks( $blocks ) {
		$acc = array();

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$acc = array_merge( $acc, $this->inflate_template_blocks( $block['innerBlocks'] ) );
			}

			$content_model_block = new Content_Model_Block( $block, $this );

			if ( empty( $content_model_block->get_bindings() ) ) {
				continue;
			}

			$acc[] = $content_model_block;
		}

		return $acc;
	}

	/**
	 * Registers meta fields for the content model.
	 *
	 * @return void
	 */
	private function register_meta_fields() {
		foreach ( $this->blocks as $block ) {
			foreach ( $block->get_bindings() as $attribute => $meta_field ) {
				if ( 'post_content' === $meta_field ) {
					continue;
				}

				$block_metadata       = WP_Block_Type_Registry::get_instance()->get_registered( $block->get_block_name() );
				$block_attributes     = $block_metadata->get_attributes();
				$block_attribute_type = $block_attributes[ $attribute ]['type'] ?? 'string';

				register_post_meta(
					$this->slug,
					$meta_field,
					array(
						'show_in_rest' => true,
						'single'       => true,
						'type'         => $block_attribute_type,
					)
				);
			}
		}

		if ( ! empty( $this->fields ) ) {
			foreach ( $this->fields as $field ) {
				do_action( 'qm/debug', $field );
				register_post_meta(
					$this->slug,
					$field['slug'],
					array(
						'description'  => $field['description'],
						'show_in_rest' => true,
						'single'       => true,
						'type'         => 'string', // todo: support other types.
						'default'      => $field['default'] ?? '',
					)
				);

			}
		}
	}

	/**
	 * Retrieves a list of meta fields registered for the content model.
	 *
	 * @return array An array of registered meta field keys.
	 */
	public function get_meta_fields() {
		$registered_meta_fields = get_registered_meta_keys( 'post', $this->slug );

		$result = array();

		foreach ( $registered_meta_fields as $meta_field => $meta_field_data ) {
			$result[] = array(
				'slug' => $meta_field,
				'type' => $meta_field_data['type'],
			);
		}

		return $result;
	}

	/**
	 * Casts meta field types for the content model. The values are saved as strings,
	 * so we need to cast them back to their original type. Not sure why WordPress doesn't
	 * do that already.
	 *
	 * @param mixed  $value The value coming from the database.
	 * @param int    $object_id The post ID.
	 * @param string $meta_key The meta key.
	 *
	 * @return mixed The casted value, if applicable. The original value otherwise.
	 */
	public function cast_meta_field_types( $value, $object_id, $meta_key ) {
		$meta_field_type = get_registered_meta_keys( 'post', $this->slug )[ $meta_key ]['type'] ?? null;

		if ( ! $meta_field_type ) {
			return $value;
		}

		if ( 'integer' === $meta_field_type ) {
			return (int) $value;
		}

		if ( 'number' === $meta_field_type ) {
			return (float) $value;
		}

		if ( 'boolean' === $meta_field_type ) {
			return (bool) $value;
		}

		return $value;
	}

	/**
	 * Register the block category, which will be used by Content_Model_Block to group block variations.
	 *
	 * @param array $categories The existing block categories.
	 */
	public function register_block_category( $categories ) {
		$categories[] = array(
			'slug'  => $this->slug . '-fields',
			// translators: %s is content model name.
			'title' => sprintf( __( '%s fields' ), ucwords( $this->title ) ),
		);

		return $categories;
	}

	/**
	 * Finds the post_content content area within blocks.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post.
	 */
	public function extract_fields_from_blocks( $post_id, $post ) {
		if ( $post->post_type !== $this->slug || 'publish' !== $post->post_status ) {
			return;
		}

		remove_action( 'save_post', array( $this, 'extract_fields_from_blocks' ), 99 );

		$blocks = parse_blocks( wp_unslash( $post->post_content ) );

		$data_extractor = new Content_Model_Data_Extractor( $blocks );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $data_extractor->get_post_content() ?? '',
				'meta_input'   => $data_extractor->get_meta_fields(),
			)
		);

		add_action( 'save_post', array( $this, 'extract_fields_from_blocks' ), 99, 2 );
	}

	/**
	 * In the editor, display the template and fill it with the data.
	 *
	 * @param WP_Post $post The current post.
	 */
	public function hydrate_template_with_content( $post ) {
		if ( $this->slug !== $post->post_type ) {
			return;
		}

		$data_hydrator = new Content_Model_Data_Hydrator( $this->template );

		$post->post_content = serialize_blocks( $data_hydrator->hydrate() );
	}


		/**
		 * Conditionally enqueues the fields UI script for the block editor.
		 *
		 * Checks if the current post is of the correct type before enqueueing the script.
		 *
		 * @return void
		 */
	private function maybe_enqueue_the_fields_ui() {
		add_action(
			'enqueue_block_editor_assets',
			function () {
				global $post;

				if ( ! $post || $this->slug !== $post->post_type ) {
					return;
				}

				$asset_file = include CONTENT_MODEL_PLUGIN_PATH . 'build/runtime/fields-ui.asset.php';

				wp_register_script(
					'data-types/fields-ui',
					CONTENT_MODEL_PLUGIN_URL . '/build/runtime/fields-ui.js',
					$asset_file['dependencies'],
					$asset_file['version'],
					true
				);

				wp_localize_script(
					'data-types/fields-ui',
					'contentModelFields',
					array(
						'postType' => $this->slug,
						'fields'   => $this->fields,
					)
				);

				wp_enqueue_script( 'data-types/fields-ui' );
			}
		);
	}
}

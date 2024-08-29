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
	 * A reverse map of meta keys, with the values being
	 * the bound block and which attribute the meta key is bound to.
	 *
	 * @var array
	 */
	private $bound_meta_keys = array();

	/**
	 * Holds the fields of the content model.
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * The ID of the content model post.
	 *
	 * @var int
	 */
	private $post_id;

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
		$this->post_id  = $content_model_post->ID;

		$this->register_post_type();

		// TODO: Not load this eagerly.
		$this->blocks = $this->inflate_template_blocks( $this->template );
		$this->fields = json_decode( get_post_meta( $content_model_post->ID, 'fields', true ), true );
		$this->register_meta_fields();
		$this->maybe_enqueue_the_fields_ui();
		$this->maybe_enqueue_bound_group_extractor();
		$this->maybe_enqueue_content_locking();

		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );

		add_filter( 'rest_request_before_callbacks', array( $this, 'remove_default_meta_keys_on_save' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'fill_empty_meta_keys_with_default_values' ), 10, 3 );

		add_action( 'rest_after_insert_' . $this->slug, array( $this, 'extract_post_content_from_blocks' ), 99, 1 );

		/**
		 * We need two different hooks here because the Editor and the front-end read from different sources.
		 *
		 * The Editor reads the whole post, while the front-end reads only the post content.
		 */
		add_action( 'the_post', array( $this, 'hydrate_bound_groups' ) );
		add_filter( 'the_content', array( $this, 'swap_post_content_with_hydrated_template' ) );

		add_filter( 'get_post_metadata', array( $this, 'cast_meta_field_types' ), 10, 3 );
	}

	/**
	 * Registers the custom post type for the content model.
	 *
	 * @return void
	 */
	private function register_post_type() {
		$singular_name = $this->title;

		$plural_name   = get_post_meta( $this->post_id, 'plural_label', true );
		$plural_name ??= $singular_name . 's';

		$labels = array(
			'name'               => $plural_name,
			'singular_name'      => $singular_name,
			'menu_name'          => $plural_name,
			// translators: %s is the plural name of the post type.
			'all_items'          => sprintf( __( 'All %s' ), $plural_name ),
			// translators: %s is the singular name of the post type.
			'add_new'            => sprintf( __( 'Add New %s' ), $singular_name ),
			// translators: %s is the singular name of the post type.
			'add_new_item'       => sprintf( __( 'Add New %s' ), $singular_name ),
			// translators: %s is the singular name of the post type.
			'edit_item'          => sprintf( __( 'Edit %s' ), $singular_name ),
			// translators: %s is the singular name of the post type.
			'new_item'           => sprintf( __( 'New %s' ), $singular_name ),
			// translators: %s is the singular name of the post type.
			'view_item'          => sprintf( __( 'View %s' ), $singular_name ),
			// translators: %s is the plural name of the post type.
			'search_items'       => sprintf( __( 'Search %s' ), $plural_name ),
			// translators: %s is the plural name of the post type.
			'not_found'          => sprintf( __( 'No %s found' ), $plural_name ),
			// translators: %s is the plural name of the post type.
			'not_found_in_trash' => sprintf( __( 'No %s found in trash' ), $plural_name ),
		);

		$icon = get_post_meta( $this->post_id, 'icon', true ) ?? 'admin-site';
		$icon = str_replace( 'dashicons-', '', $icon );

		register_post_type(
			$this->slug,
			array(
				'labels'       => $labels,
				'public'       => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'menu_icon'    => "dashicons-$icon",
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

		content_model_block_walker(
			$blocks,
			function ( $block ) use ( &$acc ) {
				$content_model_block = new Content_Model_Block( $block, $this );

				if ( empty( $content_model_block->get_bindings() ) ) {
					return $block;
				}

				$acc[ $content_model_block->get_block_variation_name() ] = $content_model_block;

				return $block;
			}
		);

		return $acc;
	}

	/**
	 * Registers meta fields for the content model.
	 *
	 * @return void
	 */
	private function register_meta_fields() {

		if ( ! empty( $this->fields ) ) {
			foreach ( $this->fields as $field ) {
				if ( strpos( $field['type'], 'core' ) !== false ) {
					continue;
				}
				register_post_meta(
					$this->slug,
					$field['slug'],
					array(
						'description'  => $field['description'],
						'show_in_rest' => true,
						'single'       => true,
						'type'         => 'string', // todo: support other types.
						'default'      => $field['default'] ?? $field['slug'],
					)
				);
			}
		}

		foreach ( $this->blocks as $block ) {
			foreach ( $block->get_bindings() as $attribute_name => $binding ) {
				$field = $binding['args']['key'];

				if ( 'post_content' === $field ) {
					continue;
				}

				$this->bound_meta_keys[ $field ] = (object) array(
					'block'          => $block,
					'attribute_name' => $attribute_name,
				);

				$args = array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => $block->get_attribute_type( $attribute_name ),
				);

				$default_value = $block->get_default_value_for_attribute( $attribute_name );

				if ( ! empty( $default_value ) ) {
					$args['default'] = $default_value;
				}

				register_post_meta(
					$this->slug,
					$field,
					$args
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
				'slug'        => $meta_field,
				'type'        => $meta_field_data['type'],
				'description' => $meta_field_data['description'],
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
	 * @param WP_Post $post The post.
	 */
	public function extract_post_content_from_blocks( $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$blocks = parse_blocks( wp_unslash( $post->post_content ) );

		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => self::get_post_content( $blocks ) ?? '',
			)
		);
	}

	/**
	 * Intercepts the saving request and removes the meta keys with default values.
	 *
	 * @param WP_HTTP_Response|null $response The response.
	 * @param WP_REST_Server        $server   Route handler used for the request.
	 * @param WP_REST_Request       $request  The request.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function remove_default_meta_keys_on_save( $response, $server, $request ) {
		$is_upserting          = in_array( $request->get_method(), array( 'POST', 'PUT' ), true );
		$is_touching_post_type = str_starts_with( $request->get_route(), '/wp/v2/' . $this->slug );

		if ( $is_upserting && $is_touching_post_type ) {
			$meta = $request->get_param( 'meta' ) ?? array();

			foreach ( $meta as $key => $value ) {
				$bound_meta_key = $this->bound_meta_keys[ $key ] ?? null;

				if ( $bound_meta_key ) {
					$fallback_value = $bound_meta_key->block->get_fallback_value_for_attribute( $bound_meta_key->attribute_name );

					if ( $value === $fallback_value ) {
						unset( $meta[ $key ] );
						delete_post_meta( $request->get_param( 'id' ), $key );
					}
				}
			}

			$request->set_param( 'meta', $meta );
		}

		return $response;
	}

	/**
	 * Intercepts the response and fills the empty meta keys with default values.
	 *
	 * @param WP_HTTP_Response $result The response.
	 * @param WP_REST_Server   $server The server.
	 * @param WP_REST_Request  $request The request.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function fill_empty_meta_keys_with_default_values( $result, $server, $request ) {
		$is_allowed_method     = in_array( $request->get_method(), array( 'GET', 'POST', 'PUT' ), true );
		$is_touching_post_type = str_starts_with( $request->get_route(), '/wp/v2/' . $this->slug );

		if ( $is_allowed_method && $is_touching_post_type ) {
			$data = $result->get_data();

			$data['meta'] ??= array();

			foreach ( $data['meta'] as $key => $value ) {
				$bound_meta_key = $this->bound_meta_keys[ $key ] ?? null;

				if ( empty( $value ) && $bound_meta_key ) {
					$fallback_value = $bound_meta_key->block->get_fallback_value_for_attribute( $bound_meta_key->attribute_name );

					$data['meta'][ $key ] = $fallback_value;
				}
			}

			$result->set_data( $data );
		}

		return $result;
	}
	/**
	 * Extracts the post content from the blocks.
	 *
	 * @param array $blocks The blocks.
	 *
	 * @return string The post content.
	 */
	private static function get_post_content( $blocks ) {
		$post_content = content_model_block_walker(
			$blocks,
			function ( $block ) {
				if ( 'core/group' !== $block['blockName'] ) {
					return $block;
				}

				$content_model_block = new Content_Model_Block( $block );
				$content_binding     = $content_model_block->get_binding( 'content' );

				if ( $content_binding && 'post_content' === $content_binding['args']['key'] ) {
						return serialize_blocks( $block['innerBlocks'] );
				}

				return $block;
			},
			false // Breadth-first because it's more likely that post content will be at the top level.
		);

		if ( ! is_string( $post_content ) ) {
			return null;
		}

		return $post_content;
	}

	/**
	 * In the editor, display the template and fill bound Groups with data.
	 * Blocks using the supported Bindings API attributes will be filled automatically.
	 *
	 * @param WP_Post $post The current post.
	 */
	public function hydrate_bound_groups( $post ) {
		if ( $this->slug !== $post->post_type ) {
			return;
		}

		$data_hydrator = new Content_Model_Data_Hydrator( $this->template, false );

		$post->post_content = serialize_blocks( $data_hydrator->hydrate() );
	}

	/**
	 * In the front-end, swap the post_content with the hydrated template.
	 *
	 * @param string $post_content The current post content.
	 */
	public function swap_post_content_with_hydrated_template( $post_content ) {
		global $post;

		if ( $this->slug !== $post->post_type ) {
			return $post_content;
		}

		return implode( '', array_map( fn( $block ) => render_block( $block ), $this->template ) );
	}

	/**
	 * When you use the Bindings API, the Editor automatically extracts bound attributes as post meta.
	 * But because we're binding to the inner blocks of Groups (and not an attribute),
	 * we need to manually extract it.
	 *
	 * @return void
	 */
	private function maybe_enqueue_bound_group_extractor() {
		add_action(
			'enqueue_block_editor_assets',
			function () {
				global $post;

				if ( ! $post || $this->slug !== $post->post_type ) {
					return;
				}

				$asset_file = include CONTENT_MODEL_PLUGIN_PATH . 'includes/runtime/dist/bound-group-extractor.asset.php';

				wp_register_script(
					'data-types/bound-group-extractor',
					CONTENT_MODEL_PLUGIN_URL . '/includes/runtime/dist/bound-group-extractor.js',
					$asset_file['dependencies'],
					$asset_file['version'],
					true
				);

				wp_localize_script(
					'data-types/bound-group-extractor',
					'contentModelFields',
					array(
						'postType' => $this->slug,
						'fields'   => $this->fields,
					)
				);

				wp_enqueue_script( 'data-types/bound-group-extractor' );
			}
		);
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

				$asset_file = include CONTENT_MODEL_PLUGIN_PATH . 'includes/runtime/dist/fields-ui.asset.php';

				wp_register_script(
					'data-types/fields-ui',
					CONTENT_MODEL_PLUGIN_URL . '/includes/runtime/dist/fields-ui.js',
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


	/**
	 * Conditionally enqueues the fields UI script for the block editor.
	 *
	 * Checks if the current post is of the correct type before enqueueing the script.
	 *
	 * @return void
	 */
	private function maybe_enqueue_content_locking() {
		add_action(
			'enqueue_block_editor_assets',
			function () {
				global $post;

				if ( ! $post || $this->slug !== $post->post_type ) {
					return;
				}

				$asset_file = include CONTENT_MODEL_PLUGIN_PATH . 'includes/runtime/dist/content-locking.asset.php';

				wp_register_script(
					'data-types/content-locking',
					CONTENT_MODEL_PLUGIN_URL . '/includes/runtime/dist/content-locking.js',
					$asset_file['dependencies'],
					$asset_file['version'],
					true
				);

				wp_localize_script(
					'data-types/content-locking',
					'contentModelFields',
					array(
						'postType' => $this->slug,
						'fields'   => $this->fields,
					)
				);

				wp_enqueue_script( 'data-types/content-locking' );
			}
		);
	}
}

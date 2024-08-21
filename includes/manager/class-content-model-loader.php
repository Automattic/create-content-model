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
	 * If they do, it registers the post type and enqueues the attribute binder.
	 *
	 * @return void
	 */
	private function __construct() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->register_post_type();
		$this->maybe_enqueue_the_attribute_binder();
		$this->maybe_enqueue_the_fields_ui();
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
	}

	/**
	 * Conditionally enqueues the attribute binder script for the block editor.
	 *
	 * Checks if the current post is of the correct type before enqueueing the script.
	 *
	 * @return void
	 */
	private function maybe_enqueue_the_attribute_binder() {
		add_action(
			'enqueue_block_editor_assets',
			function () {
				global $post;

				if ( ! $post || Content_Model_Manager::POST_TYPE_NAME !== $post->post_type ) {
					return;
				}

				$register_attribute_binder_js = include CONTENT_MODEL_PLUGIN_PATH . '/includes/manager/dist/register-attribute-binder.asset.php';

				wp_enqueue_script(
					'content-model/attribute-binder',
					CONTENT_MODEL_PLUGIN_URL . '/includes/manager/dist/register-attribute-binder.js',
					$register_attribute_binder_js['dependencies'],
					$register_attribute_binder_js['version'],
					true
				);

				wp_add_inline_script(
					'content-model/attribute-binder',
					'window.BLOCK_VARIATION_NAME_ATTR = "' . Content_Model_Block::BLOCK_VARIATION_NAME_ATTR . '";',
					'before'
				);
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

				if ( ! $post || Content_Model_Manager::POST_TYPE_NAME !== $post->post_type ) {
					return;
				}

				$asset_file = include CONTENT_MODEL_PLUGIN_PATH . 'includes/manager/dist/fields-ui.asset.php';

				wp_register_script(
					'data-types/fields-ui',
					CONTENT_MODEL_PLUGIN_URL . '/includes/manager/dist/fields-ui.js',
					$asset_file['dependencies'],
					$asset_file['version'],
					true
				);

				wp_localize_script(
					'data-types/fields-ui',
					'contentModelFields',
					array(
						'postType' => Content_Model_Manager::POST_TYPE_NAME,
					)
				);

				wp_enqueue_script( 'data-types/fields-ui' );
			}
		);
	}
}

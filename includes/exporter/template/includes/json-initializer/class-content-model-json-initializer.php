<?php
/**
 * Initializes the content models from JSON files.
 *
 * @package content-model
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initializes the content models from JSON files.
 */
class Content_Model_Json_Initializer {
	private const CREATE_CONTENT_MODEL_OPTION = 'create-content-model';

	/**
	 * Register the content models from JSON files if the current version is not the latest.
	 */
	public static function maybe_register_content_models_from_json() {
		if ( ! is_admin() ) {
			return;
		}

		$option = get_option( self::CREATE_CONTENT_MODEL_OPTION, array() );

		$version = $option['version'] ?? null;

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( CONTENT_MODEL_PLUGIN_FILE );

		if ( $plugin_data['Version'] === $version ) {
			return;
		}

		$post_types = glob( CONTENT_MODEL_PLUGIN_PATH . '/post-types/*.json' );
		$post_types = array_map(
			fn( $file ) => json_decode( file_get_contents( $file ), true ),
			$post_types
		);

		self::register_content_models_from_json( $post_types );
		self::delete_dangling_content_models( $post_types );
		$option['version'] = $plugin_data['Version'];
		update_option( self::CREATE_CONTENT_MODEL_OPTION, $option );
	}

	/**
	 * Register the content models from JSON files.
	 *
	 * @param array $post_types The post types from the JSON files.
	 */
	private static function register_content_models_from_json( $post_types ) {
		$content_models = self::group_content_models_by_slug();

		foreach ( $post_types as $post_type ) {
			$content_model_post = array(
				'post_name'    => $post_type['slug'],
				'post_title'   => $post_type['label'],
				'post_status'  => 'publish',
				'post_type'    => Content_Model_Manager::POST_TYPE_NAME,
				'post_content' => serialize_blocks( $post_type['template'] ),
			);

			$existing_content_model = $content_models[ $post_type['slug'] ] ?? null;

			if ( $existing_content_model ) {
				$content_model_post['ID'] = $existing_content_model->ID;
			}

			$post_id = wp_insert_post( $content_model_post );

			update_post_meta( $post_id, 'plural_label', $post_type['pluralLabel'] );
			update_post_meta( $post_id, 'icon', $post_type['icon'] );

			update_post_meta( $post_id, 'fields', wp_json_encode( $post_type['fields'] ) );
		}
	}

	/**
	 * Deletes content models not included in the JSON files.
	 *
	 * @param array $post_types The post types from the JSON files.
	 *
	 * @return void
	 */
	private static function delete_dangling_content_models( $post_types ) {
		$content_models = self::group_content_models_by_slug();
		$post_types     = self::group_post_types_by_slug( $post_types );

		foreach ( $content_models as $model_slug => $model ) {
			if ( ! isset( $post_types[ $model_slug ] ) ) {
				wp_delete_post( $model->ID, false );
			}
		}
	}

	/**
	 * Groups existing content models by their slug.
	 *
	 * @return array An array of content models, keyed by their slug.
	 */
	private static function group_content_models_by_slug() {
		$models = Content_Model_Manager::get_content_models_from_database();

		return array_reduce(
			$models,
			function ( $carry, $model ) {
				$carry[ $model->post_name ] = $model;
				return $carry;
			},
			array()
		);
	}

	/**
	 * Groups existing post types by their slug.
	 *
	 * @param array $post_types The post types from the JSON files.
	 *
	 * @return array An array of post types, keyed by their slug.
	 */
	private static function group_post_types_by_slug( $post_types ) {
		return array_reduce(
			$post_types,
			function ( $carry, $post_type ) {
				$carry[ $post_type['slug'] ] = $post_type;
				return $carry;
			},
			array()
		);
	}
}

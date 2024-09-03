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
		$content_models = self::get_content_models_from_database();

		foreach ( $content_models as $content_model ) {
			$this->content_models[] = new Content_Model( $content_model );
		}
	}

	/**
	 * Retrieves the list of registered content models.
	 *
	 * @return WP_Post[] An array of WP_Post objects representing the registered content models.
	 */
	public static function get_content_models_from_database() {
		return get_posts( array( 'post_type' => self::POST_TYPE_NAME ) );
	}
}

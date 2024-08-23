<?php
/**
 * Handles the export functionality for Content Models.
 *
 * @package create-content-model
 */

class Content_Model_Exporter {
	/**
	 * The instance.
	 *
	 * @var ?Content_Model_Exporter
	 */
	private static $instance = null;


	/**
	 * Inits the singleton of the Content_Model_Exporter class.
	 *
	 * @return Content_Model_Exporter
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Initializes the Content_Model_Exporter class.
	 *
	 * @return void
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_export_submenu_page' ) );
		add_action( 'admin_post_download_content_models_zip', array( $this, 'handle_zip_download' ) );
	}


	/**
	 * Adds the export submenu page to the admin menu.
	 *
	 * @return void
	 */
	public function add_export_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=content_model',
			__( 'Export Content Models' ),
			__( 'Export' ),
			'manage_options',
			'export-content-models',
			array( $this, 'render_export_page' )
		);
	}


	/**
	 * Renders the export page.
	 *
	 * @return void
	 */
	public function render_export_page() {
		$all_models_json = $this->generate_all_models_json();
		$has_models      = ! empty( $all_models_json );
		$show_error      = ! $has_models;
		if ( isset( $_GET['error'] ) && 'no_models' === $_GET['error'] && check_admin_referer( 'export_error_nonce', 'export_error_nonce' ) ) {
			$show_error = true;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Export Content Models' ); ?></h1>
			<?php if ( $show_error ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'No content models available. Please create a content model before exporting.' ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'download_content_models_zip', 'download_zip_nonce' ); ?>
				<input type="hidden" name="action" value="download_content_models_zip">
				<p><?php echo esc_html__( 'Click the button below to download a ZIP file containing all Content Models.' ); ?></p>
				<input type="submit" name="download_content_models_zip" class="button button-primary" value="<?php echo esc_attr__( 'Download ZIP file' ); ?>" <?php disabled( ! $has_models ); ?>>
			</form>
		</div>
		<?php
	}


	/**
	 * Generates the JSON data for a single content model.
	 *
	 * @param Content_Model $model The content model post object.
	 * @return array The JSON data structure.
	 */
	private function generate_json_for_model( $model ) {
		return array(
			'type'     => 'postType',
			'postType' => $model->slug,
			'slug'     => $model->slug,
			'label'    => $model->title,
			'template' => $model->template,
			'fields'   => $this->format_fields_for_export( $model->get_meta_fields() ),
		);
	}


	/**
	 * Formats the fields for export.
	 *
	 * @param array $fields The raw fields data.
	 * @return array The formatted fields for export.
	 */
	private function format_fields_for_export( $fields ) {
		$formatted_fields = array();
		foreach ( $fields as $field ) {
			$formatted_fields[] = array(
				'slug'        => $field['slug'],
				'type'        => $field['type'],
				'label'       => $field['slug'],
				'description' => $field['description'],
			);
		}
		return $formatted_fields;
	}


	/**
	 * Generates JSON data for all registered content models.
	 *
	 * @return array An associative array of content models' JSON data.
	 */
	private function generate_all_models_json() {
		$content_models  = Content_Model_Manager::get_instance()->get_content_models();
		$all_models_json = array();

		foreach ( $content_models as $model ) {
			$json_data                       = $this->generate_json_for_model( $model );
			$all_models_json[ $model->slug ] = $json_data;
		}

		return $all_models_json;
	}


	/**
	 * Handles the ZIP file download process.
	 *
	 * @return void
	 */
	public function handle_zip_download() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		check_admin_referer( 'download_content_models_zip', 'download_zip_nonce' );

		$all_models_json = $this->generate_all_models_json();

		if ( empty( $all_models_json ) ) {
			$redirect_url = add_query_arg(
				array(
					'error'              => 'no_models',
					'export_error_nonce' => wp_create_nonce( 'export_error_nonce' ),
				),
				wp_get_referer()
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$zip_file = $this->create_zip_file( $all_models_json );

		if ( $zip_file ) {
			$zip_url = wp_get_attachment_url( $zip_file );
			wp_safe_redirect( $zip_url );
			exit;
		} else {
			wp_die( esc_html__( 'Failed to create ZIP file' ) );
		}
	}


	/**
	 * Includes nested files in the ZIP archive.
	 *
	 * @param ZipArchive $zip       The ZIP archive object.
	 * @param string     $base_path The base path of the files.
	 * @param string     $folder    The folder to include.
	 * @return void
	 */
	private function include_nested_files( $zip, $base_path, $folder ) {
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base_path . $folder ), RecursiveIteratorIterator::SELF_FIRST );

		foreach ( $files as $file ) {
			$path = str_replace( $base_path, '', $file->getPathname() );

			if ( is_dir( $file ) ) {
				$zip->addEmptyDir( $path );
			} else {
				$is_js_src = str_ends_with( $path, '.js' ) && ! str_contains( $path, 'dist' );

				if ( $is_js_src ) {
					continue;
				}

				$zip->addFile( $file->getPathname(), $path );
			}
		}
	}


	/**
	 * Updates the version and name in the main plugin file.
	 *
	 * @return string The updated plugin file content.
	 */
	private function replace_content_model_plugin_file_version() {
		$plugin_data = get_plugin_data( CONTENT_MODEL_PLUGIN_FILE, false, false );
		$plugin_file = file_get_contents( CONTENT_MODEL_PLUGIN_PATH . 'create-content-model.php' );

		$original_version = $plugin_data['Version'];
		$updated_version  = $plugin_data['Version'] . '-' . time();

		$plugin_file = str_replace( "Version: $original_version", "Version: $updated_version", $plugin_file );

		$original_plugin_name = $plugin_data['Name'];
		$updated_plugin_name  = 'Content Models';

		$plugin_file = str_replace( "Plugin Name: $original_plugin_name", "Plugin Name: $updated_plugin_name", $plugin_file );

		return $plugin_file;
	}


	/**
	 * Creates a ZIP file containing the content models and necessary plugin files.
   * This does not include content models that are not published.
	 *
	 * @param array $all_models_json The JSON data for the content models.
	 * @return int|false The attachment ID of the created ZIP file, or false on failure.
	 */
	private function create_zip_file( $all_models_json ) {
		$upload_dir   = wp_upload_dir();
		$zip_filename = 'content_models_' . gmdate( 'Y-m-d_H-i-s' ) . '.zip';
		$zip_filepath = $upload_dir['path'] . '/' . $zip_filename;

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_filepath, ZipArchive::CREATE ) ) {
			return false;
		}

		$zip->addFromString( 'create-content-model.php', $this->replace_content_model_plugin_file_version() );

		$this->include_nested_files( $zip, CONTENT_MODEL_PLUGIN_PATH, 'includes/runtime' );
		$this->include_nested_files( $zip, CONTENT_MODEL_PLUGIN_PATH . 'includes/exporter/template/', '' );

		foreach ( $all_models_json as $model_slug => $model_json ) {
			$zip->addFromString( 'post-types/' . $model_slug . '.json', wp_json_encode( $model_json, JSON_UNESCAPED_UNICODE ) );
		}

		$zip->close();

		$filetype   = wp_check_filetype( $zip_filename, null );
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( $zip_filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $zip_filepath );
		if ( 0 === $attach_id ) {
			return false;
		}

		return $attach_id;
	}
}

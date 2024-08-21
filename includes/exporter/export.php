<?php
/**
 * Handles the export functionality for Content Models.
 *
 * @package create-content-model
 */

/**
 * Adds the export submenu page.
 */
function add_export_submenu_page() {
	add_submenu_page(
		'edit.php?post_type=content_model',
		__( 'Export Content Models', 'create-content-model' ),
		__( 'Export', 'create-content-model' ),
		'manage_options',
		'export-content-models',
		'render_export_page'
	);
}
add_action( 'admin_menu', 'add_export_submenu_page' );

/**
 * Renders the export page.
 */
function render_export_page() {
	render_export_ui();
	if ( isset( $_POST['export_content_models'] ) && check_admin_referer( 'export_content_models', 'export_nonce' ) ) {
		preview_json_export();
		return;
	}
}

/**
 * Renders the export UI.
 */
function render_export_ui() {
	$all_models_json = generate_all_models_json();
	$has_models      = ! empty( $all_models_json );

	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Export Content Models', 'create-content-model' ); ?></h1>
		<?php
		if ( isset( $_GET['error'] ) && 'no_models' === $_GET['error'] && check_admin_referer( 'export_error_nonce', 'export_error_nonce' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'No content models available. Please create a content model before exporting.', 'create-content-model' ) . '</p></div>';
		}
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'export_content_models', 'export_nonce' ); ?>
			<p><?php echo esc_html__( 'Click the button below to display the JSON for all Content Models.', 'create-content-model' ); ?></p>
			<input type="submit" name="export_content_models" class="button button-primary" value="<?php echo esc_attr__( 'Display Content Models JSON', 'create-content-model' ); ?>" <?php disabled( ! $has_models ); ?>>
		</form>
		
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'download_content_models_zip', 'download_zip_nonce' ); ?>
			<input type="hidden" name="action" value="download_content_models_zip">
			<p><?php echo esc_html__( 'Click the button below to download a ZIP file containing all Content Models.', 'create-content-model' ); ?></p>
			<input type="submit" name="download_content_models_zip" class="button button-secondary" value="<?php echo esc_attr__( 'Download ZIP file', 'create-content-model' ); ?>" <?php disabled( ! $has_models ); ?>>
		</form>
		<?php
		if ( ! $has_models ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'No content models available. Please create a content model before exporting.', 'create-content-model' ) . '</p></div>';
		}
		?>
	</div>
	<?php
}

/**
 * Preview the JSON export.
 */
function preview_json_export() {
	$all_models_json = generate_all_models_json();

	echo '<h2>' . esc_html__( 'Content Models JSON', 'create-content-model' ) . '</h2>';
	echo '<pre style="background-color: #f4f4f4; padding: 15px; overflow: auto; max-height: 500px;">';
	echo esc_html( wp_json_encode( $all_models_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	echo '</pre>';
	echo '<h2>' . esc_html__( 'Content Models JSON Minified', 'create-content-model' ) . '</h2>';
	echo '<pre style="background-color: #f4f4f4; padding: 15px; overflow: auto; max-height: 500px;">';
	echo esc_html( wp_json_encode( $all_models_json, JSON_UNESCAPED_UNICODE ) );
	echo '</pre>';
}

/**
 * Generates the JSON data for a single content model.
 *
 * @param Content_Model $model The content model post object.
 * @return array The JSON data structure.
 */
function generate_json_for_model( $model ) {
	return array(
		'type'     => 'postType',
		'postType' => $model->slug,
		'slug'     => $model->slug,
		'label'    => $model->title,
		'template' => $model->template,
		'fields'   => format_fields_for_export( $model->get_meta_fields() ),
	);
}

/**
 * Formats the fields for export.
 *
 * @param array $fields The raw fields data.
 * @return array The formatted fields for export.
 */
function format_fields_for_export( $fields ) {
	$formatted_fields = array();
	foreach ( $fields as $field ) {
		$formatted_fields[] = array(
			'slug'  => $field['slug'],
			'type'  => $field['type'],
			'label' => $field['slug'],
		);
	}
	return $formatted_fields;
}

/**
 * Generates JSON data for all registered content models.
 *
 * @return array An associative array of content models' JSON data.
 */
function generate_all_models_json() {
	$content_models  = Content_Model_Manager::get_instance()->get_content_models();
	$all_models_json = array();

	foreach ( $content_models as $model ) {
		$json_data                       = generate_json_for_model( $model );
		$all_models_json[ $model->slug ] = $json_data;
	}

	return $all_models_json;
}

function handle_zip_download() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'create-content-model' ) );
	}

	check_admin_referer( 'download_content_models_zip', 'download_zip_nonce' );

	$all_models_json = generate_all_models_json();

	if ( empty( $all_models_json ) ) {
		// No content models found, display an error message to the user
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

	$zip_file = create_zip_file( $all_models_json );

	if ( $zip_file ) {
		$zip_url = wp_get_attachment_url( $zip_file );
		wp_safe_redirect( $zip_url );
		exit;
	} else {
		wp_die( esc_html__( 'Failed to create ZIP file', 'create-content-model' ) );
	}
}

function create_zip_file( $all_models_json ) {
	$upload_dir   = wp_upload_dir();
	$zip_filename = 'content_models_' . gmdate( 'Y-m-d_H-i-s' ) . '.zip';
	$zip_filepath = $upload_dir['path'] . '/' . $zip_filename;

	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_filepath, ZipArchive::CREATE ) ) {
		return false;
	}

	foreach ( $all_models_json as $model_slug => $model_json ) {
		$zip->addFromString( $model_slug . '.json', wp_json_encode( $model_json, JSON_UNESCAPED_UNICODE ) );
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

add_action( 'admin_post_download_content_models_zip', 'handle_zip_download' );

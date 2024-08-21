<?php
/**
 * Plugin Name: Content Model Standalone
 * Plugin URI: https://example.com/
 * Description: Standalone plugin scaffolded from Create Content Model
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: content-model-standalone
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CMS_VERSION', '1.0.0' );
define( 'CMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Used in the runtime
define( 'CONTENT_MODEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CONTENT_MODEL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

class Content_Model_Standalone_Initializer {

	private $json_files;
	private $content_models = [];

	public function __construct( $json_files ) {
		$this->json_files = $json_files;
		add_action( 'init', array( $this, 'initialize' ), 0 );
	}

	private function load_content_models( $json_files ) {
		foreach ( $json_files as $json_file ) {
			$json_data = json_decode( file_get_contents( $json_file ), true );
			if ( $json_data ) {
				$this->content_models[] = new Content_Model( $json_data );
			}
		}
	}

	public function initialize() {
		$this->load_content_models( $this->json_files );
	}

	public static function init() {
		$json_files = glob( CMS_PLUGIN_DIR . 'post-types/*.json' );
		new self( $json_files );
	}
}

add_action( 'plugins_loaded', array( 'Content_Model_Standalone_Initializer', 'init' ) );

// Include the runtime files
require_once CMS_PLUGIN_DIR . 'includes/runtime/0-load.php';

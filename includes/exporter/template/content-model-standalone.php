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

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_VERSION', '1.0.0');
define('CMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CMS_PLUGIN_DIR . 'class-content-model-initializer.php';

function cms_init() {
    $post_types_dir = CMS_PLUGIN_DIR . 'post-types/';
    $json_files = glob($post_types_dir . '*.json');
    
    $initializer = new Content_Model_Initializer($json_files);
    $initializer->initialize();
}

add_action('plugins_loaded', 'cms_init');

// Include the runtime files
// Used in the runtime
define('CONTENT_MODEL_PLUGIN_URL', plugin_dir_url(__FILE__));
require_once CMS_PLUGIN_DIR . 'runtime/0-load.php';
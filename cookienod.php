<?php
/**
 * Plugin Name: Cookienod - Cookie Consent & Scanner
 * Plugin URI: https://cookienod.com
 * Description: GDPR/CCPA compliant cookie consent manager with automated cookie scanning and consent controls.
 * Version: 1.0.0
 * Author: CookieNod Team
 * Author URI: https://cookienod.com/about
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cookienod
 * Domain Path: /languages
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('COOKIENOD_VERSION', '1.0.0');
define('COOKIENOD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COOKIENOD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COOKIENOD_PLUGIN_FILE', __FILE__);
define('COOKIENOD_API_ENDPOINT', 'https://api.cookienod.com');

// Load required files
require_once COOKIENOD_PLUGIN_DIR . 'includes/class-core.php';

/**
 * Initialize the plugin
 *
 * @return CookieNod_Core
 */
function cookienod_wp() {
    return CookieNod_Core::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'cookienod_wp');

// Activation hook
register_activation_hook(__FILE__, 'cookienod_activate');

/**
 * Plugin activation
 */
function cookienod_activate() {
    // Create database tables
    require_once COOKIENOD_PLUGIN_DIR . 'includes/class-database.php';
    $database = new CookieNod_Database();
    $database->create_tables();

    // Set default options
    $default_options = array(
        'api_key'         => '',
        'block_mode'      => 'auto',
        'banner_position' => 'bottom',
        'banner_theme'    => 'light',
    );
    add_option('cookienod_wp_options', $default_options);
}

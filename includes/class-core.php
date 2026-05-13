<?php
/**
 * CookieNod Core Class
 * Main plugin functionality and initialization
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Core
 * Core plugin functionality
 */
class CookieNod_Core {

    /**
     * Single instance of the class
     *
     * @var CookieNod_Core|null
     */
    private static $instance = null;

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Check whether an API key is configured.
     *
     * @return bool
     */
    private function has_valid_api_key() {
        $api_key = $this->options['api_key'] ?? '';
        return !empty($api_key);
    }

    /**
     * Get single instance
     *
     * @return CookieNod_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->options = get_option('cookienod_wp_options', array());
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init() {
        // Load required files
        $this->load_dependencies();

        // Initialize core components on init hook
        add_action('init', array($this, 'init_components'));

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('wp_ajax_cookienod_verify_api_key', array($this, 'ajax_verify_api_key'));
            add_action('wp_ajax_cookienod_sync_settings', array($this, 'ajax_sync_settings'));
            add_action('wp_ajax_cookienod_scan_cookies', array($this, 'ajax_scan_cookies'));
            // Custom CSS AJAX handlers
            add_action('wp_ajax_cookienod_load_theme', array($this, 'ajax_load_theme'));
            add_action('wp_ajax_cookienod_validate_css', array($this, 'ajax_validate_css'));
            add_action('wp_ajax_cookienod_preview_css', array($this, 'ajax_preview_css'));
            add_action('wp_ajax_cookienod_generate_preview', array($this, 'ajax_generate_preview'));
        }

        // Consent logging (frontend + backend)
        add_action('wp_ajax_cookienod_log_consent', array($this, 'ajax_log_consent'));
        add_action('wp_ajax_nopriv_cookienod_log_consent', array($this, 'ajax_log_consent'));

        // Consent log export/clear (admin only)
        add_action('wp_ajax_cookienod_export_consent_log', array($this, 'ajax_export_consent_log'));
        add_action('wp_ajax_cookienod_clear_consent_log', array($this, 'ajax_clear_consent_log'));

        // Cookie detection from frontend scanner
        add_action('wp_ajax_cookienod_detect_cookies', array($this, 'ajax_detect_cookies'));
        add_action('wp_ajax_nopriv_cookienod_detect_cookies', array($this, 'ajax_detect_cookies'));

        // Frontend hooks
        add_action('template_redirect', array($this, 'start_output_buffering'), 0);
        add_action('wp_head', array($this, 'output_cookienod_script_first'), 0);
        add_action('wp_footer', array($this, 'render_consent_banner'), 1);
        add_action('shutdown', array($this, 'end_output_buffering'), 0);

        // Add settings link
        add_filter('plugin_action_links_' . plugin_basename(COOKIENOD_PLUGIN_FILE), array($this, 'add_settings_link'));

        // Admin notices
        add_action('admin_notices', array($this, 'check_database_tables'));
        add_action('admin_notices', array($this, 'tables_created_notice'));
        add_action('admin_notices', array($this, 'api_key_changed_notice'));
        add_action('admin_init', array($this, 'handle_repair_tables'));
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-database.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-admin.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-compliance.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-google-consent-mode.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-script-blocker.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-custom-css.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-ab-testing.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-policy-generator.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-woocommerce.php';
        require_once COOKIENOD_PLUGIN_DIR . 'includes/class-plugin-compat.php';
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        $has_valid_api_key = $this->has_valid_api_key();

        if ($has_valid_api_key) {
            new CookieNod_Script_Blocker();
        }

        new CookieNod_Custom_CSS();
        new CookieNod_Policy_Generator();

        if ($has_valid_api_key && !empty($this->options['enable_google_consent_mode'])) {
            new CookieNod_Google_Consent_Mode();
        }

        // A/B testing admin AJAX and screen functionality must always be available,
        // even before the feature is enabled for frontend use.
        $ab_testing = new CookieNod_AB_Testing();

        // Use wp_loaded hook for cookie setting - runs after WordPress is loaded but before headers sent
        add_action('wp_loaded', array($ab_testing, 'assign_variant'));

        if ($has_valid_api_key && class_exists('WooCommerce')) {
            new CookieNod_WooCommerce();
        }

        new CookieNod_Plugin_Compat();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $admin = new CookieNod_Admin();
        $admin->add_menu();
    }

    /**
     * Register settings
     */
    public function register_settings() {
        $admin = new CookieNod_Admin();
        $admin->register_settings();
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        $admin = new CookieNod_Admin();
        $admin->enqueue_scripts($hook);
    }

    /**
     * AJAX verify API key
     */
    public function ajax_verify_api_key() {
        $admin = new CookieNod_Admin();
        $admin->ajax_verify_api_key();
    }

    /**
     * AJAX sync settings
     */
    public function ajax_sync_settings() {
        $admin = new CookieNod_Admin();
        $admin->ajax_sync_settings();
    }

    /**
     * Start output buffering
     */
    public function start_output_buffering() {
        $frontend = new CookieNod_Frontend($this->options);
        $frontend->start_output_buffering();
    }

    /**
     * Inject cookie blocker
     */
    public function inject_cookie_blocker($buffer) {
        $frontend = new CookieNod_Frontend($this->options);
        return $frontend->inject_cookie_blocker($buffer);
    }

    /**
     * End output buffering
     */
    public function end_output_buffering() {
        $frontend = new CookieNod_Frontend($this->options);
        $frontend->end_output_buffering();
    }

    /**
     * Output CookieNod script
     */
    public function output_cookienod_script_first() {
        $frontend = new CookieNod_Frontend($this->options);
        $frontend->output_script();
    }

    /**
     * Render consent banner
     */
    public function render_consent_banner() {
        if (is_admin() || wp_is_json_request()) {
            return;
        }
        do_action('cookienod_before_banner');
        do_action('cookienod_after_banner');
    }

    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=cookienod-settings') . '">' . __('Settings', 'cookienod') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Check database tables
     */
    public function check_database_tables() {
        $database = new CookieNod_Database();
        $database->check_tables();
    }

    /**
     * Handle repair tables
     */
    public function handle_repair_tables() {
        $database = new CookieNod_Database();
        $database->handle_repair();
    }

    /**
     * Show tables created notice
     */
    public function tables_created_notice() {
        $database = new CookieNod_Database();
        $database->show_created_notice();
    }

    /**
     * Show notice when the API key changes and must be re-verified.
     */
    public function api_key_changed_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!get_transient('cookienod_api_key_changed_notice')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || strpos((string) $screen->id, 'cookienod') === false) {
            return;
        }

        delete_transient('cookienod_api_key_changed_notice');

        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('API key changed, please verify again.', 'cookienod') . '</p></div>';
    }

    /**
     * AJAX log consent
     */
    public function ajax_log_consent() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        // Check if logging is enabled (default to true if not set)
        if (empty($this->options['log_consent']) && isset($this->options['log_consent'])) {
            wp_send_json_success(array('logged' => false, 'reason' => 'logging_disabled'));
            return;
        }

        // Parse JSON preferences - use wp_unslash to preserve JSON
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data will be decoded and sanitized
        $preferences_raw = isset($_POST['preferences']) ? wp_unslash($_POST['preferences']) : '{}';
        $preferences = json_decode($preferences_raw, true);

        if (!is_array($preferences)) {
            $preferences = array();
        }

        // Sanitize preferences - only allow boolean values for known categories
        $allowed_categories = array('necessary', 'functional', 'analytics', 'marketing');
        $sanitized_preferences = array();
        foreach ($allowed_categories as $category) {
            if (isset($preferences[$category])) {
                $sanitized_preferences[$category] = rest_sanitize_boolean($preferences[$category]);
            }
        }
        $preferences = $sanitized_preferences;

        // Don't log empty preferences - banner hasn't set them yet
        if (empty($preferences)) {
            wp_send_json_success(array('logged' => false, 'reason' => 'empty_preferences'));
            return;
        }

        // Check if already logged these exact preferences (prevent duplicates)
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Session ID sanitized
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
        $client_ip = $this->get_client_ip();
        $prefs_hash = md5(json_encode($preferences));
        $logged_key = 'cookienod_logged_' . md5($session_id . $client_ip) . '_' . $prefs_hash;

        if (get_transient($logged_key)) {
            wp_send_json_success(array('logged' => false, 'reason' => 'already_logged'));
            return;
        }

        $user_id = get_current_user_id();

        $admin = new CookieNod_Admin();
        $admin->log_consent($preferences, $user_id);

        // Mark this specific preference set as logged for 5 minutes
        set_transient($logged_key, true, 5 * MINUTE_IN_SECONDS);

        wp_send_json_success(array('logged' => true));
    }

    /**
     * Get client IP address (anonymized for GDPR compliance)
     *
     * @return string Anonymized IP address.
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        $raw_ip = '';

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- IP addresses sanitized after unslash
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $raw_ip = $ip;
                    break;
                }
            }
        }

        if (empty($raw_ip)) {
            return '0.0.0.0';
        }

        // Anonymize IP for GDPR compliance
        if (filter_var($raw_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $raw_ip);
            $parts[3] = '0';
            return implode('.', $parts);
        } elseif (filter_var($raw_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = str_split(bin2hex(inet_pton($raw_ip)), 16);
            if (count($parts) >= 2) {
                $parts[1] = '0000000000000000';
            }
            return inet_ntop(hex2bin(implode('', $parts)));
        }

        return '0.0.0.0';
    }

    /**
     * Validate table name to prevent SQL injection
     *
     * @param string $table Table name to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_table_name($table) {
        global $wpdb;

        // Only allow known table names with wp prefix
        $allowed_tables = array(
            'cookienod_consent_log',
            'cookienod_ab_tests',
            'cookienod_ab_results',
        );

        $prefix = $wpdb->prefix;
        foreach ($allowed_tables as $allowed) {
            if ($table === $prefix . $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * AJAX export consent log
     */
    public function ajax_export_consent_log() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Format sanitized
        $format = isset($_GET['format']) ? sanitize_text_field(wp_unslash($_GET['format'])) : 'csv';

        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'cookienod_consent_log' );

        // Validate table name to prevent SQL injection
        if (!$this->is_valid_table_name($table)) {
            wp_send_json_error('Invalid table');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safe, built from prefix
        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name using $wpdb->prefix
            "SELECT * FROM `{$table}` ORDER BY created_at DESC",
            ARRAY_A
        );

        if ($format === 'csv') {
            // Generate CSV content
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory stream for CSV export
            $output = fopen('php://temp', 'r+');
            fputcsv($output, array('ID', 'User ID', 'IP Address', 'Preferences', 'Created At'));

            foreach ($results as $row) {
                fputcsv($output, array(
                    $row['id'],
                    $row['user_id'],
                    $row['ip_address'],
                    $row['preferences'],
                    $row['created_at'],
                ));
            }

            rewind($output);
            $csv_content = stream_get_contents($output);
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- In-memory stream for CSV export
            fclose($output);

            wp_send_json_success(array(
                'content' => $csv_content,
                'filename' => 'consent-log-' . gmdate('Y-m-d') . '.csv'
            ));
        } else {
            wp_send_json_error('Unsupported format');
        }
    }

    /**
     * AJAX clear consent log
     */
    public function ajax_clear_consent_log() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cookienod_consent_log';

        // Validate table name to prevent SQL injection
        if (!$this->is_valid_table_name($table)) {
            wp_send_json_error('Invalid table');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
        $wpdb->query("TRUNCATE TABLE `{$table}`");

        wp_send_json_success(array('cleared' => true));
    }

    /**
     * AJAX scan cookies
     */
    public function ajax_scan_cookies() {
        $admin = new CookieNod_Admin();
        $admin->ajax_scan_cookies();
    }

    /**
     * AJAX load theme for custom CSS
     */
    public function ajax_load_theme() {
        $css_editor = new CookieNod_Custom_CSS();
        $css_editor->ajax_load_theme();
    }

    /**
     * AJAX validate CSS
     */
    public function ajax_validate_css() {
        $css_editor = new CookieNod_Custom_CSS();
        $css_editor->ajax_validate_css();
    }

    /**
     * AJAX preview CSS
     */
    public function ajax_preview_css() {
        $css_editor = new CookieNod_Custom_CSS();
        $css_editor->ajax_preview_css();
    }

    /**
     * AJAX generate preview HTML
     */
    public function ajax_generate_preview() {
        $css_editor = new CookieNod_Custom_CSS();
        $css_editor->ajax_generate_preview();
    }

    /**
     * AJAX detect cookies from frontend
     */
    public function ajax_detect_cookies() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        // Decode JSON cookies data - use wp_unslash, not sanitize_text_field (which corrupts JSON)
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data will be decoded
        $cookies_raw = isset($_POST['cookies']) ? wp_unslash($_POST['cookies']) : '[]';
        $cookies = json_decode($cookies_raw, true);

        if (!is_array($cookies)) {
            wp_send_json_success(array('saved' => 0, 'error' => 'Invalid JSON'));
            return;
        }

        if (empty($cookies)) {
            wp_send_json_success(array('saved' => 0));
            return;
        }

        // Get existing cookies
        $existing = get_option('cookienod_wp_detected_cookies', array());
        if (!is_array($existing)) {
            $existing = array();
        }

        // Create lookup by name
        $cookie_lookup = array();
        foreach ($existing as $cookie) {
            if (is_array($cookie) && isset($cookie['name'])) {
                $cookie_lookup[$cookie['name']] = $cookie;
            }
        }

        // Add new cookies
        $new_count = 0;
        foreach ($cookies as $cookie) {
            if (is_array($cookie) && isset($cookie['name']) && !isset($cookie_lookup[$cookie['name']])) {
                $sanitized_cookie = array(
                    'name' => sanitize_text_field($cookie['name']),
                    'value' => isset($cookie['value']) ? sanitize_text_field($cookie['value']) : '',
                    'type' => isset($cookie['type']) ? sanitize_text_field($cookie['type']) : 'http',
                    'source' => isset($cookie['source']) ? sanitize_text_field($cookie['source']) : '',
                    'detected_at' => current_time('mysql'),
                );
                $existing[] = $sanitized_cookie;
                $cookie_lookup[$sanitized_cookie['name']] = $sanitized_cookie;
                $new_count++;
            }
        }

        // Save updated list
        update_option('cookienod_wp_detected_cookies', $existing);
        update_option('cookienod_wp_last_scan', current_time('mysql'));

        wp_send_json_success(array(
            'saved' => $new_count,
            'total' => count($existing),
        ));
    }
}

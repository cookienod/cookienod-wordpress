<?php
/**
 * CookieNod Admin Class
 * Handles admin functionality, menus, and AJAX
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Admin
 * Admin functionality
 */
class CookieNod_Admin {

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('cookienod_wp_options', array());
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        add_menu_page(
            __('CookieNod', 'cookienod'),
            __('CookieNod', 'cookienod'),
            'manage_options',
            'cookienod',
            array($this, 'render_dashboard_page'),
            'dashicons-shield-alt',
            100
        );

        add_submenu_page(
            'cookienod',
            __('Dashboard', 'cookienod'),
            __('Dashboard', 'cookienod'),
            'manage_options',
            'cookienod',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'cookienod',
            __('Settings', 'cookienod'),
            __('Settings', 'cookienod'),
            'manage_options',
            'cookienod-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'cookienod',
            __('Cookie Manager', 'cookienod'),
            __('Cookie Manager', 'cookienod'),
            'manage_options',
            'cookienod-cookies',
            array($this, 'render_cookies_page')
        );

        add_submenu_page(
            'cookienod',
            __('Consent Log', 'cookienod'),
            __('Consent Log', 'cookienod'),
            'manage_options',
            'cookienod-consent-log',
            array($this, 'render_consent_log_page')
        );

        add_submenu_page(
            'cookienod',
            __('A/B Testing', 'cookienod'),
            __('A/B Testing', 'cookienod'),
            'manage_options',
            'cookienod-ab-testing',
            array($this, 'render_ab_testing_page')
        );

        add_submenu_page(
            'cookienod',
            __('Customize CSS', 'cookienod'),
            __('Customize CSS', 'cookienod'),
            'manage_options',
            'cookienod-custom-css',
            array($this, 'render_custom_css_page')
        );

        add_submenu_page(
            'cookienod',
            __('Cookie Policy', 'cookienod'),
            __('Cookie Policy', 'cookienod'),
            'manage_options',
            'cookienod-policy',
            array($this, 'render_policy_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('cookienod_wp_options', 'cookienod_wp_options', array($this, 'sanitize_options'));
    }

    /**
     * Sanitize options
     *
     * @param array $input Input data.
     * @return array Sanitized data.
     */
    public function sanitize_options($input) {
        $existing = get_option('cookienod_wp_options', array());
        $sanitized = is_array($existing) ? $existing : array();

        // API Settings
        if (array_key_exists('api_key', $input)) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);

            $existing_api_key = isset($existing['api_key']) ? (string) $existing['api_key'] : '';
            if ($sanitized['api_key'] !== $existing_api_key) {
                delete_option('cookienod_wp_site_info');
                delete_option('cookienod_wp_verified_api_key');
                set_transient('cookienod_api_key_changed_notice', 1, MINUTE_IN_SECONDS * 5);
            }
        }

        // Display Settings
        if (array_key_exists('block_mode', $input)) {
            $sanitized['block_mode'] = in_array($input['block_mode'], array('auto', 'manual', 'manual-consent', 'silent'), true) ? $input['block_mode'] : 'auto';
        }
        if (array_key_exists('banner_position', $input)) {
            $sanitized['banner_position'] = in_array($input['banner_position'], array('bottom', 'top', 'center'), true) ? $input['banner_position'] : 'bottom';
        }
        if (array_key_exists('banner_theme', $input)) {
            $sanitized['banner_theme'] = in_array($input['banner_theme'], array('light', 'dark'), true) ? $input['banner_theme'] : 'light';
        }
        if (array_key_exists('excluded_scripts', $input)) {
            $sanitized['excluded_scripts'] = sanitize_textarea_field($input['excluded_scripts']);
        }

        // Compliance Settings
        if (array_key_exists('auto_detect_law', $input)) {
            $sanitized['auto_detect_law'] = !empty($input['auto_detect_law']);
        }
        if (array_key_exists('regulation', $input)) {
            $sanitized['regulation'] = sanitize_key($input['regulation']);
        }
        if (array_key_exists('log_consent', $input)) {
            $sanitized['log_consent'] = !empty($input['log_consent']);
        }

        // Google Consent Mode Settings
        if (array_key_exists('enable_google_consent_mode', $input)) {
            $sanitized['enable_google_consent_mode'] = !empty($input['enable_google_consent_mode']);
        }
        if (array_key_exists('gcm_enable_gtm', $input)) {
            $sanitized['gcm_enable_gtm'] = !empty($input['gcm_enable_gtm']);
        }
        if (array_key_exists('gcm_redact_ads_data', $input)) {
            $sanitized['gcm_redact_ads_data'] = !empty($input['gcm_redact_ads_data']);
        }

        // Policy Settings
        if (array_key_exists('policy_auto_update', $input)) {
            $sanitized['policy_auto_update'] = !empty($input['policy_auto_update']);
        }
        if (array_key_exists('policy_show_updated', $input)) {
            $sanitized['policy_show_updated'] = !empty($input['policy_show_updated']);
        }
        if (array_key_exists('policy_company_name', $input)) {
            $sanitized['policy_company_name'] = sanitize_text_field($input['policy_company_name']);
        }
        if (array_key_exists('policy_contact_email', $input)) {
            $sanitized['policy_contact_email'] = sanitize_email($input['policy_contact_email']);
        }

        // A/B Testing Settings
        if (array_key_exists('enable_ab_testing', $input)) {
            $sanitized['enable_ab_testing'] = !empty($input['enable_ab_testing']);
        }

        // Custom CSS
        if (array_key_exists('custom_css', $input)) {
            $sanitized['custom_css'] = sanitize_textarea_field($input['custom_css']);
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'cookienod') === false) {
            return;
        }

        wp_enqueue_style(
            'cookienod-admin',
            COOKIENOD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            COOKIENOD_VERSION
        );

        wp_enqueue_script(
            'cookienod-admin',
            COOKIENOD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            COOKIENOD_VERSION,
            true
        );

        wp_localize_script('cookienod-admin', 'cookienodWp', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cookienod_wp_nonce'),
            'strings' => array(
                'verifying' => __('Verifying...', 'cookienod'),
                'verified'  => __('API Key Verified!', 'cookienod'),
                'invalid'   => __('Invalid API Key', 'cookienod'),
                'error'     => __('Error occurred', 'cookienod'),
            ),
        ));
    }

    /**
     * AJAX verify API key
     */
    public function ajax_verify_api_key() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- API key sanitized
        $api_key = sanitize_text_field(wp_unslash($_POST['api_key'] ?? ''));

        if (empty($api_key)) {
            delete_option('cookienod_wp_site_info');
            delete_option('cookienod_wp_verified_api_key');
            wp_send_json_error('API key is required');
        }

        $site_url = get_site_url();

        $response = wp_remote_get(
            COOKIENOD_API_ENDPOINT . '/config/' . $api_key . '?url=' . urlencode($site_url),
            array('timeout' => 30)
        );

        if (is_wp_error($response)) {
            delete_option('cookienod_wp_site_info');
            wp_send_json_error($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 400) {
            // API returned error, but we still allow the plugin to work
            // Store fallback data so the plugin continues to function
            $fallback = array(
                'site_url' => $site_url,
                'plan' => 'free',
                'status' => 'active',
                'message' => 'Using local configuration'
            );
            update_option('cookienod_wp_site_info', $fallback);
            update_option('cookienod_wp_verified_api_key', $api_key);
            wp_send_json_success($fallback);
        }

        if (isset($body['site_url'])) {
            update_option('cookienod_wp_site_info', $body);
            update_option('cookienod_wp_verified_api_key', $api_key);
            wp_send_json_success($body);
        } else {
            // No site_url in response, use fallback
            $fallback = array(
                'site_url' => $site_url,
                'plan' => 'free',
                'status' => 'active',
                'message' => 'Using local configuration'
            );
            update_option('cookienod_wp_site_info', $fallback);
            update_option('cookienod_wp_verified_api_key', $api_key);
            wp_send_json_success($fallback);
        }
    }

    /**
     * AJAX sync settings
     */
    public function ajax_sync_settings() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $api_key = $this->options['api_key'] ?? '';
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
        }

        $site_url = get_site_url();

        $response = wp_remote_get(
            COOKIENOD_API_ENDPOINT . '/config/' . $api_key . '/categories?url=' . urlencode($site_url),
            array('timeout' => 30)
        );

        if (is_wp_error($response)) {
            delete_option('cookienod_wp_site_info');
            wp_send_json_error($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 400) {
            // Return default categories instead of error
            $default_body = array(
                'categories' => array(
                    'necessary' => array('enabled' => true, 'required' => true),
                    'functional' => array('enabled' => false),
                    'analytics' => array('enabled' => false),
                    'marketing' => array('enabled' => false),
                )
            );
            update_option('cookienod_wp_server_config', $default_body);
            wp_send_json_success($default_body);
        }

        update_option('cookienod_wp_server_config', $body);
        wp_send_json_success($body);
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        include COOKIENOD_PLUGIN_DIR . 'templates/dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include COOKIENOD_PLUGIN_DIR . 'templates/settings.php';
    }

    /**
     * Render scan page
     */
    public function render_cookies_page() {
        include COOKIENOD_PLUGIN_DIR . 'templates/cookies.php';
    }

    /**
     * Render consent log page
     */
    public function render_consent_log_page() {
        include COOKIENOD_PLUGIN_DIR . 'templates/consent-log.php';
    }

    /**
     * Render A/B testing page
     */
    public function render_ab_testing_page() {
        include COOKIENOD_PLUGIN_DIR . 'templates/ab-testing.php';
    }

    /**
     * Render custom CSS page
     */
    public function render_custom_css_page() {
        include COOKIENOD_PLUGIN_DIR . 'templates/custom-css.php';
    }

    /**
     * Render policy page
     */
    public function render_policy_page() {
        include COOKIENOD_PLUGIN_DIR . 'templates/policy-generator.php';
    }

    /**
     * Get dashboard stats
     *
     * @return array Stats data.
     */
    public function get_dashboard_stats() {
        global $wpdb;

        $stats = array(
            'total_consents'  => 0,
            'recent_consents' => 0,
            'api_status'      => 'not_configured',
            'last_scan'       => null,
            'cookies_detected' => 0,
        );

        $table = $wpdb->prefix . 'cookienod_consent_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;

        if ($table_exists) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
            $stats['total_consents'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
            $stats['recent_consents'] = (int) $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + esc_sql)
                    "SELECT COUNT(*) FROM {$table} WHERE created_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
                    7
                )
            );
        }

        if (!empty($this->options['api_key'])) {
            $site_info = get_option('cookienod_wp_site_info');
            if ($site_info) {
                $stats['api_status'] = 'connected';
                $stats['site_name'] = $site_info['site_name'] ?? 'Unknown';
                $stats['plan'] = $site_info['plan'] ?? 'free';

                // Fetch cookie count from API
                $detected_cookies = $this->get_detected_cookies();
                $stats['cookies_detected'] = count($detected_cookies);
            } else {
                $stats['api_status'] = 'invalid_key';
            }
        }

        return $stats;
    }

    /**
     * Get recent consents
     *
     * @param int $limit Number of records.
     * @return array Consent records.
     */
    public function get_recent_consents($limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'cookienod_consent_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
        return $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + esc_sql)
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Log consent
     *
     * @param array $preferences Consent preferences.
     * @param int   $user_id User ID.
     * @return int|false Insert ID or false.
     */
    public function log_consent($preferences, $user_id = 0) {
        global $wpdb;

        $table = $wpdb->prefix . 'cookienod_consent_log';

        $data = array(
            'user_id'     => $user_id,
            'ip_address'  => $this->get_client_ip(),
            'user_agent'  => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
            'consent_data' => wp_json_encode($this->sanitize_server_data()),
            'preferences' => wp_json_encode($preferences),
            'created_at'  => current_time('mysql'),
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for consent logging
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    /**
     * Sanitize $_SERVER data for storage
     *
     * @return array Sanitized server data.
     */
    private function sanitize_server_data() {
        $sanitized = array();
        $allowed_keys = array(
            'HTTP_USER_AGENT',
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_REFERER',
            'REQUEST_METHOD',
            'REQUEST_URI',
            'SERVER_PROTOCOL',
            'SERVER_NAME',
            'SERVER_PORT',
            'HTTPS',
        );

        foreach ($allowed_keys as $key) {
            if (!empty($_SERVER[$key])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Sanitized below
                $sanitized[$key] = sanitize_text_field(wp_unslash($_SERVER[$key]));
            }
        }

        return $sanitized;
    }

    /**
     * Get client IP
     *
     * @return string IP address.
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- IP addresses sanitized after unslash
                $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER[$key])));
                return trim($ips[0]);
            }
        }

        return '';
    }

    /**
     * Export consent log
     *
     * @param string $format Export format.
     * @return bool
     */
    public function export_consent_log($format = 'csv') {
        global $wpdb;

        $table = $wpdb->prefix . 'cookienod_consent_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="consent-log.csv"');

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
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV output
            echo stream_get_contents($output);
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- In-memory stream for CSV export
            fclose($output);
            exit;
        }

        return false;
    }

    /**
     * Clear consent log
     */
    public function clear_consent_log() {
        global $wpdb;
        $table = $wpdb->prefix . 'cookienod_consent_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
        $wpdb->query("TRUNCATE TABLE $table");
    }

    /**
     * Get cookie scan results
     *
     * @return array
     */
    public function get_cookie_scan_results() {
        return get_option('cookienod_wp_scan_results', array());
    }

    /**
     * Get detected cookies
     * Fetches from backend API if available, falls back to local storage
     *
     * @return array
     */
    public function get_detected_cookies() {
        $api_key = $this->options['api_key'] ?? '';

        // Try to fetch from backend API first
        if (!empty($api_key)) {
            $site_url = get_site_url();
            $api_endpoint = defined('COOKIENOD_API_ENDPOINT') ? COOKIENOD_API_ENDPOINT : 'https://api.cookienod.com';

            $response = wp_remote_get(
                $api_endpoint . '/api/sites/detected-cookies?url=' . urlencode($site_url) . '&api_key=' . urlencode($api_key),
                array('timeout' => 30)
            );

            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = json_decode(wp_remote_retrieve_body($response), true);

                if ($status_code === 200 && isset($body['cookies']) && is_array($body['cookies'])) {
                    // Transform API cookies to plugin format
                    $transformed_cookies = array();
                    foreach ($body['cookies'] as $cookie) {
                        $transformed_cookies[] = array(
                            'name' => $cookie['name'] ?? 'Unknown',
                            'type' => $cookie['type'] ?? 'http',
                            'source' => $cookie['domain'] ?? 'Unknown',
                            'category' => $cookie['category'] ?? 'uncategorized',
                            'description' => $cookie['description'] ?? '',
                        );
                    }

                    // Cache the API response locally
                    update_option('cookienod_wp_detected_cookies', $transformed_cookies);
                    update_option('cookienod_wp_last_scan', current_time('mysql'));

                    return $transformed_cookies;
                }
            }
        }

        // Fall back to locally stored cookies
        return get_option('cookienod_wp_detected_cookies', array());
    }

    /**
     * Categorize detected cookies
     *
     * @param array $cookies Cookie list.
     * @return array Categorized cookies.
     */
    public function categorize_cookies($cookies) {
        $categorized = array(
            'necessary'   => array(),
            'functional'  => array(),
            'analytics'   => array(),
            'marketing'   => array(),
            'uncategorized' => array(),
        );

        $patterns = array(
            'necessary' => array(
                '/^PHPSESSID$/i', '/^wordpress_/i', '/^wp-/i',
                '/^_csrf$/i', '/^csrftoken$/i', '/^session/i',
                '/^auth/i', '/^login/i', '/^security/i',
                '/^wordpress_logged_in/i', '/^wp-settings/i',
            ),
            'functional' => array(
                '/^lang/i', '/^language/i', '/^currency/i',
                '/^theme/i', '/^preferences/i', '/^settings/i',
            ),
            'analytics' => array(
                '/^_ga/i', '/^_gid/i', '/^_gat/i', '/_utm/i',
                '/^analytics/i', '/^__hstc/i', '/^__hssc/i',
            ),
            'marketing' => array(
                '/^_fbp/i', '/^fr$/i', '/^IDE$/i', '/^NID$/i',
                '/^test_cookie/i', '/^ads/i', '/^advertising/i',
            ),
        );

        foreach ($cookies as $cookie) {
            $categorized_name = 'uncategorized';

            foreach ($patterns as $category => $cat_patterns) {
                foreach ($cat_patterns as $pattern) {
                    if (preg_match($pattern, $cookie['name'])) {
                        $categorized_name = $category;
                        break 2;
                    }
                }
            }

            $categorized[$categorized_name][] = $cookie;
        }

        return $categorized;
    }

    /**
     * Get cookie description based on name and category
     *
     * @param string $name Cookie name.
     * @param string $category Cookie category.
     * @return string Description.
     */
    public function get_cookie_description($name, $category) {
        $descriptions = array(
            // Necessary
            'PHPSESSID' => __('Session identifier for PHP applications.', 'cookienod'),
            'wordpress_' => __('WordPress authentication and session cookies.', 'cookienod'),
            'wp-' => __('WordPress core functionality cookies.', 'cookienod'),
            'wordpress_logged_in' => __('Indicates when a user is logged in.', 'cookienod'),
            'wp-settings' => __('Stores user interface preferences.', 'cookienod'),
            'wordpress_test_cookie' => __('Tests if browser accepts cookies.', 'cookienod'),
            'wp_woocommerce_session' => __('WooCommerce shopping session.', 'cookienod'),
            'woocommerce_' => __('WooCommerce e-commerce functionality.', 'cookienod'),
            'wc_' => __('WooCommerce cart and session data.', 'cookienod'),
            'cart_' => __('Shopping cart identifier.', 'cookienod'),
            '_csrf' => __('Security token for form submissions.', 'cookienod'),
            'csrftoken' => __('Cross-site request forgery protection.', 'cookienod'),
            'session' => __('Generic session management.', 'cookienod'),
            'auth' => __('Authentication token.', 'cookienod'),
            'login' => __('Login session identifier.', 'cookienod'),
            'security' => __('Security-related functionality.', 'cookienod'),
            '__cf' => __('Cloudflare security and performance.', 'cookienod'),

            // Analytics
            '_ga' => __('Google Analytics - distinguishes users.', 'cookienod'),
            '_gid' => __('Google Analytics - distinguishes users (24 hours).', 'cookienod'),
            '_gat' => __('Google Analytics - throttles request rate.', 'cookienod'),
            '_utm' => __('Google Analytics campaign tracking.', 'cookienod'),
            '__hstc' => __('HubSpot tracking cookie.', 'cookienod'),
            '_hj' => __('Hotjar analytics and user recordings.', 'cookienod'),
            'sbjs_' => __('SourceBuster attribution tracking.', 'cookienod'),
            '_fbp' => __('Facebook Pixel tracking.', 'cookienod'),
            'fr' => __('Facebook advertising cookie.', 'cookienod'),
            'IDE' => __('Google DoubleClick advertising.', 'cookienod'),
            'NID' => __('Google preferences and advertising.', 'cookienod'),
            'test_cookie' => __('Tests browser cookie support.', 'cookienod'),

            // Functional
            'lang' => __('Language preference setting.', 'cookienod'),
            'language' => __('Website language selection.', 'cookienod'),
            'currency' => __('Currency preference for e-commerce.', 'cookienod'),
            'theme' => __('User theme/visual preference.', 'cookienod'),
            'preferences' => __('User preference settings.', 'cookienod'),
            'settings' => __('Application settings.', 'cookienod'),
        );

        // Check for exact match
        if (isset($descriptions[$name])) {
            return $descriptions[$name];
        }

        // Check for pattern match
        foreach ($descriptions as $pattern => $desc) {
            if (stripos($name, $pattern) !== false) {
                return $desc;
            }
        }

        // Category-based defaults
        $category_desc = array(
            'necessary' => __('Essential for website operation.', 'cookienod'),
            'functional' => __('Enhances website functionality.', 'cookienod'),
            'analytics' => __('Helps analyze website usage.', 'cookienod'),
            'marketing' => __('Used for advertising purposes.', 'cookienod'),
            'uncategorized' => __('Cookie purpose not yet classified.', 'cookienod'),
        );

        return isset($category_desc[$category]) ? $category_desc[$category] : '';
    }

    /**
     * AJAX scan cookies
     */
    public function ajax_scan_cookies() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Get cookies from request (sent by frontend scanner)
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookies sanitized per-field below
        $detected_cookies = isset($_POST['cookies']) ? map_deep(wp_unslash($_POST['cookies']), 'sanitize_text_field') : array();

        // If no cookies sent, try to get from server-side
        if (empty($detected_cookies)) {
            $detected_cookies = $this->scan_server_cookies();
        }

        // Merge with existing cookies
        $existing_cookies = $this->get_detected_cookies();
        $all_cookies = array_merge($existing_cookies, $detected_cookies);

        // Remove duplicates by cookie name
        $unique_cookies = array();
        foreach ($all_cookies as $cookie) {
            if (is_array($cookie) && isset($cookie['name'])) {
                $unique_cookies[$cookie['name']] = $cookie;
            }
        }
        $unique_cookies = array_values($unique_cookies);

        // Save detected cookies
        update_option('cookienod_wp_detected_cookies', $unique_cookies);
        update_option('cookienod_wp_last_scan', current_time('mysql'));

        wp_send_json_success(array(
            'cookies_found' => count($detected_cookies),
            'total_cookies' => count($unique_cookies),
            'cookies' => $unique_cookies,
        ));
    }

    /**
     * Scan cookies from server-side
     */
    private function scan_server_cookies() {
        $cookies = array();

        // Get WordPress cookies
        foreach ($_COOKIE as $name => $value) {
            $cookies[] = array(
                'name' => sanitize_text_field($name),
                'value' => substr(sanitize_text_field($value), 0, 50), // Truncate for storage
                'type' => 'server',
                'source' => 'WordPress',
            );
        }

        return $cookies;
    }
}

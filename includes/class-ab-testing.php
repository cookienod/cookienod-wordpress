<?php
/**
 * A/B Testing for Banner Designs
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_AB_Testing
 * Handles A/B testing of consent banners
 */
class CookieNod_AB_Testing {

    /**
     * Options
     */
    private $options;

    /**
     * Current test
     */
    private $current_test = null;

    /**
     * Decode and sanitize test variants from JSON.
     *
     * @param string $variants_json Raw variants JSON.
     * @return array
     */
    private function parse_variants($variants_json) {
        $variants = json_decode(wp_unslash($variants_json), true);

        if (!is_array($variants)) {
            return array();
        }

        $sanitized_variants = array();

        foreach ($variants as $variant) {
            if (!is_array($variant)) {
                continue;
            }

            $sanitized_variants[] = array(
                'id' => intval($variant['id'] ?? 0),
                'name' => sanitize_text_field($variant['name'] ?? ''),
                'position' => in_array($variant['position'] ?? '', array('bottom', 'top', 'center'), true) ? $variant['position'] : 'bottom',
                'primary_color' => sanitize_hex_color($variant['primary_color'] ?? '') ?: '#2271b1',
            );
        }

        return $sanitized_variants;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('cookienod_wp_options', array());
        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init() {

        // Track events (assign_variant is registered in CookieNod_Core::init_components)
        add_action('wp_ajax_cookienod_track_event', array($this, 'track_event'));
        add_action('wp_ajax_nopriv_cookienod_track_event', array($this, 'track_event'));

        // Add variant to cookienod script
        add_filter('cookienod_script_attributes', array($this, 'add_variant_attribute'));

        // Admin AJAX - these run on admin-ajax.php which is not is_admin()
        add_action('wp_ajax_cookienod_create_test', array($this, 'ajax_create_test'));
        add_action('wp_ajax_cookienod_update_test', array($this, 'ajax_update_test'));
        add_action('wp_ajax_cookienod_delete_test', array($this, 'ajax_delete_test'));
        add_action('wp_ajax_cookienod_get_test_stats', array($this, 'ajax_get_test_stats'));
        add_action('wp_ajax_cookienod_set_winner', array($this, 'ajax_set_winner'));
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $ab_tests_table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );
        $ab_results_table = esc_sql( $wpdb->prefix . 'cookienod_ab_results' );

        // Tests table
        $sql1 = "CREATE TABLE IF NOT EXISTS $ab_tests_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'draft',
            variants longtext NOT NULL,
            traffic_split varchar(255) DEFAULT '50,50',
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            winner bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Results table
        $sql2 = "CREATE TABLE IF NOT EXISTS $ab_results_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL,
            variant_id int(11) NOT NULL,
            session_id varchar(100) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            ip_address varchar(100),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY variant_id (variant_id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }

    /**
     * Assign variant to user
     */
    public function assign_variant() {
        if (is_admin()) {
            return;
        }

        // Check if there's an active test
        $active_test = $this->get_active_test();
        if (!$active_test) {
            return;
        }

        $this->current_test = $active_test;

        // Check if user already has a variant assigned
        if (isset($_COOKIE['cookienod_ab_test'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookie value will be validated by json_decode
            $test_data = json_decode(sanitize_text_field(wp_unslash($_COOKIE['cookienod_ab_test'])), true);
            if ($test_data && $test_data['test_id'] == $active_test->id) {
                return;
            }
        }

        // Assign variant based on traffic split
        $variants = json_decode($active_test->variants, true);
        $split = array_map('intval', explode(',', $active_test->traffic_split));
        $assigned_variant = $this->assign_variant_by_split($variants, $split);

        // Store in cookie (30 days)
        $test_data = array(
            'test_id' => $active_test->id,
            'variant_id' => $assigned_variant['id'],
        );

        setcookie(
            'cookienod_ab_test',
            json_encode($test_data),
            array(
                'expires' => time() + 30 * DAY_IN_SECONDS,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => !empty($_SERVER['HTTPS']) || strpos(get_option('siteurl'), 'https://') === 0,
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );

        // Log impression
        $this->log_event($active_test->id, $assigned_variant['id'], 'impression');
    }

    /**
     * Assign variant based on traffic split
     */
    private function assign_variant_by_split($variants, $split) {
        $total = array_sum($split);
        $random = wp_rand(1, $total);
        $cumulative = 0;

        foreach ($variants as $index => $variant) {
            $cumulative += $split[$index] ?? 0;
            if ($random <= $cumulative) {
                return $variant;
            }
        }

        return $variants[0]; // Default to first variant
    }

    /**
     * Log event
     */
    private function log_event($test_id, $variant_id, $event_type, $event_data = array()) {
        global $wpdb;

        if (!$this->table_exists('cookienod_ab_results')) {
            return;
        }

        $table = $wpdb->prefix . 'cookienod_ab_results';
        $session_id = $this->get_session_id();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for event logging
        $wpdb->insert($table, array(
            'test_id' => $test_id,
            'variant_id' => $variant_id,
            'session_id' => $session_id,
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'ip_address' => $this->get_client_ip(),
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- User agent sanitized
            'user_agent' => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
            'created_at' => current_time('mysql'),
        ));
    }

    /**
     * Get session ID
     */
    private function get_session_id() {
        if (isset($_COOKIE['cookienod_session'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookie value will be sanitized
            return sanitize_text_field(wp_unslash($_COOKIE['cookienod_session']));
        }

        $session_id = wp_hash(microtime() . wp_rand());
        setcookie(
            'cookienod_session',
            $session_id,
            array(
                'expires' => time() + 30 * DAY_IN_SECONDS,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );
        return $session_id;
    }

    /**
     * Get client IP (anonymized for GDPR compliance)
     *
     * @return string Anonymized IP address (last octet removed for IPv4, last 64 bits for IPv6).
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        $raw_ip = '';

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- IP addresses sanitized after unslash
                $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER[$key])));
                $raw_ip = trim($ips[0]);
                break;
            }
        }

        if (empty($raw_ip)) {
            return '';
        }

        // Anonymize IP for GDPR compliance - remove last octet(s)
        if (filter_var($raw_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: remove last octet (e.g., 192.168.1.100 -> 192.168.1.0)
            $parts = explode('.', $raw_ip);
            $parts[3] = '0';
            return implode('.', $parts);
        } elseif (filter_var($raw_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: anonymize by replacing last 64 bits
            $parts = str_split(bin2hex(inet_pton($raw_ip)), 16);
            if (count($parts) >= 2) {
                $parts[1] = '0000000000000000';
            }
            return inet_ntop(hex2bin(implode('', $parts)));
        }

        return '';
    }

    /**
     * Track event via AJAX
     */
    public function track_event() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        $test_id = intval($_POST['test_id'] ?? 0);
        $variant_id = intval($_POST['variant_id'] ?? 0);
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Event type sanitized
        $event_type = sanitize_text_field(wp_unslash($_POST['event_type'] ?? ''));
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Event data will be sanitized
        $event_data_raw = json_decode(wp_unslash($_POST['event_data'] ?? '{}'), true);

        // Sanitize event data - only allow known keys with proper types
        $event_data = array();
        if (is_array($event_data_raw)) {
            $allowed_keys = array('consent_given', 'categories', 'timestamp', 'page_url');
            foreach ($allowed_keys as $key) {
                if (isset($event_data_raw[$key])) {
                    if (is_bool($event_data_raw[$key])) {
                        $event_data[$key] = $event_data_raw[$key];
                    } elseif (is_string($event_data_raw[$key])) {
                        $event_data[$key] = sanitize_text_field($event_data_raw[$key]);
                    } elseif (is_array($event_data_raw[$key])) {
                        $event_data[$key] = array_map('sanitize_text_field', $event_data_raw[$key]);
                    } elseif (is_numeric($event_data_raw[$key])) {
                        $event_data[$key] = intval($event_data_raw[$key]);
                    }
                }
            }
        }

        if (!$test_id || !$variant_id || !$event_type) {
            wp_send_json_error('Missing parameters');
        }

        // Validate test exists and is active
        if (!$this->is_valid_active_test($test_id, $variant_id)) {
            wp_send_json_error('Invalid test or variant');
        }

        $this->log_event($test_id, $variant_id, $event_type, $event_data);

        wp_send_json_success(array('logged' => true));
    }

    /**
     * Validate test exists and is active, and variant belongs to it
     *
     * @param int $test_id Test ID.
     * @param int $variant_id Variant ID.
     * @return bool
     */
    private function is_valid_active_test($test_id, $variant_id) {
        global $wpdb;

        $tests_table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );

        // Check if test exists and is active
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is necessary for validation
        $test = $wpdb->get_row(
            $wpdb->prepare(                
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name using $wpdb->prefix
                "SELECT id, variants FROM {$tests_table} WHERE id = %d AND status = 'active'",
                $test_id
            ),
            ARRAY_A
        );

        if (!$test) {
            return false;
        }

        // Validate variant belongs to this test
        $variants = json_decode($test['variants'], true);
        if (!is_array($variants)) {
            return false;
        }

        foreach ($variants as $variant) {
            if (isset($variant['id']) && $variant['id'] == $variant_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add variant attributes to script
     * Passes variant-specific settings to the external cookienod.js
     */
    public function add_variant_attribute($attributes) {
        // Read test data from cookie
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookie value will be validated by json_decode
        $test_data = isset($_COOKIE['cookienod_ab_test']) ?
            json_decode(sanitize_text_field(wp_unslash($_COOKIE['cookienod_ab_test'])), true) : array();

        $test_id = $test_data['test_id'] ?? 0;
        $variant_id = $test_data['variant_id'] ?? 0;

        if (!$test_id || !$variant_id) {
            return $attributes;
        }

        // Get the test from database
        $test = $this->get_test($test_id);

        if (!$test || $test['status'] !== 'active') {
            return $attributes;
        }

        // Get variant details from the test
        $variants = json_decode($test['variants'], true);

        $variant = null;
        foreach ($variants as $v) {
            if (isset($v['id']) && $v['id'] == $variant_id) {
                $variant = $v;
                break;
            }
        }

        if (!$variant) {
            return $attributes;
        }

        // Apply variant-specific settings to script attributes
        // Replace existing data-banner-position with variant position (not duplicate)
        if (!empty($variant['position'])) {
            // Use regex to replace existing data-banner-position value
            $attributes = preg_replace(
                '/data-banner-position="[^"]*"/',
                sprintf('data-banner-position="%s"', esc_attr($variant['position'])),
                $attributes
            );
        }

        // Add variant ID for tracking purposes
        $attributes .= sprintf(' data-ab-variant="%d"', $variant_id);

        return $attributes;
    }

    /**
     * Check if table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;

        // Validate table name to prevent SQL injection
        if (!$this->is_valid_table_name($table)) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
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
     * Get active test
     */
    private function get_active_test() {
        global $wpdb;

        if (!$this->table_exists('cookienod_ab_tests')) {
            return null;
        }

        $ab_tests_table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );
        $now   = current_time( 'mysql' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name safely built from prefix with static string
        return $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name using $wpdb->prefix
                "SELECT * FROM {$ab_tests_table}
                WHERE status = %s
                AND start_date <= %s
                AND (end_date IS NULL OR end_date >= %s)",
                'active',
                $now,
                $now
            )
        );
    }

    /**
     * AJAX create test
     */
    public function ajax_create_test() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Ensure tables exist
        $this->create_tables();

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Name sanitized
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Variants validated in parse_variants()
        $variants = $this->parse_variants(wp_unslash($_POST['variants'] ?? '[]'));
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Traffic split sanitized
        $traffic_split = sanitize_text_field(wp_unslash($_POST['traffic_split'] ?? '50,50'));

        if (empty($name) || empty($variants) || count($variants) < 2) {
            wp_send_json_error('Invalid test configuration');
        }

        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $wpdb->insert() is safe and intended
        $result = $wpdb->insert(
            $table,
            array(
                'name'           => $name,
                'status'         => 'draft',
                'variants'       => wp_json_encode( $variants ),
                'traffic_split'  => $traffic_split,
                'created_at'     => current_time( 'mysql' ),
            )
        );

        if ($result) {
            wp_send_json_success(array(
                'test_id' => $wpdb->insert_id,
                'message' => __('Test created successfully', 'cookienod'),
            ));
        } else {
            wp_send_json_error('Failed to create test');
        }
    }

    /**
     * AJAX update test
     */
    public function ajax_update_test() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $test_id = intval($_POST['test_id'] ?? 0);
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Status sanitized
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? ''));

        if (!$test_id) {
            wp_send_json_error('Invalid test ID');
        }

        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );

        $data = array();

        if (isset($_POST['status'])) {
            $data['status'] = $status;
            if ($status === 'active') {
                $data['start_date'] = current_time('mysql');
            }
        }

        if (isset($_POST['name'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Name sanitized
            $data['name'] = sanitize_text_field(wp_unslash($_POST['name']));
        }

        if (isset($_POST['variants'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Variants validated in parse_variants()
            $data['variants'] = wp_json_encode($this->parse_variants(wp_unslash($_POST['variants'])));
        }

        if (isset($_POST['traffic_split'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Traffic split sanitized
            $data['traffic_split'] = sanitize_text_field(wp_unslash($_POST['traffic_split']));
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for test update
        $wpdb->update($table, $data, array('id' => $test_id));

        wp_send_json_success(array('updated' => true));
    }

    /**
     * AJAX delete test
     */
    public function ajax_delete_test() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $test_id = intval($_POST['test_id'] ?? 0);

        if (!$test_id) {
            wp_send_json_error('Invalid test ID');
        }

        global $wpdb;
        $tests_table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );
        $results_table = esc_sql( $wpdb->prefix . 'cookienod_ab_results' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for test deletion
        $wpdb->delete($results_table, array('test_id' => $test_id));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for test deletion
        $wpdb->delete($tests_table, array('id' => $test_id));

        wp_send_json_success(array('deleted' => true));
    }

    /**
     * AJAX get test stats
     */
    public function ajax_get_test_stats() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $test_id = intval($_POST['test_id'] ?? 0);

        if (!$test_id) {
            wp_send_json_error('Invalid test ID');
        }

        $stats = $this->get_test_stats($test_id);

        wp_send_json_success(array('stats' => $stats));
    }

    /**
     * Get test statistics
     */
    public function get_test_stats($test_id) {
        global $wpdb;

        $results_table = esc_sql( $wpdb->prefix . 'cookienod_ab_results' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is necessary and lightweight, caching not needed
        $results = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name using $wpdb->prefix
                "SELECT variant_id, event_type, COUNT(*) as count FROM {$results_table} WHERE test_id = %d GROUP BY variant_id, event_type",
                $test_id
            ),
            ARRAY_A
        );       

        // Organize by variant
        $stats = array();
        foreach ($results as $row) {
            $variant_id = $row['variant_id'];
            $event_type = $row['event_type'];

            if (!isset($stats[$variant_id])) {
                $stats[$variant_id] = array(
                    'impressions' => 0,
                    'accept_all' => 0,
                    'reject_all' => 0,
                    'customize' => 0,
                );
            }

            $stats[$variant_id][$event_type] = intval($row['count']);
        }

        // Calculate conversion rates
        foreach ($stats as $variant_id => &$data) {
            $data['accept_rate'] = $data['impressions'] > 0 ?
                round(($data['accept_all'] / $data['impressions']) * 100, 2) : 0;
            $data['interaction_rate'] = $data['impressions'] > 0 ?
                round((($data['accept_all'] + $data['reject_all'] + $data['customize']) / $data['impressions']) * 100, 2) : 0;
        }

        return $stats;
    }

    /**
     * AJAX set winner
     */
    public function ajax_set_winner() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $test_id = intval($_POST['test_id'] ?? 0);
        $winner_id = intval($_POST['winner_id'] ?? 0);

        if (!$test_id || !$winner_id) {
            wp_send_json_error('Invalid parameters');
        }

        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for setting test winner
        $wpdb->update($table, array(
            'status' => 'completed',
            'winner' => $winner_id,
            'end_date' => current_time('mysql'),
        ), array('id' => $test_id));

        wp_send_json_success(array(
            'message' => __('Winner set successfully', 'cookienod'),
            'test_id' => $test_id,
            'winner_id' => $winner_id,
        ));
    }

    /**
     * Get all tests
     */
    public function get_all_tests() {
        global $wpdb;

        $results_table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safe, built from prefix with static string
        return $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name using $wpdb->prefix
            $wpdb->prepare("SELECT * FROM {$results_table} WHERE 1=%d ORDER BY created_at DESC", 1),
            ARRAY_A
        );
    }

    /**
     * Get test by ID
     */
    public function get_test($test_id) {
        global $wpdb;

        $results_table = esc_sql( $wpdb->prefix . 'cookienod_ab_tests' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safe, built from prefix with static string
        return $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table name using $wpdb->prefix
                "SELECT * FROM {$results_table} WHERE id = %d",
                $test_id
            ),
            ARRAY_A
        );
    }
}

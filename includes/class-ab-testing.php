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

        // Assign variant to user
        add_action('init', array($this, 'assign_variant'));

        // Track events
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
        $ab_tests_table = $wpdb->prefix . 'cookienod_ab_tests';
        $ab_results_table = $wpdb->prefix . 'cookienod_ab_results';

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
            $test_data = json_decode(sanitize_text_field($_COOKIE['cookienod_ab_test']), true);
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
            time() + 30 * DAY_IN_SECONDS,
            COOKIEPATH,
            COOKIE_DOMAIN
        );

        // Log impression
        $this->log_event($active_test->id, $assigned_variant['id'], 'impression');
    }

    /**
     * Assign variant based on traffic split
     */
    private function assign_variant_by_split($variants, $split) {
        $total = array_sum($split);
        $random = mt_rand(1, $total);
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

        $wpdb->insert($table, array(
            'test_id' => $test_id,
            'variant_id' => $variant_id,
            'session_id' => $session_id,
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'created_at' => current_time('mysql'),
        ));
    }

    /**
     * Get session ID
     */
    private function get_session_id() {
        if (isset($_COOKIE['cookienod_session'])) {
            return sanitize_text_field($_COOKIE['cookienod_session']);
        }

        $session_id = wp_generate_password(32, false);
        setcookie(
            'cookienod_session',
            $session_id,
            time() + 30 * DAY_IN_SECONDS,
            COOKIEPATH,
            COOKIE_DOMAIN
        );
        return $session_id;
    }

    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', sanitize_text_field($_SERVER[$key]));
                return trim($ips[0]);
            }
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
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $event_data = json_decode(sanitize_text_field($_POST['event_data'] ?? '{}'), true);

        if (!$test_id || !$variant_id || !$event_type) {
            wp_send_json_error('Missing parameters');
        }

        $this->log_event($test_id, $variant_id, $event_type, $event_data);

        wp_send_json_success(array('logged' => true));
    }

    /**
     * Add variant attribute to script
     */
    public function add_variant_attribute($attributes) {
        if (!$this->current_test) {
            return $attributes;
        }

        $variant_id = isset($_COOKIE['cookienod_ab_test']) ?
            json_decode(sanitize_text_field($_COOKIE['cookienod_ab_test']), true)['variant_id'] ?? 0 : 0;

        if ($variant_id) {
            $attributes .= sprintf(' data-ab-variant="%d"', $variant_id);
        }

        return $attributes;
    }

    /**
     * Check if table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }

    /**
     * Get active test
     */
    private function get_active_test() {
        global $wpdb;

        if (!$this->table_exists('cookienod_ab_tests')) {
            return null;
        }

        $table = $wpdb->prefix . 'cookienod_ab_tests';
        $now = current_time('mysql');

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE status = 'active' AND start_date <= %s AND (end_date IS NULL OR end_date >= %s)",
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

        $name = sanitize_text_field($_POST['name'] ?? '');
        $variants = $this->parse_variants($_POST['variants'] ?? '[]');
        $traffic_split = sanitize_text_field($_POST['traffic_split'] ?? '50,50');

        if (empty($name) || empty($variants) || count($variants) < 2) {
            wp_send_json_error('Invalid test configuration');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cookienod_ab_tests';

        $result = $wpdb->insert($table, array(
            'name' => $name,
            'status' => 'draft',
            'variants' => json_encode($variants),
            'traffic_split' => $traffic_split,
            'created_at' => current_time('mysql'),
        ));

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
        $status = sanitize_text_field($_POST['status'] ?? '');

        if (!$test_id) {
            wp_send_json_error('Invalid test ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cookienod_ab_tests';

        $data = array();

        if (isset($_POST['status'])) {
            $data['status'] = $status;
            if ($status === 'active') {
                $data['start_date'] = current_time('mysql');
            }
        }

        if (isset($_POST['name'])) {
            $data['name'] = sanitize_text_field($_POST['name']);
        }

        if (isset($_POST['variants'])) {
            $data['variants'] = wp_json_encode($this->parse_variants($_POST['variants']));
        }

        if (isset($_POST['traffic_split'])) {
            $data['traffic_split'] = sanitize_text_field($_POST['traffic_split']);
        }

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
        $tests_table = $wpdb->prefix . 'cookienod_ab_tests';
        $results_table = $wpdb->prefix . 'cookienod_ab_results';

        $wpdb->delete($results_table, array('test_id' => $test_id));
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

        $results_table = $wpdb->prefix . 'cookienod_ab_results';

        // Get event counts by variant
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT variant_id, event_type, COUNT(*) as count
                FROM $results_table
                WHERE test_id = %d
                GROUP BY variant_id, event_type",
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
        $table = $wpdb->prefix . 'cookienod_ab_tests';

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

        $table = $wpdb->prefix . 'cookienod_ab_tests';

        return $wpdb->get_results(
            "SELECT * FROM $table ORDER BY created_at DESC",
            ARRAY_A
        );
    }

    /**
     * Get test by ID
     */
    public function get_test($test_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cookienod_ab_tests';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $test_id),
            ARRAY_A
        );
    }
}

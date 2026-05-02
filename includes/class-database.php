<?php
/**
 * CookieNod Database Class
 * Handles database table creation and management
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Database
 * Database functionality
 */
class CookieNod_Database {

    /**
     * Constructor
     */
    public function __construct() {
        // Hooks are registered in main class
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Consent log table
        $table_name = $wpdb->prefix . 'cookienod_consent_log';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            ip_address varchar(100) DEFAULT '',
            user_agent text,
            consent_data longtext,
            preferences longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);

        // A/B Tests table
        $ab_tests_table = $wpdb->prefix . 'cookienod_ab_tests';
        $sql2 = "CREATE TABLE IF NOT EXISTS $ab_tests_table (
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
        dbDelta($sql2);

        // A/B Test Results table
        $ab_results_table = $wpdb->prefix . 'cookienod_ab_results';
        $sql3 = "CREATE TABLE IF NOT EXISTS $ab_results_table (
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
        dbDelta($sql3);
    }

    /**
     * Check if database tables exist and show notice if missing
     */
    public function check_tables() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $missing_tables = array();

        $tables = array(
            'cookienod_consent_log',
            'cookienod_ab_tests',
            'cookienod_ab_results',
        );

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for checking table existence
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
                $missing_tables[] = $table;
            }
        }

        if (!empty($missing_tables)) {
            $repair_url = wp_nonce_url(admin_url('admin.php?cookienod_repair_tables=1'), 'cookienod_repair_tables');
            ?>
            <div class="notice notice-error">
                <p><strong>CookieNod:</strong> <?php esc_html_e('The following database tables are missing:', 'cookienod'); ?> <code><?php echo esc_html(implode(', ', $missing_tables)); ?></code></p>
                <p><a href="<?php echo esc_url($repair_url); ?>" class="button button-primary"><?php esc_html_e('Create Missing Tables', 'cookienod'); ?></a></p>
            </div>
            <?php
        }
    }

    /**
     * Handle repair tables action
     */
    public function handle_repair() {
        if (!isset($_GET['cookienod_repair_tables'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'cookienod'));
        }

        $nonce_value = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce_value, 'cookienod_repair_tables')) {
            wp_die(esc_html__('Invalid nonce', 'cookienod'));
        }

        $this->create_tables();

        wp_safe_redirect(admin_url('plugins.php?cookienod_tables_created=1'));
        exit;
    }

    /**
     * Show success notice when tables are created
     */
    public function show_created_notice() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action performed, just showing notice
        if (!isset($_GET['cookienod_tables_created'])) {
            return;
        }
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>CookieNod:</strong> <?php esc_html_e('Database tables created successfully.', 'cookienod'); ?></p>
        </div>
        <?php
    }
}

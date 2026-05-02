<?php
/**
 * Uninstall script
 *
 * @package Cookienod
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we should delete all data
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Uninstall script, variables scoped to file
$cookienod_options = get_option('cookienod_wp_options', array());
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Uninstall script, variables scoped to file
$cookienod_delete_all = $cookienod_options['delete_data_on_uninstall'] ?? false;

if ($cookienod_delete_all) {
    global $wpdb;

    // Delete options
    delete_option('cookienod_wp_options');
    delete_option('cookienod_wp_site_info');
    delete_option('cookienod_wp_verified_api_key');
    delete_option('cookienod_wp_scan_results');
    delete_option('cookienod_wp_detected_cookies');
    delete_option('cookienod_wp_server_config');
    delete_option('cookienod_policy_page_id');

    // Drop custom tables
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Uninstall script, variables scoped to file
    $cookienod_tables = array(
        $wpdb->prefix . 'cookienod_consent_log',
        $wpdb->prefix . 'cookienod_ab_tests',
        $wpdb->prefix . 'cookienod_ab_results',
    );

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Uninstall script, variables scoped to file
    foreach ($cookienod_tables as $cookienod_table) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup
        $wpdb->query("DROP TABLE IF EXISTS $cookienod_table");
    }

    // Delete post meta
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_page'));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_template'));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_created'));
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_last_updated'));
}

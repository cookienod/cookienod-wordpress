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
$options = get_option('cookienod_wp_options', array());
$delete_all = $options['delete_data_on_uninstall'] ?? false;

if ($delete_all) {
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
    $tables = array(
        $wpdb->prefix . 'cookienod_consent_log',
        $wpdb->prefix . 'cookienod_ab_tests',
        $wpdb->prefix . 'cookienod_ab_results',
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }

    // Delete post meta
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_page'));
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_template'));
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_created'));
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_cookienod_policy_last_updated'));
}

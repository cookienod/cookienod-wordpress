<?php
/**
 * Consent Log Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_admin = new CookieNod_Admin();

// Pagination
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pagination doesn't require nonce
$cookienod_paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$cookienod_per_page = 25;
$cookienod_offset = ($cookienod_paged - 1) * $cookienod_per_page;

global $wpdb;
$cookienod_table = $wpdb->prefix . 'cookienod_consent_log';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for checking table existence
$cookienod_table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $cookienod_table)) === $cookienod_table;

$cookienod_total_items = 0;
$cookienod_total_pages = 0;
$cookienod_consents = array();

if ($cookienod_table_exists) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
    $cookienod_total_items = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $cookienod_table);
    $cookienod_total_pages = ceil($cookienod_total_items / $cookienod_per_page);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safe, built from prefix
    $cookienod_consents = $wpdb->get_results(
        $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe (prefix + esc_sql)
            "SELECT * FROM {$cookienod_table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $cookienod_per_page,
            $cookienod_offset
        ),
        ARRAY_A
    );
}
?>

<div class="wrap cookienod-consent-log">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Actions -->
    <div class="actions">
        <div class="alignleft">
            <select id="export-format">
                <option value="csv"><?php esc_html_e('CSV', 'cookienod'); ?></option>
            </select>

            <button type="button" class="button" id="export-consent-log">
                <?php esc_html_e('Export Log', 'cookienod'); ?>
            </button>

            <button type="button" class="button button-link-delete" id="clear-consent-log">
                <?php esc_html_e('Clear Log', 'cookienod'); ?>
            </button>
        </div>

        <div class="alignright">
            <span class="displaying-num">
                <?php
                printf(
                    esc_html(
                        sprintf(
                            /* translators: %s: Number of items */
                            _n('%s item', '%s items', $cookienod_total_items, 'cookienod'),
                            number_format_i18n($cookienod_total_items)
                        )
                    )
                );
                ?>
            </span>
        </div>
    </div>

    <!-- Consents Table -->
    <?php if ($cookienod_consents) : ?>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'cookienod'); ?></th>
                    <th><?php esc_html_e('User', 'cookienod'); ?></th>
                    <th><?php esc_html_e('IP Address', 'cookienod'); ?></th>
                    <th><?php esc_html_e('Preferences', 'cookienod'); ?></th>
                    <th><?php esc_html_e('Date', 'cookienod'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cookienod_consents as $cookienod_consent) : ?>
                    <?php
                    // Decode preferences - handle both JSON and serialized data
                    $cookienod_preferences_raw = $cookienod_consent['preferences'] ?? '{}';
                    $cookienod_preferences = json_decode($cookienod_preferences_raw, true);
                    if (!is_array($cookienod_preferences) || json_last_error() !== JSON_ERROR_NONE) {
                        // Try unserialize as fallback
                        $cookienod_preferences = maybe_unserialize($cookienod_preferences_raw);
                    }
                    if (!is_array($cookienod_preferences)) {
                        $cookienod_preferences = array();
                    }
                    // Filter out non-category keys like 'timestamp'
                    $cookienod_valid_categories = array('necessary', 'functional', 'analytics', 'marketing');
                    $cookienod_display_preferences = array_filter($cookienod_preferences, function($cookienod_key) use ($cookienod_valid_categories) {
                        return in_array($cookienod_key, $cookienod_valid_categories);
                    }, ARRAY_FILTER_USE_KEY);

                    $cookienod_user = $cookienod_consent['user_id'] ? get_user_by('id', $cookienod_consent['user_id']) : null;
                    ?>

                    <tr>
                        <td><?php echo esc_html($cookienod_consent['id']); ?></td>
                        <td>
                            <?php if ($cookienod_user) : ?>
                                <a href="<?php echo esc_url(get_edit_user_link($cookienod_user->ID)); ?>">
                                    <?php echo esc_html($cookienod_user->display_name); ?>
                                </a>
                            <?php else : ?>
                                <?php esc_html_e('Anonymous', 'cookienod'); ?>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($cookienod_consent['ip_address']); ?></code></td>
                        <td>
                            <div class="cookienod-preferences">
                                <?php if (empty($cookienod_display_preferences)) : ?>
                                    <span class="cookienod-pref-badge disabled"><?php esc_html_e('Rejected All', 'cookienod'); ?></span>
                                <?php else : ?>
                                    <?php
                                    $cookienod_all_false = true;
                                    foreach ($cookienod_display_preferences as $cookienod_enabled) {
                                        if ($cookienod_enabled) {
                                            $cookienod_all_false = false;
                                            break;
                                        }
                                    }
                                    if ($cookienod_all_false) :
                                    ?>
                                        <span class="cookienod-pref-badge disabled"><?php esc_html_e('Rejected All', 'cookienod'); ?></span>
                                    <?php else : ?>
                                        <?php foreach ($cookienod_display_preferences as $cookienod_cat => $cookienod_enabled) : ?>
                                            <span class="cookienod-pref-badge <?php echo esc_attr($cookienod_enabled ? 'enabled' : 'disabled'); ?>">
                                                <?php echo esc_html(ucfirst($cookienod_cat)); ?>:
                                                <?php echo $cookienod_enabled ? esc_html('✓') : esc_html('✗'); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo esc_html(human_time_diff(strtotime($cookienod_consent['created_at']), current_time('timestamp'))); ?> ago</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($cookienod_total_pages > 1) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        printf(
                            esc_html(
                                sprintf(
                                    /* translators: %s: Number of items */
                                    _n('%s item', '%s items', $cookienod_total_items, 'cookienod'),
                                    number_format_i18n($cookienod_total_items)
                                )
                            )
                        );
                        ?>
                    </span>

                    <span class="pagination-links">
                        <?php if ($cookienod_paged > 1) : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $cookienod_paged - 1)); ?>" class="prev-page button">
                                <span class="screen-reader-text"><?php esc_html_e('Previous', 'cookienod'); ?></span>
                                ‹
                            </a>
                        <?php endif; ?>

                        <span class="paging-input">
                            <span class="tablenav-paging-text">
                                <?php echo esc_html($cookienod_paged); ?> of <?php echo esc_html($cookienod_total_pages); ?>
                            </span>
                        </span>

                        <?php if ($cookienod_paged < $cookienod_total_pages) : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $cookienod_paged + 1)); ?>" class="next-page button">
                                <span class="screen-reader-text"><?php esc_html_e('Next', 'cookienod'); ?></span>
                                ›
                            </a>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

    <?php else : ?>

        <div class="cookienod-card">
            <div class="cookienod-empty-state">
                <h3><?php esc_html_e('No Consent Records', 'cookienod'); ?></h3>
                <p><?php esc_html_e('Consent records will appear here once users interact with the consent banner on your website.', 'cookienod'); ?></p>
            </div>
        </div>

    <?php endif; ?>

    <!-- Compliance Info -->
    <div class="cookienod-card" style="margin-top: 20px;">
        <h3><?php esc_html_e('About Consent Logging', 'cookienod'); ?></h3>

        <p>
            <?php esc_html_e('Consent logging helps you comply with GDPR, CCPA, and other privacy regulations by keeping a record of user consent choices.', 'cookienod'); ?>
        </p>

        <ul>
            <li><?php esc_html_e('Logs include: User ID (if logged in), IP address, consent preferences, and timestamp', 'cookienod'); ?></li>
            <li><?php esc_html_e('Logs are stored locally in your WordPress database', 'cookienod'); ?></li>
            <li><?php esc_html_e('Export logs for compliance audits', 'cookienod'); ?></li>
            <li><?php esc_html_e('Consider data retention policies when managing logs', 'cookienod'); ?></li>
        </ul>
    </div>
</div>

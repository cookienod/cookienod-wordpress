<?php
/**
 * Consent Log Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin = new CookieNod_Admin();

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 25;
$offset = ($paged - 1) * $per_page;

global $wpdb;
$table = $wpdb->prefix . 'cookienod_consent_log';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;

$total_items = 0;
$total_pages = 0;
$consents = array();

if ($table_exists) {
    $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $total_pages = ceil($total_items / $per_page);

    $consents = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
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
                <option value="csv"><?php _e('CSV', 'cookienod'); ?></option>
            </select>

            <button type="button" class="button" id="export-consent-log">
                <?php _e('Export Log', 'cookienod'); ?>
            </button>

            <button type="button" class="button button-link-delete" id="clear-consent-log">
                <?php _e('Clear Log', 'cookienod'); ?>
            </button>
        </div>

        <div class="alignright">
            <span class="displaying-num">
                <?php echo sprintf(
                    _n('%s item', '%s items', $total_items, 'cookienod'),
                    number_format($total_items)
                ); ?>
            </span>
        </div>
    </div>

    <!-- Consents Table -->
    <?php if ($consents) : ?>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'cookienod'); ?></th>
                    <th><?php _e('User', 'cookienod'); ?></th>
                    <th><?php _e('IP Address', 'cookienod'); ?></th>
                    <th><?php _e('Preferences', 'cookienod'); ?></th>
                    <th><?php _e('Date', 'cookienod'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consents as $consent) : ?>
                    <?php
                    // Decode preferences - handle both JSON and serialized data
                    $preferences_raw = $consent['preferences'] ?? '{}';
                    $preferences = json_decode($preferences_raw, true);
                    if (!is_array($preferences) || json_last_error() !== JSON_ERROR_NONE) {
                        // Try unserialize as fallback
                        $preferences = maybe_unserialize($preferences_raw);
                    }
                    if (!is_array($preferences)) {
                        $preferences = array();
                    }
                    // Filter out non-category keys like 'timestamp'
                    $valid_categories = array('necessary', 'functional', 'analytics', 'marketing');
                    $display_preferences = array_filter($preferences, function($key) use ($valid_categories) {
                        return in_array($key, $valid_categories);
                    }, ARRAY_FILTER_USE_KEY);

                    $user = $consent['user_id'] ? get_user_by('id', $consent['user_id']) : null;
                    ?>

                    <tr>
                        <td><?php echo esc_html($consent['id']); ?></td>
                        <td>
                            <?php if ($user) : ?>
                                <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>">
                                    <?php echo esc_html($user->display_name); ?>
                                </a>
                            <?php else : ?>
                                <?php _e('Anonymous', 'cookienod'); ?>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($consent['ip_address']); ?></code></td>
                        <td>
                            <div class="cookienod-preferences">
                                <?php if (empty($display_preferences)) : ?>
                                    <span class="cookienod-pref-badge disabled"><?php _e('Rejected All', 'cookienod'); ?></span>
                                <?php else : ?>
                                    <?php
                                    $all_false = true;
                                    foreach ($display_preferences as $enabled) {
                                        if ($enabled) {
                                            $all_false = false;
                                            break;
                                        }
                                    }
                                    if ($all_false) :
                                    ?>
                                        <span class="cookienod-pref-badge disabled"><?php _e('Rejected All', 'cookienod'); ?></span>
                                    <?php else : ?>
                                        <?php foreach ($display_preferences as $cat => $enabled) : ?>
                                            <span class="cookienod-pref-badge <?php echo $enabled ? 'enabled' : 'disabled'; ?>">
                                                <?php echo esc_html(ucfirst($cat)); ?>:
                                                <?php echo $enabled ? '✓' : '✗'; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo esc_html(human_time_diff(strtotime($consent['created_at']), current_time('timestamp'))); ?> ago</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo sprintf(
                            _n('%s item', '%s items', $total_items, 'cookienod'),
                            number_format($total_items)
                        ); ?>
                    </span>

                    <span class="pagination-links">
                        <?php if ($paged > 1) : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $paged - 1)); ?>" class="prev-page button">
                                <span class="screen-reader-text"><?php _e('Previous', 'cookienod'); ?></span>
                                ‹
                            </a>
                        <?php endif; ?>

                        <span class="paging-input">
                            <span class="tablenav-paging-text">
                                <?php echo $paged; ?> of <?php echo $total_pages; ?>
                            </span>
                        </span>

                        <?php if ($paged < $total_pages) : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $paged + 1)); ?>" class="next-page button">
                                <span class="screen-reader-text"><?php _e('Next', 'cookienod'); ?></span>
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
                <h3><?php _e('No Consent Records', 'cookienod'); ?></h3>
                <p><?php _e('Consent records will appear here once users interact with the consent banner on your website.', 'cookienod'); ?></p>
            </div>
        </div>

    <?php endif; ?>

    <!-- Compliance Info -->
    <div class="cookienod-card" style="margin-top: 20px;">
        <h3><?php _e('About Consent Logging', 'cookienod'); ?></h3>

        <p>
            <?php _e('Consent logging helps you comply with GDPR, CCPA, and other privacy regulations by keeping a record of user consent choices.', 'cookienod'); ?>
        </p>

        <ul>
            <li><?php _e('Logs include: User ID (if logged in), IP address, consent preferences, and timestamp', 'cookienod'); ?></li>
            <li><?php _e('Logs are stored locally in your WordPress database', 'cookienod'); ?></li>
            <li><?php _e('Export logs for compliance audits', 'cookienod'); ?></li>
            <li><?php _e('Consider data retention policies when managing logs', 'cookienod'); ?></li>
        </ul>
    </div>
</div>

<style>
.cookienod-preferences {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.cookienod-pref-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.85em;
}

.cookienod-pref-badge.enabled {
    background: #d4edda;
    color: #155724;
}

.cookienod-pref-badge.disabled {
    background: #f8d7da;
    color: #721c24;
}
</style>

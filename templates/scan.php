<?php
/**
 * Cookie Scan Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin = new CookieNod_Admin();
$cookies = $admin->get_detected_cookies();
$categorized = $admin->categorize_cookies($cookies);
$last_scan = get_option('cookienod_wp_last_scan');

// Get counts
$counts = array(
    'necessary' => count($categorized['necessary']),
    'functional' => count($categorized['functional']),
    'analytics' => count($categorized['analytics']),
    'marketing' => count($categorized['marketing']),
    'uncategorized' => count($categorized['uncategorized']),
);
$total = array_sum($counts);
?>

<div class="wrap cookienod-scan">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Scan Status -->
    <div class="cookienod-card">
        <div class="cookienod-scan-header">
            <div class="cookienod-scan-status">
                <h2><?php _e('Cookie Scan Status', 'cookienod'); ?></h2>
                <?php if ($last_scan) : ?>
                    <p>
                        <?php _e('Last scan:', 'cookienod'); ?>
                        <strong><?php echo esc_html(human_time_diff(strtotime($last_scan), current_time('timestamp'))); ?> ago</strong>
                    </p>
                    <p>
                        <?php _e('Total cookies detected:', 'cookienod'); ?>
                        <strong><?php echo number_format($total); ?></strong>
                    </p>
                <?php else : ?>
                    <p><?php _e('No scan has been performed yet.', 'cookienod'); ?></p>
                <?php endif; ?>
            </div>

            <div class="cookienod-scan-actions">
                <button id="start-cookie-scan" class="button button-primary button-hero">
                    <?php _e('Start New Scan', 'cookienod'); ?>
                </button>

                <div class="cookienod-progress" style="margin-top: 15px; display: none;">
                    <div class="cookienod-progress-bar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Summary -->
    <?php if ($total > 0) : ?>
        <div class="cookienod-dashboard-grid">
            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo number_format($counts['necessary']); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge necessary"><?php _e('Necessary', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>

            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo number_format($counts['functional']); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge functional"><?php _e('Functional', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>

            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo number_format($counts['analytics']); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge analytics"><?php _e('Analytics', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>

            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo number_format($counts['marketing']); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge marketing"><?php _e('Marketing', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Cookie List -->
        <div class="cookienod-card">
            <h2><?php _e('Detected Cookies', 'cookienod'); ?></h2>

            <!-- Filters -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="cookie-category-filter" class="screen-reader-text">
                        <?php _e('Filter by category', 'cookienod'); ?>
                    </label>
                    <select id="cookie-category-filter">
                        <option value="all"><?php _e('All Categories', 'cookienod'); ?></option>
                        <option value="necessary"><?php _e('Necessary', 'cookienod'); ?></option>
                        <option value="functional"><?php _e('Functional', 'cookienod'); ?></option>
                        <option value="analytics"><?php _e('Analytics', 'cookienod'); ?></option>
                        <option value="marketing"><?php _e('Marketing', 'cookienod'); ?></option>
                        <option value="uncategorized"><?php _e('Uncategorized', 'cookienod'); ?></option>
                    </select>

                    <input type="text" id="cookie-search" placeholder="<?php esc_attr_e('Search cookies...', 'cookienod'); ?>"
                           class="regular-text" />
                </div>
            </div>

            <table class="wp-list-table widefat striped cookienod-scan-table" id="cookie-scan-table">
                <thead>
                    <tr>
                        <th><?php _e('Cookie Name', 'cookienod'); ?></th>
                        <th><?php _e('Category', 'cookienod'); ?></th>
                        <th><?php _e('Type', 'cookienod'); ?></th>
                        <th><?php _e('Source', 'cookienod'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorized as $category => $cat_cookies) : ?>
                        <?php foreach ($cat_cookies as $cookie) : ?>
                            <tr data-category="<?php echo esc_attr($category); ?>">
                                <td><code><?php echo esc_html($cookie['name']); ?></code></td>
                                <td>
                                    <span class="cookienod-category-badge <?php echo esc_attr($category); ?>">
                                        <?php echo esc_html(ucfirst($category)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($cookie['type'] ?? 'Unknown'); ?></td>
                                <td><?php echo esc_html($cookie['source'] ?? 'Unknown'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else : ?>

        <div class="cookienod-card">
            <div class="cookienod-empty-state">
                <h3><?php _e('No Cookies Detected Yet', 'cookienod'); ?></h3>
                <p>
                    <?php _e('Run a scan to detect cookies on your website. The scan will analyze your site\'s cookies and categorize them automatically.', 'cookienod'); ?>
                </p>

                <p><?php _e('Note: For best results, visit your website in an incognito window after activating the plugin to trigger the JavaScript scanner.', 'cookienod'); ?></p>
            </div>
        </div>

    <?php endif; ?>
</div>

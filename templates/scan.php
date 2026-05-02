<?php
/**
 * Cookie Scan Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_admin = new CookieNod_Admin();
$cookienod_detected_cookies = $cookienod_admin->get_detected_cookies();
$cookienod_categorized = $cookienod_admin->categorize_cookies($cookienod_detected_cookies);
$cookienod_last_scan = get_option('cookienod_wp_last_scan');

// Get counts
$cookienod_counts = array(
    'necessary' => count($cookienod_categorized['necessary']),
    'functional' => count($cookienod_categorized['functional']),
    'analytics' => count($cookienod_categorized['analytics']),
    'marketing' => count($cookienod_categorized['marketing']),
    'uncategorized' => count($cookienod_categorized['uncategorized']),
);
$cookienod_total = array_sum($cookienod_counts);
?>

<div class="wrap cookienod-scan">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Scan Status -->
    <div class="cookienod-card">
        <div class="cookienod-scan-header">
            <div class="cookienod-scan-status">
                <h2><?php esc_html_e('Cookie Scan Status', 'cookienod'); ?></h2>
                <?php if ($cookienod_last_scan) : ?>
                    <p>
                        <?php esc_html_e('Last scan:', 'cookienod'); ?>
                        <strong><?php echo esc_html(human_time_diff(strtotime($cookienod_last_scan), current_time('timestamp'))); ?> ago</strong>
                    </p>
                    <p>
                        <?php esc_html_e('Total cookies detected:', 'cookienod'); ?>
                        <strong><?php echo esc_html(number_format($cookienod_total)); ?></strong>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e('No scan has been performed yet.', 'cookienod'); ?></p>
                <?php endif; ?>
            </div>

            <div class="cookienod-scan-actions">
                <button id="start-cookie-scan" class="button button-primary button-hero">
                    <?php esc_html_e('Start New Scan', 'cookienod'); ?>
                </button>

                <div class="cookienod-progress" style="margin-top: 15px; display: none;">
                    <div class="cookienod-progress-bar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Summary -->
    <?php if ($cookienod_total > 0) : ?>
        <div class="cookienod-dashboard-grid">
            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo esc_html(number_format($cookienod_counts['necessary'])); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge necessary"><?php esc_html_e('Necessary', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>

            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo esc_html(number_format($cookienod_counts['functional'])); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge functional"><?php esc_html_e('Functional', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>

            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo esc_html(number_format($cookienod_counts['analytics'])); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge analytics"><?php esc_html_e('Analytics', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>

            <div class="cookienod-card">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo esc_html(number_format($cookienod_counts['marketing'])); ?></span>
                    <span class="cookienod-stat-label">
                        <span class="cookienod-category-badge marketing"><?php esc_html_e('Marketing', 'cookienod'); ?></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Cookie List -->
        <div class="cookienod-card">
            <h2><?php esc_html_e('Detected Cookies', 'cookienod'); ?></h2>

            <!-- Filters -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="cookie-category-filter" class="screen-reader-text">
                        <?php esc_html_e('Filter by category', 'cookienod'); ?>
                    </label>
                    <select id="cookie-category-filter">
                        <option value="all"><?php esc_html_e('All Categories', 'cookienod'); ?></option>
                        <option value="necessary"><?php esc_html_e('Necessary', 'cookienod'); ?></option>
                        <option value="functional"><?php esc_html_e('Functional', 'cookienod'); ?></option>
                        <option value="analytics"><?php esc_html_e('Analytics', 'cookienod'); ?></option>
                        <option value="marketing"><?php esc_html_e('Marketing', 'cookienod'); ?></option>
                        <option value="uncategorized"><?php esc_html_e('Uncategorized', 'cookienod'); ?></option>
                    </select>

                    <input type="text" id="cookie-search" placeholder="<?php esc_attr_e('Search cookies...', 'cookienod'); ?>"
                           class="regular-text" />
                </div>
            </div>

            <table class="wp-list-table widefat striped cookienod-scan-table" id="cookie-scan-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Cookie Name', 'cookienod'); ?></th>
                        <th><?php esc_html_e('Category', 'cookienod'); ?></th>
                        <th><?php esc_html_e('Type', 'cookienod'); ?></th>
                        <th><?php esc_html_e('Source', 'cookienod'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cookienod_categorized as $cookienod_category => $cookienod_cat_cookies) : ?>
                        <?php foreach ($cookienod_cat_cookies as $cookienod_cookie) : ?>
                            <tr data-category="<?php echo esc_attr($cookienod_category); ?>">
                                <td><code><?php echo esc_html($cookienod_cookie['name']); ?></code></td>
                                <td>
                                    <span class="cookienod-category-badge <?php echo esc_attr($cookienod_category); ?>">
                                        <?php echo esc_html(ucfirst($cookienod_category)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($cookienod_cookie['type'] ?? 'Unknown'); ?></td>
                                <td><?php echo esc_html($cookienod_cookie['source'] ?? 'Unknown'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else : ?>

        <div class="cookienod-card">
            <div class="cookienod-empty-state">
                <h3><?php esc_html_e('No Cookies Detected Yet', 'cookienod'); ?></h3>
                <p>
                    <?php esc_html_e('Run a scan to detect cookies on your website. The scan will analyze your site\'s cookies and categorize them automatically.', 'cookienod'); ?>
                </p>

                <p><?php esc_html_e('Note: For best results, visit your website in an incognito window after activating the plugin to trigger the JavaScript scanner.', 'cookienod'); ?></p>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php
/**
 * Cookie Manager Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_admin = new CookieNod_Admin();
$cookienod_detected_cookies = $cookienod_admin->get_detected_cookies();
$cookienod_categorized = $cookienod_admin->categorize_cookies($cookienod_detected_cookies);

// Get counts
$cookienod_counts = array(
    'necessary' => count($cookienod_categorized['necessary']),
    'functional' => count($cookienod_categorized['functional']),
    'analytics' => count($cookienod_categorized['analytics']),
    'marketing' => count($cookienod_categorized['marketing']),
    'uncategorized' => count($cookienod_categorized['uncategorized']),
);
$cookienod_total = array_sum($cookienod_counts);

// Category colors
$cookienod_category_colors = array(
    'necessary' => '#28a745',
    'functional' => '#17a2b8',
    'analytics' => '#ffc107',
    'marketing' => '#dc3545',
    'uncategorized' => '#6c757d',
);

$cookienod_last_detected = get_option('cookienod_wp_last_scan');
?>

<div class="wrap cookienod-cookies">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php if ($cookienod_last_detected) : ?>
            <span style="color: #666; font-size: 0.9em;">
                <?php
                // translators: Time since last update
                printf(esc_html__('Last updated: %s ago', 'cookienod'), esc_html(human_time_diff(strtotime($cookienod_last_detected), current_time('timestamp'))));
                ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ($cookienod_total > 0) : ?>

        <!-- Summary Cards -->
        <div class="cookienod-dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <?php foreach ($cookienod_counts as $cookienod_category => $cookienod_count) : ?>
                <div class="cookienod-card" style="border-left: 4px solid <?php echo esc_attr($cookienod_category_colors[$cookienod_category]); ?>;">
                    <div class="cookienod-stat" style="text-align: center; padding: 15px;">
                        <span class="cookienod-stat-number" style="display: block; font-size: 2.5em; font-weight: bold; color: <?php echo esc_attr($cookienod_category_colors[$cookienod_category]); ?>;">
                            <?php echo esc_html(number_format($cookienod_count)); ?>
                        </span>
                        <span class="cookienod-stat-label" style="color: #666; text-transform: capitalize;">
                            <?php echo esc_html($cookienod_category); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Category Tabs -->
        <div class="cookienod-card">
            <div class="nav-tab-wrapper">
                <a href="#all" class="nav-tab nav-tab-active" data-category="all"><?php esc_html_e('All Cookies', 'cookienod'); ?> (<?php echo esc_html($cookienod_total); ?>)</a>
                <?php foreach ($cookienod_categorized as $cookienod_category => $cookienod_cat_cookies) : ?>
                    <?php if (count($cookienod_cat_cookies) > 0) : ?>
                        <a href="#<?php echo esc_attr($cookienod_category); ?>" class="nav-tab" data-category="<?php echo esc_attr($cookienod_category); ?>">
                            <span style="text-transform: capitalize;"><?php echo esc_html($cookienod_category); ?></span> (<?php echo esc_html(count($cookienod_cat_cookies)); ?>)
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <div style="padding: 15px; background: #f9f9f9; border-bottom: 1px solid #ddd;">
                <input type="text" id="cookie-search" class="regular-text"
                       placeholder="<?php esc_attr_e('Search cookies...', 'cookienod'); ?>"
                       style="width: 100%; max-width: 400px;" />
            </div>

<!-- Cookie Table -->
            <table class="wp-list-table widefat striped" id="cookies-table">
                <thead>
                    <tr>
                        <th style="width: 30%;"><?php esc_html_e('Cookie Name', 'cookienod'); ?></th>
                        <th style="width: 15%;"><?php esc_html_e('Category', 'cookienod'); ?></th>
                        <th style="width: 15%;"><?php esc_html_e('Type', 'cookienod'); ?></th>
                        <th><?php esc_html_e('Source', 'cookienod'); ?></th>
                        <th><?php esc_html_e('Description', 'cookienod'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cookienod_categorized as $cookienod_category => $cookienod_cat_cookies) : ?>
                        <?php foreach ($cookienod_cat_cookies as $cookienod_cookie) : ?>
                            <tr data-category="<?php echo esc_attr($cookienod_category); ?>">
                                <td>
                                    <code style="background: #f5f5f5; padding: 3px 8px; border-radius: 3px;">
                                        <?php echo esc_html($cookienod_cookie['name']); ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="cookienod-category-badge" style="
                                        display: inline-block;
                                        padding: 4px 12px;
                                        border-radius: 12px;
                                        font-size: 0.85em;
                                        font-weight: 500;
                                        background: <?php echo esc_attr($cookienod_category_colors[$cookienod_category]); ?>20;
                                        color: <?php echo esc_attr($cookienod_category_colors[$cookienod_category]); ?>;
                                        border: 1px solid <?php echo esc_attr($cookienod_category_colors[$cookienod_category]); ?>40;
                                        text-transform: capitalize;">
                                        <?php echo esc_html($cookienod_category); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($cookienod_cookie['type'] ?? 'HTTP Cookie'); ?></td>
                                <td><?php echo esc_html($cookienod_cookie['source'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php echo esc_html($cookienod_admin->get_cookie_description($cookienod_cookie['name'], $cookienod_category)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Category Legend -->
        <div class="cookienod-card" style="margin-top: 20px;">
            <h2><?php esc_html_e('Cookie Categories Explained', 'cookienod'); ?></h2>
            <table class="widefat" style="border: none;">
                <tbody>
                    <tr>
                        <td style="width: 30px;"><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($cookienod_category_colors['necessary']); ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php esc_html_e('Necessary', 'cookienod'); ?></strong></td>
                        <td><?php esc_html_e('Essential cookies required for the website to function properly. Cannot be disabled.', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($cookienod_category_colors['functional']); ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php esc_html_e('Functional', 'cookienod'); ?></strong></td>
                        <td><?php esc_html_e('Enable enhanced functionality and personalization, such as language preferences.', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($cookienod_category_colors['analytics']); ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php esc_html_e('Analytics', 'cookienod'); ?></strong></td>
                        <td><?php esc_html_e('Help understand how visitors interact with the website (Google Analytics, etc.).', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($cookienod_category_colors['marketing']); ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php esc_html_e('Marketing', 'cookienod'); ?></strong></td>
                        <td><?php esc_html_e('Used to track visitors across websites to deliver relevant advertisements.', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($cookienod_category_colors['uncategorized']); ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php esc_html_e('Uncategorized', 'cookienod'); ?></strong></td>
                        <td><?php esc_html_e('Cookies that have not yet been classified into a category.', 'cookienod'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    <?php else : ?>

        <div class="cookienod-card">
            <div class="cookienod-empty-state" style="text-align: center; padding: 40px;">
                <h3><?php esc_html_e('No Cookies Detected Yet', 'cookienod'); ?></h3>
                <p>
                    <?php esc_html_e('Cookies will be detected automatically when visitors browse your website.', 'cookienod'); ?>
                </p>
                <p style="margin: 20px 0;">
                    <a href="<?php echo esc_url(home_url()); ?>" target="_blank" class="button button-primary">
                        <?php esc_html_e('Visit Your Site to Detect Cookies', 'cookienod'); ?>
                    </a>
                </p>
                <p style="color: #666;">
                    <em><?php esc_html_e('The CookieNod JavaScript detects cookies as they are set in users\' browsers. Visit your site and accept cookies to trigger detection.', 'cookienod'); ?></em>
                </p>
            </div>
        </div>

    <?php endif; ?>
</div>

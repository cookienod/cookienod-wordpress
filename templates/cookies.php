<?php
/**
 * Cookie Manager Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin = new CookieNod_Admin();
$cookies = $admin->get_detected_cookies();
$categorized = $admin->categorize_cookies($cookies);

// Get counts
$counts = array(
    'necessary' => count($categorized['necessary']),
    'functional' => count($categorized['functional']),
    'analytics' => count($categorized['analytics']),
    'marketing' => count($categorized['marketing']),
    'uncategorized' => count($categorized['uncategorized']),
);
$total = array_sum($counts);

// Category colors
$category_colors = array(
    'necessary' => '#28a745',
    'functional' => '#17a2b8',
    'analytics' => '#ffc107',
    'marketing' => '#dc3545',
    'uncategorized' => '#6c757d',
);

$last_detected = get_option('cookienod_wp_last_scan');
?>

<div class="wrap cookienod-cookies">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php if ($last_detected) : ?>
            <span style="color: #666; font-size: 0.9em;">
                <?php printf(__('Last updated: %s ago', 'cookienod'), human_time_diff(strtotime($last_detected), current_time('timestamp'))); ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ($total > 0) : ?>

        <!-- Summary Cards -->
        <div class="cookienod-dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <?php foreach ($counts as $category => $count) : ?>
                <div class="cookienod-card" style="border-left: 4px solid <?php echo esc_attr($category_colors[$category]); ?>;">
                    <div class="cookienod-stat" style="text-align: center; padding: 15px;">
                        <span class="cookienod-stat-number" style="display: block; font-size: 2.5em; font-weight: bold; color: <?php echo esc_attr($category_colors[$category]); ?>;">
                            <?php echo number_format($count); ?>
                        </span>
                        <span class="cookienod-stat-label" style="color: #666; text-transform: capitalize;">
                            <?php echo esc_html($category); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Category Tabs -->
        <div class="cookienod-card">
            <div class="nav-tab-wrapper">
                <a href="#all" class="nav-tab nav-tab-active" data-category="all"><?php _e('All Cookies', 'cookienod'); ?> (<?php echo $total; ?>)</a>
                <?php foreach ($categorized as $category => $cat_cookies) : ?>
                    <?php if (count($cat_cookies) > 0) : ?>
                        <a href="#<?php echo esc_attr($category); ?>" class="nav-tab" data-category="<?php echo esc_attr($category); ?>">
                            <span style="text-transform: capitalize;"><?php echo esc_html($category); ?></span> (<?php echo count($cat_cookies); ?>)
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
                        <th style="width: 30%;"><?php _e('Cookie Name', 'cookienod'); ?></th>
                        <th style="width: 15%;"><?php _e('Category', 'cookienod'); ?></th>
                        <th style="width: 15%;"><?php _e('Type', 'cookienod'); ?></th>
                        <th><?php _e('Source', 'cookienod'); ?></th>
                        <th><?php _e('Description', 'cookienod'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorized as $category => $cat_cookies) : ?>
                        <?php foreach ($cat_cookies as $cookie) : ?>
                            <tr data-category="<?php echo esc_attr($category); ?>">
                                <td>
                                    <code style="background: #f5f5f5; padding: 3px 8px; border-radius: 3px;">
                                        <?php echo esc_html($cookie['name']); ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="cookienod-category-badge" style="
                                        display: inline-block;
                                        padding: 4px 12px;
                                        border-radius: 12px;
                                        font-size: 0.85em;
                                        font-weight: 500;
                                        background: <?php echo esc_attr($category_colors[$category]); ?>20;
                                        color: <?php echo esc_attr($category_colors[$category]); ?>;
                                        border: 1px solid <?php echo esc_attr($category_colors[$category]); ?>40;
                                        text-transform: capitalize;">
                                        <?php echo esc_html($category); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($cookie['type'] ?? 'HTTP Cookie'); ?></td>
                                <td><?php echo esc_html($cookie['source'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php echo esc_html($admin->get_cookie_description($cookie['name'], $category)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Category Legend -->
        <div class="cookienod-card" style="margin-top: 20px;">
            <h2><?php _e('Cookie Categories Explained', 'cookienod'); ?></h2>
            <table class="widefat" style="border: none;">
                <tbody>
                    <tr>
                        <td style="width: 30px;"><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $category_colors['necessary']; ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php _e('Necessary', 'cookienod'); ?></strong></td>
                        <td><?php _e('Essential cookies required for the website to function properly. Cannot be disabled.', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $category_colors['functional']; ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php _e('Functional', 'cookienod'); ?></strong></td>
                        <td><?php _e('Enable enhanced functionality and personalization, such as language preferences.', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $category_colors['analytics']; ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php _e('Analytics', 'cookienod'); ?></strong></td>
                        <td><?php _e('Help understand how visitors interact with the website (Google Analytics, etc.).', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $category_colors['marketing']; ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php _e('Marketing', 'cookienod'); ?></strong></td>
                        <td><?php _e('Used to track visitors across websites to deliver relevant advertisements.', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $category_colors['uncategorized']; ?>; border-radius: 3px;"></span></td>
                        <td><strong><?php _e('Uncategorized', 'cookienod'); ?></strong></td>
                        <td><?php _e('Cookies that have not yet been classified into a category.', 'cookienod'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    <?php else : ?>

        <div class="cookienod-card">
            <div class="cookienod-empty-state" style="text-align: center; padding: 40px;">
                <h3><?php _e('No Cookies Detected Yet', 'cookienod'); ?></h3>
                <p>
                    <?php _e('Cookies will be detected automatically when visitors browse your website.', 'cookienod'); ?>
                </p>
                <p style="margin: 20px 0;">
                    <a href="<?php echo esc_url(home_url()); ?>" target="_blank" class="button button-primary">
                        <?php _e('Visit Your Site to Detect Cookies', 'cookienod'); ?>
                    </a>
                </p>
                <p style="color: #666;">
                    <em><?php _e('The CookieNod JavaScript detects cookies as they are set in users\' browsers. Visit your site and accept cookies to trigger detection.', 'cookienod'); ?></em>
                </p>
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
.cookienod-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.cookienod-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.cookienod-stat {
    text-align: center;
    padding: 15px;
}
.cookienod-stat-number {
    display: block;
    font-size: 2.5em;
    font-weight: bold;
}
.cookienod-stat-label {
    color: #666;
    text-transform: capitalize;
}
.nav-tab-wrapper {
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
    padding: 10px 10px 0;
}
.nav-tab {
    display: inline-block;
    padding: 8px 16px;
    margin-right: 5px;
    border: 1px solid #ddd;
    border-bottom: none;
    background: #f1f1f1;
    color: #555;
    text-decoration: none;
    border-radius: 4px 4px 0 0;
}
.nav-tab-active {
    background: #fff;
    border-bottom: 1px solid #fff;
    margin-bottom: -1px;
    color: #000;
}
#cookies-table tbody tr {
    transition: background-color 0.2s;
}
#cookies-table tbody tr:hover {
    background-color: #f9f9f9;
}
</style>

<script>
(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab switching
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();

            var category = $(this).data('category');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            if (category === 'all') {
                $('#cookies-table tbody tr').show();
            } else {
                $('#cookies-table tbody tr').hide();
                $('#cookies-table tbody tr[data-category="' + category + '"]').show();
            }
        });

        // Search functionality
        $('#cookie-search').on('input', function() {
            var search = $(this).val().toLowerCase();
            var activeCategory = $('.nav-tab-active').data('category');

            $('#cookies-table tbody tr').each(function() {
                var $row = $(this);
                var text = $row.text().toLowerCase();
                var category = $row.data('category');

                var matchesSearch = text.indexOf(search) >= 0;
                var matchesCategory = activeCategory === 'all' || category === activeCategory;

                if (matchesSearch && matchesCategory) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        });
    });

})(jQuery);
</script>

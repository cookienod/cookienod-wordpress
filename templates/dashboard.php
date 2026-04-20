<?php
/**
 * Dashboard Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin = new CookieNod_Admin();
$stats = $admin->get_dashboard_stats();
$compliance = new CookieNod_Compliance();
?>

<div class="wrap cookienod-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="cookienod-dashboard-grid">
        <!-- Status Card -->
        <div class="cookienod-card cookienod-status-card">
            <h2><?php _e('Status', 'cookienod'); ?></h2>
            <div class="cookienod-status-indicator status-<?php echo esc_attr($stats['api_status']); ?>">
                <?php
                $status_labels = array(
                    'connected' => __('Connected', 'cookienod'),
                    'not_configured' => __('Not Configured', 'cookienod'),
                    'invalid_key' => __('Invalid API Key', 'cookienod'),
                );
                echo esc_html($status_labels[$stats['api_status']] ?? $stats['api_status']);
                ?>
            </div>

            <?php if ($stats['api_status'] === 'connected') : ?>
                <p>
                    <strong><?php _e('Site:', 'cookienod'); ?></strong>
                    <?php echo esc_html($stats['site_name']); ?>
                </p>
                <p>
                    <strong><?php _e('Plan:', 'cookienod'); ?></strong>
                    <span class="cookienod-plan-badge plan-<?php echo esc_attr($stats['plan']); ?>">
                        <?php echo esc_html(ucfirst($stats['plan'])); ?>
                    </span>
                </p>
            <?php else : ?>
                <p><?php _e('Please configure your API key in Settings.', 'cookienod'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-settings')); ?>" class="button button-primary">
                    <?php _e('Configure Settings', 'cookienod'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Stats Card -->
        <div class="cookienod-card">
            <h2><?php _e('Statistics', 'cookienod'); ?></h2>
            <div class="cookienod-stats-grid">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo number_format($stats['cookies_detected']); ?></span>
                    <span class="cookienod-stat-label"><?php _e('Cookies Detected', 'cookienod'); ?></span>
                </div>
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo number_format($stats['total_consents']); ?></span>
                    <span class="cookienod-stat-label"><?php _e('Total Consents', 'cookienod'); ?></span>
                </div>
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo number_format($stats['recent_consents']); ?></span>
                    <span class="cookienod-stat-label"><?php _e('Last 7 Days', 'cookienod'); ?></span>
                </div>
            </div>
        </div>

        <!-- Compliance Card -->
        <div class="cookienod-card">
            <h2><?php _e('Compliance', 'cookienod'); ?></h2>
            <p><?php _e('CookieNod supports the following regulations:', 'cookienod'); ?></p>

            <ul class="cookienod-compliance-list">
                <?php foreach ($compliance->get_all_regulations() as $key => $reg) : ?>
                    <li>
                        <strong><?php echo esc_html($reg['name']); ?></strong>
                        - <?php echo esc_html($reg['full_name']); ?>
                        <span class="cookienod-regions">(<?php echo esc_html(implode(', ', $reg['regions'])); ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Quick Actions Card -->
        <div class="cookienod-card">
            <h2><?php _e('Quick Actions', 'cookienod'); ?></h2>

            <div class="cookienod-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-settings')); ?>" class="button">
                    <?php _e('Settings', 'cookienod'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-cookies')); ?>" class="button">
                    <?php _e('Cookie Manager', 'cookienod'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-consent-log')); ?>" class="button">
                    <?php _e('Consent Log', 'cookienod'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Integration Guide -->
    <div class="cookienod-card cookienod-guide-card">
        <h2><?php _e('Integration Guide', 'cookienod'); ?></h2>

        <ol>
            <li>
                <strong><?php _e('Get an API Key', 'cookienod'); ?></strong><br>
                <?php _e('Sign up at', 'cookienod'); ?> <a href="https://cookienod.com" target="_blank">cookienod.com</a>
                <?php _e('to get your free API key.', 'cookienod'); ?>
            </li>
            <li>
                <strong><?php _e('Configure Settings', 'cookienod'); ?></strong><br>
                <?php _e('Enter your API key in the Settings page and choose your preferred blocking mode.', 'cookienod'); ?>
            </li>
            <li>
                <strong><?php _e('Customize Banner', 'cookienod'); ?></strong><br>
                <?php _e('Select your preferred banner position and theme to match your site design.', 'cookienod'); ?>
            </li>
            <li>
                <strong><?php _e('Enable Google Consent Mode (Optional)', 'cookienod'); ?></strong><br>
                <?php _e('If you use Google Analytics or Google Tag Manager, enable Google Consent Mode for proper integration.', 'cookienod'); ?>
            </li>
        </ol>
    </div>
</div>